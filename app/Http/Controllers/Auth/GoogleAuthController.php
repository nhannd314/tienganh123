<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Middleware\CaptureReferral;
use App\Models\User;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialUser;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;

/**
 * Sign in or sign up with a Google account (Laravel Socialite).
 *
 * Needs GOOGLE_CLIENT_ID and GOOGLE_CLIENT_SECRET, and the callback route registered as an authorized redirect URI.
 */
class GoogleAuthController extends Controller
{
    /**
     * Send the visitor to Google's consent screen.
     */
    public function redirect(): SymfonyRedirectResponse
    {
        if (blank(config('services.google.client_id')) || blank(config('services.google.client_secret'))) {
            return $this->failed('Đăng nhập bằng Google hiện chưa được cấu hình.');
        }

        $this->useCallbackRoute();

        return Socialite::driver('google')->with(['prompt' => 'select_account'])->redirect();
    }

    /**
     * Finish the sign-in Google sent the visitor back from.
     */
    public function callback(Request $request): RedirectResponse
    {
        if ($request->has('error')) {
            return $this->failed('Bạn đã hủy đăng nhập bằng Google.');
        }

        $this->useCallbackRoute();

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (InvalidStateException) {
            return $this->failed('Phiên đăng nhập bằng Google đã hết hạn, bạn thử lại nhé.');
        } catch (ClientException) {
            return $this->failed('Không đăng nhập được bằng Google, bạn thử lại nhé.');
        }

        if (($googleUser->user['email_verified'] ?? false) !== true || blank($googleUser->getEmail())) {
            return $this->failed('Email Google của bạn chưa được xác minh nên chưa thể đăng nhập.');
        }

        Auth::login($this->findOrCreateUser($googleUser, $request->cookie(CaptureReferral::COOKIE)), remember: true);
        $request->session()->regenerate();

        return redirect()->intended(route('home'));
    }

    /**
     * Find the account of this Google user, or of the same verified email, or create it.
     *
     * An existing account whose email was never verified may have been registered by someone else, so its password is
     * replaced: only Google can open it from now on.
     */
    private function findOrCreateUser(SocialUser $googleUser, ?string $referralCode): User
    {
        $user = User::where('google_id', $googleUser->getId())->first();

        if ($user !== null) {
            return $user;
        }

        $email = Str::lower($googleUser->getEmail());
        $user = User::where('email', $email)->first();

        if ($user === null) {
            $user = User::create([
                'name' => $googleUser->getName() ?: Str::before($email, '@'),
                'email' => $email,
                'password' => Str::random(40),
            ]);
            $user->forceFill(['google_id' => $googleUser->getId(), 'email_verified_at' => now()])->save();

            $user->attachReferrer($referralCode);

            event(new Registered($user));

            return $user;
        }

        $user->google_id = $googleUser->getId();

        if ($user->email_verified_at === null) {
            $user->forceFill(['password' => Str::random(40), 'email_verified_at' => now()]);
        }

        $user->save();

        return $user;
    }

    /**
     * Use the callback route as the redirect URI unless GOOGLE_REDIRECT_URI sets another one.
     */
    private function useCallbackRoute(): void
    {
        config(['services.google.redirect' => config('services.google.redirect') ?: route('auth.google.callback')]);
    }

    private function failed(string $message): RedirectResponse
    {
        return redirect()->route('login')->withErrors(['credentials' => $message]);
    }
}
