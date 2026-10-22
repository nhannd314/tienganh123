<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use Database\Factories\ExamFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * An exam of a course, made of groups of questions and scored out of 100 (see App\Services\ExamPaper). It is opened with
 * `password` (kept in plain text) when one is set, and must be done within `duration_minutes` when that is set.
 */
#[Fillable(['course_id', 'title', 'slug', 'description', 'password', 'duration_minutes', 'is_published'])]
class Exam extends Model
{
    /** @use HasFactory<ExamFactory> */
    use HasFactory, HasSlug;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'duration_minutes' => 'integer',
            'is_published' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * @return HasMany<ExamGroup, $this>
     */
    public function groups(): HasMany
    {
        return $this->hasMany(ExamGroup::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * @return HasMany<ExamAttempt, $this>
     */
    public function attempts(): HasMany
    {
        return $this->hasMany(ExamAttempt::class);
    }

    /**
     * Whether a password must be given to start the exam.
     */
    public function requiresPassword(): bool
    {
        return filled($this->password);
    }

    /**
     * Whether the given password opens the exam; any password does when none is set.
     */
    public function acceptsPassword(?string $password): bool
    {
        return ! $this->requiresPassword() || hash_equals((string) $this->password, (string) $password);
    }

    /**
     * Scope a query to only include published exams.
     */
    #[Scope]
    protected function published(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }
}
