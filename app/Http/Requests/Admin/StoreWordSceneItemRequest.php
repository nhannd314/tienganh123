<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreWordSceneItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * The word typed for an object of the picture and the rectangle around it, as fractions (0 to 1) of the picture size.
     *
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'word' => ['required', 'string', 'max:255'],
            'x' => ['required', 'numeric', 'between:0,1'],
            'y' => ['required', 'numeric', 'between:0,1'],
            'width' => ['required', 'numeric', 'between:0.001,1'],
            'height' => ['required', 'numeric', 'between:0.001,1'],
        ];
    }
}
