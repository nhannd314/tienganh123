<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitExamRequest extends FormRequest
{
    /**
     * The attempt is checked by the controller, from the session.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * The answers as answers[group id][question key]; a question left blank may be missing.
     *
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'answers' => ['nullable', 'array'],
            'answers.*' => ['array'],
            'answers.*.*' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
