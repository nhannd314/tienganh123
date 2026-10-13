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
use Illuminate\Support\Facades\Storage;

#[Fillable(['title', 'slug', 'description', 'thumbnail', 'monthly_price', 'unit_count', 'lesson_count', 'level', 'sort_order', 'is_published'])]
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
            'unit_count' => 'integer',
            'lesson_count' => 'integer',
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
     * The discount shown on the price, in percent, or null when none is shown (a free course, or the sale is off).
     */
    public function displayDiscountPercent(): ?int
    {
        $percent = (int) config('payment.display_discount_percent');

        return $this->isFree() || $percent <= 0 || $percent >= 100 ? null : $percent;
    }

    /**
     * The struck-through price the discount is taken from, rounded to the thousand, e.g. "100.000₫" for 50.000₫ at 50%.
     */
    public function originalPriceLabel(): ?string
    {
        $percent = $this->displayDiscountPercent();

        if ($percent === null) {
            return null;
        }

        return number_format(round($this->monthly_price * 100 / (100 - $percent), -3), 0, ',', '.').'₫';
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
