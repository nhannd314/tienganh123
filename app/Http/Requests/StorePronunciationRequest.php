<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePronunciationRequest extends FormRequest
{
    /**
     * Any visitor who may open the lesson may have a word scored, like the other review parts.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * One recording of one word, or of one sentence in part 10: a WAV of at most 1 MB (about 30 seconds of 16 kHz mono audio).
     *
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'word_id' => ['required', 'integer'],
            'step' => ['nullable', 'integer', 'in:5,6,10'],
            'audio' => ['required', 'file', 'mimes:wav', 'max:1024'],
        ];
    }
}
