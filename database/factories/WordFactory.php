<?php

namespace Database\Factories;

use App\Models\Word;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Word>
 */
class WordFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $word = fake()->unique()->word();

        return [
            'word' => $word,
            'ipa_us' => '/'.fake()->lexify('???').'/',
            'ipa_uk' => '/'.fake()->lexify('???').'/',
            'meanings' => [
                ['pos' => 'noun', 'meaning' => fake()->sentence(3)],
                ['pos' => 'verb', 'meaning' => fake()->sentence(3)],
            ],
            'examples' => [
                [
                    'sentence' => fake()->sentence(),
                    'translation' => fake()->sentence(),
                    'audio' => null,
                ],
            ],
            'image' => null,
        ];
    }
}
