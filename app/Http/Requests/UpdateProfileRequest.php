<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\s.-]+$/'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'birthday' => ['nullable', 'date_format:d/m/Y', 'before:today'],
            'grade' => ['nullable', 'string', 'max:50'],
            'school' => ['nullable', 'string', 'max:255'],
            'parent_email' => ['nullable', 'email', 'max:255'],
            'parent_phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\s.-]+$/'],
        ];
    }

    /**
     * The validated data with the birthday as the date the database stores, from the d/m/Y the form sends.
     *
     * @return array<string, mixed>
     */
    public function profileData(): array
    {
        $data = $this->validated();

        if (filled($data['birthday'] ?? null)) {
            $data['birthday'] = Carbon::createFromFormat('d/m/Y', $data['birthday'])->toDateString();
        }

        return $data;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Bạn chưa nhập họ và tên.',
            'name.max' => 'Họ và tên dài quá, bạn rút gọn lại nhé.',
            'phone.regex' => 'Số điện thoại chưa đúng, bạn kiểm tra lại nhé.',
            'avatar.image' => 'Ảnh đại diện phải là một tệp ảnh.',
            'avatar.mimes' => 'Ảnh đại diện chỉ nhận định dạng JPG, PNG hoặc WebP.',
            'avatar.max' => 'Ảnh đại diện nặng quá, bạn chọn ảnh dưới 10MB nhé.',
            'birthday.date_format' => 'Ngày sinh cần có dạng dd/mm/yyyy.',
            'birthday.before' => 'Ngày sinh phải là một ngày trong quá khứ.',
            'parent_email.email' => 'Email chưa đúng định dạng, bạn kiểm tra lại nhé.',
            'parent_phone.regex' => 'Số điện thoại chưa đúng, bạn kiểm tra lại nhé.',
        ];
    }
}
