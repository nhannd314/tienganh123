<?php

namespace App\Mail;

use App\Enums\BankTransactionStatus;
use App\Models\BankTransaction;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Tells the owner about money received by bank transfer and what became of it: a payment activated, or a transfer
 * whose code or amount did not match and needs a look.
 */
class BankTransactionNotice extends Mailable
{
    public function __construct(public BankTransaction $transaction) {}

    public function envelope(): Envelope
    {
        $amount = number_format($this->transaction->amount, 0, ',', '.').'đ';

        return new Envelope(subject: match ($this->transaction->status) {
            BankTransactionStatus::Matched => "Thanh toán thành công {$amount}",
            BankTransactionStatus::Underpaid => "Thanh toán thiếu tiền {$amount} - cần xử lý",
            default => "Chuyển khoản không khớp mã {$amount} - cần xử lý",
        });
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.bank-transaction-notice', with: [
            'transaction' => $this->transaction,
            'payment' => $this->transaction->payment?->load(['user', 'payable']),
            'status' => $this->transaction->status,
        ]);
    }
}
