<?php

namespace Database\Factories;

use App\Models\TokenPackage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TokenPackage>
 */
class TokenPackageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'tokens' => 100,
            'price' => 50000,
            'sort_order' => 0,
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the pack can no longer be bought.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
