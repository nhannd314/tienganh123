<?php

namespace App\Http\Requests;

use App\Models\SlideDeck;
use Illuminate\Validation\Rule;

class UpdateSlideDeckRequest extends StoreSlideDeckRequest
{
    public function authorize(): bool
    {
        return $this->route('slideDeck')->user_id === $this->user()?->id;
    }

    /**
     * A slide that already exists is sent with its `id` and may keep its image; a new one needs an image.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        /** @var SlideDeck $deck */
        $deck = $this->route('slideDeck');

        return [
            ...parent::rules(),
            'slides.*.id' => ['nullable', 'integer', Rule::exists('slides', 'id')->where('slide_deck_id', $deck->id)],
            'slides.*.image' => ['required_without:slides.*.id', 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }
}
