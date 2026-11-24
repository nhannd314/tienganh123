<?php

namespace App\Http\Requests\Admin;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClassScheduleSlotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * The slot (a date, which must be in the month shown, and a shift from 1 to 5), the classroom that studies in it and an optional note; no classroom empties the slot.
     *
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'month' => ['required', 'date_format:Y-m'],
            'date' => [
                'required',
                'date_format:Y-m-d',
                fn (string $attribute, mixed $value, Closure $fail) => str_starts_with((string) $value, $this->input('month').'-')
                    ?: $fail('Chỉ được nhập lịch của tháng đang xem.'),
            ],
            'shift' => ['required', 'integer', 'between:1,5'],
            'classroom_id' => ['nullable', 'integer', Rule::exists('classrooms', 'id')],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
