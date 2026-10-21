<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAffiliateBankRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'affiliate_bank_name' => ['required', 'string', 'max:255'],
            'affiliate_bank_account_number' => ['required', 'string', 'max:50'],
            'affiliate_bank_account_name' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'affiliate_bank_name.required' => 'Bạn chưa nhập tên ngân hàng.',
            'affiliate_bank_account_number.required' => 'Bạn chưa nhập số tài khoản.',
            'affiliate_bank_account_name.required' => 'Bạn chưa nhập tên chủ tài khoản.',
        ];
    }
}
