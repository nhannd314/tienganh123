<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'phone', 'avatar', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'token_balance' => 'integer',
            'role' => UserRole::class,
        ];
    }

    /**
     * @return HasMany<Subscription, $this>
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * The token ledger, newest first.
     *
     * @return HasMany<TokenTransaction, $this>
     */
    public function tokenTransactions(): HasMany
    {
        return $this->hasMany(TokenTransaction::class)->latest('id');
    }

    /**
     * Whether the user has a running membership, which opens every course.
     */
    public function hasActiveMembership(): bool
    {
        return $this->subscriptions()
            ->active()
            ->where('subscribable_type', (new MembershipPlan)->getMorphClass())
            ->exists();
    }

    /**
     * Whether the user has a running subscription to the course.
     */
    public function isSubscribedTo(Course $course): bool
    {
        return $this->subscriptions()
            ->active()
            ->where('subscribable_type', $course->getMorphClass())
            ->where('subscribable_id', $course->getKey())
            ->exists();
    }

    /**
     * Whether every lesson of the course is open to the user, free or not: an admin, a member or a subscriber.
     */
    public function hasPaidAccessTo(Course $course): bool
    {
        return $this->isAdmin() || $this->hasActiveMembership() || $this->isSubscribedTo($course);
    }

    /**
     * Only admins can sign in to the Filament admin panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->isAdmin();
    }

    /**
     * Determine whether the user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }
}
