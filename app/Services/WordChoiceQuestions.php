<?php

namespace App\Services;

use App\Models\Word;
use Illuminate\Support\Collection;

/**
 * The multiple-choice questions of the first review parts, asked about a list of words (those of a lesson, or of a
 * vocabulary): 1 (word → meaning), 2 (meaning → word) and 3 (heard word → meaning). The wrong options are taken from
 * the other words of the list first, then from random stored words.
 */
class WordChoiceQuestions
{
    public const OPTIONS_PER_QUESTION = 4;

    /**
     * How many random stored words are fetched for the wrong options.
     */
    private const EXTRA_WORDS = 30;

    /**
     * Part 1 questions: a word and all its meanings among the meanings of other words.
     *
     * @param  Collection<int, Word>  $words
     * @return list<array{id: int, prompt: string, phonetic: string|null, options: list<string>, answer: int}>
     */
    public function meaning(Collection $words): array
    {
        if ($words->isEmpty()) {
            return [];
        }

        $extraLabels = Word::query()
            ->whereNotIn('id', $words->pluck('id'))
            ->inRandomOrder()
            ->limit(self::EXTRA_WORDS)
            ->get()
            ->toBase()
            ->map(fn (Word $word): string => $word->allMeaningsLabel())
            ->filter();

        return $words->map(function (Word $word) use ($words, $extraLabels): array {
            $correct = $word->allMeaningsLabel();

            $distractors = $words
                ->toBase()
                ->reject(fn (Word $other): bool => $other->is($word))
                ->map(fn (Word $other): string => $other->allMeaningsLabel())
                ->shuffle()
                ->concat($extraLabels->shuffle())
                ->unique()
                ->reject(fn (string $label): bool => $label === $correct)
                ->take(self::OPTIONS_PER_QUESTION - 1);

            $options = $distractors->push($correct)->shuffle()->values();

            return [
                'id' => $word->id,
                'prompt' => $word->word,
                'phonetic' => $word->ipa_us,
                'options' => $options->all(),
                'answer' => $options->search($correct),
            ];
        })->all();
    }

    /**
     * Part 2 questions: all the meanings of a word and the word among other words.
     *
     * @param  Collection<int, Word>  $words
     * @return list<array{id: int, prompt: string, phonetic: null, options: list<string>, answer: int}>
     */
    public function word(Collection $words): array
    {
        if ($words->isEmpty()) {
            return [];
        }

        $extraWords = Word::query()
            ->whereNotIn('id', $words->pluck('id'))
            ->inRandomOrder()
            ->limit(self::EXTRA_WORDS)
            ->pluck('word');

        return $words->map(function (Word $word) use ($words, $extraWords): array {
            $distractors = $words
                ->reject(fn (Word $other): bool => $other->is($word))
                ->pluck('word')
                ->shuffle()
                ->concat($extraWords->shuffle())
                ->unique(fn (string $other): string => mb_strtolower($other))
                ->reject(fn (string $other): bool => mb_strtolower($other) === mb_strtolower($word->word))
                ->take(self::OPTIONS_PER_QUESTION - 1);

            $options = $distractors->push($word->word)->shuffle()->values();

            return [
                'id' => $word->id,
                'prompt' => $word->allMeaningsLabel(),
                'phonetic' => null,
                'options' => $options->all(),
                'answer' => $options->search($word->word),
            ];
        })->all();
    }

    /**
     * Part 3 questions: those of part 1, but the word is only heard (`prompt` is what is spoken), so no IPA is given.
     *
     * @param  Collection<int, Word>  $words
     * @return list<array{id: int, prompt: string, phonetic: null, options: list<string>, answer: int}>
     */
    public function listening(Collection $words): array
    {
        return array_map(fn (array $question): array => [...$question, 'phonetic' => null], $this->meaning($words));
    }
}
