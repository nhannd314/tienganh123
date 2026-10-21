<?php

use App\Enums\AffiliateCommissionStatus;
use App\Enums\AffiliatePayoutStatus;
use App\Mail\AffiliatePayoutRequested;
use App\Mail\NewUserRegistered;
use App\Models\AffiliateCommission;
use App\Models\AffiliatePayout;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

it('remembers the referral code of a visitor and credits the affiliate when they register', function () {
    $affiliate = User::factory()->create();

    $this->get(route('home', ['ref' => $affiliate->referralCode()]))->assertCookie('ref');

    $this->withCookie('ref', $affiliate->referral_code)->post(route('register.store'), [
        'name' => 'Nguyen Van A',
        'email' => 'a@example.com',
        'password' => 'password-123',
        'password_confirmation' => 'password-123',
        'agree' => '1',
    ]);

    expect(User::where('email', 'a@example.com')->first()->referrer->is($affiliate))->toBeTrue();
});

it('does not let a user refer themselves or change their referrer', function () {
    $first = User::factory()->create();
    $second = User::factory()->create();
    $user = User::factory()->create();

    $user->attachReferrer($user->referralCode());
    expect($user->fresh()->referred_by_id)->toBeNull();

    $user->attachReferrer($first->referralCode());
    $user->attachReferrer($second->referralCode());
    expect($user->fresh()->referred_by_id)->toBe($first->id);
});

it('pays the affiliate the default rate on a referred payment, once', function () {
    $affiliate = User::factory()->create();
    $payment = Payment::factory()->create(['user_id' => User::factory()->create(['referred_by_id' => $affiliate->id]), 'amount' => 100000]);

    $payment->markAsPaid();
    $payment->markAsPaid();

    $commission = AffiliateCommission::sole();

    expect($commission->affiliate_id)->toBe($affiliate->id)
        ->and($commission->rate)->toBe(50)
        ->and($commission->amount)->toBe(50000)
        ->and($commission->status)->toBe(AffiliateCommissionStatus::Pending);
});

it('uses the rate of the affiliate when they have their own', function () {
    $affiliate = User::factory()->create(['affiliate_commission_rate' => 30]);
    $payment = Payment::factory()->create(['user_id' => User::factory()->create(['referred_by_id' => $affiliate->id]), 'amount' => 100000]);

    $payment->markAsPaid();

    expect(AffiliateCommission::sole()->amount)->toBe(30000);
});

it('pays nothing on a payment of a user nobody referred', function () {
    Payment::factory()->create()->markAsPaid();

    expect(AffiliateCommission::count())->toBe(0);
});

it('approves commissions after the delay and rejects those of refunded payments', function () {
    $old = AffiliateCommission::factory()->create(['created_at' => now()->subDays(15)]);
    $recent = AffiliateCommission::factory()->create();
    $refunded = AffiliateCommission::factory()->create(['created_at' => now()->subDays(15)]);
    $refunded->payment->update(['status' => 'refunded']);

    $this->artisan('affiliate:approve-commissions')->assertSuccessful();

    expect($old->fresh()->status)->toBe(AffiliateCommissionStatus::Approved)
        ->and($recent->fresh()->status)->toBe(AffiliateCommissionStatus::Pending)
        ->and($refunded->fresh()->status)->toBe(AffiliateCommissionStatus::Rejected);
});

it('gathers the approved commissions of an affiliate into a payout and settles it', function () {
    $affiliate = User::factory()->create(['affiliate_bank_name' => 'Vietcombank', 'affiliate_bank_account_number' => '123']);
    $commissions = AffiliateCommission::factory()->approved()->count(2)->create(['affiliate_id' => $affiliate->id, 'amount' => 300000]);
    AffiliateCommission::factory()->create(['affiliate_id' => $affiliate->id]);

    $payout = AffiliatePayout::openFor($affiliate);

    expect($payout->amount)->toBe(600000)
        ->and($payout->bank_account_number)->toBe('123')
        ->and(AffiliatePayout::openFor($affiliate))->toBeNull();

    $payout->markAsPaid('FT123');

    expect($payout->fresh()->status)->toBe(AffiliatePayoutStatus::Paid)
        ->and($commissions->every(fn ($commission) => $commission->fresh()->status === AffiliateCommissionStatus::Paid))->toBeTrue();
});

