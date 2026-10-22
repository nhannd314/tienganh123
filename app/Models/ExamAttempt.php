<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\ExamAttemptFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One sitting of an exam by a student. `score` (out of 100) and the graded `answers` are set once it is submitted;
 * `pronunciation_scores` keeps, by ExamPaper::pronunciationKey, the best score out of 10 of each word read and the
 * number of times it was read.
 */
#[Fillable(['exam_id', 'user_id', 'student_name', 'class_name', 'score', 'answers', 'pronunciation_scores', 'started_at', 'submitted_at'])]
class ExamAttempt extends Model
{
    /** @use HasFactory<ExamAttemptFactory> */
    use HasFactory;

    /**
     * How long after the time is up the answers are still taken, for a slow network.
     */
    public const GRACE_SECONDS = 120;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'score' => 'float',
            'answers' => 'array',
            'pronunciation_scores' => 'array',
            'started_at' => 'datetime',
            'submitted_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Exam, $this>
     */
    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isSubmitted(): bool
    {
        return $this->submitted_at !== null;
    }

    /**
     * When the time is up, or null when the exam has no time limit.
     */
    public function deadline(): ?CarbonInterface
    {
        $minutes = $this->exam->duration_minutes;

        return $minutes ? $this->started_at->copy()->addMinutes($minutes) : null;
    }

    /**
     * The seconds left to do the exam (0 once the time is up), or null when it has no time limit.
     */
    public function secondsLeft(): ?int
    {
        $deadline = $this->deadline();

        return $deadline ? max(0, (int) now()->diffInSeconds($deadline, false)) : null;
    }

    /**
     * Whether the time and its grace period are over, so no answer is taken any more.
     */
    public function isOverdue(): bool
    {
        $deadline = $this->deadline();

        return $deadline !== null && now()->greaterThan($deadline->copy()->addSeconds(self::GRACE_SECONDS));
    }
}
