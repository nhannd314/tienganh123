<?php

use App\Enums\PaymentStatus;
use App\Models\Course;
use App\Models\Payment;
use App\Models\User;
use App\Services\MembershipPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'payment.bank' => [
            'name' => 'Vietcombank',
            'bin' => '970436',
            'account_number' => '0123456789',
            'account_name' => 'CONG TY FUNENGLISH',
        ],
        'payment.transfer_prefix' => 'FE',
    ]);
});

it('prices the membership by cycle', function (int $months, int $price, int $discountPercent) {
    expect(MembershipPlan::priceForMonths($months))->toBe($price)
        ->and(MembershipPlan::discountPercentForMonths($months))->toBe($discountPercent);
})->with([
    'one month' => [1, 99000, 50],
    'three months' => [3, 282150, 53],
    'six months' => [6, 534600, 55],
    'one year' => [12, 950400, 60],
]);

it('sends a guest to the login page', function () {
    $this->get(route('membership.enroll'))->assertRedirect(route('login'));
});

it('shows the chosen cycle with its price and opens one reusable membership payment', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('membership.enroll', ['months' => 6]))
        ->assertOk()
        ->assertSee('Đăng ký Membership')
        ->assertSee('534.600₫')
        ->assertSee('amount=534600', false);

    $this->get(route('membership.enroll', ['months' => 6]))->assertOk();

    $payment = Payment::sole();

    expect($payment)
        ->user_id->toBe($user->id)
        ->payable_type->toBeNull()
        ->amount->toBe(534600)
        ->status->toBe(PaymentStatus::Pending)
        ->and($payment->isForMembership())->toBeTrue()
        ->and($payment->monthsBought())->toBe(6);
});

it('falls back to the shortest cycle for an unknown one', function () {
    $this->actingAs(User::factory()->create())->get(route('membership.enroll', ['months' => 5]))->assertOk();

    expect(Payment::sole()->amount)->toBe(99000);
});

it('makes the user a member for the months bought when the payment is paid', function () {
    $this->travelTo(Carbon::parse('2026-11-12 10:00:00'));
    $user = User::factory()->create();
    $payment = Payment::pendingMembershipBankTransfer($user, 3);

    $payment->markAsPaid();
    $payment->markAsPaid();

    expect($user->fresh()->isMember())->toBeTrue()
        ->and($user->fresh()->membership_expires_on->toDateString())->toBe('2027-02-12')
        ->and($payment->fresh()->status)->toBe(PaymentStatus::Paid)
        ->and($payment->fresh()->meta['membership_expires_on'])->toBe('2027-02-12');
});

it('renews from the day the running membership ends, and from today once it has ended', function () {
    $this->travelTo(Carbon::parse('2026-11-12 10:00:00'));
    $running = User::factory()->member('2026-12-01')->create();
    $ended = User::factory()->member('2026-10-01')->create();

    Payment::pendingMembershipBankTransfer($running, 1)->markAsPaid();
    Payment::pendingMembershipBankTransfer($ended, 1)->markAsPaid();

    expect($running->fresh()->membership_expires_on->toDateString())->toBe('2027-01-01')
        ->and($ended->fresh()->membership_expires_on->toDateString())->toBe('2026-12-12');
});

it('leaves a membership that never expires as it is', function () {
    $user = User::factory()->member()->create();

    Payment::pendingMembershipBankTransfer($user, 1)->markAsPaid();

    expect($user->fresh()->membership_expires_on)->toBeNull()
        ->and($user->fresh()->isMember())->toBeTrue();
});

it('takes the learner to the course list once the membership is paid', function () {
    $user = User::factory()->create();
    $payment = Payment::pendingMembershipBankTransfer($user, 1);
    $payment->markAsPaid();

    $this->actingAs($user)->getJson(route('payments.status', $payment))
        ->assertExactJson(['paid' => true, 'redirect' => route('courses.index')]);
});

it('offers the membership under the payment of a course, but not to a member', function () {
    $course = Course::factory()->published()->priced(50000)->create();

    $this->actingAs(User::factory()->create())->get(route('courses.enroll', $course))
        ->assertSee('Gói thành viên Membership')
        ->assertSee('99.000₫/tháng')
        ->assertSee(route('membership.enroll'), false);

    $this->actingAs(User::factory()->member()->create())->get(route('courses.enroll', $course))
        ->assertDontSee('Gói thành viên Membership');
});
