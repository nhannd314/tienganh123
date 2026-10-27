<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\Membership;
use App\Enums\UserRole;
use App\Notifications\ResetPasswordNotification;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Fillable(['name', 'email', 'phone', 'avatar', 'birthday', 'grade', 'school', 'parent_email', 'parent_phone', 'password', 'membership', 'membership_expires_on', 'affiliate_commission_rate', 'affiliate_bank_name', 'affiliate_bank_account_number', 'affiliate_bank_account_name'])]
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
            'referred_at' => 'datetime',
            'affiliate_commission_rate' => 'integer',
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
     * The affiliate who brought the user in.
     *
     * @return BelongsTo<User, $this>
     */
    public function referrer(): BelongsTo
    {
        return $this->belongsTo(self::class, 'referred_by_id');
    }

    /**
     * @return HasMany<User, $this>
     */
    public function referrals(): HasMany
    {
        return $this->hasMany(self::class, 'referred_by_id');
    }

    /**
     * @return HasMany<AffiliateCommission, $this>
     */
    public function affiliateCommissions(): HasMany
    {
        return $this->hasMany(AffiliateCommission::class, 'affiliate_id');
    }

    /**
     * @return HasMany<AffiliatePayout, $this>
     */
    public function affiliatePayouts(): HasMany
    {
        return $this->hasMany(AffiliatePayout::class);
    }

    /**
     * The percent of each payment of a referred user that the affiliate earns: their own rate, else the default.
     */
    public function affiliateCommissionRate(): int
    {
        return $this->affiliate_commission_rate ?? (int) config('affiliate.commission_rate');
    }

    /**
     * What the affiliate has in approved commissions that no payout has taken yet.
     */
    public function affiliatePayableBalance(): int
    {
        return (int) $this->affiliateCommissions()->payable()->sum('amount');
    }

    /**
     * The code in the user's referral link, made the first time it is needed.
     */
    public function referralCode(): string
    {
        if ($this->referral_code === null) {
            do {
                $code = Str::upper(Str::random(8));
            } while (static::where('referral_code', $code)->exists());

            $this->forceFill(['referral_code' => $code])->save();
        }

        return $this->referral_code;
    }

    /**
     * The link the user shares to bring people in.
     */
    public function referralUrl(): string
    {
        return route('home', ['ref' => $this->referralCode()]);
    }

    /**
     * Credit the affiliate behind the referral code, once: a user already referred, or the affiliate themselves, is left alone.
     */
    public function attachReferrer(?string $code): void
    {
        if ($this->referred_by_id !== null || blank($code)) {
            return;
        }

        $referrer = static::where('referral_code', Str::upper($code))->whereKeyNot($this->getKey())->first();

        if ($referrer !== null) {
            $this->forceFill(['referred_by_id' => $referrer->id, 'referred_at' => now()])->save();
        }
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
     * Mail the link to choose a new password, in Vietnamese.
     */
    public function sendPasswordResetNotification(#[\SensitiveParameter] $token): void
    {
        $this->notify(new ResetPasswordNotification($token));
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
     * The member id of the user in a presence channel: one per tab (its socket) in the `learning` channel, so closing a
     * tab does not take the user out while another is open, and each tab tells what it is about (see routes/channels.php).
     */
    public function getAuthIdentifierForBroadcasting(): mixed
    {
        $socketId = request('socket_id');

        if (request('channel_name') === 'presence-learning' && is_string($socketId) && $socketId !== '') {
            return $this->getAuthIdentifier().'.'.$socketId;
        }

        return $this->getAuthIdentifier();
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
