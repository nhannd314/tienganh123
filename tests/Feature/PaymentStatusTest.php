<?php

use App\Enums\PaymentStatus;
use App\Events\PaymentPaid;
use App\Models\Course;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

function coursePayment(?User $user = null): Payment
{
    $course = Course::factory()->published()->priced(50000)->create();

    return Payment::factory()->create([
        'user_id' => ($user ?? User::factory()->create())->id,
        'payable_type' => $course->getMorphClass(),
        'payable_id' => $course->id,
        'amount' => 50000,
        'provider' => 'bank_transfer',
        'provider_reference' => 'FEABCD1234',
    ]);
}

it('says a pending payment has not arrived yet', function () {
    $payment = coursePayment();

    $this->actingAs($payment->user)->getJson(route('payments.status', $payment))
        ->assertOk()
        ->assertExactJson(['paid' => false, 'redirect' => null]);
});

it('sends the learner to the course once the payment is paid', function () {
    $payment = coursePayment();
    $payment->markAsPaid();

    $this->actingAs($payment->user)->getJson(route('payments.status', $payment))
        ->assertOk()
        ->assertExactJson(['paid' => true, 'redirect' => route('courses.show', $payment->payable)]);
});

it('hides the payment of another user', function () {
    $payment = coursePayment();
    $payment->markAsPaid();

    $this->actingAs(User::factory()->create())->getJson(route('payments.status', $payment))->assertNotFound();
});

it('needs a signed-in user', function () {
    $this->getJson(route('payments.status', coursePayment()))->assertUnauthorized();
});

it('puts the status URL and channel on the payment page', function () {
    $user = User::factory()->create();
    $course = Course::factory()->published()->priced(50000)->create();
    config(['payment.bank.account_number' => '0123456789']);

    $response = $this->actingAs($user)->get(route('courses.enroll', [$course, 'months' => 1]))->assertOk();

    $payment = Payment::sole();

    $response->assertSee('data-payment-status="'.route('payments.status', $payment).'"', false)
        ->assertSee('data-payment-channel="payments.'.$payment->id.'"', false);
});

it('announces the payment once, on its own private channel, with where to go', function () {
    Event::fake([PaymentPaid::class]);
    $payment = coursePayment();

    $payment->markAsPaid();
    $payment->markAsPaid();

    Event::assertDispatchedTimes(PaymentPaid::class, 1);
    Event::assertDispatched(PaymentPaid::class, function (PaymentPaid $event) use ($payment): bool {
        return $event->broadcastOn()[0]->name === "private-payments.{$payment->id}"
            && $event->broadcastAs() === 'paid'
            && $event->broadcastWith() === ['redirect' => route('courses.show', $payment->payable)];
    });
});

it('keeps the payment paid when the broadcast fails', function () {
    Broadcast::shouldReceive('queue')->andThrow(new RuntimeException('Reverb is down'));
    $payment = coursePayment();

    $subscription = $payment->markAsPaid();

    expect($payment->status)->toBe(PaymentStatus::Paid)->and($subscription)->not->toBeNull();
});

it('lets only the owner listen on the payment channel', function () {
    config([
        'broadcasting.default' => 'reverb',
        'broadcasting.connections.reverb.key' => 'key',
        'broadcasting.connections.reverb.secret' => 'secret',
        'broadcasting.connections.reverb.app_id' => '1',
    ]);
    require base_path('routes/channels.php');
    $payment = coursePayment();
    $auth = ['channel_name' => "private-payments.{$payment->id}", 'socket_id' => '1234.5678'];

    $this->actingAs($payment->user)->post('/broadcasting/auth', $auth)->assertOk();
    $this->actingAs(User::factory()->create())->post('/broadcasting/auth', $auth)->assertForbidden();
});
