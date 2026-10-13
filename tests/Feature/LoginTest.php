<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;

uses(RefreshDatabase::class);

beforeEach(function () {
    RateLimiter::clear('nhan@example.com|127.0.0.1');
});

function memberAccount(array $attributes = []): User
{
    return User::factory()->create(['email' => 'nhan@example.com', 'password' => 'matkhau123', ...$attributes]);
}

it('shows the login page with its fields and the sign-up link', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('Chào mừng bạn quay lại FunEnglish!')
        ->assertSee('name="email"', false)
        ->assertSee('name="password"', false)
        ->assertSee('data-password-toggle', false)
        ->assertSee('Quên mật khẩu?')
        ->assertSee('Ghi nhớ đăng nhập')
        ->assertSee('Chưa có tài khoản?');
});

it('signs the visitor in and sends them to the home page', function () {
    $user = memberAccount();

    $this->post(route('login.store'), ['email' => 'nhan@example.com', 'password' => 'matkhau123'])
        ->assertRedirect(route('home'));

    $this->assertAuthenticatedAs($user);
});

it('sends the visitor back to the page they were going to', function () {
    memberAccount();

    $this->get(route('logout'))->assertMethodNotAllowed();
    $this->withSession(['url.intended' => route('courses.index')])
        ->post(route('login.store'), ['email' => 'nhan@example.com', 'password' => 'matkhau123'])
        ->assertRedirect(route('courses.index'));
});

it('lets an admin sign in here and then open the admin panel', function () {
    memberAccount(['role' => 'admin']);

    $this->post(route('login.store'), ['email' => 'nhan@example.com', 'password' => 'matkhau123']);

    $this->get('/admin')->assertOk();
});

it('shows one message for a wrong password or an unknown email and keeps the email', function (string $email, string $password) {
    memberAccount();

    $this->from(route('login'))
        ->post(route('login.store'), ['email' => $email, 'password' => $password])
        ->assertRedirect(route('login'));

    $this->assertGuest();

    $this->get(route('login'))
        ->assertSee('Email hoặc mật khẩu chưa đúng.')
        ->assertSee('role="alert"', false);
})->with([
    'wrong password' => ['nhan@example.com', 'sai-mat-khau'],
    'unknown email' => ['khac@example.com', 'matkhau123'],
]);

it('validates the email and password with Vietnamese messages', function (array $input, array $errors) {
    $this->from(route('login'))
        ->post(route('login.store'), $input)
        ->assertSessionHasErrors($errors);
})->with([
    'nothing' => [[], ['email' => 'Bạn chưa nhập email.', 'password' => 'Bạn chưa nhập mật khẩu.']],
    'bad email' => [['email' => 'khong-hop-le', 'password' => 'x'], ['email' => 'Email chưa đúng định dạng, bạn kiểm tra lại nhé.']],
    'no password' => [['email' => 'nhan@example.com'], ['password' => 'Bạn chưa nhập mật khẩu.']],
]);

it('shows the validation message under the field and keeps the typed email', function () {
    $this->from(route('login'))->post(route('login.store'), ['email' => 'khong-hop-le', 'password' => '']);

    $this->get(route('login'))
        ->assertSee('Email chưa đúng định dạng, bạn kiểm tra lại nhé.')
        ->assertSee('Bạn chưa nhập mật khẩu.')
        ->assertSee('value="khong-hop-le"', false)
        ->assertSee('aria-invalid="true"', false);
});

it('never puts the password back into the form', function () {
    memberAccount();

    $this->from(route('login'))->post(route('login.store'), ['email' => 'nhan@example.com', 'password' => 'sai-mat-khau']);

    $this->get(route('login'))->assertDontSee('sai-mat-khau');
});

it('locks the login after five wrong attempts, even for the right password', function () {
    memberAccount();

    foreach (range(1, 5) as $attempt) {
        $this->post(route('login.store'), ['email' => 'nhan@example.com', 'password' => 'sai-mat-khau']);
    }

    $this->from(route('login'))
        ->post(route('login.store'), ['email' => 'nhan@example.com', 'password' => 'matkhau123'])
        ->assertSessionHasErrors('credentials');

    expect(session('errors')->first('credentials'))->toContain('quá nhiều lần');
    $this->assertGuest();
});

it('keeps a signed-in visitor away from the login page', function () {
    $this->actingAs(User::factory()->create())->get(route('login'))->assertRedirect();
});

it('signs the visitor out and ends the session', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('logout'))
        ->assertRedirect(route('home'));

    $this->assertGuest();
});

it('does not let a guest sign out', function () {
    $this->post(route('logout'))->assertRedirect(route('login'));
});

it('offers sign in to a guest and sign out to a signed-in visitor in the header', function () {
    $this->get(route('courses.index'))
        ->assertSee(route('login'), false)
        ->assertDontSee(route('logout'), false);

    $this->actingAs(User::factory()->create())->get(route('courses.index'))
        ->assertSee('action="'.route('logout').'"', false)
        ->assertSee('Đăng xuất');
});
