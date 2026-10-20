<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\Membership;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Fillable(['name', 'email', 'phone', 'avatar', 'birthday', 'grade', 'school', 'parent_email', 'parent_phone', 'password', 'membership', 'membership_expires_on'])]
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
            'birthday' => 'date',
            'role' => UserRole::class,
            'membership' => Membership::class,
            'membership_expires_on' => 'date',
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
        return $this->isAdmin() || $this->isMember() || $this->isSubscribedTo($course);
    }

    /**
     * The membership of the user while it runs (through its expiry date, or for ever without one), else null.
     */
    public function activeMembership(): ?Membership
    {
        if ($this->membership === null) {
            return null;
        }

        if ($this->membership_expires_on !== null && $this->membership_expires_on->lt(today())) {
            return null;
        }

        return $this->membership;
    }

    /**
     * Whether the user has a running membership, of any kind.
     */
    public function isMember(): bool
    {
        return $this->activeMembership() !== null;
    }

    /**
     * Whether the members-only features (the vocabulary and sentence set reviews) are open to the user: an admin or a member.
     */
    public function hasMemberAccess(): bool
    {
        return $this->isAdmin() || $this->isMember();
    }

    /**
     * The last word of the name: the given name, for a Vietnamese one.
     */
    public function lastName(): string
    {
        return Str::afterLast(trim($this->name), ' ');
    }

    /**
     * The first letter of the last word of the name, shown in the avatar circle.
     */
    public function initial(): string
    {
        return Str::upper(Str::substr($this->lastName(), 0, 1));
    }

    /**
     * Public URL of the avatar stored on the public disk, or null when the learner has none.
     *
     * @return Attribute<string|null, never>
     */
    protected function avatarUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->avatar ? Storage::disk('public')->url($this->avatar) : null);
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
