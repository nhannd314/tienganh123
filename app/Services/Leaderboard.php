<?php

namespace App\Services;

use App\Events\LearnerAchieved;
use App\Models\Lesson;
use App\Models\LessonScore;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

/**
 * The BXH: the top learners by the total of their best scores over all the published lessons (see the x-leaderboard
 * component). It is cached for CACHE_SECONDS, and refreshed at once when a new best score changes it.
 */
class Leaderboard
{
    public const CACHE_KEY = 'leaderboard';

    public const CACHE_SECONDS = 300;

    private const SIZE = 10;

    /**
     * The top learners, best first, from the cache.
     *
     * @return list<array{userId: int, name: string, avatarUrl: string|null, points: int}>
     */
    public function top(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_SECONDS, fn (): array => $this->compute());
    }

    /**
     * Work the BXH out again after the user beat their best score: when it changed, put it in the cache, and when the user
     * climbed (or came in), announce it (see LearnerAchieved::leaderboardRank).
     */
    public function refreshAfterScoreOf(User $user): void
    {
        $before = $this->top();
        $after = $this->compute();

        if ($after === $before) {
            return;
        }

        Cache::put(self::CACHE_KEY, $after, self::CACHE_SECONDS);

        $rankBefore = $this->rankOf($user, $before);
        $rankAfter = $this->rankOf($user, $after);

        if ($rankAfter !== null && ($rankBefore === null || $rankAfter < $rankBefore)) {
            LearnerAchieved::leaderboardRank($user, $rankAfter, $after[$rankAfter - 1]['points'])->announce();
        }
    }

    /**
     * Top SIZE learners by the total of their best scores over all the published lessons, best first (ties go to whoever
     * has the lowest user id).
     *
     * @return list<array{userId: int, name: string, avatarUrl: string|null, points: int}>
     */
    private function compute(): array
    {
        $lessonIds = Lesson::query()
            ->published()
            ->whereHas('unit', fn (Builder $unit) => $unit->whereHas('course', fn (Builder $course) => $course->published()))
            ->select('lessons.id');

        return LessonScore::query()
            ->whereIn('lesson_id', $lessonIds)
            ->groupBy('user_id')
            ->selectRaw('user_id, sum(score) as points')
            ->orderByDesc('points')
            ->orderBy('user_id')
            ->limit(self::SIZE)
            ->with('user')
            ->get()
            ->map(fn (LessonScore $row): array => [
                'userId' => (int) $row->user_id,
                'name' => $row->user->name,
                'avatarUrl' => $row->user->avatar_url,
                'points' => (int) $row->points,
            ])
            ->all();
    }

    /**
     * The 1-based rank of the user in the BXH, or null when they are not in it.
     *
     * @param  list<array{userId: int, name: string, avatarUrl: string|null, points: int}>  $top
     */
    private function rankOf(User $user, array $top): ?int
    {
        $index = array_search($user->id, array_column($top, 'userId'), true);

        return $index === false ? null : $index + 1;
    }
}