it('returns the commissions to the approved ones when a payout is cancelled', function () {
    $affiliate = User::factory()->create();
    AffiliateCommission::factory()->approved()->create(['affiliate_id' => $affiliate->id, 'amount' => 500000]);

    AffiliatePayout::openFor($affiliate)->cancel();

    expect(AffiliateCommission::payable()->count())->toBe(1);
});

it('shows the referral link and earnings on the affiliate tab of the profile', function () {
    $affiliate = User::factory()->create();
    AffiliateCommission::factory()->approved()->create(['affiliate_id' => $affiliate->id]);

    $this->actingAs($affiliate)
        ->get(route('profile.show', ['tab' => 'affiliate']))
        ->assertOk()
        ->assertSee($affiliate->fresh()->referralUrl())
        ->assertSee('25.000₫');
});

it('saves the bank account of the affiliate from the profile', function () {
    $affiliate = User::factory()->create();

    $this->actingAs($affiliate)
        ->put(route('profile.affiliate.update'), [
            'affiliate_bank_name' => 'Vietcombank',
            'affiliate_bank_account_number' => '0123456789',
            'affiliate_bank_account_name' => 'NGUYEN VAN A',
        ])
        ->assertRedirect(route('profile.show', ['tab' => 'affiliate']));

    expect($affiliate->fresh()->affiliate_bank_account_number)->toBe('0123456789');

    $this->actingAs($affiliate)->put(route('profile.affiliate.update'), [])->assertSessionHasErrors(['affiliate_bank_name', 'affiliate_bank_account_number', 'affiliate_bank_account_name']);
});

it('opens no payout below the minimum', function () {
    $affiliate = User::factory()->create();
    AffiliateCommission::factory()->approved()->create(['affiliate_id' => $affiliate->id, 'amount' => 499999]);

    expect(AffiliatePayout::openFor($affiliate))->toBeNull();

    AffiliateCommission::factory()->approved()->create(['affiliate_id' => $affiliate->id, 'amount' => 1]);

    expect(AffiliatePayout::openFor($affiliate)->amount)->toBe(500000);
});

it('opens a payout and mails the owner when an affiliate asks to withdraw', function () {
    Mail::fake();
    config(['payment.notify_email' => 'owner@example.com']);
    $affiliate = User::factory()->create(['affiliate_bank_name' => 'Vietcombank', 'affiliate_bank_account_number' => '123', 'affiliate_bank_account_name' => 'NGUYEN VAN A']);
    AffiliateCommission::factory()->approved()->create(['affiliate_id' => $affiliate->id, 'amount' => 600000]);

    $this->actingAs($affiliate)->post(route('profile.affiliate.payout'))->assertRedirect();

    expect(AffiliatePayout::sole()->amount)->toBe(600000);
    Mail::assertSent(AffiliatePayoutRequested::class, fn (AffiliatePayoutRequested $mail): bool => $mail->hasTo('owner@example.com') && str_contains($mail->render(), '123'));
});

it('refuses a withdrawal below the minimum or without a bank account', function () {
    Mail::fake();
    $affiliate = User::factory()->create(['affiliate_bank_name' => 'Vietcombank', 'affiliate_bank_account_number' => '123', 'affiliate_bank_account_name' => 'NGUYEN VAN A']);
    AffiliateCommission::factory()->approved()->create(['affiliate_id' => $affiliate->id, 'amount' => 100000]);
    $noBank = User::factory()->create();
    AffiliateCommission::factory()->approved()->create(['affiliate_id' => $noBank->id, 'amount' => 600000]);

    $this->actingAs($affiliate)->post(route('profile.affiliate.payout'))->assertSessionHasErrors('payout');
    $this->actingAs($noBank)->post(route('profile.affiliate.payout'))->assertSessionHasErrors('payout');

    expect(AffiliatePayout::count())->toBe(0);
    Mail::assertNothingSent();
});

it('mails the owner when someone registers', function () {
    Mail::fake();
    config(['payment.notify_email' => 'owner@example.com']);

    $this->post(route('register.store'), [
        'name' => 'Nguyen Van B',
        'email' => 'b@example.com',
        'password' => 'password-123',
        'password_confirmation' => 'password-123',
        'agree' => '1',
    ]);

    Mail::assertSent(NewUserRegistered::class, fn (NewUserRegistered $mail): bool => $mail->hasTo('owner@example.com') && str_contains($mail->render(), 'b@example.com'));
});
