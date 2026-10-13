<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use Database\Factories\LessonFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'unit_id',
    'title',
    'slug',
    'description',
    'passage',
    'passage_audio',
    'content',
    'attachments',
    'sort_order',
    'is_free',
    'is_published',
])]
class Lesson extends Model
{
    /** @use HasFactory<LessonFactory> */
    use HasFactory, HasSlug;

    /**
     * Parts of the lesson review, and the most questions one part asks.
     */
    public const REVIEW_PARTS = 6;

    public const REVIEW_QUESTIONS_PER_PART = 10;

    /**
     * Slugs are unique within the parent unit_id.
     *
     * @return list<string>
     */
    protected function slugScopeColumns(): array
    {
        return ['unit_id'];
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'attachments' => 'array',
            'sort_order' => 'integer',
            'is_free' => 'boolean',
            'is_published' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Unit, $this>
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * @return HasMany<LessonReview, $this>
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(LessonReview::class);
    }

    /**
     * @return BelongsToMany<Vocabulary, $this>
     */
    public function vocabularies(): BelongsToMany
    {
        return $this->belongsToMany(Vocabulary::class)
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    /**
     * @return BelongsToMany<FlashcardSet, $this>
     */
    public function flashcardSets(): BelongsToMany
    {
        return $this->belongsToMany(FlashcardSet::class)
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    /**
     * New words of the lesson: the words of its vocabularies, in vocabulary order then word order, without repeats.
     *
     * @return Attribute<Collection<int, Word>, never>
     */
    protected function words(): Attribute
    {
        return Attribute::get(fn (): Collection => $this->vocabularies()
            ->with('words')
            ->get()
            ->flatMap(fn (Vocabulary $vocabulary): Collection => $vocabulary->words)
            ->unique('id')
            ->values())->withoutObjectCaching();
    }

    /**
     * The words a review asks about: the first ten that have at least one meaning.
     *
     * @return Collection<int, Word>
     */
    public function reviewWords(): Collection
    {
        return $this->words
            ->filter(fn (Word $word): bool => $word->meaningLabels() !== [])
            ->take(self::REVIEW_QUESTIONS_PER_PART)
            ->values();
    }

    /**
     * Progress of the user over the whole review: completed parts out of all parts, answered questions out of the
     * questions of every part, and the time spent. The answers count once per part, with its latest attempt; a part
     * is completed once an attempt at it passed (see LessonReview::isPassed), even if a later one did not.
     *
     * @return array{percent: int, completedParts: int, parts: int, done: int, correct: int, wrong: int, total: int, seconds: int}
     */
    public function reviewProgress(User $user): array
    {
        $attempts = $this->reviews()->where('user_id', $user->id)->orderByDesc('id')->get();
        $latest = $attempts->unique('step');
        $completedParts = $attempts->filter(fn (LessonReview $review): bool => $review->isPassed())->unique('step')->count();
        $done = (int) $latest->sum('total');
        $correct = (int) $latest->sum('correct');

        return [
            'percent' => self::reviewPercent($completedParts),
            'completedParts' => $completedParts,
            'parts' => self::REVIEW_PARTS,
            'done' => $done,
            'correct' => $correct,
            'wrong' => $done - $correct,
            'total' => $this->reviewWords()->count() * self::REVIEW_PARTS,
            'seconds' => (int) $latest->sum('duration_seconds'),
        ];
    }

    /**
     * The review percent (as in reviewProgress) of the user for each of the lessons, keyed by lesson id, in one query.
     * A lesson without a completed part is left out.
     *
     * @param  iterable<int>  $lessonIds
     * @return Collection<int, int>
     */
    public static function reviewPercentsFor(User $user, iterable $lessonIds): Collection
    {
        return LessonReview::query()
            ->passed()
            ->where('user_id', $user->id)
            ->whereIn('lesson_id', $lessonIds)
            ->groupBy('lesson_id')
            ->selectRaw('lesson_id, count(distinct step) as completed_parts')
            ->pluck('completed_parts', 'lesson_id')
            ->map(fn ($completedParts): int => self::reviewPercent((int) $completedParts));
    }

    /**
     * The share of the review parts finished, in percent.
     */
    private static function reviewPercent(int $completedParts): int
    {
        return (int) round($completedParts / self::REVIEW_PARTS * 100);
    }

    /**
     * Public URL of the passage audio stored on the public disk, or null when absent.
     *
     * @return Attribute<string|null, never>
     */
    protected function passageAudioUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->passage_audio ? Storage::disk('public')->url($this->passage_audio) : null);
    }

    /**
     * Scope a query to only include published lessons.
     */
    #[Scope]
    protected function published(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    /**
     * Scope a query to only include free lessons.
     */
    #[Scope]
    protected function free(Builder $query): Builder
    {
        return $query->where('is_free', true);
    }
}
