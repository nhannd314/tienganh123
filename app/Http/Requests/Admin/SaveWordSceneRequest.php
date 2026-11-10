<?php

namespace App\Http\Requests\Admin;

use App\Models\WordScene;
use Illuminate\Foundation\Http\FormRequest;

class SaveWordSceneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * The picture is required to create a scene; when editing one, it is only sent to replace the picture it has.
     *
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'image' => [$this->route('wordScene') instanceof WordScene ? 'nullable' : 'required', 'image', 'max:5120'],
        ];
    }
}
