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
    | Subscription cycles
    |--------------------------------------------------------------------------
    |
    | The number of months a course can be bought for, each with the percentage taken off the monthly price times the
    | months. The first cycle is the one chosen by default.
    |
    */

    'cycles' => [
        1 => 0,
        3 => 5,
        6 => 10,
    ],

];
