<?php

namespace App\Events;

use App\Models\Payment;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A payment went through. Pushed at once over Reverb to the payment page, which then takes the learner to what they
 * bought; sent without the queue so it needs no worker.
 */
class PaymentPaid implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Payment $payment) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel("payments.{$this->payment->id}")];
    }

    public function broadcastAs(): string
    {
        return 'paid';
    }

    /**
     * @return array{redirect: string}
     */
    public function broadcastWith(): array
    {
        return ['redirect' => $this->payment->successUrl()];
    }
}
