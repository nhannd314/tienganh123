<?php

use App\Enums\BankTransactionStatus;
use App\Enums\PaymentStatus;
use App\Events\PaymentPaid;
use App\Mail\BankTransactionNotice;
use App\Models\BankTransaction;
use App\Models\Course;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'services.sepay.webhook_secret' => 'whsec_test',
        'services.sepay.api_key' => null,
        'payment.bank.account_number' => '0123456789',
        'payment.transfer_prefix' => 'FE',
    ]);
});

/**
 * What SePay sends for money received.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function sepayPayload(array $overrides = []): array
{
    return [
        'id' => 92704,
        'gateway' => 'Vietcombank',
        'transactionDate' => '2026-10-12 11:08:33',
        'accountNumber' => '0123456789',
        'code' => 'FEABCD1234',
        'content' => 'NGUYEN VAN A chuyen khoan FEABCD1234',
        'transferType' => 'in',
        'transferAmount' => 50000,
        'accumulated' => 1050000,
        'subAccount' => null,
        'referenceCode' => 'FT24012345678',
        'description' => 'BankAPINotify NGUYEN VAN A chuyen khoan FEABCD1234',
        ...$overrides,
    ];
}

/**
 * Post a webhook the way SePay does, signed with the secret unless other headers are given.
 *
 * @param  array<string, mixed>  $payload
 * @param  array<string, string>|null  $headers
 */
function sepayWebhook(array $payload, ?array $headers = null): TestResponse
{
    $json = json_encode($payload);
    $timestamp = (string) now()->timestamp;
    $headers ??= [
        'X-SePay-Timestamp' => $timestamp,
        'X-SePay-Signature' => 'sha256='.hash_hmac('sha256', $timestamp.'.'.$json, 'whsec_test'),
    ];

    $server = ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'];

    foreach ($headers as $name => $value) {
        $server['HTTP_'.strtoupper(str_replace('-', '_', $name))] = $value;
    }

    return test()->call('POST', route('webhooks.sepay'), [], [], [], $server, $json);
}

function pendingPayment(?Course $course = null, int $amount = 50000, string $reference = 'FEABCD1234', ?User $user = null): Payment
{
    $course ??= Course::factory()->published()->priced($amount)->create();

    return Payment::factory()->create([
        'user_id' => ($user ?? User::factory()->create())->id,
        'payable_type' => $course->getMorphClass(),
        'payable_id' => $course->id,
        'amount' => $amount,
        'provider' => 'bank_transfer',
        'provider_reference' => $reference,
    ]);
}

it('activates the course a transfer paid for and answers what SePay expects', function () {
    Event::fake([PaymentPaid::class]);
    $payment = pendingPayment();

    sepayWebhook(sepayPayload())->assertOk()->assertExactJson(['success' => true]);

    Event::assertDispatched(PaymentPaid::class, fn (PaymentPaid $event): bool => $event->payment->is($payment));

    $subscription = Subscription::sole();
    $course = $payment->payable;

    expect($payment->fresh())
        ->status->toBe(PaymentStatus::Paid)
        ->paid_at->not->toBeNull()
        ->and($payment->fresh()->meta)->toMatchArray(['sepay_id' => 92704, 'subscription_id' => $subscription->id])
        ->and($subscription)
        ->user_id->toBe($payment->user_id)
        ->subscribable_type->toBe($course->getMorphClass())
        ->subscribable_id->toBe($course->id)
        ->price_paid->toBe(50000)
        ->and($subscription->isActive())->toBeTrue()
        ->and($subscription->ends_at->isSameDay(now()->addMonth()))->toBeTrue()
        ->and($payment->user->isSubscribedTo($course))->toBeTrue();

    expect(BankTransaction::sole())
        ->sepay_id->toBe(92704)
        ->status->toBe(BankTransactionStatus::Matched)
        ->payment_id->toBe($payment->id)
        ->amount->toBe(50000)
        ->code->toBe('FEABCD1234')
        ->and(BankTransaction::sole()->transaction_date->equalTo(Carbon::parse('2026-10-12 04:08:33', 'UTC')))->toBeTrue();
});

it('finds the transfer code in the content when SePay found none, whatever its case', function (?string $code, string $content) {
    $payment = pendingPayment();

    sepayWebhook(sepayPayload(['code' => $code, 'content' => $content]))->assertOk();

    expect($payment->fresh()->status)->toBe(PaymentStatus::Paid);
})->with([
    'no code, plain content' => [null, 'Thanh toan FEABCD1234'],
    'no code, lower case' => [null, 'thanh toan feabcd1234 cam on'],
    'lower case code' => ['feabcd1234', 'x'],
]);

