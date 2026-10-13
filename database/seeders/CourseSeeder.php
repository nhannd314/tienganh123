<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CourseSeeder extends Seeder
{
    private const LESSONS_PER_UNIT = 5;

    /**
     * Seed courses with their units and lessons.
     */
    public function run(): void
    {
        foreach ($this->courses() as $courseIndex => $courseData) {
            $course = Course::factory()->published()->create([
                'title' => $courseData['title'],
                'slug' => Str::slug($courseData['title']),
                'description' => $courseData['description'],
                'sort_order' => $courseIndex + 1,
            ]);

            foreach ($courseData['units'] as $unitIndex => $unitTitle) {
                $unit = Unit::factory()->published()->for($course)->create([
                    'title' => $unitTitle,
                    'slug' => Str::slug($unitTitle),
                    'sort_order' => $unitIndex + 1,
                ]);

                Lesson::factory()
                    ->count(self::LESSONS_PER_UNIT)
                    ->published()
                    ->for($unit)
                    ->state(new Sequence(fn (Sequence $sequence) => [
                        'sort_order' => $sequence->index + 1,
                        'is_free' => $sequence->index === 0,
                    ]))
                    ->create();
            }
        }
    }

    /**
     * Get the sample courses and their unit titles.
     *
     * @return list<array{title: string, description: string, units: list<string>}>
     */
    private function courses(): array
    {
        return [
            [
                'title' => 'Tiếng Anh cho người mới bắt đầu',
                'description' => 'Làm quen với phát âm, từ vựng và mẫu câu cơ bản trong giao tiếp hằng ngày.',
                'units' => [
                    'Greetings and Introductions',
                    'Family and Friends',
                    'Daily Routines',
                    'Food and Drinks',
                ],
            ],
            [
                'title' => 'Ngữ pháp tiếng Anh căn bản',
                'description' => 'Nắm vững các thì, cấu trúc câu và từ loại thường gặp.',
                'units' => [
                    'Present Tenses',
                    'Past Tenses',
                    'Future Forms',
                    'Modal Verbs',
                ],
            ],
            [
                'title' => 'Tiếng Anh giao tiếp công sở',
                'description' => 'Tự tin trao đổi qua email, họp và thuyết trình bằng tiếng Anh.',
                'units' => [
                    'Writing Emails',
                    'Meetings',
                    'Presentations',
                ],
            ],
        ];
    }
}
