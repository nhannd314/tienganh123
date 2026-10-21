<?php

namespace App\Events;

use App\Models\Payment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A payment went through and was committed. Unlike PaymentPaid it is not broadcast, so what reacts to it (the affiliate
 * commission) still runs when Reverb is down.
 */
class PaymentSettled
{
    use Dispatchable, SerializesModels;

    public function __construct(public Payment $payment) {}
}
