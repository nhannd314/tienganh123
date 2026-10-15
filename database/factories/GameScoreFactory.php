<?php

namespace Database\Factories;

use App\Models\GameScore;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GameScore>
 */
class GameScoreFactory extends Factory
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
            'game' => 'soldier-and-monster',
            'milliseconds' => fake()->numberBetween(5000, 60000),
        ];
    }
}
