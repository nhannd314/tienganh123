<?php

namespace App\Console\Commands;

use App\Enums\AffiliateCommissionStatus;
use App\Enums\PaymentStatus;
use App\Models\AffiliateCommission;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

#[Signature('affiliate:approve-commissions')]
#[Description('Approve the pending commissions past the approval delay, and reject those of refunded payments')]
class ApproveAffiliateCommissions extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $rejected = AffiliateCommission::query()
            ->where('status', AffiliateCommissionStatus::Pending)
            ->whereHas('payment', fn (Builder $query): Builder => $query->whereIn('status', [PaymentStatus::Refunded, PaymentStatus::Failed]))
            ->update(['status' => AffiliateCommissionStatus::Rejected]);

        $approved = AffiliateCommission::query()
            ->where('status', AffiliateCommissionStatus::Pending)
            ->where('created_at', '<=', now()->subDays((int) config('affiliate.approval_days')))
            ->update(['status' => AffiliateCommissionStatus::Approved, 'approved_at' => now()]);

        $this->info("Approved {$approved}, rejected {$rejected} commission(s).");

        return self::SUCCESS;
    }
}
