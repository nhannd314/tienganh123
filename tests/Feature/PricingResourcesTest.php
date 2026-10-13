<?php

use App\Enums\PaymentStatus;
use App\Filament\Resources\AiServices\Pages\CreateAiService;
use App\Filament\Resources\AiServices\Pages\EditAiService;
use App\Filament\Resources\AiServices\Pages\ListAiServices;
use App\Filament\Resources\MembershipPlans\Pages\CreateMembershipPlan;
use App\Filament\Resources\MembershipPlans\Pages\EditMembershipPlan;
use App\Filament\Resources\MembershipPlans\Pages\ListMembershipPlans;
use App\Filament\Resources\Payments\Pages\ListPayments;
use App\Filament\Resources\Payments\Pages\ViewPayment;
use App\Filament\Resources\TokenPackages\Pages\CreateTokenPackage;
use App\Filament\Resources\TokenPackages\Pages\EditTokenPackage;
use App\Filament\Resources\TokenPackages\Pages\ListTokenPackages;
use App\Models\AiService;
use App\Models\Course;
use App\Models\MembershipPlan;
use App\Models\Payment;
use App\Models\TokenPackage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->admin()->create());
});

it('shows the three pricing pages in the admin menu to an admin', function (string $url) {
    $this->get($url)->assertOk();
})->with([
    '/admin/membership-plans',
    '/admin/token-packages',
    '/admin/ai-services',
]);

it('keeps the pricing pages from a regular user', function (string $url) {
    auth()->logout();
    $this->actingAs(User::factory()->create())->get($url)->assertForbidden();
})->with([
    '/admin/membership-plans',
    '/admin/token-packages',
    '/admin/ai-services',
]);

it('creates a membership plan with a slug and a price', function () {
    Livewire::test(CreateMembershipPlan::class)
        ->fillForm(['name' => 'Thành viên', 'monthly_price' => 100000])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(MembershipPlan::firstWhere('name', 'Thành viên'))
        ->slug->toBe('thanh-vien')
        ->monthly_price->toBe(100000)
        ->is_active->toBeTrue();
});

it('changes the price of a membership plan', function () {
    $plan = MembershipPlan::factory()->create(['monthly_price' => 100000]);

    Livewire::test(EditMembershipPlan::class, ['record' => $plan->getRouteKey()])
        ->fillForm(['monthly_price' => 120000])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($plan->refresh()->monthly_price)->toBe(120000);
});

it('requires a valid price and a unique slug for a membership plan', function () {
    MembershipPlan::factory()->create(['slug' => 'thanh-vien']);

    Livewire::test(CreateMembershipPlan::class)
        ->fillForm(['name' => 'Gói', 'monthly_price' => -5, 'slug' => 'thanh-vien'])
        ->call('create')
        ->assertHasFormErrors(['monthly_price', 'slug' => 'unique']);

    Livewire::test(CreateMembershipPlan::class)
        ->fillForm(['name' => 'Gói', 'monthly_price' => null])
        ->call('create')
        ->assertHasFormErrors(['monthly_price' => 'required']);
});

it('lists the membership plans with their price', function () {
    $plan = MembershipPlan::factory()->create(['name' => 'Thành viên', 'monthly_price' => 100000]);

    Livewire::test(ListMembershipPlans::class)
        ->assertCanSeeTableRecords([$plan])
        ->assertSee('100.000 ₫');
});

it('creates and edits a token package', function () {
    Livewire::test(CreateTokenPackage::class)
        ->fillForm(['name' => 'Gói 120 token', 'tokens' => 120, 'price' => 50000, 'sort_order' => 2])
        ->call('create')
        ->assertHasNoFormErrors();

    $package = TokenPackage::firstWhere('name', 'Gói 120 token');

    expect($package)->tokens->toBe(120)->price->toBe(50000)->is_active->toBeTrue();

    Livewire::test(EditTokenPackage::class, ['record' => $package->getRouteKey()])
        ->fillForm(['price' => 45000, 'is_active' => false])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($package->refresh())->price->toBe(45000)->is_active->toBeFalse();
});

it('requires at least one token and a non-negative price for a token package', function () {
    Livewire::test(CreateTokenPackage::class)
        ->fillForm(['name' => 'Gói', 'tokens' => 0, 'price' => -1, 'sort_order' => 0])
        ->call('create')
        ->assertHasFormErrors(['tokens', 'price']);
});

it('lists the token packages', function () {
    $packages = TokenPackage::factory()->count(2)->create();

    Livewire::test(ListTokenPackages::class)->assertCanSeeTableRecords($packages);
});

it('creates an AI service with its token cost', function () {
    Livewire::test(CreateAiService::class)
        ->fillForm(['name' => 'Chấm điểm phát âm', 'key' => 'pronunciation_scoring', 'tokens_per_use' => 2])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(AiService::firstWhere('key', 'pronunciation_scoring'))->tokens_per_use->toBe(2)->is_active->toBeTrue();
});

it('changes the cost of an AI service but never its key', function () {
    $service = AiService::factory()->create(['key' => 'pronunciation_scoring', 'tokens_per_use' => 1]);

    Livewire::test(EditAiService::class, ['record' => $service->getRouteKey()])
        ->assertFormFieldIsDisabled('key')
        ->fillForm(['tokens_per_use' => 3, 'key' => 'doi_ten'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($service->refresh())->tokens_per_use->toBe(3)->key->toBe('pronunciation_scoring');
});

it('needs a unique key made of letters, numbers, dashes and underscores for an AI service', function () {
    AiService::factory()->create(['key' => 'da_co']);

    Livewire::test(CreateAiService::class)
        ->fillForm(['name' => 'Dịch vụ', 'key' => 'da_co', 'tokens_per_use' => 1])
        ->call('create')
        ->assertHasFormErrors(['key' => 'unique']);

    Livewire::test(CreateAiService::class)
        ->fillForm(['name' => 'Dịch vụ', 'key' => 'co dau cach', 'tokens_per_use' => 1])
        ->call('create')
        ->assertHasFormErrors(['key']);
});

it('lists the AI services', function () {
    $services = AiService::factory()->count(2)->create();

    Livewire::test(ListAiServices::class)->assertCanSeeTableRecords($services);
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
    $plan = MembershipPlan::factory()->create();
    $payment = Payment::factory()->create(['payable_type' => $plan->getMorphClass(), 'payable_id' => $plan->id]);

    Livewire::test(ViewPayment::class, ['record' => $payment->getRouteKey()])
        ->callAction('markAsPaid')
        ->assertNotified();

    expect($payment->fresh()->status)->toBe(PaymentStatus::Paid)
        ->and($payment->user->hasActiveMembership())->toBeTrue();
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
