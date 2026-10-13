<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * An account made through Google has no password the learner knows, so it may set one without the current one.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'current_password' => [Rule::requiredIf(blank($this->user()->google_id)), 'nullable', 'current_password'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'current_password.required' => 'Bạn chưa nhập mật khẩu hiện tại.',
            'current_password.current_password' => 'Mật khẩu hiện tại chưa đúng.',
            'password.required' => 'Mật khẩu mới cần có ít nhất 6 ký tự.',
            'password.min' => 'Mật khẩu mới cần có ít nhất 6 ký tự.',
            'password.confirmed' => 'Mật khẩu nhập lại chưa khớp.',
        ];
    }
}
