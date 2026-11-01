<?php

namespace App\Models;

use App\Enums\ChallengeStatus;
use Database\Factories\ChallengeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A learner challenging another one, who is on the site right now, on a lesson. The opponent gets a popup and accepts or
 * declines; once accepted, both go to the challenge page.
 */
#[Fillable([
    'challenger_id', 'opponent_id', 'lesson_id', 'status', 'responded_at',
    'challenger_ready_at', 'opponent_ready_at', 'started_at', 'words', 'word_index', 'max_health',
    'challenger_health', 'opponent_health', 'challenger_progress', 'opponent_progress', 'winner_id', 'finished_at',
])]
class Challenge extends Model
{
    /** @use HasFactory<ChallengeFactory> */
    use HasFactory;

    /**
     * How long the opponent has to answer. After that the challenge can no longer be accepted.
     */
    public const EXPIRES_AFTER_SECONDS = 60;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ChallengeStatus::class,
            'responded_at' => 'datetime',
            'challenger_ready_at' => 'datetime',
            'opponent_ready_at' => 'datetime',
            'started_at' => 'datetime',
            'words' => 'array',
            'word_index' => 'integer',
            'max_health' => 'integer',
            'challenger_health' => 'integer',
            'opponent_health' => 'integer',
            'challenger_progress' => 'integer',
            'opponent_progress' => 'integer',
            'finished_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function challenger(): BelongsTo
    {
        return $this->belongsTo(User::class, 'challenger_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function opponent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opponent_id');
    }

    /**
     * @return BelongsTo<Lesson, $this>
     */
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    /**
     * Whether the challenge still waits for an answer: pending and sent less than EXPIRES_AFTER_SECONDS ago.
     */
    public function isOpen(): bool
    {
        return $this->status === ChallengeStatus::Pending
            && $this->created_at->greaterThan(now()->subSeconds(self::EXPIRES_AFTER_SECONDS));
    }

    /**
     * The side of the user in the game: "challenger" or "opponent".
     */
    public function sideOf(User $user): string
    {
        return $user->id === $this->challenger_id ? 'challenger' : 'opponent';
    }

    /**
     * Whether the user is one of the two players.
     */
    public function involves(User $user): bool
    {
        return in_array($user->id, [$this->challenger_id, $this->opponent_id], true);
    }

    /**
     * Close the challenge with the given answer.
     */
    public function respond(ChallengeStatus $status): void
    {
        $this->update(['status' => $status, 'responded_at' => now()]);
    }
}
