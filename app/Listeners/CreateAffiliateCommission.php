<?php

namespace App\Listeners;

use App\Events\PaymentSettled;
use App\Models\AffiliateCommission;

/**
 * Credits the affiliate who referred the payer with a commission on the payment.
 */
class CreateAffiliateCommission
{
    public function handle(PaymentSettled $event): void
    {
        AffiliateCommission::recordFor($event->payment);
    }
}
