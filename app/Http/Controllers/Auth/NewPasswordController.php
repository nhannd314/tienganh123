<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class NewPasswordController extends Controller
{
    /**
     * Show the page where the visitor chooses a new password, from the link in the email.
     */
    public function create(Request $request, string $token): View
    {
        return view('auth.reset-password', ['token' => $token, 'email' => $request->query('email')]);
    }

    /**
     * Save the new password; the visitor then signs in with it.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:6'],
            'password_confirmation' => ['required', 'same:password'],
        ], [
            'email.required' => 'Bạn chưa nhập email.',
            'email.email' => 'Email chưa đúng định dạng, bạn kiểm tra lại nhé.',
            'password.required' => 'Mật khẩu cần có ít nhất 6 ký tự.',
            'password.min' => 'Mật khẩu cần có ít nhất 6 ký tự.',
            'password_confirmation.required' => 'Bạn chưa nhập lại mật khẩu.',
            'password_confirmation.same' => 'Mật khẩu nhập lại chưa khớp.',
        ]);

        $status = Password::reset($validated, function (User $user, string $password): void {
            $user->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();

            event(new PasswordReset($user));
        });

        if ($status !== Password::PasswordReset) {
            throw ValidationException::withMessages(['credentials' => 'Link đặt lại mật khẩu không đúng hoặc đã hết hạn. Bạn hãy yêu cầu link mới nhé.']);
        }

        return redirect()->route('login')->with('status', 'Đã đặt lại mật khẩu. Bạn đăng nhập bằng mật khẩu mới nhé!');
    }
}
