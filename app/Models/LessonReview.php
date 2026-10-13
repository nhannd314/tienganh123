<?php

namespace App\Models;

use Database\Factories\LessonReviewFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['lesson_id', 'user_id', 'step', 'total', 'correct', 'duration_seconds', 'answers'])]
class LessonReview extends Model
{
    /** @use HasFactory<LessonReviewFactory> */
    use HasFactory;

    /**
     * A part counts as completed only with at least this share of right answers, in percent.
     */
    public const PASS_PERCENT = 80;

    /**
     * Whether this attempt completes its part: at least PASS_PERCENT of the answers are right.
     */
    public function isPassed(): bool
    {
        return $this->correct * 100 >= $this->total * self::PASS_PERCENT;
    }

    /**
     * Scope a query to the attempts that complete their part (see isPassed).
     */
    #[Scope]
    protected function passed(Builder $query): Builder
    {
        return $query->whereRaw('correct * 100 >= total * ?', [self::PASS_PERCENT]);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'step' => 'integer',
            'total' => 'integer',
            'correct' => 'integer',
            'duration_seconds' => 'integer',
            'answers' => 'array',
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
}
