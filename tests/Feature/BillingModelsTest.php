<?php

use App\Enums\PaymentStatus;
use App\Enums\TokenTransactionType;
use App\Models\AiService;
use App\Models\Course;
use App\Models\MembershipPlan;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\TokenPackage;
use App\Models\TokenTransaction;
use App\Models\User;
use Database\Seeders\PricingSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('knows whether a course is free from its monthly price', function () {
    expect(Course::factory()->create()->isFree())->toBeTrue()
        ->and(Course::factory()->priced(50000)->create())->monthly_price->toBe(50000)
        ->and(Course::factory()->priced(50000)->create()->isFree())->toBeFalse();
});

it('gives each course its own price', function () {
    $cheap = Course::factory()->priced(30000)->create();
    $dear = Course::factory()->priced(80000)->create();

    expect($cheap->fresh()->monthly_price)->toBe(30000)
        ->and($dear->fresh()->monthly_price)->toBe(80000);
});

it('subscribes a user to a course and to a membership plan', function () {
    $user = User::factory()->create();
    $course = Course::factory()->priced()->create();
    $plan = MembershipPlan::factory()->create();

    $courseSubscription = Subscription::factory()->for($user)->create(['subscribable_type' => $course->getMorphClass(), 'subscribable_id' => $course->id]);
    $membership = Subscription::factory()->for($user)->membership($plan)->create();

    expect($user->subscriptions)->toHaveCount(2)
        ->and($courseSubscription->subscribable->is($course))->toBeTrue()
        ->and($membership->subscribable->is($plan))->toBeTrue()
        ->and($course->subscriptions)->toHaveCount(1)
        ->and($plan->subscriptions)->toHaveCount(1)
        ->and($membership->price_paid)->toBe($plan->monthly_price);
});

it('counts a subscription as active only inside its period and until it is cancelled', function (string $state, bool $active) {
    $subscription = Subscription::factory()->{$state}()->create();

    expect($subscription->isActive())->toBe($active)
        ->and(Subscription::active()->whereKey($subscription->id)->exists())->toBe($active);
})->with([
    'running' => ['free', true],
    'expired' => ['expired', false],
    'cancelled' => ['cancelled', false],
]);

it('keeps a free course enrolment active without an end date', function () {
    $subscription = Subscription::factory()->free()->create();

    expect($subscription->ends_at)->toBeNull()
        ->and($subscription->isActive())->toBeTrue();
});

it('does not count a subscription that starts in the future', function () {
    $subscription = Subscription::factory()->create(['starts_at' => now()->addDay()]);

    expect($subscription->isActive())->toBeFalse()
        ->and(Subscription::active()->whereKey($subscription->id)->exists())->toBeFalse();
});

it('records what a payment bought and whether it went through', function () {
    $subscription = Subscription::factory()->create();
    $payment = Payment::factory()->paid()->create(['user_id' => $subscription->user_id, 'payable_type' => $subscription->getMorphClass(), 'payable_id' => $subscription->id, 'amount' => 50000]);
    Payment::factory()->create(['user_id' => $subscription->user_id]);
    Payment::factory()->failed()->create(['user_id' => $subscription->user_id]);

    expect($payment->status)->toBe(PaymentStatus::Paid)
        ->and($payment->paid_at)->not->toBeNull()
        ->and($payment->payable->is($subscription))->toBeTrue()
        ->and($subscription->user->payments)->toHaveCount(3)
        ->and(Payment::paid()->count())->toBe(1);
});

it('cannot record the same provider reference twice', function () {
    Payment::factory()->create(['provider' => 'momo', 'provider_reference' => 'ABC']);

    expect(fn () => Payment::factory()->create(['provider' => 'momo', 'provider_reference' => 'ABC']))->toThrow(QueryException::class);
});

it('keeps a token ledger per user, newest first, tied to the AI service that was used', function () {
    $user = User::factory()->create();
    $service = AiService::factory()->create(['key' => 'pronunciation_scoring', 'tokens_per_use' => 2]);
    $payment = Payment::factory()->paid()->create(['user_id' => $user->id]);

    TokenTransaction::factory()->for($user)->create(['reference_type' => $payment->getMorphClass(), 'reference_id' => $payment->id]);
    $usage = TokenTransaction::factory()->for($user)->usage(2)->create(['ai_service_id' => $service->id]);

    expect($user->tokenTransactions->first()->is($usage))->toBeTrue()
        ->and($usage->type)->toBe(TokenTransactionType::Usage)
        ->and($usage->amount)->toBe(-2)
        ->and($usage->aiService->is($service))->toBeTrue()
        ->and($service->transactions)->toHaveCount(1)
        ->and($user->tokenTransactions->last()->reference->is($payment))->toBeTrue();
});

it('starts every user with no tokens', function () {
    expect(User::factory()->create()->fresh()->token_balance)->toBe(0);
});

it('does not let a mass assignment set the token balance or the role', function () {
    $user = User::create(['name' => 'An', 'email' => 'an@example.com', 'password' => 'secret123', 'token_balance' => 999, 'role' => 'admin']);

    expect($user->fresh()->token_balance)->toBe(0)
        ->and($user->fresh()->isAdmin())->toBeFalse();
});

it('lists only active membership plans, token packs and AI services as buyable', function () {
    MembershipPlan::factory()->create();
    MembershipPlan::factory()->inactive()->create();
    TokenPackage::factory()->create();
    TokenPackage::factory()->inactive()->create();
    AiService::factory()->create();
    AiService::factory()->inactive()->create();

    expect(MembershipPlan::active()->count())->toBe(1)
        ->and(TokenPackage::active()->count())->toBe(1)
        ->and(AiService::active()->count())->toBe(1);
});

it('slugs a membership plan from its name', function () {
    expect(MembershipPlan::factory()->create(['name' => 'Thành viên', 'slug' => null])->slug)->toBe('thanh-vien');
});

it('seeds the sample prices once, however many times it runs', function () {
    $this->seed(PricingSeeder::class);
    $this->seed(PricingSeeder::class);

    expect(MembershipPlan::count())->toBe(1)
        ->and(MembershipPlan::first()->monthly_price)->toBe(100000)
        ->and(TokenPackage::count())->toBe(3)
        ->and(AiService::firstWhere('key', 'pronunciation_scoring')->tokens_per_use)->toBe(1);
});

it('removes the subscriptions, payments and ledger of a deleted user', function () {
    $user = User::factory()->create();
    Subscription::factory()->for($user)->create();
    Payment::factory()->for($user)->create();
    TokenTransaction::factory()->for($user)->create();

    $user->delete();

    expect(Subscription::count())->toBe(0)->and(Payment::count())->toBe(0)->and(TokenTransaction::count())->toBe(0);
});

it('keeps the ledger entry when its AI service is deleted', function () {
    $service = AiService::factory()->create();
    $entry = TokenTransaction::factory()->usage()->create(['ai_service_id' => $service->id]);

    $service->delete();

    expect($entry->fresh()->ai_service_id)->toBeNull();
});
