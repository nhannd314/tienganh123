<?php

namespace App\Http\Requests;

use App\Models\SlideDeck;
use Illuminate\Foundation\Http\FormRequest;

class StoreSlideDeckRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:120'],
            'slides' => ['required', 'array', 'min:1', 'max:'.SlideDeck::MAX_SLIDES],
            'slides.*.image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'slides.*.text' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Bạn chưa nhập tiêu đề.',
            'title.max' => 'Tiêu đề tối đa 120 ký tự.',
            'slides.required' => 'Hãy thêm ít nhất một slide.',
            'slides.min' => 'Hãy thêm ít nhất một slide.',
            'slides.max' => 'Một bộ slide tối đa '.SlideDeck::MAX_SLIDES.' slide.',
            'slides.*.image.required' => 'Mỗi slide cần có một ảnh.',
            'slides.*.image.image' => 'Tệp phải là ảnh JPG, PNG hoặc WebP.',
            'slides.*.image.mimes' => 'Tệp phải là ảnh JPG, PNG hoặc WebP.',
            'slides.*.image.max' => 'Mỗi ảnh tối đa 5MB.',
            'slides.*.image.uploaded' => 'Không tải được ảnh lên, ảnh tối đa 5MB.',
            'slides.*.text.max' => 'Nội dung mỗi slide tối đa 1000 ký tự.',
        ];
    }
}
