<?php

namespace App\Services;

use App\Enums\ExamGroupType;
use App\Enums\ExamQuestionType;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamGroup;
use App\Models\ExamQuestion;
use App\Models\Word;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * The questions of an exam, group by group, and their grading out of 100.
 *
 * The 100 points are shared among all the questions, a question being worth the weight of its group: with groups of
 * weight 1 every question is worth the same, and a question of a group of weight 2 is worth twice as much. A word or a
 * sentence read aloud earns its points in proportion to its best pronunciation score out of 10.
 */
class ExamPaper
{
    /**
     * How many times a word may be read; its best score counts.
     */
    public const PRONUNCIATION_ATTEMPTS = 3;

    /**
     * A word read aloud counts as right from this score out of 10 (its points are still earned in proportion).
     */
    public const PRONUNCIATION_PASS_SCORE = 7;

    /**
     * How many times a word of a "listen and choose the meaning" group may be heard (counted by the browser).
     */
    public const LISTEN_LIMIT = 3;

    public const TOTAL_POINTS = 100;

    /**
     * The groups that have questions, in order, each with the points of one of its questions. A question is answered as
     * answers[group id][key]; `kind` is "choice" (the `value` of one of `options`: the number of the option of a written
     * question, the meaning itself in a word group), "text" (typed) or "speak" (read into the microphone),
     * and `accepted` lists the answers taken as right, so it must never be shown. The options of the word groups are
     * drawn at random, hence only made when `$withOptions` (grading does not need them). A "listen and fill" group also
     * has its passage as `segments`, the words to fill in carrying the key of their question.
     *
     * @return list<array{group: ExamGroup, points: float, segments: list<array{text: string, blank: int|null}>, questions: list<array{key: string, kind: string, accepted: list<string>, prompt: string|null, phonetic: string|null, audio: string|null, options: list<array{value: string, label: string}>|null}>}>
     */
    public function sections(Exam $exam, bool $withOptions = true): array
    {
        $sections = $exam->groups()->with('questions')->get()
            ->map(fn (ExamGroup $group): array => [
                'group' => $group,
                'segments' => $group->type === ExamGroupType::ListenAndFill ? $group->passageSegments() : [],
                'questions' => $this->questionsOf($group, $withOptions),
            ])
            ->filter(fn (array $section): bool => $section['questions'] !== [])
            ->values();

        $totalWeight = $sections->sum(fn (array $section): float => max(0, $section['group']->weight) * count($section['questions']));

        return $sections
            ->map(fn (array $section): array => [
                ...$section,
                'points' => $totalWeight > 0 ? self::TOTAL_POINTS * max(0, $section['group']->weight) / $totalWeight : 0.0,
            ])
            ->all();
    }

    /**
     * The score out of 100 (rounded to 2 decimals) of the given answers, and each answer graded. The words read aloud are
     * graded from the best scores kept in the attempt, whatever the browser sends.
     *
     * @param  array<int|string, array<int|string, string|null>>  $answers  answers[group id][question key]
     * @return array{score: float, answers: list<array{group_id: int, key: string, answer: string, correct: bool, points: float}>}
     */
    public function grade(ExamAttempt $attempt, array $answers): array
    {
        $graded = [];
        $score = 0.0;

        foreach ($this->sections($attempt->exam, withOptions: false) as $section) {
            $group = $section['group'];

            foreach ($section['questions'] as $question) {
                if ($question['kind'] === 'speak') {
                    $best = (int) ($attempt->pronunciation_scores[self::pronunciationKey($group, (int) $question['key'])]['best'] ?? 0);
                    $given = (string) $best;
                    $isCorrect = $best >= self::PRONUNCIATION_PASS_SCORE;
                    $earned = $section['points'] * $best / 10;
                } else {
                    $given = trim((string) ($answers[$group->id][$question['key']] ?? ''));
                    $isCorrect = $given !== '' && $this->isAccepted($question, $given);
                    $earned = $isCorrect ? $section['points'] : 0.0;
                }

                $score += $earned;
                $graded[] = [
                    'group_id' => $group->id,
                    'key' => $question['key'],
                    'answer' => Str::limit($given, 255, ''),
                    'correct' => $isCorrect,
                    'points' => round($earned, 2),
                ];
            }
        }

        return ['score' => round(min(self::TOTAL_POINTS, $score), 2), 'answers' => $graded];
    }

    /**
     * Where the best score of a question read aloud (a word id, or a sentence number) is kept in ExamAttempt::$pronunciation_scores.
     */
    public static function pronunciationKey(ExamGroup $group, int $key): string
    {
        return "{$group->id}:{$key}";
    }

