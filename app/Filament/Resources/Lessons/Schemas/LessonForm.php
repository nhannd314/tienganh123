<?php

namespace App\Filament\Resources\Lessons\Schemas;

use App\Filament\Forms\Components\RichEditor\RichContentCustomBlocks\YoutubeVideoBlock;
use App\Jobs\GenerateLessonSentenceAudio;
use App\Models\Lesson;
use App\Models\Unit;
use App\Services\AzureTextToSpeech;
use Closure;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
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
                ToggleButtons::make('video_source')
                    ->label('Video')
                    ->options(['youtube' => 'YouTube link', 'upload' => 'Upload file'])
                    ->inline()
                    ->live()
                    ->default('youtube')
                    ->helperText('Shown below the flashcards of the lesson.')
                    ->columnSpanFull(),
                TextInput::make('video_url')
                    ->label('YouTube URL')
                    ->url()
                    ->maxLength(255)
                    ->placeholder('https://www.youtube.com/watch?v=...')
                    ->rules([
                        fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                            if (filled($value) && Lesson::youtubeEmbedUrl(is_string($value) ? $value : null) === null) {
                                $fail('This is not a valid YouTube link.');
                            }
                        },
                    ])
                    ->visible(fn (Get $get): bool => $get('video_source') !== 'upload')
                    ->columnSpanFull(),
                FileUpload::make('video_upload')
                    ->label('Video file')
                    ->acceptedFileTypes(['video/mp4', 'video/webm'])
                    ->disk('public')
                    ->directory('lessons/videos')
                    ->visibility('public')
                    ->maxSize(204800)
                    ->downloadable()
                    ->visible(fn (Get $get): bool => $get('video_source') === 'upload')
                    ->columnSpanFull(),
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
                            ->placeholder('I have an `apple`.')
                            ->helperText('Wrap a word in backticks to show its IPA, audio and meaning in a popover when the word exists.'),
                        TextInput::make('translation')
                            ->label('Meaning')
                            ->maxLength(500)
                            ->placeholder('Tôi có 1 quả táo.'),
                        FileUpload::make('image')
                            ->image()
                            ->disk('public')
                            ->directory('lessons/sentence-images')
                            ->visibility('public')
                            ->maxSize(2048)
                            ->columnSpanFull(),
                        Hidden::make('audio'),
                    ])
                    ->columns(2)
                    ->defaultItems(0)
                    ->addActionLabel('Add sentence')
                    ->reorderable()
                    ->collapsible()
                    ->itemLabel(fn (array $state): ?string => $state['sentence'] ?? null)
                    ->helperText('Azure reads every new or edited sentence when the lesson is saved.')
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
     * Split the stored `video` into the form fields: the source, and the link or the uploaded file.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function fillVideoFields(array $data): array
    {
        $isUpload = filled($data['video'] ?? null) && ! preg_match('~^https?://~i', $data['video']);

        $data['video_source'] = $isUpload ? 'upload' : 'youtube';
        $data['video_url'] = $isUpload ? null : ($data['video'] ?? null);
        $data['video_upload'] = $isUpload ? $data['video'] : null;

        return $data;
    }

    /**
     * Merge the link or the uploaded file of the chosen source back into the single `video` column.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function mergeVideoFields(array $data): array
    {
        $isUpload = ($data['video_source'] ?? 'youtube') === 'upload';
        $upload = $data['video_upload'] ?? null;

        $data['video'] = $isUpload ? (is_string($upload) && filled($upload) ? $upload : null) : (filled($data['video_url'] ?? null) ? $data['video_url'] : null);

        unset($data['video_source'], $data['video_url'], $data['video_upload']);

        return $data;
    }

    /**
     * Give back the Azure audio of a sentence whose text is unchanged; a new or edited sentence has none, so Azure reads it again.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function keepSentenceAudio(array $data, Lesson $lesson): array
    {
        $audioBySentence = collect($lesson->example_sentences ?? [])
            ->filter(fn (mixed $example): bool => is_array($example) && filled($example['sentence'] ?? null) && filled($example['audio'] ?? null))
            ->mapWithKeys(fn (array $example): array => [$example['sentence'] => $example['audio']]);

        $data['example_sentences'] = collect($data['example_sentences'] ?? [])
            ->map(fn (array $example): array => [...$example, 'audio' => $audioBySentence->get($example['sentence'])])
            ->all();

        return $data;
    }

    /**
     * Delete from the public disk the images of the example sentences that the save removed or replaced.
     *
     * @param  array<int, mixed>  $previousExampleSentences
     */
    public static function deleteDroppedSentenceImages(array $previousExampleSentences, Lesson $lesson): void
    {
        $images = fn (?array $exampleSentences): array => collect($exampleSentences ?? [])
            ->map(fn (mixed $example): mixed => is_array($example) ? ($example['image'] ?? null) : null)
            ->filter(fn (mixed $image): bool => is_string($image) && filled($image))
            ->all();

        $droppedImages = array_diff($images($previousExampleSentences), $images($lesson->example_sentences));

        if ($droppedImages !== []) {
            Storage::disk('public')->delete(array_values($droppedImages));
        }
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
                ['attachFiles', 'customBlocks'],
                ['undo', 'redo'],
            ])
            ->customBlocks([YoutubeVideoBlock::class])
            ->fileAttachmentsDisk('public')
            ->fileAttachmentsDirectory('lessons/content')
            ->fileAttachmentsVisibility('public')
            ->fileAttachmentsAcceptedFileTypes(['image/png', 'image/jpeg', 'image/webp', 'image/gif'])
            ->fileAttachmentsMaxSize(5120)
            ->helperText('Wrap a word in inline code (`apple`) to show its IPA, audio and meaning in a popover when the word exists.')
            ->columnSpanFull();
    }
}
