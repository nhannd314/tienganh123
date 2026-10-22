<?php

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

uses(RefreshDatabase::class);

it('links the login page to the forgot password page', function () {
    $this->get(route('login'))->assertSee(route('password.request'), false);

    $this->get(route('password.request'))
        ->assertOk()
        ->assertSee('name="email"', false);
});

it('mails a reset link to a registered email', function () {
    Notification::fake();
    $user = User::factory()->create();

    $this->post(route('password.email'), ['email' => $user->email])
        ->assertRedirect()
        ->assertSessionHas('status');

    Notification::assertSentTo($user, ResetPasswordNotification::class);
});

it('gives the same answer for an unknown email and sends nothing', function () {
    Notification::fake();

    $this->post(route('password.email'), ['email' => 'nobody@example.com'])
        ->assertSessionHas('status');

    Notification::assertNothingSent();
});

it('rejects a missing or malformed email', function () {
    $this->post(route('password.email'), ['email' => 'not-an-email'])
        ->assertSessionHasErrors(['email' => 'Email chưa đúng định dạng, bạn kiểm tra lại nhé.']);
});

it('shows the reset page from the link in the email', function () {
    $this->get(route('password.reset', ['token' => 'abc', 'email' => 'nhan@example.com']))
        ->assertOk()
        ->assertSee('value="abc"', false)
        ->assertSee('value="nhan@example.com"', false);
});

it('saves the new password with a valid token', function () {
    $user = User::factory()->create(['password' => 'matkhaucu']);
    $token = Password::createToken($user);

    $this->post(route('password.store'), [
        'token' => $token,
        'email' => $user->email,
        'password' => 'matkhaumoi',
        'password_confirmation' => 'matkhaumoi',
    ])->assertRedirect(route('login'))->assertSessionHas('status');

    expect(Hash::check('matkhaumoi', $user->fresh()->password))->toBeTrue();
});

it('does not reset the password with a wrong token', function () {
    $user = User::factory()->create(['password' => 'matkhaucu']);

    $this->post(route('password.store'), [
        'token' => 'wrong',
        'email' => $user->email,
        'password' => 'matkhaumoi',
        'password_confirmation' => 'matkhaumoi',
    ])->assertSessionHasErrors('credentials');

    expect(Hash::check('matkhaucu', $user->fresh()->password))->toBeTrue();
});

it('needs the repeated password to match', function () {
    $user = User::factory()->create();

    $this->post(route('password.store'), [
        'token' => Password::createToken($user),
        'email' => $user->email,
        'password' => 'matkhaumoi',
        'password_confirmation' => 'khacnhau',
    ])->assertSessionHasErrors(['password_confirmation' => 'Mật khẩu nhập lại chưa khớp.']);
});
