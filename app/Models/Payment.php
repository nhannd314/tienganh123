<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use App\Events\PaymentPaid;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Money a user paid through a payment provider, for a subscription or a token top-up (`payable`).
 */
#[Fillable(['user_id', 'payable_type', 'payable_id', 'amount', 'currency', 'provider', 'provider_reference', 'status', 'paid_at', 'meta'])]
class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'status' => PaymentStatus::class,
            'paid_at' => 'datetime',
            'meta' => 'array',
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
     * @return MorphTo<Model, $this>
     */
    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Record that the payment went through and give the user the month it bought.
     *
     * The month starts now, or when the current subscription to the same course or plan ends, so renewing early loses
     * nothing. Returns the new subscription, or null when the payment was already paid or bought nothing subscribable.
     *
     * Once committed, PaymentPaid tells the payment page; a broadcast that fails (Reverb down) is only reported, as the
     * payment itself went through.
     */
    public function markAsPaid(array $meta = []): ?Subscription
    {
        return DB::transaction(function () use ($meta): ?Subscription {
            $payment = static::query()->lockForUpdate()->findOrFail($this->id);

            if ($payment->status === PaymentStatus::Paid) {
                return null;
            }

            $payment->forceFill([
                'status' => PaymentStatus::Paid,
                'paid_at' => now(),
                'meta' => [...($payment->meta ?? []), ...$meta],
            ])->save();

            DB::afterCommit(fn () => rescue(fn () => PaymentPaid::dispatch($payment)));

            $subscribable = $payment->payable;

            if (! $subscribable instanceof Course && ! $subscribable instanceof MembershipPlan) {
                $this->refresh();

                return null;
            }

            $currentEnd = $payment->user->subscriptions()
                ->active()
                ->where('subscribable_type', $subscribable->getMorphClass())
                ->where('subscribable_id', $subscribable->getKey())
                ->whereNotNull('ends_at')
                ->max('ends_at');
            $startsAt = $currentEnd ? max(now(), Carbon::parse($currentEnd)) : now();

            $subscription = $payment->user->subscriptions()->create([
                'subscribable_type' => $subscribable->getMorphClass(),
                'subscribable_id' => $subscribable->getKey(),
                'starts_at' => $startsAt,
                'ends_at' => $startsAt->copy()->addMonth(),
                'price_paid' => $payment->amount,
            ]);

            $payment->update(['meta' => [...$payment->meta, 'subscription_id' => $subscription->id]]);
            $this->refresh();

            return $subscription;
        });
    }

    /**
     * The user's pending bank transfer for the payable at its current price, opened the first time and reused after.
     */
    public static function pendingBankTransfer(User $user, Model $payable, int $amount): static
    {
        return static::firstOrCreate(
            [
                'user_id' => $user->id,
                'payable_type' => $payable->getMorphClass(),
                'payable_id' => $payable->getKey(),
                'provider' => 'bank_transfer',
                'status' => PaymentStatus::Pending,
                'amount' => $amount,
            ],
            ['provider_reference' => static::newTransferReference()],
        );
    }

    /**
     * A transfer code nobody has used: the prefix and eight letters and digits, e.g. FE7K3QX9AB.
     */
    private static function newTransferReference(): string
    {
        do {
            $reference = config('payment.transfer_prefix').Str::upper(Str::random(8));
        } while (static::where('provider', 'bank_transfer')->where('provider_reference', $reference)->exists());

        return $reference;
    }

    /**
     * The VietQR image that fills in the account, amount and transfer content, or null while the bank is not configured.
     *
     * @param  array<string, string|null>  $bank
     */
    public function qrUrl(array $bank): ?string
    {
        if (blank($bank['bin']) || blank($bank['account_number'])) {
            return null;
        }

        return "https://img.vietqr.io/image/{$bank['bin']}-{$bank['account_number']}-compact2.png?".http_build_query(array_filter([
            'amount' => $this->amount,
            'addInfo' => $this->provider_reference,
            'accountName' => $bank['account_name'],
        ]));
    }

    /**
     * Where to take the learner once the payment went through: the course it bought, else the course list.
     */
    public function successUrl(): string
    {
        $payable = $this->payable;

        return $payable instanceof Course ? route('courses.show', $payable) : route('courses.index');
    }

    /**
     * Scope a query to only include payments that went through.
     */
    #[Scope]
    protected function paid(Builder $query): Builder
    {
        return $query->where('status', PaymentStatus::Paid);
    }
}
