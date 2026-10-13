<?php

namespace App\Models;

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
}
