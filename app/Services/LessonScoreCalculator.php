<?php

namespace App\Services;

/**
 * Works out the score of a user in a lesson from their review progress (see Lesson::reviewProgress).
 *
 * There is no fixed scale: every right answer earns points, so a lesson with more words is worth more.
 *
 *  - Right answers are the main source: POINTS_PER_CORRECT each.
 *  - Wrong answers take PENALTY_PER_WRONG each off.
 *  - Words read aloud well add pronunciation points: for each word of parts 5 and 6, PRONUNCIATION_POINTS_PER_SCORE for
 *    every point the average score is above PRONUNCIATION_FREE_SCORE (the pass score), so 10/10 adds 6 per word.
 *  - Finishing quickly adds up to SPEED_BONUS_RATE of the points so far. It is full at SPEED_FULL_RATIO of the par time
 *    (PAR_SECONDS_PER_QUESTION per answered question) and fades to nothing at SPEED_NONE_RATIO. It only applies from
 *    SPEED_MIN_ACCURACY right answers, so rushing through guesses earns nothing.
 *  - Having answered right at least 90% of all the questions adds 5% of the points so far, and at least 96% adds 10% (the
 *    higher step only, see COMPLETION_BONUSES).
 *  - The total is divided by SCORE_DIVISOR, so that the scores of many lessons can be added up without growing huge.
 *
 * The score is never below 0. The points of each factor in the result are before that division.
 */
class LessonScoreCalculator
{
    public const POINTS_PER_CORRECT = 10;

    public const PENALTY_PER_WRONG = 4;

    public const PRONUNCIATION_FREE_SCORE = 7;

    public const PRONUNCIATION_POINTS_PER_SCORE = 2;

    public const PAR_SECONDS_PER_QUESTION = 10;

    public const SPEED_BONUS_RATE = 0.20;

    public const SPEED_FULL_RATIO = 0.5;

    public const SPEED_NONE_RATIO = 1.5;

    public const SPEED_MIN_ACCURACY = 0.7;

    /**
     * Share of all the questions answered right => share of the points so far it adds, best step first.
     *
     * @var array<string, float>
     */
    public const COMPLETION_BONUSES = ['0.96' => 0.10, '0.90' => 0.05];

    public const SCORE_DIVISOR = 10;

    /**
     * The score (the total divided by SCORE_DIVISOR, rounded) with the points each factor gave before the division.
     *
     * @param  array{done: int, correct: int, wrong: int, total: int, seconds: int, pronunciation: float, pronunciationWords: int}  $progress
     * @return array{score: int, correct: int, penalty: int, pronunciation: int, speed: int, completion: int}
     */
    public function calculate(array $progress): array
    {
        $correct = $progress['correct'] * self::POINTS_PER_CORRECT;
        $penalty = $progress['wrong'] * self::PENALTY_PER_WRONG;
        $pronunciation = (int) round(
            $progress['pronunciationWords']
            * max(0, $progress['pronunciation'] - self::PRONUNCIATION_FREE_SCORE)
            * self::PRONUNCIATION_POINTS_PER_SCORE
        );

        $subtotal = max(0, $correct - $penalty + $pronunciation);
        $speed = (int) round($subtotal * self::SPEED_BONUS_RATE * $this->speedFactor($progress));
        $completion = (int) round($subtotal * $this->completionRate($progress));

        return [
            'score' => (int) round(($subtotal + $speed + $completion) / self::SCORE_DIVISOR),
            'correct' => $correct,
            'penalty' => $penalty,
            'pronunciation' => $pronunciation,
            'speed' => $speed,
            'completion' => $completion,
        ];
    }

    /**
     * How much of the speed bonus is earned, from 0 to 1.
     *
     * @param  array{done: int, correct: int, seconds: int}  $progress
     */
    private function speedFactor(array $progress): float
    {
        if ($progress['done'] === 0 || $progress['seconds'] <= 0 || $progress['correct'] < $progress['done'] * self::SPEED_MIN_ACCURACY) {
            return 0.0;
        }

        $ratio = $progress['seconds'] / ($progress['done'] * self::PAR_SECONDS_PER_QUESTION);

        return max(0.0, min(1.0, (self::SPEED_NONE_RATIO - $ratio) / (self::SPEED_NONE_RATIO - self::SPEED_FULL_RATIO)));
    }

    /**
     * The share of the points added for having answered most of the questions right (see COMPLETION_BONUSES).
     *
     * @param  array{correct: int, total: int}  $progress
     */
    private function completionRate(array $progress): float
    {
        if ($progress['total'] <= 0) {
            return 0.0;
        }

        foreach (self::COMPLETION_BONUSES as $ratio => $rate) {
            if ($progress['correct'] >= $progress['total'] * (float) $ratio) {
                return $rate;
            }
        }

        return 0.0;
    }
}
