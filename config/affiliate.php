<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Commission rate
    |--------------------------------------------------------------------------
    |
    | The percentage of every payment of a referred user that goes to the affiliate who brought them in. An affiliate
    | with their own rate on the user record is paid that rate instead.
    |
    */

    'commission_rate' => 50,

    /*
    |--------------------------------------------------------------------------
    | Approval delay
    |--------------------------------------------------------------------------
    |
    | Days a commission stays pending after the payment, which is the time left to refund it, before it is approved
    | and can be paid out.
    |
    */

    'approval_days' => 14,

    /*
    |--------------------------------------------------------------------------
    | Minimum payout
    |--------------------------------------------------------------------------
    |
    | The least (in VND) an affiliate must have in approved commissions before they can ask for a payout.
    |
    */

    'minimum_payout' => 500000,

    /*
    |--------------------------------------------------------------------------
    | Referral cookie
    |--------------------------------------------------------------------------
    |
    | A visitor who comes in through a referral link (?ref=CODE) is remembered for this many days, so the affiliate is
    | credited when they register later.
    |
    */

    'cookie_days' => 30,

];
