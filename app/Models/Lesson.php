<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use App\Services\LessonScoreCalculator;
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
    'example_sentences',
    'content',
    'attachments',
    'video',
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

    /**
     * The review parts in which words are read aloud and scored out of 10.
     *
     * @var list<int>
     */
    public const PRONUNCIATION_STEPS = [5, 6];

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
            'example_sentences' => 'array',
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
     * The example sentence as it is read aloud: without the backticks that mark its words, e.g. "I have an `apple`.".
     */
    public static function spokenSentence(string $sentence): string
    {
        return str_replace('`', '', $sentence);
    }

    /**
     * The example sentence cut into plain text and the words marked with backticks, in order.
     *
     * @return list<array{text: string, isMarked: bool}>
     */
    public static function sentenceSegments(string $sentence): array
    {
        $pieces = preg_split('/(`[^`]+`)/u', $sentence, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

        return array_map(
            fn (string $piece): array => preg_match('/^`([^`]+)`$/u', $piece, $match)
                ? ['text' => $match[1], 'isMarked' => true]
                : ['text' => str_replace('`', '', $piece), 'isMarked' => false],
            $pieces,
        );
    }

    /**
     * The text of the example sentences that have no audio yet, without repeats.
     *
     * @param  array<int, mixed>|null  $exampleSentences
     * @return list<string>
     */
    public static function sentencesWithoutAudio(?array $exampleSentences): array
    {
        return collect($exampleSentences ?? [])
            ->filter(fn (mixed $example): bool => is_array($example) && filled($example['sentence'] ?? null) && blank($example['audio'] ?? null))
            ->map(fn (array $example): string => $example['sentence'])
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Progress of the user over the whole review: completed parts out of all parts, answered questions out of the
     * questions of every part, and the time spent. The answers count once per part, with its latest attempt; a part
     * is completed once an attempt at it passed (see LessonReview::isPassed), even if a later one did not.
     *
     * `pronunciation` is the average of the average scores out of 10 of parts 5 and 6 (their latest attempts), 0 when neither was played.
     *
     * `pronunciationWords` is the number of words read in those parts.
     *
     * @return array{percent: int, completedParts: int, parts: int, done: int, correct: int, wrong: int, total: int, seconds: int, pronunciation: float, pronunciationWords: int}
     */
    public function reviewProgress(User $user): array
    {
        $attempts = $this->reviews()->where('user_id', $user->id)->orderByDesc('id')->get();
        $latest = $attempts->unique('step');
        $completedParts = $attempts->filter(fn (LessonReview $review): bool => $review->isPassed())->unique('step')->count();
        $done = (int) $latest->sum('total');
        $correct = (int) $latest->sum('correct');

        $pronunciationScores = $latest
            ->whereIn('step', self::PRONUNCIATION_STEPS)
            ->map(fn (LessonReview $review): float => collect(array_column($review->answers ?? [], 'selected'))->map(fn ($score): float => (float) $score)->avg() ?? 0.0);

        return [
            'percent' => self::reviewPercent($completedParts),
            'completedParts' => $completedParts,
            'parts' => self::REVIEW_PARTS,
            'done' => $done,
            'correct' => $correct,
            'wrong' => $done - $correct,
            'total' => $this->reviewWords()->count() * self::REVIEW_PARTS,
            'seconds' => (int) $latest->sum('duration_seconds'),
            'pronunciation' => $pronunciationScores->isEmpty() ? 0.0 : round($pronunciationScores->avg(), 1),
            'pronunciationWords' => (int) $latest->whereIn('step', self::PRONUNCIATION_STEPS)->sum('total'),
        ];
    }

    /**
     * @return HasMany<LessonScore, $this>
     */
    public function scores(): HasMany
    {
        return $this->hasMany(LessonScore::class);
    }

    /**
     * Work out the score of the user in the lesson (see LessonScoreCalculator) and keep it when it beats their best.
     */
    public function recordScore(User $user): LessonScore
    {
        $progress = $this->reviewProgress($user);
        $score = (new LessonScoreCalculator)->calculate($progress)['score'];
        $best = $this->scores()->firstWhere('user_id', $user->id);

        if ($best === null) {
            return $this->scores()->create(['user_id' => $user->id, 'score' => $score, 'percent' => $progress['percent']]);
        }

        if ($score > $best->score) {
            $best->update(['score' => $score, 'percent' => $progress['percent']]);
        }

        return $best;
    }

    /**
     * The best score of the user in the lesson, their rank among the learners who scored, and how many scored.
     *
     * @return array{score: int, rank: int|null, learners: int}
     */
    public function standingOf(User $user): array
    {
        $mine = $this->scores()->firstWhere('user_id', $user->id);

        return [
            'score' => $mine?->score ?? 0,
            'rank' => $mine ? $this->scores()->where('score', '>', $mine->score)->count() + 1 : null,
            'learners' => $this->scores()->count(),
        ];
    }

    /**
     * The learners with the best scores in the lesson, best first (ties go to whoever scored first).
     *
     * @return Collection<int, LessonScore>
     */
    public function topScores(int $limit = 10): Collection
    {
        return $this->scores()->with('user')->orderByDesc('score')->orderBy('id')->limit($limit)->get();
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
     * The privacy-friendly embed URL of a YouTube link (watch, share, shorts, live or embed form), or null when it is not one.
     */
    public static function youtubeEmbedUrl(?string $url): ?string
    {
        $pattern = '~^https?://(?:www\.|m\.)?(?:youtube(?:-nocookie)?\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/)|youtu\.be/)([A-Za-z0-9_-]{11})(?:[?&#/].*)?$~i';

        return preg_match($pattern, trim($url ?? ''), $match) ? 'https://www.youtube-nocookie.com/embed/'.$match[1] : null;
    }

    /**
     * Embed URL of the lesson video, or null when it has none.
     *
     * @return Attribute<string|null, never>
     */
    protected function videoEmbedUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => self::youtubeEmbedUrl($this->video));
    }

    /**
     * Whether the video is an uploaded file (a path on the public disk) rather than a link.
     */
    public function hasUploadedVideo(): bool
    {
        return filled($this->video) && ! preg_match('~^https?://~i', $this->video);
    }

    /**
     * Public URL of the uploaded lesson video stored on the public disk, or null when it is a link or absent.
     *
     * @return Attribute<string|null, never>
     */
    protected function videoFileUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->hasUploadedVideo() ? Storage::disk('public')->url($this->video) : null);
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
