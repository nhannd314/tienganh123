<?php

namespace Database\Factories;

use App\Models\Slide;
use App\Models\SlideDeck;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Slide>
 */
class SlideFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'slide_deck_id' => SlideDeck::factory(),
            'position' => 1,
            'image' => 'slides/'.fake()->uuid().'.jpg',
            'text' => fake()->sentence(),
        ];
    }
}
