<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Bank account for transfers
    |--------------------------------------------------------------------------
    |
    | Where learners send the money for a course. The QR code is built from the bank BIN (the 6-digit VietQR bank id,
    | e.g. 970436 for Vietcombank) and the account number.
    |
    */

    'bank' => [
        'name' => env('PAYMENT_BANK_NAME'),
        'bin' => env('PAYMENT_BANK_BIN'),
        'account_number' => env('PAYMENT_BANK_ACCOUNT_NUMBER'),
        'account_name' => env('PAYMENT_BANK_ACCOUNT_NAME'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Transfer content prefix
    |--------------------------------------------------------------------------
    |
    | Every transfer carries a code made of this prefix and random letters and digits, which tells the payments apart.
    |
    */

    'transfer_prefix' => env('PAYMENT_TRANSFER_PREFIX', 'FE'),

    /*
    |--------------------------------------------------------------------------
    | Displayed discount
    |--------------------------------------------------------------------------
    |
    | The sale shown next to the price of a paid course: the price is shown as this percentage off a struck-through
    | original price worked out from it. 0 shows the price alone. What the learner pays never changes.
    |
    */

    'display_discount_percent' => (int) env('PAYMENT_DISPLAY_DISCOUNT_PERCENT', 50),

];