    /**
     * The English text Azure compares a reading with: the word of a pronunciation group (shown only by its meaning), or
     * the sentence of a sentence pronunciation group; null when the group has no such question.
     */
    public function spokenText(ExamGroup $group, int $key): ?string
    {
        return match ($group->type) {
            ExamGroupType::Pronunciation => $group->words()->firstWhere('id', $key)?->word,
            ExamGroupType::SentencePronunciation => $group->numberedSentences()[$key] ?? null,
            default => null,
        };
    }

    /**
     * A typed answer as it is compared: any case, straight apostrophes, one space between the words.
     */
    public static function normalize(string $text): string
    {
        return mb_strtolower(Str::squish(str_replace(['’', '‘'], "'", $text)));
    }

    /**
     * @param  array{kind: string, accepted: list<string>}  $question
     */
    private function isAccepted(array $question, string $given): bool
    {
        if ($question['kind'] === 'choice') {
            return in_array($given, $question['accepted'], true);
        }

        return in_array(self::normalize($given), array_map(self::normalize(...), $question['accepted']), true);
    }

    /**
     * @return list<array{key: string, kind: string, accepted: list<string>, prompt: string|null, phonetic: string|null, audio: string|null, options: list<array{value: string, label: string}>|null}>
     */
    private function questionsOf(ExamGroup $group, bool $withOptions): array
    {
        return match ($group->type) {
            ExamGroupType::Questions => $group->questions->map(fn (ExamQuestion $question): array => $this->question(
                key: (string) $question->id,
                kind: $question->type === ExamQuestionType::Choice ? 'choice' : 'text',
                accepted: $question->acceptedAnswers(),
                prompt: $question->prompt,
                options: $question->type === ExamQuestionType::Choice
                    ? collect($question->options ?? [])->values()->map(fn (string $option, int $index): array => ['value' => (string) ($index + 1), 'label' => $option])->all()
                    : null,
            ))->all(),
            ExamGroupType::MeaningToWord => $group->words()->map(fn (Word $word): array => $this->question(
                key: (string) $word->id,
                kind: 'text',
                accepted: [$word->word],
                prompt: $word->allMeaningsLabel(),
            ))->all(),
            ExamGroupType::WordToMeaning, ExamGroupType::ListenToMeaning => $this->meaningQuestions($group, $withOptions),
            ExamGroupType::Pronunciation => $group->words()->map(fn (Word $word): array => $this->question(
                key: (string) $word->id,
                kind: 'speak',
                accepted: [],
                prompt: $word->allMeaningsLabel(),
            ))->all(),
            ExamGroupType::SentencePronunciation => collect($group->numberedSentences())->map(fn (string $sentence, int $number): array => $this->question(
                key: (string) $number,
                kind: 'speak',
                accepted: [],
                prompt: $sentence,
            ))->values()->all(),
            ExamGroupType::ListenAndFill => collect($group->passageSegments())
                ->whereNotNull('blank')
                ->map(fn (array $segment): array => $this->question(
                    key: (string) $segment['blank'],
                    kind: 'text',
                    accepted: [$segment['text']],
                ))
                ->values()
                ->all(),
        };
    }

    /**
     * A word whose meaning is chosen, seen (WordToMeaning) or only heard (ListenToMeaning). Every meaning of the word is
     * taken, as in the lesson review.
     *
     * @return list<array{key: string, kind: string, accepted: list<string>, prompt: string|null, phonetic: string|null, audio: string|null, options: list<array{value: string, label: string}>|null}>
     */
    private function meaningQuestions(ExamGroup $group, bool $withOptions): array
    {
        $words = $group->words();
        $isListening = $group->type === ExamGroupType::ListenToMeaning;
        /** @var Collection<int, array{id: int, options: list<string>, audio: string|null}> $choices */
        $choices = collect($withOptions ? app(WordChoiceQuestions::class)->meaning($words) : [])->keyBy('id');

        return $words->map(fn (Word $word): array => $this->question(
            key: (string) $word->id,
            kind: 'choice',
            accepted: [$word->allMeaningsLabel(), ...$word->meaningLabels()],
            prompt: $word->word,
            phonetic: $isListening ? null : $word->ipa_us,
            audio: $choices[$word->id]['audio'] ?? null,
            options: isset($choices[$word->id]) ? array_map(fn (string $option): array => ['value' => $option, 'label' => $option], $choices[$word->id]['options']) : null,
        ))->all();
    }

    /**
     * @param  list<string>  $accepted
     * @param  list<array{value: string, label: string}>|null  $options
     * @return array{key: string, kind: string, accepted: list<string>, prompt: string|null, phonetic: string|null, audio: string|null, options: list<array{value: string, label: string}>|null}
     */
    private function question(string $key, string $kind, array $accepted, ?string $prompt = null, ?string $phonetic = null, ?string $audio = null, ?array $options = null): array
    {
        return compact('key', 'kind', 'accepted', 'prompt', 'phonetic', 'audio', 'options');
    }
}
