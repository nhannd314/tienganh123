<?php

namespace App\Models;

use App\Enums\AffiliateCommissionStatus;
use App\Enums\PaymentStatus;
use Database\Factories\AffiliateCommissionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * What an affiliate earns on one paid payment of a user they referred.
 *
 * The payment amount and the rate (a percent) are kept as they were, so the commission does not change with the config.
 */
#[Fillable(['affiliate_id', 'referred_user_id', 'payment_id', 'affiliate_payout_id', 'payment_amount', 'rate', 'amount', 'status', 'approved_at'])]
class AffiliateCommission extends Model
{
    /** @use HasFactory<AffiliateCommissionFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payment_amount' => 'integer',
            'rate' => 'integer',
            'amount' => 'integer',
            'status' => AffiliateCommissionStatus::class,
            'approved_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function affiliate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'affiliate_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function referredUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_user_id');
    }

    /**
     * @return BelongsTo<Payment, $this>
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * @return BelongsTo<AffiliatePayout, $this>
     */
    public function payout(): BelongsTo
    {
        return $this->belongsTo(AffiliatePayout::class, 'affiliate_payout_id');
    }

    /**
     * Credit the payer's referrer with a commission on the payment, once. Returns null when the payer was not referred,
     * the payment is not paid or there is nothing to earn.
     */
    public static function recordFor(Payment $payment): ?static
    {
        $affiliate = $payment->user?->referrer;

        if ($affiliate === null || $payment->status !== PaymentStatus::Paid) {
            return null;
        }

        $rate = $affiliate->affiliateCommissionRate();
        $amount = intdiv($payment->amount * $rate, 100);

        if ($amount <= 0) {
            return null;
        }

        return static::firstOrCreate(
            ['payment_id' => $payment->id],
            [
                'affiliate_id' => $affiliate->id,
                'referred_user_id' => $payment->user_id,
                'payment_amount' => $payment->amount,
                'rate' => $rate,
                'amount' => $amount,
                'status' => AffiliateCommissionStatus::Pending,
            ],
        );
    }

    /**
     * Scope a query to the approved commissions that are not in a payout yet.
     */
    #[Scope]
    protected function payable(Builder $query): Builder
    {
        return $query->where('status', AffiliateCommissionStatus::Approved)->whereNull('affiliate_payout_id');
    }
}
