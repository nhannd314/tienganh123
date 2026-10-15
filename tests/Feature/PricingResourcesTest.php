<?php

use App\Enums\PaymentStatus;
use App\Filament\Resources\Payments\Pages\CreatePayment;
use App\Filament\Resources\Payments\Pages\ListPayments;
use App\Filament\Resources\Payments\Pages\ViewPayment;
use App\Models\Course;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->admin()->create());
});

it('lists the payments of customers with who paid for what', function () {
    $course = Course::factory()->published()->priced(50000)->create(['title' => 'Tiếng Anh lớp 3']);
    $payment = Payment::factory()->create(['payable_type' => $course->getMorphClass(), 'payable_id' => $course->id, 'amount' => 50000]);

    Livewire::test(ListPayments::class)
        ->assertCanSeeTableRecords([$payment])
        ->assertSee('Tiếng Anh lớp 3')
        ->assertSee('50.000 ₫')
        ->filterTable('status', PaymentStatus::Paid->value)
        ->assertCanNotSeeTableRecords([$payment]);
});

it('confirms a pending payment by hand and opens what it bought', function () {
    $course = Course::factory()->priced()->create();
    $payment = Payment::factory()->create(['payable_type' => $course->getMorphClass(), 'payable_id' => $course->id]);

    Livewire::test(ViewPayment::class, ['record' => $payment->getRouteKey()])
        ->callAction('markAsPaid')
        ->assertNotified();

    expect($payment->fresh()->status)->toBe(PaymentStatus::Paid)
        ->and($payment->user->isSubscribedTo($course))->toBeTrue();
});

it('offers no manual confirmation for a payment that is already paid', function () {
    $payment = Payment::factory()->paid()->create();

    Livewire::test(ViewPayment::class, ['record' => $payment->getRouteKey()])
        ->assertActionHidden('markAsPaid');
});

it('keeps the payments page from a regular user', function () {
    auth()->logout();
    $this->actingAs(User::factory()->create())->get('/admin/payments')->assertForbidden();
});

it('adds a payment by hand that is already paid and opens the course', function () {
    $course = Course::factory()->published()->priced(50000)->create();
    $user = User::factory()->create();

    Livewire::test(CreatePayment::class)
        ->fillForm(['user_id' => $user->id, 'course_id' => $course->id, 'months' => 3, 'amount' => 142500, 'is_paid' => true])
        ->call('create')
        ->assertHasNoFormErrors();

    $payment = Payment::sole();

    expect($payment)
        ->status->toBe(PaymentStatus::Paid)
        ->amount->toBe(142500)
        ->provider->toBe('bank_transfer')
        ->provider_reference->toStartWith(config('payment.transfer_prefix'))
        ->and($payment->meta)->toMatchArray(['months' => 3, 'confirmed_by' => auth()->id()])
        ->and($user->isSubscribedTo($course))->toBeTrue();
});

it('adds a pending payment by hand that opens nothing until it is confirmed', function () {
    $course = Course::factory()->published()->priced(50000)->create();
    $user = User::factory()->create();

    Livewire::test(CreatePayment::class)
        ->fillForm(['user_id' => $user->id, 'course_id' => $course->id, 'months' => 1, 'amount' => 50000, 'is_paid' => false])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Payment::sole()->status)->toBe(PaymentStatus::Pending)
        ->and($user->isSubscribedTo($course))->toBeFalse();
});

it('fills the amount from the course price and the months chosen', function () {
    $course = Course::factory()->published()->priced(50000)->create();

    Livewire::test(CreatePayment::class)
        ->fillForm(['course_id' => $course->id, 'months' => 3])
        ->assertFormSet(['amount' => $course->priceForMonths(3)]);
});