it('starts a renewal when the current month ends, so nothing is lost', function () {
    $course = Course::factory()->published()->priced()->create();
    $user = User::factory()->create();
    $current = Subscription::factory()->for($user)->create([
        'subscribable_type' => $course->getMorphClass(),
        'subscribable_id' => $course->id,
        'ends_at' => now()->addDays(10),
    ]);
    pendingPayment($course, user: $user);

    sepayWebhook(sepayPayload())->assertOk();

    $renewal = Subscription::where('id', '!=', $current->id)->sole();

    expect($renewal->starts_at->equalTo($current->ends_at))->toBeTrue()
        ->and($renewal->ends_at->equalTo($current->ends_at->copy()->addMonth()))->toBeTrue();
});

it('accepts more than the price', function () {
    $payment = pendingPayment();

    sepayWebhook(sepayPayload(['transferAmount' => 60000]))->assertOk();

    expect($payment->fresh()->status)->toBe(PaymentStatus::Paid)
        ->and(Subscription::sole()->price_paid)->toBe(50000);
});

it('keeps the course closed and flags the transaction when less than the price arrives', function () {
    $payment = pendingPayment();

    sepayWebhook(sepayPayload(['transferAmount' => 40000]))->assertOk()->assertExactJson(['success' => true]);

    expect($payment->fresh()->status)->toBe(PaymentStatus::Pending)
        ->and(Subscription::count())->toBe(0)
        ->and(BankTransaction::sole())->status->toBe(BankTransactionStatus::Underpaid)->payment_id->toBe($payment->id);
});

it('handles a transaction only once, however often SePay retries it', function () {
    pendingPayment();

    sepayWebhook(sepayPayload())->assertOk();
    sepayWebhook(sepayPayload())->assertOk()->assertExactJson(['success' => true]);
    sepayWebhook(sepayPayload())->assertOk();

    expect(Subscription::count())->toBe(1)->and(BankTransaction::count())->toBe(1);
});

it('does not activate twice when a second transfer repeats the code of a paid payment', function () {
    pendingPayment();

    sepayWebhook(sepayPayload())->assertOk();
    sepayWebhook(sepayPayload(['id' => 92705]))->assertOk();

    expect(Subscription::count())->toBe(1)
        ->and(BankTransaction::count())->toBe(2)
        ->and(BankTransaction::firstWhere('sepay_id', 92705)->status)->toBe(BankTransactionStatus::Unmatched);
});

it('stores a transfer nobody is waiting for and answers success so SePay stops', function (array $overrides) {
    pendingPayment();

    sepayWebhook(sepayPayload($overrides))->assertOk()->assertExactJson(['success' => true]);

    expect(BankTransaction::sole()->status)->toBe(BankTransactionStatus::Unmatched)
        ->and(Subscription::count())->toBe(0);
})->with([
    'no code' => [['code' => null, 'content' => 'chuyen tien sinh hoat']],
    'unknown code' => [['code' => 'FEZZZZ9999', 'content' => 'FEZZZZ9999']],
    'code too short' => [['code' => null, 'content' => 'FE123']],
]);

it('ignores money going out and money into another account', function (array $overrides) {
    $payment = pendingPayment();

    sepayWebhook(sepayPayload($overrides))->assertOk();

    expect($payment->fresh()->status)->toBe(PaymentStatus::Pending)
        ->and(BankTransaction::sole()->status)->toBe(BankTransactionStatus::Ignored);
})->with([
    'money out' => [['transferType' => 'out']],
    'other account' => [['accountNumber' => '999999999']],
]);

it('only pays the payment that carries the code', function () {
    $other = pendingPayment(reference: 'FEOTHER0001');
    $mine = pendingPayment();

    sepayWebhook(sepayPayload())->assertOk();

    expect($mine->fresh()->status)->toBe(PaymentStatus::Paid)->and($other->fresh()->status)->toBe(PaymentStatus::Pending);
});

it('rolls everything back when handling fails, so the retry can start clean', function () {
    pendingPayment();
    Payment::updating(fn () => throw new RuntimeException('boom'));

    $this->withoutExceptionHandling();

    try {
        expect(fn () => sepayWebhook(sepayPayload()))->toThrow(RuntimeException::class)
            ->and(BankTransaction::count())->toBe(0)
            ->and(Subscription::count())->toBe(0);
    } finally {
        Payment::flushEventListeners();
    }
});

it('rejects a payload that is not a SePay transaction', function () {
    sepayWebhook(['hello' => 'world'])->assertUnprocessable();

    expect(BankTransaction::count())->toBe(0);
});

it('needs no CSRF token', function () {
    pendingPayment();

    sepayWebhook(sepayPayload())->assertOk();
});

