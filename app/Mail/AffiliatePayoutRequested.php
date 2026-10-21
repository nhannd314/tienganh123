<?php

namespace App\Mail;

use App\Models\AffiliatePayout;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Tells the owner that an affiliate asked to withdraw their commissions, with the bank account to pay.
 */
class AffiliatePayoutRequested extends Mailable
{
    public function __construct(public AffiliatePayout $payout) {}

    public function envelope(): Envelope
    {
        $amount = number_format($this->payout->amount, 0, ',', '.').'đ';

        return new Envelope(subject: "Yêu cầu rút tiền {$amount} từ {$this->payout->user->name}");
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.affiliate-payout-requested', with: [
            'payout' => $this->payout,
            'affiliate' => $this->payout->user,
        ]);
    }
}
