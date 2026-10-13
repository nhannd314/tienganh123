<?php

use App\Enums\PaymentStatus;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\MembershipPlan;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

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

function paidCourse(int $price = 50000): Course
{
    return Course::factory()->published()->priced($price)->create(['title' => 'Tiếng Anh lớp 3']);
}

it('sends a guest to the login page and back to the payment page afterwards', function () {
    $course = paidCourse();

    $this->get(route('courses.enroll', $course))->assertRedirect(route('login'));

    expect(session('url.intended'))->toBe(route('courses.enroll', $course));
});

it('shows the amount, bank, account, QR code and transfer content to a signed-in user', function () {
    $course = paidCourse(50000);
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('courses.enroll', $course))
        ->assertOk()
        ->assertSee('Đăng ký khóa học')
        ->assertSee('Tiếng Anh lớp 3')
        ->assertSee('Vietcombank')
        ->assertSee('CONG TY FUNENGLISH')
        ->assertSee('0123456789')
        ->assertSee('50.000₫');

    $payment = Payment::sole();

    expect($payment->provider_reference)->toMatch('/^FE[A-Z0-9]{8}$/');

    $response->assertSee($payment->provider_reference)
        ->assertSee('https://img.vietqr.io/image/970436-0123456789-compact2.png?', false)
        ->assertSee('amount=50000', false)
        ->assertSee('addInfo='.$payment->provider_reference, false);
});

it('opens one pending payment for the user and the course and reuses it on every visit', function () {
    $course = paidCourse();
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('courses.enroll', $course));
    $first = Payment::sole();
    $this->get(route('courses.enroll', $course))->assertSee($first->provider_reference);

    expect(Payment::count())->toBe(1)
        ->and($first)
        ->user_id->toBe($user->id)
        ->payable_type->toBe($course->getMorphClass())
        ->payable_id->toBe($course->id)
        ->amount->toBe(50000)
        ->provider->toBe('bank_transfer')
        ->status->toBe(PaymentStatus::Pending);
});

it('opens a new payment when the price has changed, and one per user', function () {
    $course = paidCourse(50000);
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('courses.enroll', $course));
    $course->update(['monthly_price' => 70000]);
    $this->get(route('courses.enroll', $course))->assertSee('70.000₫');

    $this->actingAs(User::factory()->create())->get(route('courses.enroll', $course));

    expect(Payment::count())->toBe(3)
        ->and(Payment::pluck('provider_reference')->unique())->toHaveCount(3);
});

it('does not reuse a payment that is already paid', function () {
    $course = paidCourse();
    $user = User::factory()->create();
    Payment::factory()->paid()->create(['user_id' => $user->id, 'payable_type' => $course->getMorphClass(), 'payable_id' => $course->id, 'amount' => 50000, 'provider' => 'bank_transfer']);

    $this->actingAs($user)->get(route('courses.enroll', $course))->assertOk();

    expect(Payment::count())->toBe(2)->and(Payment::where('status', PaymentStatus::Pending)->count())->toBe(1);
});

it('sends the visitor of a free course straight back to the course', function () {
    $course = Course::factory()->published()->create();

    $this->actingAs(User::factory()->create())->get(route('courses.enroll', $course))->assertRedirect(route('courses.show', $course));

    expect(Payment::count())->toBe(0);
});

it('keeps an unpublished course not found', function () {
    $course = Course::factory()->priced()->create();

    $this->actingAs(User::factory()->create())->get(route('courses.enroll', $course))->assertNotFound();
});

it('tells a member the course is already open and opens no payment', function () {
    $course = paidCourse();
    $user = User::factory()->create();
    Subscription::factory()->for($user)->membership()->create();

    $this->actingAs($user)->get(route('courses.enroll', $course))
        ->assertOk()
        ->assertSee('Bạn đang là thành viên')
        ->assertDontSee('0123456789');

    expect(Payment::count())->toBe(0);
});

it('shows until when a current subscriber is subscribed and still offers to renew', function () {
    $course = paidCourse();
    $user = User::factory()->create();
    Subscription::factory()->for($user)->create(['subscribable_type' => $course->getMorphClass(), 'subscribable_id' => $course->id, 'ends_at' => now()->addDays(10)]);

    $this->actingAs($user)->get(route('courses.enroll', $course))
        ->assertSee('Bạn đang học khóa này')
        ->assertSee(now()->addDays(10)->format('d/m/Y'))
        ->assertSee('0123456789');
});

it('says so when the bank account is not configured and shows no QR code', function () {
    config(['payment.bank' => ['name' => null, 'bin' => null, 'account_number' => null, 'account_name' => null]]);
    $course = paidCourse();

    $this->actingAs(User::factory()->create())->get(route('courses.enroll', $course))
        ->assertOk()
        ->assertSee('Chưa có thông tin chuyển khoản')
        ->assertDontSee('img.vietqr.io', false);
});

