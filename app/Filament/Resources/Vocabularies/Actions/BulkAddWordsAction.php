<?php

namespace App\Filament\Resources\Vocabularies\Actions;

use App\Models\Vocabulary;
use App\Models\Word;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
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
                    ->placeholder("apple (noun) quả táo, táo\nwork (noun) công việc (verb) làm việc")
                    ->helperText('One word per line: word (part of speech) meaning, another meaning (part of speech) meaning. New words are created; existing words get the missing meanings.'),
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
     * Parse one word per line: `word (pos) meaning, meaning (pos) meaning`.
     *
     * @return list<array{word: string, meanings: list<array{pos: string, meaning: string}>}>
     */
    public static function parse(string $text): array
    {
        $entries = [];

        foreach (preg_split('/\R/u', $text) as $line) {
            $line = trim($line);
            $parenthesis = mb_strpos($line, '(');
            $word = trim($parenthesis === false ? $line : mb_substr($line, 0, $parenthesis));

            if ($word === '') {
                continue;
            }

            $meanings = [];

            if ($parenthesis !== false) {
                preg_match_all('/\(([^)]*)\)\s*([^(]*)/u', mb_substr($line, $parenthesis), $matches, PREG_SET_ORDER);

                foreach ($matches as [, $pos, $meaningList]) {
                    foreach (explode(',', $meaningList) as $meaning) {
                        $meaning = trim($meaning);
                        $pos = mb_strtolower(trim($pos));

                        if ($meaning !== '' && $pos !== '') {
                            $meanings[mb_strtolower($pos.'|'.$meaning)] ??= ['pos' => $pos, 'meaning' => $meaning];
                        }
                    }
                }
            }

            $entries[] = ['word' => $word, 'meanings' => array_values($meanings)];
        }

        return $entries;
    }

    /**
     * Create the missing words, add the missing meanings to existing words and attach them all to the vocabulary.
     *
     * @param  list<array{word: string, meanings: list<array{pos: string, meaning: string}>}>  $entries
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

                if (! $word) {
                    $word = Word::create(['word' => $entry['word'], 'meanings' => $entry['meanings']]);
                    $result['created']++;
                } else {
                    $meanings = $word->meanings ?? [];
                    $known = collect($meanings)->map(fn (array $meaning): string => mb_strtolower(($meaning['pos'] ?? '').'|'.($meaning['meaning'] ?? '')))->all();
                    $missing = array_values(array_filter(
                        $entry['meanings'],
                        fn (array $meaning): bool => ! in_array(mb_strtolower($meaning['pos'].'|'.$meaning['meaning']), $known, true),
                    ));

                    if ($missing !== []) {
                        $word->update(['meanings' => [...$meanings, ...$missing]]);
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
