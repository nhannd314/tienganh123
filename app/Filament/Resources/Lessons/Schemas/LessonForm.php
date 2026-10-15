<?php

namespace App\Filament\Resources\Lessons\Schemas;

use App\Jobs\GenerateLessonSentenceAudio;
use App\Models\Lesson;
use App\Models\Unit;
use App\Services\AzureTextToSpeech;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Unique;

class LessonForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('unit_id')
                    ->relationship(
                        'unit',
                        'title',
                        modifyQueryUsing: fn (Builder $query): Builder => $query
                            ->select('units.*')
                            ->join('courses', 'courses.id', '=', 'units.course_id')
                            ->orderBy('courses.title')
                            ->orderBy('units.sort_order'),
                    )
                    ->getOptionLabelFromRecordUsing(fn (Unit $record): string => $record->course->title.' » '.$record->title)
                    ->searchable(['units.title', 'courses.title'])
                    ->live()
                    ->required(),
                TextInput::make('title')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Get $get, Set $set, ?string $state, string $operation): void {
                        if ($operation === 'create' && blank($get('slug'))) {
                            $set('slug', Str::slug($state ?? ''));
                        }
                    }),
                TextInput::make('slug')
                    ->maxLength(255)
                    ->alphaDash()
                    ->unique(
                        ignoreRecord: true,
                        modifyRuleUsing: fn (Unique $rule, Get $get): Unique => $rule->where('unit_id', $get('unit_id')),
                    )
                    ->helperText('Leave empty to generate it from the title. Must be unique within the unit.'),
                TextInput::make('sort_order')
                    ->required()
                    ->numeric()
                    ->integer()
                    ->minValue(0)
                    ->default(0),
                Toggle::make('is_free')
                    ->helperText('Free lessons can be tried without enrolling.')
                    ->default(false),
                Toggle::make('is_published')
                    ->default(false),
                Textarea::make('description')
                    ->rows(3)
                    ->columnSpanFull(),
                self::richContentEditor('passage')
                    ->helperText('Opening passage of the lesson. Wrap a word in inline code (`apple`) to show its IPA, audio and meaning in a popover when the word exists.'),
                FileUpload::make('passage_audio')
                    ->label('Passage audio')
                    ->acceptedFileTypes(['audio/mpeg'])
                    ->disk('public')
                    ->directory('lessons/passage-audio')
                    ->visibility('public')
                    ->maxSize(10240)
                    ->downloadable()
                    ->columnSpanFull(),
                Repeater::make('example_sentences')
                    ->label('Example sentences')
                    ->schema([
                        TextInput::make('sentence')
                            ->required()
                            ->maxLength(500)
                            ->helperText('Wrap a word in backticks, e.g. I have an `apple`., to show its IPA, audio and meaning in a popover when the word exists.'),
                        TextInput::make('translation')
                            ->label('Meaning')
                            ->maxLength(500),
                        FileUpload::make('audio')
                            ->acceptedFileTypes(['audio/mpeg'])
                            ->disk('public')
                            ->directory('lessons/sentence-audio')
                            ->visibility('public')
                            ->maxSize(2048)
                            ->downloadable()
                            ->helperText('Leave empty to have Azure read the sentence when the lesson is saved.')
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->defaultItems(0)
                    ->addActionLabel('Add sentence')
                    ->reorderable()
                    ->collapsible()
                    ->itemLabel(fn (array $state): ?string => $state['sentence'] ?? null)
                    ->columnSpanFull(),
                self::richContentEditor('content'),
                FileUpload::make('attachments')
                    ->multiple()
                    ->reorderable()
                    ->disk('public')
                    ->directory('lessons/attachments')
                    ->visibility('public')
                    ->maxSize(10240)
                    ->downloadable()
                    ->columnSpanFull(),
            ]);
    }

    /**
     * Drop the audio of a sentence whose text was changed, so the new text is read again.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function dropStaleSentenceAudio(array $data, Lesson $lesson): array
    {
        $sentenceByAudio = collect($lesson->example_sentences ?? [])
            ->filter(fn (mixed $example): bool => is_array($example) && filled($example['audio'] ?? null))
            ->mapWithKeys(fn (array $example): array => [$example['audio'] => $example['sentence'] ?? null]);

        $data['example_sentences'] = collect($data['example_sentences'] ?? [])
            ->map(function (array $example) use ($sentenceByAudio): array {
                $audio = $example['audio'] ?? null;

                if (filled($audio) && $sentenceByAudio->has($audio) && $sentenceByAudio->get($audio) !== ($example['sentence'] ?? null)) {
                    $example['audio'] = null;
                }

                return $example;
            })
            ->values()
            ->all();

        return $data;
    }

    /**
     * Queue the Azure voice of the example sentences that have no audio; it appears on the lesson once the job has run.
     */
    public static function queueMissingSentenceAudio(Lesson $lesson): void
    {
        if (Lesson::sentencesWithoutAudio($lesson->example_sentences) === []) {
            return;
        }

        if (! app(AzureTextToSpeech::class)->isConfigured()) {
            Notification::make()
                ->title('The sentence audio was not generated')
                ->body('AZURE_SPEECH_KEY and AZURE_SPEECH_REGION are not set. Upload an audio file instead.')
                ->warning()
                ->send();

            return;
        }

        GenerateLessonSentenceAudio::dispatch($lesson);

        Notification::make()
            ->title('The sentence audio is being generated')
            ->body('It will appear on the lesson in a moment.')
            ->success()
            ->send();
    }

    /**
     * Rich text editor shared by the passage and the content: formatting, headings, lists and image attachments.
     */
    private static function richContentEditor(string $name): RichEditor
    {
        return RichEditor::make($name)
            ->toolbarButtons([
                ['bold', 'italic', 'underline', 'strike', 'code', 'link'],
                ['h2', 'h3'],
                ['blockquote', 'bulletList', 'orderedList'],
                ['attachFiles'],
                ['undo', 'redo'],
            ])
            ->fileAttachmentsDisk('public')
            ->fileAttachmentsDirectory('lessons/content')
            ->fileAttachmentsVisibility('public')
            ->fileAttachmentsAcceptedFileTypes(['image/png', 'image/jpeg', 'image/webp', 'image/gif'])
            ->fileAttachmentsMaxSize(5120)
            ->helperText('Wrap a word in inline code (`apple`) to show its IPA, audio and meaning in a popover when the word exists.')
            ->columnSpanFull();
    }
}
