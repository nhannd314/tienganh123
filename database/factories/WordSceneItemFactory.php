<?php

namespace Database\Factories;

use App\Models\Word;
use App\Models\WordScene;
use App\Models\WordSceneItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WordSceneItem>
 */
class WordSceneItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'word_scene_id' => WordScene::factory(),
            'word_id' => Word::factory(),
            'x' => 0.1,
            'y' => 0.2,
            'width' => 0.3,
            'height' => 0.4,
            'sort_order' => 0,
        ];
    }

    /**
     * A word whose rectangle has not been drawn yet.
     */
    public function undrawn(): static
    {
        return $this->state(['x' => null, 'y' => null, 'width' => null, 'height' => null]);
    }
}
