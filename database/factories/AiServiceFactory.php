<?php

namespace Database\Factories;

use App\Models\AiService;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AiService>
 */
class AiServiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'key' => Str::snake($name),
            'name' => $name,
            'tokens_per_use' => 1,
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the service is switched off.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
