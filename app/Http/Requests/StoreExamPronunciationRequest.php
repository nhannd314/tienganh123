<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreExamPronunciationRequest extends FormRequest
{
    /**
     * The attempt is checked by the controller, from the session.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * One recording of one word (by its id) or one sentence (by its number) of a pronunciation group: a WAV of at most 1 MB.
     *
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'group_id' => ['required', 'integer'],
            'key' => ['required', 'integer'],
            'audio' => ['required', 'file', 'mimes:wav', 'max:1024'],
        ];
    }
}
