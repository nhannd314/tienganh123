<?php

namespace App\Enums;

enum TokenTransactionType: string
{
    case TopUp = 'top_up';
    case Usage = 'usage';
    case Refund = 'refund';
    case Adjustment = 'adjustment';
}
