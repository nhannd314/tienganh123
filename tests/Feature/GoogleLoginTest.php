<?php

use App\Enums\UserRole;
use App\Models\User;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\User as GoogleUser;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'services.google.client_id' => 'client-id',
        'services.google.client_secret' => 'client-secret',
        'services.google.redirect' => null,
    ]);
});

/**
 * Fake the Google user Socialite hands back to the callback.
 *
 * @param  array<string, mixed>  $profile
 * @param  array<string, mixed>  $raw
 */
function fakeGoogleUser(array $profile = [], array $raw = []): void
{
    $googleUser = (new GoogleUser)
        ->map(['id' => '1234567890', 'name' => 'Lê Minh Châu', 'email' => 'chau@gmail.com', ...$profile])
        ->setRaw(['email_verified' => true, ...$raw]);

    Socialite::fake('google', $googleUser);
}

it('offers Google on the login and sign-up pages', function () {
    $this->get(route('login'))
        ->assertSee('Đăng nhập với Google')
        ->assertSee('hoặc đăng nhập bằng email')
        ->assertSee(route('auth.google.redirect'), false)
        ->assertSee('images/google.svg', false);

    $this->get(route('register'))
        ->assertSee('Đăng ký với Google')
        ->assertSee('hoặc đăng ký bằng email')
        ->assertSee(route('auth.google.redirect'), false);
});

it('sends the visitor to the Google consent screen with a state it remembers', function () {
    $location = $this->get(route('auth.google.redirect'))->assertRedirect()->headers->get('Location');
    parse_str(parse_url($location, PHP_URL_QUERY), $query);

    expect($location)->toStartWith('https://accounts.google.com/o/oauth2/auth?')
        ->and($query)->toMatchArray([
            'client_id' => 'client-id',
            'redirect_uri' => route('auth.google.callback'),
            'response_type' => 'code',
            'prompt' => 'select_account',
        ])
        ->and($query['scope'])->toContain('email')->toContain('profile')
        ->and($query['state'])->toBe(session('state'));
});

it('uses the configured redirect URI when there is one', function () {
    config(['services.google.redirect' => 'https://funenglish.vn/auth/google']);

    $location = $this->get(route('auth.google.redirect'))->headers->get('Location');
    parse_str(parse_url($location, PHP_URL_QUERY), $query);

    expect($query['redirect_uri'])->toBe('https://funenglish.vn/auth/google');
});

it('says so on the login page when Google sign-in is not configured', function () {
    config(['services.google.client_id' => null]);

    $this->get(route('auth.google.redirect'))->assertRedirect(route('login'));

    $this->get(route('login'))->assertSee('Đăng nhập bằng Google hiện chưa được cấu hình.');
});

it('creates and signs in a new learner from a verified Google profile', function () {
    Event::fake([Registered::class]);
    fakeGoogleUser();

    $this->get(route('auth.google.callback'))->assertRedirect(route('home'));

    $user = User::firstWhere('email', 'chau@gmail.com');

    expect($user)->not->toBeNull()
        ->and($user->name)->toBe('Lê Minh Châu')
        ->and($user->google_id)->toBe('1234567890')
        ->and($user->fresh()->role)->toBe(UserRole::User)
        ->and($user->email_verified_at)->not->toBeNull()
        ->and($user->password)->not->toBeEmpty();

    $this->assertAuthenticatedAs($user);
    Event::assertDispatched(Registered::class);
});

it('recognises a returning Google user by the Google id, even after the email changed', function () {
    $user = User::factory()->create(['email' => 'cu@example.com', 'google_id' => '1234567890']);
    fakeGoogleUser(['email' => 'moi@gmail.com']);

    $this->get(route('auth.google.callback'));

    $this->assertAuthenticatedAs($user);
    expect(User::count())->toBe(1)->and($user->fresh()->email)->toBe('cu@example.com');
});

it('links Google to the existing account of the same email without touching its password', function () {
    $user = User::factory()->create(['email' => 'chau@gmail.com', 'password' => 'matkhau123', 'email_verified_at' => now()]);
    fakeGoogleUser(['email' => 'Chau@Gmail.com']);

    $this->get(route('auth.google.callback'));

    $this->assertAuthenticatedAs($user);
    expect(User::count())->toBe(1)
        ->and($user->fresh()->google_id)->toBe('1234567890')
        ->and(Hash::check('matkhau123', $user->fresh()->password))->toBeTrue();
});

it('takes over an account whose email was never verified by replacing its password', function () {
    $user = User::factory()->unverified()->create(['email' => 'chau@gmail.com', 'password' => 'mat-khau-cua-ke-gian']);
    fakeGoogleUser();

    $this->get(route('auth.google.callback'));

    $this->assertAuthenticatedAs($user);
    expect(Hash::check('mat-khau-cua-ke-gian', $user->fresh()->password))->toBeFalse()
        ->and($user->fresh()->email_verified_at)->not->toBeNull()
        ->and($user->fresh()->google_id)->toBe('1234567890');
});

it('lets an admin sign in with Google', function () {
    $admin = User::factory()->admin()->create(['email' => 'chau@gmail.com']);
    fakeGoogleUser();

    $this->get(route('auth.google.callback'));

    $this->get('/admin')->assertOk();
    $this->assertAuthenticatedAs($admin);
});

it('sends the visitor back to the page they were going to', function () {
    fakeGoogleUser();

    $this->withSession(['url.intended' => route('courses.index')])
        ->get(route('auth.google.callback'))
        ->assertRedirect(route('courses.index'));
});

it('explains when the visitor cancels on the Google screen', function () {
    $this->get(route('auth.google.callback', ['error' => 'access_denied']))->assertRedirect(route('login'));

    $this->get(route('login'))->assertSee('Bạn đã hủy đăng nhập bằng Google.');
    $this->assertGuest();
});

it('does not sign in when the state does not match or Google refuses the code', function (Throwable $failure, string $message) {
    Socialite::fake('google', fn () => throw $failure);

    $this->get(route('auth.google.callback'))->assertRedirect(route('login'));

    $this->get(route('login'))->assertSee($message);
    $this->assertGuest();
    expect(User::count())->toBe(0);
})->with([
    'bad state' => [fn () => new InvalidStateException, 'Phiên đăng nhập bằng Google đã hết hạn, bạn thử lại nhé.'],
    'refused code' => [fn () => new ClientException('refused', new Request('POST', '/token'), new Response(400)), 'Không đăng nhập được bằng Google, bạn thử lại nhé.'],
]);

it('does not sign in with an unverified Google email', function (array $profile, array $raw) {
    fakeGoogleUser($profile, $raw);

    $this->get(route('auth.google.callback'))->assertRedirect(route('login'));

    $this->assertGuest();
    expect(User::count())->toBe(0);
})->with([
    'unverified' => [[], ['email_verified' => false]],
    'verified flag missing' => [[], ['email_verified' => null]],
    'no email' => [['email' => null], []],
]);

it('falls back to the part of the email before the @ when Google sends no name', function () {
    fakeGoogleUser(['name' => null]);

    $this->get(route('auth.google.callback'));

    expect(User::firstWhere('email', 'chau@gmail.com')->name)->toBe('chau');
});

it('keeps a signed-in visitor away from the Google routes', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('auth.google.redirect'))->assertRedirect();
    $this->get(route('auth.google.callback'))->assertRedirect();
});
