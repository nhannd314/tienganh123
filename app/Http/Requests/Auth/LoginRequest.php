<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Closure;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    private const MAX_ATTEMPTS = 5;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * A login with an "@" is an email; anything else is a phone number, looked up in the form it is stored in.
     */
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('login'))) {
            $login = trim($this->input('login'));

            $this->merge(['login' => $this->isEmail($login) ? $login : User::normalizePhone($login)]);
        }
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'login' => ['required', 'string', function (string $attribute, string $value, Closure $fail): void {
                $isValid = $this->isEmail($value)
                    ? filter_var($value, FILTER_VALIDATE_EMAIL) !== false
                    : preg_match(User::PHONE_PATTERN, $value) === 1;

                if (! $isValid) {
                    $fail('Email hoặc số điện thoại chưa đúng định dạng, bạn kiểm tra lại nhé.');
                }
            }],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'login.required' => 'Bạn chưa nhập email hoặc số điện thoại.',
            'password.required' => 'Bạn chưa nhập mật khẩu.',
        ];
    }

    /**
     * Sign the user in by email or phone number, counting failed attempts per login and IP address.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $login = $this->string('login')->toString();
        $credentials = [$this->isEmail($login) ? 'email' : 'phone' => $login, 'password' => $this->string('password')->toString()];

        if (! Auth::attempt($credentials, $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages(['credentials' => 'Email, số điện thoại hoặc mật khẩu chưa đúng.']);
        }

        RateLimiter::clear($this->throttleKey());
    }

    private function isEmail(string $login): bool
    {
        return str_contains($login, '@');
    }

    /**
     * @throws ValidationException
     */
    private function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), self::MAX_ATTEMPTS)) {
            return;
        }

        event(new Lockout($this));

        $minutes = (int) ceil(RateLimiter::availableIn($this->throttleKey()) / 60);

        throw ValidationException::withMessages([
            'credentials' => "Bạn đã thử đăng nhập quá nhiều lần. Vui lòng thử lại sau {$minutes} phút.",
        ]);
    }

    private function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('login')->toString()).'|'.$this->ip());
    }
}
