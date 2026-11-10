<?php

namespace Database\Factories;

use App\Models\WordScene;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WordScene>
 */
class WordSceneFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->words(3, true),
            'image' => null,
        ];
    }
}
