<?php

namespace App\Http\Requests;

use App\Models\Exam;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StartExamRequest extends FormRequest
{
    /**
     * Any visitor may start an exam: they give their name and class, and its password when it has one.
     */
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
            'student_name' => ['required', 'string', 'max:100'],
            'class_name' => ['required', 'string', 'max:50'],
            'password' => [$this->exam()->requiresPassword() ? 'required' : 'nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (! $validator->errors()->has('password') && ! $this->exam()->acceptsPassword($this->input('password'))) {
                    $validator->errors()->add('password', 'Mật khẩu không đúng.');
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'student_name' => 'họ tên',
            'class_name' => 'lớp',
            'password' => 'mật khẩu',
        ];
    }

    private function exam(): Exam
    {
        return $this->route('exam');
    }
}
