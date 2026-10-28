<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLessonReviewRequest extends FormRequest
{
    /**
     * Review results can be sent by any visitor; a signed-in user is recorded when there is one.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * A part asks about every word (or sentence) of the lesson, so the number of answers has no small limit: the
     * controller checks it matches the questions, the maximum only stops oversized requests.
     *
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'duration' => ['required', 'integer', 'min:0', 'max:86400'],
            'answers' => ['required', 'array', 'min:1', 'max:500'],
            'answers.*.word_id' => ['required', 'integer', 'distinct'],
            'answers.*.selected' => ['required', 'string', 'max:255'],
        ];
    }
}
