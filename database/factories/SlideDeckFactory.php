<?php

namespace Database\Factories;

use App\Models\SlideDeck;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SlideDeck>
 */
class SlideDeckFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'code' => SlideDeck::newCode(),
            'title' => fake()->sentence(3),
        ];
    }
}
