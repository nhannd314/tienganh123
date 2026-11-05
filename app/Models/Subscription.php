<?php

namespace App\Models;

use App\Enums\SubscriptionStatus;
use Database\Factories\SubscriptionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * A period during which a user can learn a course.
 *
 * A null `ends_at` never expires, which is what a free course gets.
 */
#[Fillable(['user_id', 'subscribable_type', 'subscribable_id', 'starts_at', 'ends_at', 'cancelled_at', 'price_paid'])]
class Subscription extends Model
{
    /** @use HasFactory<SubscriptionFactory> */
    use HasFactory;

    /**
     * A running subscription with this many days left or fewer is about to expire.
     */
    public const EXPIRING_SOON_DAYS = 7;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'price_paid' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The Course it gives access to.
     *
     * @return MorphTo<Model, $this>
     */
    public function subscribable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Whether it gives access right now.
     */
    public function isActive(): bool
    {
        return $this->cancelled_at === null
            && $this->starts_at->isPast()
            && ($this->ends_at === null || $this->ends_at->isFuture());
    }

    /**
     * Where the subscription stands right now.
     */
    public function currentStatus(): SubscriptionStatus
    {
        return match (true) {
            $this->cancelled_at !== null => SubscriptionStatus::Cancelled,
            $this->starts_at->isFuture() => SubscriptionStatus::Upcoming,
            $this->ends_at === null => SubscriptionStatus::Active,
            ! $this->ends_at->isFuture() => SubscriptionStatus::Expired,
            $this->ends_at->lte(now()->addDays(self::EXPIRING_SOON_DAYS)) => SubscriptionStatus::ExpiringSoon,
            default => SubscriptionStatus::Active,
        };
    }

    /**
     * The days until it ends, a started day counting as a whole one: 0 once it has ended, null when it never does.
     */
    public function daysRemaining(): ?int
    {
        if ($this->ends_at === null) {
            return null;
        }

        return (int) max(0, ceil(now()->diffInDays($this->ends_at, false)));
    }

    /**
     * How many months it was bought for, null when it never ends.
     */
    public function durationInMonths(): ?int
    {
        return $this->ends_at === null ? null : (int) round($this->starts_at->diffInMonths($this->ends_at));
    }

    /**
     * Scope a query to only include subscriptions that give access right now.
     */
    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query
            ->whereNull('cancelled_at')
            ->where('starts_at', '<=', now())
            ->where(fn (Builder $query): Builder => $query->whereNull('ends_at')->orWhere('ends_at', '>', now()));
    }

    /**
     * Scope a query to only include subscriptions in the status, as currentStatus tells it.
     */
    #[Scope]
    protected function withStatus(Builder $query, SubscriptionStatus $status): Builder
    {
        $expiringFrom = now()->addDays(self::EXPIRING_SOON_DAYS);

        return match ($status) {
            SubscriptionStatus::Cancelled => $query->whereNotNull('cancelled_at'),
            SubscriptionStatus::Upcoming => $query->whereNull('cancelled_at')->where('starts_at', '>', now()),
            SubscriptionStatus::Expired => $query->whereNull('cancelled_at')->where('starts_at', '<=', now())->where('ends_at', '<=', now()),
            SubscriptionStatus::ExpiringSoon => $query->active()->where('ends_at', '<=', $expiringFrom),
            SubscriptionStatus::Active => $query->active()->where(fn (Builder $query): Builder => $query->whereNull('ends_at')->orWhere('ends_at', '>', $expiringFrom)),
        };
    }
}
