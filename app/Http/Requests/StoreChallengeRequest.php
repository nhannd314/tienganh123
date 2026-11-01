<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreChallengeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * The opponent is another learner; the lesson is a published lesson of a published course.
     *
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'opponent_id' => ['required', 'integer', Rule::exists('users', 'id'), Rule::notIn([$this->user()->id])],
            'lesson_id' => ['required', 'integer', Rule::exists('lessons', 'id')->where(fn ($query) => $query
                ->where('is_published', true)
                ->whereIn('unit_id', fn ($units) => $units->select('units.id')->from('units')
                    ->join('courses', 'courses.id', '=', 'units.course_id')
                    ->where('courses.is_published', true)))],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'opponent_id.required' => 'Bạn chưa chọn bạn để thách đấu.',
            'opponent_id.exists' => 'Không tìm thấy bạn này.',
            'opponent_id.not_in' => 'Bạn không thể tự thách đấu chính mình.',
            'lesson_id.required' => 'Bạn chưa chọn bài học.',
            'lesson_id.exists' => 'Bài học này không có hoặc chưa mở.',
        ];
    }
}
