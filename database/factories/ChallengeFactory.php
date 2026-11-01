<?php

namespace Database\Factories;

use App\Enums\ChallengeStatus;
use App\Models\Challenge;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Challenge>
 */
class ChallengeFactory extends Factory
{
    /**
     * Define the model's default state: a challenge just sent, waiting for the opponent.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'challenger_id' => User::factory(),
            'opponent_id' => User::factory(),
            'lesson_id' => Lesson::factory()->published(),
            'status' => ChallengeStatus::Pending,
            'responded_at' => null,
        ];
    }

    /**
     * Indicate that the opponent accepted the challenge.
     */
    public function accepted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ChallengeStatus::Accepted,
            'responded_at' => now(),
        ]);
    }

    /**
     * Indicate that the opponent declined the challenge.
     */
    public function declined(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ChallengeStatus::Declined,
            'responded_at' => now(),
        ]);
    }
}
