<?php

namespace App\Models;

use App\Enums\BankTransactionStatus;
use Database\Factories\BankTransactionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A bank transaction reported by SePay, and what became of it.
 */
#[Fillable(['sepay_id', 'gateway', 'account_number', 'transaction_date', 'transfer_type', 'amount', 'accumulated', 'code', 'content', 'reference_code', 'description', 'payment_id', 'status', 'payload'])]
class BankTransaction extends Model
{
    /** @use HasFactory<BankTransactionFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sepay_id' => 'integer',
            'transaction_date' => 'datetime',
            'amount' => 'integer',
            'accumulated' => 'integer',
            'status' => BankTransactionStatus::class,
            'payload' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Payment, $this>
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
