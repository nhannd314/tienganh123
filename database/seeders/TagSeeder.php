<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TagSeeder extends Seeder
{
    /**
     * Seed the common English-learning topics used to tag vocabulary and flashcard sets.
     */
    public function run(): void
    {
        foreach ($this->tags() as $name) {
            Tag::updateOrCreate(['slug' => Str::slug($name)], ['name' => $name]);
        }
    }

    /**
     * Get the topic names.
     *
     * @return list<string>
     */
    private function tags(): array
    {
        return [
            'Gia đình',
            'Bạn bè',
            'Trường học',
            'Công việc',
            'Nghề nghiệp',
            'Thức ăn',
            'Đồ uống',
            'Nhà hàng',
            'Mua sắm',
            'Quần áo',
            'Màu sắc',
            'Số đếm',
            'Thời gian',
            'Thời tiết',
            'Du lịch',
            'Giao thông',
            'Sức khỏe',
            'Cơ thể người',
            'Cảm xúc',
            'Tính cách',
            'Nhà cửa',
            'Đồ dùng hằng ngày',
            'Động vật',
            'Thiên nhiên',
            'Thể thao',
            'Âm nhạc',
            'Công nghệ',
            'Giáo dục',
            'Môi trường',
            'Giao tiếp hằng ngày',
        ];
    }
}
