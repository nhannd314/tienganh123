<?php

namespace Database\Factories;

use App\Models\Sentence;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sentence>
 */
class SentenceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sentence' => fake()->sentence(),
            'translation' => fake()->sentence(),
            'image' => null,
            'audio' => null,
        ];
    }
}
