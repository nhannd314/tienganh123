<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Store the phone number in one form, so the same number typed differently is still found at sign-in.
     */
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('phone'))) {
            $this->merge(['phone' => User::normalizePhone($this->input('phone'))]);
        }
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'regex:'.User::PHONE_PATTERN, 'unique:users,phone'],
            'password' => ['required', 'string', 'min:6'],
            'password_confirmation' => ['required', 'same:password'],
            'agree' => ['accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Bạn chưa nhập họ và tên.',
            'name.max' => 'Họ và tên dài quá, bạn rút gọn lại nhé.',
            'email.required' => 'Bạn chưa nhập email.',
            'email.email' => 'Email chưa đúng định dạng, bạn kiểm tra lại nhé.',
            'email.unique' => 'Email này đã được đăng ký, bạn hãy đăng nhập nhé.',
            'phone.required' => 'Bạn chưa nhập số điện thoại.',
            'phone.regex' => 'Số điện thoại chưa đúng, bạn kiểm tra lại nhé.',
            'phone.unique' => 'Số điện thoại này đã được đăng ký, bạn hãy đăng nhập nhé.',
            'password.required' => 'Mật khẩu cần có ít nhất 6 ký tự.',
            'password.min' => 'Mật khẩu cần có ít nhất 6 ký tự.',
            'password_confirmation.required' => 'Bạn chưa nhập lại mật khẩu.',
            'password_confirmation.same' => 'Mật khẩu nhập lại chưa khớp.',
            'agree.accepted' => 'Bạn cần đồng ý với điều khoản để tiếp tục.',
        ];
    }
}
