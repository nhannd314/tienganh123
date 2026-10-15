<?php

namespace App\Models;

use Database\Factories\GameScoreFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

/**
 * The fastest time a user won a game of a lesson in. `game` is the slug of the game (see LessonGameController::GAMES).
 */
#[Fillable(['lesson_id', 'user_id', 'game', 'milliseconds'])]
class GameScore extends Model
{
    /** @use HasFactory<GameScoreFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'milliseconds' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Lesson, $this>
     */
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @param  Builder<GameScore>  $query
     * @return Builder<GameScore>
     */
    protected function scopeForGame(Builder $query, Lesson $lesson, string $game): Builder
    {
        return $query->where('lesson_id', $lesson->id)->where('game', $game);
    }

    /**
     * Keep the time of the user when it beats their best in the game of the lesson.
     */
    public static function record(Lesson $lesson, string $game, User $user, int $milliseconds): self
    {
        $best = self::forGame($lesson, $game)->firstWhere('user_id', $user->id);

        if ($best === null) {
            return self::create(['lesson_id' => $lesson->id, 'game' => $game, 'user_id' => $user->id, 'milliseconds' => $milliseconds]);
        }

        if ($milliseconds < $best->milliseconds) {
            $best->update(['milliseconds' => $milliseconds]);
        }

        return $best;
    }

    /**
     * The fastest players of the game of the lesson, fastest first (ties go to whoever was first).
     *
     * @return Collection<int, array{name: string, seconds: string, isMine: bool}>
     */
    public static function leaderboard(Lesson $lesson, string $game, ?User $viewer = null, int $limit = 10): Collection
    {
        return self::forGame($lesson, $game)
            ->with('user')
            ->orderBy('milliseconds')
            ->orderBy('id')
            ->limit($limit)
            ->get()
            ->map(fn (self $score): array => [
                'name' => $score->user->name,
                'seconds' => number_format($score->milliseconds / 1000, 1, '.', ''),
                'isMine' => $viewer?->is($score->user) ?? false,
            ]);
    }
}
