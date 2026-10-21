<?php

namespace App\Models;

use App\Enums\AffiliateCommissionStatus;
use App\Enums\AffiliatePayoutStatus;
use Database\Factories\AffiliatePayoutFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * A transfer of approved commissions to an affiliate, with the bank details as they were when it was made.
 */
#[Fillable(['user_id', 'amount', 'status', 'bank_name', 'bank_account_number', 'bank_account_name', 'transfer_reference', 'note', 'paid_at'])]
class AffiliatePayout extends Model
{
    /** @use HasFactory<AffiliatePayoutFactory> */
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
            'status' => AffiliatePayoutStatus::class,
            'paid_at' => 'datetime',
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
     * @return HasMany<AffiliateCommission, $this>
     */
    public function commissions(): HasMany
    {
        return $this->hasMany(AffiliateCommission::class);
    }

    /**
     * Open a payout for the affiliate's approved commissions that are not in a payout yet, or null when there are none or they are below the minimum payout.
     */
    public static function openFor(User $affiliate): ?static
    {
        return DB::transaction(function () use ($affiliate): ?static {
            $commissions = AffiliateCommission::query()
                ->where('affiliate_id', $affiliate->id)
                ->payable()
                ->lockForUpdate()
                ->get();

            if ($commissions->isEmpty() || $commissions->sum('amount') < (int) config('affiliate.minimum_payout')) {
                return null;
            }

            $payout = static::create([
                'user_id' => $affiliate->id,
                'amount' => $commissions->sum('amount'),
                'status' => AffiliatePayoutStatus::Pending,
                'bank_name' => $affiliate->affiliate_bank_name,
                'bank_account_number' => $affiliate->affiliate_bank_account_number,
                'bank_account_name' => $affiliate->affiliate_bank_account_name,
            ]);

            AffiliateCommission::whereIn('id', $commissions->modelKeys())->update(['affiliate_payout_id' => $payout->id]);

            return $payout;
        });
    }

    /**
     * Record that the money was sent: its commissions become paid.
     */
    public function markAsPaid(?string $transferReference = null): void
    {
        if ($this->status !== AffiliatePayoutStatus::Pending) {
            return;
        }

        DB::transaction(function () use ($transferReference): void {
            $this->update([
                'status' => AffiliatePayoutStatus::Paid,
                'paid_at' => now(),
                'transfer_reference' => $transferReference ?? $this->transfer_reference,
            ]);

            $this->commissions()->update(['status' => AffiliateCommissionStatus::Paid]);
        });
    }

    /**
     * Drop the payout: its commissions go back to the approved ones that can be paid out again.
     */
    public function cancel(): void
    {
        if ($this->status !== AffiliatePayoutStatus::Pending) {
            return;
        }

        DB::transaction(function (): void {
            $this->commissions()->update(['affiliate_payout_id' => null]);
            $this->update(['status' => AffiliatePayoutStatus::Cancelled]);
        });
    }
}
