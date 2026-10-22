<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class PasswordResetLinkController extends Controller
{
    /**
     * Show the page where the visitor asks for a reset link.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Mail the reset link. The answer is the same whether or not the email has an account, so it cannot be used to find out who is registered.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ], [
            'email.required' => 'Bạn chưa nhập email.',
            'email.email' => 'Email chưa đúng định dạng, bạn kiểm tra lại nhé.',
        ]);

        Password::sendResetLink($validated);

        return back()->with('status', 'Nếu email này đã có tài khoản, chúng mình vừa gửi link đặt lại mật khẩu vào hộp thư của bạn. Link có hiệu lực trong 60 phút.');
    }
}
