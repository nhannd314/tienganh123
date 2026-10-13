<?php

namespace Database\Factories;

use App\Models\Lesson;
use App\Models\LessonScore;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LessonScore>
 */
class LessonScoreFactory extends Factory
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
            'user_id' => User::factory(),
            'score' => fake()->numberBetween(0, 900),
            'percent' => fake()->numberBetween(0, 100),
        ];
    }
}
