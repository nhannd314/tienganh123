<?php

namespace App\Filament\Resources\Exams\Schemas;

use App\Enums\ExamGroupType;
use App\Enums\ExamQuestionType;
use App\Filament\Forms\Components\RichEditor\RichContentCustomBlocks\AudioBlock;
use App\Filament\Forms\Components\RichEditor\RichContentCustomBlocks\VideoBlock;
use App\Filament\Forms\Components\RichEditor\RichContentCustomBlocks\YoutubeVideoBlock;
use App\Models\ExamQuestion;
use App\Models\Word;
use Closure;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ExamForm
{
    /**
     * The longest sentence to read aloud: it must be read within the 15 seconds a recording may last.
     */
    public const MAX_SENTENCE_LENGTH = 255;

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('course_id')
                    ->relationship('course', 'title')
                    ->searchable()
                    ->preload()
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
                    ->unique(ignoreRecord: true)
                    ->helperText('Leave empty to generate it from the title. The exam is opened at /de-thi/{slug}.'),
                TextInput::make('password')
                    ->maxLength(255)
                    ->helperText('Mật khẩu học sinh phải nhập để vào thi (lưu dạng văn bản thường). Để trống nếu không cần.'),
                TextInput::make('duration_minutes')
                    ->label('Duration')
                    ->numeric()
                    ->integer()
                    ->minValue(1)
                    ->maxValue(600)
                    ->suffix('phút')
                    ->helperText('Hết giờ bài làm được tự động nộp. Để trống nếu không giới hạn thời gian.'),
                Toggle::make('is_published')
                    ->default(false),
                Textarea::make('description')
                    ->rows(3)
                    ->columnSpanFull(),
                Repeater::make('groups')
                    ->relationship()
                    ->orderColumn('sort_order')
                    ->schema(self::groupFields())
                    ->columns(2)
                    ->collapsible()
                    ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                    ->addActionLabel('Thêm nhóm câu hỏi')
                    ->helperText('100 điểm được chia cho tất cả câu hỏi; mỗi câu được nhân với trọng số của nhóm (trọng số 2 = gấp đôi điểm một câu trọng số 1).')
                    ->columnSpanFull(),
            ]);
    }

    /**
     * The sentences typed one per line, without blank lines.
     *
     * @return list<string>
     */
    public static function parseSentenceLines(?string $lines): array
    {
        return collect(preg_split('/\R/u', (string) $lines))
            ->map(fn (string $sentence): string => Str::squish($sentence))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * The words typed one per line (or separated by commas), without blanks or repeats (in any case).
     *
     * @return list<string>
     */
    public static function parseWordLines(?string $lines): array
    {
        return collect(preg_split('/[\r\n,]+/u', (string) $lines))
            ->map(fn (string $word): string => Str::squish($word))
            ->filter()
            ->unique(fn (string $word): string => mb_strtolower($word))
            ->values()
            ->all();
    }

    /**
     * The ids of the stored words typed, in the order they were typed; a word that is not stored is left out.
     *
     * @return list<int>
     */
    public static function wordIdsFromLines(?string $lines): array
    {
        $typed = self::parseWordLines($lines);
        $ids = self::storedWordIds($typed);

        return collect($typed)->map(fn (string $word): ?int => $ids->get(mb_strtolower($word)))->filter()->unique()->values()->all();
    }

    /**
     * The ids of the stored words among the given ones, by the word in lower case (matched in any case on every database).
     *
     * @param  list<string>  $words
     * @return Collection<string, int>
     */
    private static function storedWordIds(array $words): Collection
    {
        return Word::query()
            ->whereIn(DB::raw('lower(word)'), array_map(mb_strtolower(...), $words))
            ->pluck('id', 'word')
            ->mapWithKeys(fn (int $id, string $word): array => [mb_strtolower($word) => $id]);
    }

    /**
     * @return list<Component|Field>
     */
    private static function groupFields(): array
    {
        $isType = fn (ExamGroupType ...$types): Closure => fn (Get $get): bool => in_array(self::typeOf($get('type')), $types, true);

        return [
            Select::make('type')
                ->options(ExamGroupType::class)
                ->default(ExamGroupType::Questions)
                ->required()
                ->live(),
            TextInput::make('title')
                ->required()
                ->maxLength(255),
            RichEditor::make('instructions')
                ->label('Yêu cầu đề bài')
                ->toolbarButtons([
                    ['bold', 'italic', 'underline', 'strike', 'link'],
                    ['bulletList', 'orderedList', 'table'],
                    ['attachFiles', 'customBlocks'],
                    ['undo', 'redo'],
                ])
                ->customBlocks([YoutubeVideoBlock::class, VideoBlock::class, AudioBlock::class])
                ->fileAttachmentsDisk('public')
                ->fileAttachmentsDirectory('exams/instructions')
                ->fileAttachmentsVisibility('public')
                ->fileAttachmentsAcceptedFileTypes(['image/png', 'image/jpeg', 'image/webp', 'image/gif'])
                ->fileAttachmentsMaxSize(5120)
                ->visible($isType(ExamGroupType::Questions))
                ->columnSpanFull(),
            TextInput::make('weight')
                ->label('Trọng số')
                ->required()
                ->numeric()
                ->minValue(0.1)
                ->maxValue(100)
                ->default(1),
            Textarea::make('word_ids')
                ->label('Words')
                ->rows(6)
                ->placeholder("apple\nbanana\nice cream")
                ->helperText('Mỗi dòng một từ (hoặc ngăn cách bằng dấu phẩy). Từ phải có sẵn trong bảng Words; nghĩa, phát âm, audio lấy từ đó.')
                ->formatStateUsing(fn (mixed $state): ?string => is_array($state)
                    ? Word::query()->whereIn('id', $state)->get()->sortBy(fn (Word $word): int|false => array_search($word->id, $state))->pluck('word')->implode("\n")
                    : $state)
                ->dehydrateStateUsing(fn (?string $state): array => self::wordIdsFromLines($state))
                ->required()
                ->rules([
                    fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                        $typed = self::parseWordLines(is_string($value) ? $value : null);
                        $stored = self::storedWordIds($typed);
                        $missing = array_filter($typed, fn (string $word): bool => ! $stored->has(mb_strtolower($word)));

                        if ($missing !== []) {
                            $fail('Chưa có trong bảng Words: '.implode(', ', $missing).'.');
                        }
                    },
                ])
                ->visible($isType(ExamGroupType::MeaningToWord, ExamGroupType::WordToMeaning, ExamGroupType::ListenToMeaning, ExamGroupType::Pronunciation))
                ->columnSpanFull(),
            Textarea::make('sentences')
                ->rows(6)
                ->placeholder("I like playing football.\nMy mother is a teacher.")
                ->helperText('Mỗi dòng một câu tiếng Anh; học sinh đọc từng câu và được Azure chấm điểm phát âm.')
                ->formatStateUsing(fn (mixed $state): ?string => is_array($state) ? implode("\n", $state) : $state)
                ->dehydrateStateUsing(fn (?string $state): array => self::parseSentenceLines($state))
                ->required()
                ->rules([
                    fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                        foreach (self::parseSentenceLines(is_string($value) ? $value : null) as $sentence) {
                            if (mb_strlen($sentence) > self::MAX_SENTENCE_LENGTH) {
                                $fail('Câu "'.Str::limit($sentence, 40).'" dài quá '.self::MAX_SENTENCE_LENGTH.' ký tự.');

                                return;
                            }
                        }
                    },
                ])
                ->visible($isType(ExamGroupType::SentencePronunciation))
                ->columnSpanFull(),
            Textarea::make('passage')
                ->rows(6)
                ->placeholder('Tom has a `dog`. It likes to `run` in the park.')
                ->helperText('Đặt từ cần điền trong dấu `...`; mỗi từ là một câu hỏi.')
                ->required()
                ->rules([
                    fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                        if (! preg_match('/`[^`]*\S[^`]*`/u', (string) $value)) {
                            $fail('Đoạn văn cần ít nhất một từ đặt trong dấu `...`.');
                        }
                    },
                ])
                ->visible($isType(ExamGroupType::ListenAndFill))
                ->columnSpanFull(),
            FileUpload::make('audio')
                ->acceptedFileTypes(['audio/mpeg'])
                ->disk('public')
                ->directory('exams/audio')
                ->visibility('public')
                ->maxSize(20480)
                ->downloadable()
                ->required()
                ->visible($isType(ExamGroupType::ListenAndFill))
                ->columnSpanFull(),
            Repeater::make('questions')
                ->relationship()
                ->orderColumn('sort_order')
                ->schema(self::questionFields())
                ->columns(2)
                ->collapsible()
                ->itemLabel(fn (array $state): ?string => Str::limit((string) ($state['prompt'] ?? ''), 60) ?: null)
                ->addActionLabel('Thêm câu hỏi')
                ->minItems(1)
                ->visible($isType(ExamGroupType::Questions))
                ->columnSpanFull(),
        ];
    }

    /**
     * @return list<Field>
     */
    private static function questionFields(): array
    {
        $isChoice = fn (Get $get): bool => self::questionTypeOf($get('type')) === ExamQuestionType::Choice;

        return [
            Select::make('type')
                ->options(ExamQuestionType::class)
                ->default(ExamQuestionType::Choice)
                ->required()
                ->live(),
            Textarea::make('prompt')
                ->label('Câu hỏi')
                ->rows(2)
                ->required()
                ->helperText('Phần đặt trong dấu `...` được in đậm và gạch chân, ví dụ `a`pple.')
                ->columnSpanFull(),
            TagsInput::make('options')
                ->label('Các lựa chọn')
                ->placeholder('Gõ một lựa chọn rồi bấm Enter')
                ->helperText('Phần đặt trong dấu `...` được in đậm và gạch chân, ví dụ c`a`t. Kéo để đổi thứ tự.')
                ->extraAttributes(['class' => 'fi-numbered-tags'])
                ->reorderable()
                ->required()
                ->live()
                ->visible($isChoice)
                ->columnSpanFull(),
            TextInput::make('answer')
                ->label('Đáp án đúng')
                ->helperText(fn (Get $get): string => $isChoice($get)
                    ? 'Số thứ tự của lựa chọn đúng (1, 2, 3...).'
                    : 'Nhiều đáp án được chấp nhận thì ngăn cách bằng dấu '.ExamQuestion::ANSWER_SEPARATOR.' (không phân biệt hoa thường).')
                ->required()
                ->maxLength(255)
                ->rules([
                    fn (Get $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get, $isChoice): void {
                        $optionCount = count($get('options') ?? []);

                        if ($isChoice($get) && ! (ctype_digit((string) $value) && (int) $value >= 1 && (int) $value <= $optionCount)) {
                            $fail("Đáp án đúng phải là số thứ tự của một lựa chọn, từ 1 đến {$optionCount}.");
                        }
                    },
                ]),
        ];
    }

    private static function typeOf(mixed $type): ?ExamGroupType
    {
        return $type instanceof ExamGroupType ? $type : ExamGroupType::tryFrom((string) $type);
    }

    private static function questionTypeOf(mixed $type): ?ExamQuestionType
    {
        return $type instanceof ExamQuestionType ? $type : ExamQuestionType::tryFrom((string) $type);
    }
}
