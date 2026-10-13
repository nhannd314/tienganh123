<?php

namespace App\Enums;

enum BankTransactionStatus: string
{
    /** Paid a pending payment, which is now activated. */
    case Matched = 'matched';

    /** Money in, but no pending payment carries its transfer code. */
    case Unmatched = 'unmatched';

    /** It carries the code of a payment but is less than the price, so nothing was activated. */
    case Underpaid = 'underpaid';

    /** Money out, or money into another account: not ours to handle. */
    case Ignored = 'ignored';
}
