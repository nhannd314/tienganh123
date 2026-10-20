<?php

namespace App\Filament\Resources\Lessons\Schemas;

use App\Filament\Forms\Components\RichEditor\RichContentCustomBlocks\AudioBlock;
use App\Filament\Forms\Components\RichEditor\RichContentCustomBlocks\VideoBlock;
use App\Filament\Forms\Components\RichEditor\RichContentCustomBlocks\YoutubeVideoBlock;
use App\Filament\Resources\FlashcardSets\Schemas\FlashcardSetForm;
use App\Filament\Resources\SentenceSets\Schemas\SentenceSetForm;
use App\Filament\Resources\Vocabularies\Actions\BulkAddWordsAction;
use App\Models\FlashcardSet;
use App\Models\Lesson;
use App\Models\SentenceSet;
use App\Models\Unit;
use App\Models\Vocabulary;
use Closure;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
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
                Select::make('sentence_set_id')
                    ->label('Sentence set')
                    ->relationship('sentenceSet', 'title')
                    ->searchable()
                    ->preload()
                    ->helperText('The sentences shown in the example sentences section of the lesson.')
                    ->live()
                    ->columnSpanFull(),
                ...self::contentLinesFields(),
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
     * Fields of the create form only: the words, flashcards and sentences of the new lesson, one per line, each list
     * saved as a vocabulary, a flashcard set and a sentence set titled like the lesson. The sentences are left out
     * once an existing sentence set is chosen.
     *
     * @return list<Component>
     */
    private static function contentLinesFields(): array
    {
        $sentenceFields = array_map(
            fn (Component $field): Component => $field
                ->visible(fn (Get $get, string $operation): bool => $operation === 'create' && blank($get('sentence_set_id')))
                ->columnSpanFull(),
            SentenceSetForm::sentenceLinesFields(isRequired: false),
        );

        return [
            Textarea::make('vocabulary_lines')
                ->label('Vocabulary')
                ->rows(8)
                ->placeholder("apple [noun] quả táo, táo | I have an apple = Tôi có một quả táo\nwork [noun] công việc [verb] làm việc")
                ->helperText('Mỗi dòng một từ: từ [từ loại] nghĩa, nghĩa khác | câu ví dụ = bản dịch. Gõ một từ đã có rồi bấm Tab để tự nạp nghĩa. Lưu thành một bộ từ vựng cùng tên với bài học.')
                ->live(onBlur: true)
                ->afterStateUpdated(fn (Set $set, ?string $state) => $set('vocabulary_lines', BulkAddWordsAction::loadKnownWords((string) $state)))
                ->visibleOn('create')
                ->columnSpanFull(),
            Textarea::make('flashcard_lines')
                ->label('Flashcards')
                ->rows(6)
                ->placeholder("apple\nbanana")
                ->helperText('Mỗi dòng một flashcard. Flashcard đã có được dùng lại; flashcard mới được nối với từ cùng cách viết và tự tạo ảnh, âm thanh. Lưu thành một bộ flashcard cùng tên với bài học.')
                ->rules([
                    fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                        foreach (FlashcardSetForm::parseLines(is_string($value) ? $value : null) as $title) {
                            if (mb_strlen($title) > 255) {
                                $fail("The flashcard \"{$title}\" is longer than 255 characters.");

                                return;
                            }
                        }
                    },
                ])
                ->visibleOn('create')
                ->columnSpanFull(),
            ...$sentenceFields,
        ];
    }

    /**
     * Save the lines typed in the create form as the vocabulary, the flashcard set and the sentence set of the new
     * lesson, each titled like the lesson. An empty list creates nothing.
     *
     * @param  array{vocabulary_lines?: string|null, flashcard_lines?: string|null, sentence_lines?: string|null, generate_sentence_images?: bool|null}  $lines
     */
    public static function createContentSets(Lesson $lesson, array $lines): void
    {
        if (filled($entries = BulkAddWordsAction::parse((string) ($lines['vocabulary_lines'] ?? '')))) {
            $vocabulary = Vocabulary::create(['title' => $lesson->title]);
            BulkAddWordsAction::apply($vocabulary, $entries);
            $lesson->vocabularies()->attach($vocabulary, ['sort_order' => 1]);
        }

        if (filled(FlashcardSetForm::parseLines($lines['flashcard_lines'] ?? null))) {
            $flashcardSet = FlashcardSet::create(['title' => $lesson->title]);
            FlashcardSetForm::addFlashcards($flashcardSet, $lines['flashcard_lines']);
            $lesson->flashcardSets()->attach($flashcardSet, ['sort_order' => 1]);
        }

        if (blank($lesson->sentence_set_id) && filled(SentenceSetForm::parseLines($lines['sentence_lines'] ?? null))) {
            $sentenceSet = SentenceSet::create(['title' => $lesson->title]);
            SentenceSetForm::addSentences($sentenceSet, $lines['sentence_lines'], (bool) ($lines['generate_sentence_images'] ?? false));
            $lesson->update(['sentence_set_id' => $sentenceSet->id]);
        }
    }

    /**
     * Rich text editor shared by the passage and the content: formatting, headings, lists, image attachments, and YouTube, video and audio blocks.
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
            ->customBlocks([YoutubeVideoBlock::class, VideoBlock::class, AudioBlock::class])
            ->fileAttachmentsDisk('public')
            ->fileAttachmentsDirectory('lessons/content')
            ->fileAttachmentsVisibility('public')
            ->fileAttachmentsAcceptedFileTypes(['image/png', 'image/jpeg', 'image/webp', 'image/gif'])
            ->fileAttachmentsMaxSize(5120)
            ->helperText('Wrap a word in inline code (`apple`) to show its IPA, audio and meaning in a popover when the word exists.')
            ->columnSpanFull();
    }
}