describe('authentication', function () {
    it('rejects an unsigned or wrongly signed request', function (array $headers) {
        $payment = pendingPayment();

        sepayWebhook(sepayPayload(), $headers)->assertUnauthorized();

        expect($payment->fresh()->status)->toBe(PaymentStatus::Pending)->and(BankTransaction::count())->toBe(0);
    })->with([
        'no headers' => [[]],
        'wrong signature' => [['X-SePay-Timestamp' => '1', 'X-SePay-Signature' => 'sha256=abc']],
    ]);

    it('rejects a signature of another body', function () {
        pendingPayment();
        $timestamp = (string) now()->timestamp;

        sepayWebhook(sepayPayload(), [
            'X-SePay-Timestamp' => $timestamp,
            'X-SePay-Signature' => 'sha256='.hash_hmac('sha256', $timestamp.'.'.json_encode(sepayPayload(['transferAmount' => 1])), 'whsec_test'),
        ])->assertUnauthorized();
    });

    it('rejects a signature of another secret', function () {
        pendingPayment();
        $timestamp = (string) now()->timestamp;

        sepayWebhook(sepayPayload(), [
            'X-SePay-Timestamp' => $timestamp,
            'X-SePay-Signature' => 'sha256='.hash_hmac('sha256', $timestamp.'.'.json_encode(sepayPayload()), 'forged'),
        ])->assertUnauthorized();
    });

    it('rejects a replayed request with an old timestamp', function () {
        pendingPayment();
        $timestamp = (string) now()->subMinutes(10)->timestamp;

        sepayWebhook(sepayPayload(), [
            'X-SePay-Timestamp' => $timestamp,
            'X-SePay-Signature' => 'sha256='.hash_hmac('sha256', $timestamp.'.'.json_encode(sepayPayload()), 'whsec_test'),
        ])->assertUnauthorized();
    });

    it('accepts the API key when no secret is set', function () {
        config(['services.sepay.webhook_secret' => null, 'services.sepay.api_key' => 'key-123']);
        $payment = pendingPayment();

        sepayWebhook(sepayPayload(), ['Authorization' => 'Apikey key-123'])->assertOk();

        expect($payment->fresh()->status)->toBe(PaymentStatus::Paid);
    });

    it('rejects a wrong API key', function (string $header) {
        config(['services.sepay.webhook_secret' => null, 'services.sepay.api_key' => 'key-123']);
        pendingPayment();

        sepayWebhook(sepayPayload(), ['Authorization' => $header])->assertUnauthorized();
    })->with(['Apikey wrong', 'key-123', 'Bearer key-123', '']);

    it('prefers the secret over the API key when both are set', function () {
        config(['services.sepay.api_key' => 'key-123']);
        pendingPayment();

        sepayWebhook(sepayPayload(), ['Authorization' => 'Apikey key-123'])->assertUnauthorized();
    });

    it('stays closed while neither the secret nor the API key is set', function () {
        config(['services.sepay.webhook_secret' => null, 'services.sepay.api_key' => null]);
        $payment = pendingPayment();

        sepayWebhook(sepayPayload(), ['Authorization' => 'Apikey '])->assertStatus(503);

        expect($payment->fresh()->status)->toBe(PaymentStatus::Pending);
    });
});

it('marks a payment paid and subscribes the user to the course through the model too', function () {
    $payment = pendingPayment();

    $subscription = $payment->markAsPaid();

    expect($payment->status)->toBe(PaymentStatus::Paid)
        ->and($subscription->isActive())->toBeTrue()
        ->and($payment->markAsPaid())->toBeNull()
        ->and(Subscription::count())->toBe(1);
});

it('mails the owner about a paid transfer, a short one and one with no matching code', function (array $overrides, bool $hasPayment, string $subject) {
    Mail::fake();
    config(['payment.notify_email' => 'owner@example.com']);
    $hasPayment && pendingPayment();

    sepayWebhook(sepayPayload($overrides))->assertOk();

    Mail::assertSent(BankTransactionNotice::class, fn (BankTransactionNotice $mail): bool => $mail->hasTo('owner@example.com') && str_contains($mail->envelope()->subject, $subject) && str_contains($mail->render(), 'Số tiền nhận'));
})->with([
    'paid' => [[], true, 'Thanh toán thành công'],
    'short of the price' => [['transferAmount' => 10000], true, 'thiếu tiền'],
    'no pending payment has the code' => [['code' => 'FEZZZZ9999', 'content' => 'x'], true, 'không khớp mã'],
    'no code at all' => [['code' => null, 'content' => 'chuyen tien'], false, 'không khớp mã'],
]);

it('sends no mail for money going out or when no owner email is set', function () {
    Mail::fake();
    config(['payment.notify_email' => 'owner@example.com']);

    sepayWebhook(sepayPayload(['transferType' => 'out']))->assertOk();

    config(['payment.notify_email' => null]);
    sepayWebhook(sepayPayload(['id' => 1, 'code' => null, 'content' => 'x']))->assertOk();

    Mail::assertNothingSent();
});
