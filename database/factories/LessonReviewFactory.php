<?php

namespace Database\Factories;

use App\Models\Lesson;
use App\Models\LessonReview;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LessonReview>
 */
class LessonReviewFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'lesson_id' => Lesson::factory(),
            'user_id' => null,
            'step' => 1,
            'total' => 10,
            'correct' => fake()->numberBetween(0, 10),
            'duration_seconds' => fake()->numberBetween(30, 300),
            'answers' => [],
        ];
    }
}