it('shows the transfer details without a QR code while only the account number is set', function () {
    config(['payment.bank' => ['name' => 'ACB', 'bin' => null, 'account_number' => '999', 'account_name' => null]]);

    $this->actingAs(User::factory()->create())->get(route('courses.enroll', paidCourse()))
        ->assertSee('ACB')
        ->assertSee('999')
        ->assertDontSee('img.vietqr.io', false);
});

it('gives the copy buttons what to copy', function () {
    $this->actingAs(User::factory()->create())->get(route('courses.enroll', paidCourse()))
        ->assertSee('data-copy="0123456789"', false)
        ->assertSee('data-copy="50000"', false)
        ->assertSee('data-copy="'.Payment::sole()->provider_reference.'"', false);
});

it('offers the registration button on a paid course and none on a free course', function () {
    $paid = paidCourse();
    $free = Course::factory()->published()->create();

    $this->get(route('courses.show', $paid))
        ->assertSee('Đăng ký khóa học')
        ->assertSee(route('courses.enroll', $paid), false)
        ->assertSee('Đăng nhập');

    $this->get(route('courses.show', $free))
        ->assertDontSee(route('courses.enroll', $free), false)
        ->assertSee('Hoàn toàn miễn phí');

    $this->actingAs(User::factory()->create())->get(route('courses.show', $paid))
        ->assertSee(route('courses.enroll', $paid), false)
        ->assertDontSee('Bạn cần');
});

it('shows Bắt đầu học, linking to the first lesson, instead of the registration button to an admin, a member and a subscriber', function (string $who) {
    $course = paidCourse();
    $unit = Unit::factory()->published()->for($course)->create(['sort_order' => 1]);
    $first = Lesson::factory()->published()->for($unit)->create(['sort_order' => 1]);
    Lesson::factory()->published()->for($unit)->create(['sort_order' => 2]);

    $user = match ($who) {
        'admin' => User::factory()->admin()->create(),
        'member' => tap(User::factory()->create(), fn (User $user) => Subscription::factory()->for($user)->membership()->create()),
        'subscriber' => tap(User::factory()->create(), fn (User $user) => Subscription::factory()->for($user)->create(['subscribable_type' => $course->getMorphClass(), 'subscribable_id' => $course->id])),
    };

    $this->actingAs($user)->get(route('courses.show', $course))
        ->assertSee('Bắt đầu học')
        ->assertSee(route('lessons.show', [$course, $unit, $first]), false)
        ->assertDontSee('Đăng ký khóa học')
        ->assertDontSee(route('courses.enroll', $course), false);
})->with(['admin', 'member', 'subscriber']);

it('keeps the registration button for a user without access, even when the subscription expired', function () {
    $course = paidCourse();
    $user = User::factory()->create();
    Subscription::factory()->for($user)->expired()->create(['subscribable_type' => $course->getMorphClass(), 'subscribable_id' => $course->id]);

    $this->actingAs($user)->get(route('courses.show', $course))
        ->assertSee('Đăng ký khóa học')
        ->assertDontSee('Bắt đầu học');
});

it('shows no button at all to someone with access when the course has no lessons yet', function () {
    $this->actingAs(User::factory()->admin()->create())->get(route('courses.show', paidCourse()))
        ->assertDontSee('Bắt đầu học')
        ->assertDontSee('Đăng ký khóa học');
});

it('offers the VIP membership with its price below the course payment', function () {
    MembershipPlan::factory()->create(['monthly_price' => 100000]);

    $this->actingAs(User::factory()->create())->get(route('courses.enroll', paidCourse()))
        ->assertSee('Học tất cả các khóa học không giới hạn')
        ->assertSee('100.000₫')
        ->assertSee(route('membership.enroll'), false);
});

it('offers no VIP membership while no plan is active', function () {
    MembershipPlan::factory()->create(['is_active' => false]);

    $this->actingAs(User::factory()->create())->get(route('courses.enroll', paidCourse()))
        ->assertDontSee(route('membership.enroll'), false);
});

it('opens a pending payment for the membership plan and shows the transfer details', function () {
    $plan = MembershipPlan::factory()->create(['monthly_price' => 100000]);
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('membership.enroll'))
        ->assertOk()
        ->assertSee('Đăng ký thành viên VIP')
        ->assertSee('0123456789')
        ->assertSee('100.000₫');

    expect(Payment::sole())
        ->user_id->toBe($user->id)
        ->payable_type->toBe($plan->getMorphClass())
        ->payable_id->toBe($plan->id)
        ->amount->toBe(100000);
});

it('sends a guest to log in and a current member to the courses from the membership page', function () {
    MembershipPlan::factory()->create();

    $this->get(route('membership.enroll'))->assertRedirect(route('login'));

    $member = User::factory()->create();
    Subscription::factory()->for($member)->membership()->create();

    $this->actingAs($member)->get(route('membership.enroll'))->assertRedirect(route('courses.index'));
    expect(Payment::count())->toBe(0);
});
