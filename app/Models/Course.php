<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use Database\Factories\CourseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

#[Fillable(['title', 'slug', 'description', 'thumbnail', 'monthly_price', 'regular_price', 'unit_count', 'lesson_count', 'level', 'age_groups', 'sort_order', 'is_published'])]
class Course extends Model
{
    /** @use HasFactory<CourseFactory> */
    use HasFactory, HasSlug;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'monthly_price' => 'integer',
            'regular_price' => 'integer',
            'unit_count' => 'integer',
            'lesson_count' => 'integer',
            'age_groups' => 'array',
            'sort_order' => 'integer',
            'is_published' => 'boolean',
        ];
    }

    /**
     * @return HasMany<Unit, $this>
     */
    public function units(): HasMany
    {
        return $this->hasMany(Unit::class)->orderBy('sort_order');
    }

    /**
     * @return HasManyThrough<Lesson, Unit, $this>
     */
    public function lessons(): HasManyThrough
    {
        return $this->hasManyThrough(Lesson::class, Unit::class);
    }

    /**
     * @return HasMany<Exam, $this>
     */
    public function exams(): HasMany
    {
        return $this->hasMany(Exam::class);
    }

    /**
     * The learners with the highest total score over the lessons of the course, best first (ties go to whoever has the
     * lowest user id). A learner's `progress` is the average review percent of all the lessons of the course, so a lesson
     * they have not started counts as 0.
     *
     * @return Collection<int, array{name: string, points: int, progress: int}>
     */
    public function topLearners(int $limit = 10): Collection
    {
        $lessonIds = $this->lessons()->where('lessons.is_published', true)->pluck('lessons.id');

        if ($lessonIds->isEmpty()) {
            return collect();
        }

        return LessonScore::query()
            ->whereIn('lesson_id', $lessonIds)
            ->groupBy('user_id')
            ->selectRaw('user_id, sum(score) as points, sum(percent) as percent_sum')
            ->orderByDesc('points')
            ->orderBy('user_id')
            ->limit($limit)
            ->with('user')
            ->get()
            ->map(fn (LessonScore $row): array => [
                'name' => $row->user->name,
                'points' => (int) $row->points,
                'progress' => (int) round($row->percent_sum / $lessonIds->count()),
            ]);
    }

    /**
     * The public avatars of some learners who have scored in the lessons of the course, in a random order, each with the last
     * word of the name of the learner. A learner with no avatar is left out.
     *
     * @return list<array{url: string, name: string}>
     */
    public function learnerAvatars(int $limit = 12): array
    {
        return User::query()
            ->whereNotNull('avatar')
            ->whereIn('id', LessonScore::query()->whereIn('lesson_id', $this->lessons()->select('lessons.id'))->select('user_id'))
            ->inRandomOrder()
            ->limit($limit)
            ->get()
            ->map(fn (User $user): array => ['url' => $user->avatar_url, 'name' => $user->lastName()])
            ->values()
            ->all();
    }

    /**
     * @return MorphMany<Subscription, $this>
     */
    public function subscriptions(): MorphMany
    {
        return $this->morphMany(Subscription::class, 'subscribable');
    }

    /**
     * Whether the course costs nothing: a monthly price of 0.
     */
    public function isFree(): bool
    {
        return (int) $this->monthly_price === 0;
    }

    /**
     * Whether the visitor should be offered to enroll: the course is paid and they have no access to all of it yet.
     */
    public function needsEnrollmentBy(?User $user): bool
    {
        return ! $this->isFree() && ! ($user?->hasPaidAccessTo($this) ?? false);
    }

    /**
     * The price as shown on a course card: "Miễn phí" or e.g. "50.000₫".
     */
    public function priceLabel(): string
    {
        return $this->isFree() ? 'Miễn phí' : number_format($this->monthly_price, 0, ',', '.').'₫';
    }

    /**
     * The months a course can be bought for, from the shortest, with the percentage off each (see config payment.cycles).
     *
     * @return array<int, int>
     */
    public static function cycles(): array
    {
        return config('payment.cycles');
    }

    /**
     * The price of the given number of months, with the discount of that cycle taken off, in VND.
     */
    public function priceForMonths(int $months): int
    {
        $discount = self::cycles()[$months] ?? 0;

        return (int) round($this->monthly_price * $months * (100 - $discount) / 100);
    }

    /**
     * The price of one month before any sale: the regular price when the course is on sale, else the monthly price, in VND.
     */
    public function basePrice(): int
    {
        return $this->isOnSale() ? $this->regular_price : (int) $this->monthly_price;
    }

    /**
     * The given number of months at the base price, with no sale or cycle discount taken off, in VND.
     */
    public function basePriceForMonths(int $months): int
    {
        return $this->basePrice() * $months;
    }

    /**
     * The percentage the price of the given number of months saves off the same months at the base price (sale and
     * cycle discount together), rounded, 0 when nothing is saved.
     */
    public function discountPercentForMonths(int $months): int
    {
        $basePrice = $this->basePriceForMonths($months);

        if ($basePrice <= 0) {
            return 0;
        }

        return max(0, (int) round(($basePrice - $this->priceForMonths($months)) * 100 / $basePrice));
    }

    /**
     * The price of the given number of months formatted as on a course card, e.g. "142.500₫".
     */
    public function priceLabelForMonths(int $months): string
    {
        return number_format($this->priceForMonths($months), 0, ',', '.').'₫';
    }

    /**
     * Whether the course is on sale: it is paid and its regular price is above its monthly price.
     */
    public function isOnSale(): bool
    {
        return ! $this->isFree() && $this->regular_price !== null && $this->regular_price > $this->monthly_price;
    }

    /**
     * The discount shown on the price, in percent off the regular price (at least 1), or null when the course is not on sale.
     */
    public function displayDiscountPercent(): ?int
    {
        if (! $this->isOnSale()) {
            return null;
        }

        return max(1, (int) round(($this->regular_price - $this->monthly_price) * 100 / $this->regular_price));
    }

    /**
     * The struck-through regular price, e.g. "100.000₫", or null when the course is not on sale.
     */
    public function originalPriceLabel(): ?string
    {
        return $this->isOnSale() ? number_format($this->regular_price, 0, ',', '.').'₫' : null;
    }

    /**
     * Public URL of the course thumbnail stored on the public disk, or null when absent.
     *
     * @return Attribute<string|null, never>
     */
    protected function thumbnailUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->thumbnail ? Storage::disk('public')->url($this->thumbnail) : null);
    }

    /**
     * Scope a query to only include published courses.
     */
    #[Scope]
    protected function published(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }
}
