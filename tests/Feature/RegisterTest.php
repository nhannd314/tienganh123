<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function registrationData(array $overrides = []): array
{
    return [
        'name' => 'Nguyễn Văn An',
        'email' => 'an@example.com',
        'password' => 'matkhau123',
        'password_confirmation' => 'matkhau123',
        'agree' => '1',
        ...$overrides,
    ];
}

it('shows the sign-up page with its fields and the login link', function () {
    $this->get(route('register'))
        ->assertOk()
        ->assertSee('Tạo tài khoản để bắt đầu học tiếng Anh cùng FunEnglish nhé!')
        ->assertSee('name="name"', false)
        ->assertSee('name="email"', false)
        ->assertSee('name="password"', false)
        ->assertSee('name="password_confirmation"', false)
        ->assertSee('name="agree"', false)
        ->assertSee('Dùng ít nhất 6 ký tự.')
        ->assertSee('Điều khoản sử dụng')
        ->assertSee('Chính sách bảo mật')
        ->assertSee('Đã có tài khoản?')
        ->assertSee(route('login'), false);
});

it('has a single show / hide button for both password fields', function () {
    $html = $this->get(route('register'))->getContent();

    expect(substr_count($html, 'data-password-toggle'))->toBe(1)
        ->and(substr_count($html, 'data-password-input'))->toBe(2);
});

it('links the login page to the sign-up page', function () {
    $this->get(route('login'))->assertSee(route('register'), false);
});

it('creates a learner account, signs it in and goes to the home page', function () {
    Event::fake([Registered::class]);

    $this->post(route('register.store'), registrationData(['name' => '  Nguyễn Văn An  ']))
        ->assertRedirect(route('home'));

    $user = User::firstWhere('email', 'an@example.com');

    expect($user)->not->toBeNull()
        ->and($user->name)->toBe('Nguyễn Văn An')
        ->and($user->fresh()->role)->toBe(UserRole::User)
        ->and(Hash::check('matkhau123', $user->password))->toBeTrue();

    $this->assertAuthenticatedAs($user);
    Event::assertDispatched(Registered::class);
});

it('cannot create an admin by sending a role', function () {
    $this->post(route('register.store'), registrationData(['role' => 'admin']));

    expect(User::firstWhere('email', 'an@example.com')->fresh()->role)->toBe(UserRole::User);
});

it('sends the new learner back to the page they were going to', function () {
    $this->withSession(['url.intended' => route('courses.index')])
        ->post(route('register.store'), registrationData())
        ->assertRedirect(route('courses.index'));
});

it('validates every field with the messages of the design', function (array $overrides, array $errors) {
    User::factory()->create(['email' => 'da-co@example.com']);

    $this->from(route('register'))
        ->post(route('register.store'), registrationData($overrides))
        ->assertSessionHasErrors($errors);

    expect(User::where('email', 'an@example.com')->exists())->toBeFalse();
    $this->assertGuest();
})->with([
    'no name' => [['name' => ''], ['name' => 'Bạn chưa nhập họ và tên.']],
    'no email' => [['email' => ''], ['email' => 'Bạn chưa nhập email.']],
    'bad email' => [['email' => 'khong-hop-le'], ['email' => 'Email chưa đúng định dạng, bạn kiểm tra lại nhé.']],
    'email taken' => [['email' => 'da-co@example.com'], ['email' => 'Email này đã được đăng ký, bạn hãy đăng nhập nhé.']],
    'short password' => [['password' => '12345', 'password_confirmation' => '12345'], ['password' => 'Mật khẩu cần có ít nhất 6 ký tự.']],
    'no password' => [['password' => '', 'password_confirmation' => ''], ['password' => 'Mật khẩu cần có ít nhất 6 ký tự.']],
    'no repeat' => [['password_confirmation' => ''], ['password_confirmation' => 'Bạn chưa nhập lại mật khẩu.']],
    'repeat differs' => [['password_confirmation' => 'khac-hoan-toan'], ['password_confirmation' => 'Mật khẩu nhập lại chưa khớp.']],
    'terms not accepted' => [['agree' => null], ['agree' => 'Bạn cần đồng ý với điều khoản để tiếp tục.']],
]);

it('accepts a password of exactly six characters', function () {
    $this->post(route('register.store'), registrationData(['password' => '123456', 'password_confirmation' => '123456']))
        ->assertRedirect(route('home'));
});

it('shows the messages under the fields and keeps what was typed, except the passwords', function () {
    $this->from(route('register'))->post(route('register.store'), registrationData([
        'name' => 'Trần Thị Bình',
        'email' => 'khong-hop-le',
        'password' => 'bi-mat-1234',
        'password_confirmation' => 'khong-khop',
    ]));

    $this->get(route('register'))
        ->assertSee('Email chưa đúng định dạng, bạn kiểm tra lại nhé.')
        ->assertSee('Mật khẩu nhập lại chưa khớp.')
        ->assertSee('value="Trần Thị Bình"', false)
        ->assertSee('value="khong-hop-le"', false)
        ->assertDontSee('bi-mat-1234')
        ->assertDontSee('khong-khop');
});

it('keeps a signed-in visitor away from the sign-up page', function () {
    $this->actingAs(User::factory()->create())->get(route('register'))->assertRedirect();
});

it('lets the new learner sign in again with the same password', function () {
    $this->post(route('register.store'), registrationData());
    auth()->logout();

    $this->post(route('login.store'), ['email' => 'an@example.com', 'password' => 'matkhau123'])
        ->assertRedirect(route('home'));
});

it('keeps a new learner out of the admin panel', function () {
    $this->post(route('register.store'), registrationData());

    $this->get('/admin')->assertForbidden();
});
