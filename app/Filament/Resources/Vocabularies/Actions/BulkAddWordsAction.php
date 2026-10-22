<?php

namespace App\Filament\Resources\Vocabularies\Actions;

use App\Models\Vocabulary;
use App\Models\Word;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Facades\DB;

class BulkAddWordsAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'bulkAddWords';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Add words')
            ->modalHeading('Add words')
            ->modalSubmitActionLabel('Add words')
            ->modalWidth('2xl')
            ->schema([
                Textarea::make('words')
                    ->required()
                    ->rows(12)
                    ->placeholder("apple [noun] quả táo, táo | I have an apple = Tôi có một quả táo\nwork [noun] công việc [verb] làm việc")
                    ->helperText('Mỗi dòng một từ: từ [từ loại] nghĩa, nghĩa khác [từ loại] nghĩa | câu ví dụ = bản dịch | câu ví dụ khác. Mỗi [từ loại] là một nghĩa: "[n] táo, quả táo" lưu chung một nghĩa, "[n] quả táo [n] táo" lưu hai nghĩa. Từ loại có thể viết tắt (n, v, a, adv, pron, prep, conj, interj, det) và được lưu thành dạng đầy đủ. Gõ một từ đã có rồi bấm Tab hoặc click ra ngoài ô để tự nạp nghĩa của từ đó. Từ mới sẽ được tạo; từ đã có chỉ được thêm các nghĩa và ví dụ còn thiếu.')
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (Set $set, ?string $state) => $set('words', self::loadKnownWords((string) $state))),
            ])
            ->action(function (array $data, RelationManager $livewire): void {
                $result = self::apply($livewire->getOwnerRecord(), self::parse($data['words']));

                Notification::make()
                    ->title('Words added to the vocabulary')
                    ->body("{$result['created']} created, {$result['updated']} updated, {$result['attached']} attached.")
                    ->success()
                    ->send();
            });
    }

    /**
     * Parse one word per line: `word [pos] meaning, meaning [pos] meaning | example = translation | example = translation`.
     * Each `[pos]` makes one meaning, its comma separated words kept together ("[n] táo, quả táo" is one meaning, "[n] táo
     * [n] quả táo" two). The part of speech may be abbreviated (`[n]`); it is stored in full (`noun`).
     * An example is a sentence and its translation, e.g. "I have an apple = Tôi có một quả táo". A comma inside round brackets, a
     * note on a meaning, does not split it.
     *
     * @return list<array{word: string, meanings: list<array{pos: string, meaning: string}>, examples: list<array{sentence: string, translation: string|null}>}>
     */
    public static function parse(string $text): array
    {
        $entries = [];

        foreach (preg_split('/\R/u', $text) as $line) {
            [$head, $examplePieces] = [...explode('|', trim($line), 2), ''];
            $bracket = mb_strpos($head, '[');
            $word = trim($bracket === false ? $head : mb_substr($head, 0, $bracket));

            if ($word === '') {
                continue;
            }

            $meanings = [];

            if ($bracket !== false) {
                preg_match_all('/\[([^\]]*)\]\s*([^\[]*)/u', mb_substr($head, $bracket), $matches, PREG_SET_ORDER);

                foreach ($matches as [, $pos, $meaningList]) {
                    $pos = Word::expandPartOfSpeech($pos);
                    $meaning = implode(', ', collect(self::meaningPieces($meaningList))->unique(fn (string $piece): string => mb_strtolower($piece))->all());

                    if ($meaning !== '' && $pos !== '') {
                        $meanings[mb_strtolower($pos.'|'.$meaning)] ??= ['pos' => $pos, 'meaning' => $meaning];
                    }
                }
            }

            $examples = [];

            foreach (explode('|', $examplePieces) as $piece) {
                [$sentence, $translation] = [...explode('=', $piece, 2), ''];
                $sentence = trim($sentence);

                if ($sentence !== '') {
                    $examples[mb_strtolower($sentence)] ??= ['sentence' => $sentence, 'translation' => filled(trim($translation)) ? trim($translation) : null];
                }
            }

            $entries[] = ['word' => $word, 'meanings' => array_values($meanings), 'examples' => array_values($examples)];
        }

        return $entries;
    }

    /**
     * The words of a meaning separated by commas, e.g. "táo, quả táo" gives "táo" and "quả táo". A comma inside round
     * brackets, a note on the meaning, does not split it.
     *
     * @return list<string>
     */
    public static function meaningPieces(string $meaning): array
    {
        return array_values(array_filter(
            array_map(fn (string $piece): string => trim($piece), preg_split('/,(?![^()]*\))/u', $meaning)),
            fn (string $piece): bool => $piece !== '',
        ));
    }

    /**
     * The word as a line to be parsed again, each meaning after its own part of speech: `word [pos] meaning [pos] meaning`.
     */
    public static function formatLine(Word $word): string
    {
        $meanings = collect($word->meanings ?? [])
            ->filter(fn (array $meaning): bool => filled($meaning['meaning'] ?? null))
            ->map(function (array $meaning): string {
                $pos = Word::abbreviatePartOfSpeech($meaning['pos'] ?? '');

                return trim(($pos !== '' ? "[{$pos}] " : '').$meaning['meaning']);
            })
            ->implode(' ');

        return trim($word->word.' '.$meanings);
    }

    /**
     * Replace each line that is only a word already in the list of words by that word with its meanings.
     */
    public static function loadKnownWords(string $text): string
    {
        $lines = preg_split('/\R/u', $text);
        $typed = collect($lines)
            ->map(fn (string $line): string => trim($line))
            ->filter(fn (string $line): bool => $line !== '' && ! str_contains($line, '[') && ! str_contains($line, '|'));

        if ($typed->isEmpty()) {
            return $text;
        }

        $known = Word::query()
            ->whereIn(DB::raw('lower(word)'), $typed->map(fn (string $line): string => mb_strtolower($line))->unique()->all())
            ->get()
            ->keyBy(fn (Word $word): string => mb_strtolower($word->word));

        return collect($lines)
            ->map(fn (string $line): string => $known->has(mb_strtolower(trim($line))) && $typed->contains(trim($line)) ? self::formatLine($known->get(mb_strtolower(trim($line)))) : $line)
            ->implode("\n");
    }

    /**
     * The typed meanings with only their words (separated by commas) that the stored meanings of the same part of speech
     * do not have yet, in any case; a meaning whose words are all stored is left out. With "[n] quả táo" stored, "[n]
     * táo, quả táo" adds "[n] táo".
     *
     * @param  list<array{pos?: string, meaning?: string}>  $stored
     * @param  list<array{pos: string, meaning: string}>  $typed
     * @return list<array{pos: string, meaning: string}>
     */
    private static function missingMeanings(array $stored, array $typed): array
    {
        $known = [];

        foreach ($stored as $meaning) {
            foreach (self::meaningPieces($meaning['meaning'] ?? '') as $piece) {
                $known[mb_strtolower(($meaning['pos'] ?? '').'|'.$piece)] = true;
            }
        }

        $missing = [];

        foreach ($typed as $meaning) {
            $pieces = array_values(array_filter(self::meaningPieces($meaning['meaning']), fn (string $piece): bool => ! isset($known[mb_strtolower($meaning['pos'].'|'.$piece)])));

            foreach ($pieces as $piece) {
                $known[mb_strtolower($meaning['pos'].'|'.$piece)] = true;
            }

            if ($pieces !== []) {
                $missing[] = ['pos' => $meaning['pos'], 'meaning' => implode(', ', $pieces)];
            }
        }

        return $missing;
    }

    /**
     * Create the missing words, add the missing meanings and examples to existing words and attach them all to the vocabulary.
     *
     * @param  list<array{word: string, meanings: list<array{pos: string, meaning: string}>, examples: list<array{sentence: string, translation: string|null}>}>  $entries
     * @return array{created: int, updated: int, attached: int}
     */
    public static function apply(Vocabulary $vocabulary, array $entries): array
    {
        $result = ['created' => 0, 'updated' => 0, 'attached' => 0];

        DB::transaction(function () use ($vocabulary, $entries, &$result): void {
            $attachedIds = $vocabulary->words()->pluck('words.id')->all();
            $sortOrder = (int) $vocabulary->words()->max('vocabulary_word.sort_order');

            foreach ($entries as $entry) {
                $word = Word::query()->whereRaw('lower(word) = ?', [mb_strtolower($entry['word'])])->first();
                $examples = array_map(fn (array $example): array => [...$example, 'audio' => null], $entry['examples']);

                if (! $word) {
                    $word = Word::create(['word' => $entry['word'], 'meanings' => $entry['meanings'], 'examples' => $examples]);
                    $result['created']++;
                } else {
                    $meanings = $word->meanings ?? [];
                    $missing = self::missingMeanings($meanings, $entry['meanings']);

                    $storedExamples = $word->examples ?? [];
                    $knownSentences = collect($storedExamples)->map(fn (array $example): string => mb_strtolower(trim($example['sentence'] ?? '')))->all();
                    $missingExamples = array_values(array_filter(
                        $examples,
                        fn (array $example): bool => ! in_array(mb_strtolower($example['sentence']), $knownSentences, true),
                    ));

                    if ($missing !== [] || $missingExamples !== []) {
                        $word->update(['meanings' => [...$meanings, ...$missing], 'examples' => [...$storedExamples, ...$missingExamples]]);
                        $result['updated']++;
                    }
                }

                if (! in_array($word->id, $attachedIds, true)) {
                    $vocabulary->words()->attach($word->id, ['sort_order' => ++$sortOrder]);
                    $attachedIds[] = $word->id;
                    $result['attached']++;
                }
            }
        });

        return $result;
    }
}
