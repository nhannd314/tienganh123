<?php

use App\Enums\PaymentStatus;
use App\Models\Course;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
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

it('subscribes a user to a course', function () {
    $user = User::factory()->create();
    $course = Course::factory()->priced()->create();

    $courseSubscription = Subscription::factory()->for($user)->create(['subscribable_type' => $course->getMorphClass(), 'subscribable_id' => $course->id]);

    expect($user->subscriptions)->toHaveCount(1)
        ->and($courseSubscription->subscribable->is($course))->toBeTrue()
        ->and($course->subscriptions)->toHaveCount(1);
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

it('does not let a mass assignment set the role', function () {
    $user = User::create(['name' => 'An', 'email' => 'an@example.com', 'password' => 'secret123', 'role' => 'admin']);

    expect($user->fresh()->isAdmin())->toBeFalse();
});

it('removes the subscriptions and payments of a deleted user', function () {
    $user = User::factory()->create();
    Subscription::factory()->for($user)->create();
    Payment::factory()->for($user)->create();

    $user->delete();

    expect(Subscription::count())->toBe(0)->and(Payment::count())->toBe(0);
});
