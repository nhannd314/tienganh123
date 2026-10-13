<?php

use App\Enums\PaymentStatus;
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
