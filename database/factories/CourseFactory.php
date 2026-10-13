<?php

namespace Database\Factories;

use App\Models\Course;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Course>
 */
class CourseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->unique()->sentence(3);

        return [
            'title' => $title,
            'slug' => Str::slug($title),
            'description' => fake()->paragraph(),
            'thumbnail' => null,
            'monthly_price' => 0,
            'sort_order' => 0,
            'is_published' => false,
        ];
    }

    /**
     * Indicate that the course costs the given amount (VND) per month.
     */
    public function priced(int $monthlyPrice = 50000): static
    {
        return $this->state(fn (array $attributes) => [
            'monthly_price' => $monthlyPrice,
        ]);
    }

    /**
     * Indicate that the course is published.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => true,
        ]);
    }
}
