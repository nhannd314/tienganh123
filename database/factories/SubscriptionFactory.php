<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subscription>
 */
class SubscriptionFactory extends Factory
{
    /**
     * Define the model's default state: a month of a course that has just started.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'subscribable_type' => (new Course)->getMorphClass(),
            'subscribable_id' => Course::factory(),
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDays(29),
            'cancelled_at' => null,
            'price_paid' => 50000,
        ];
    }

    /**
     * Indicate that it is the enrolment in a free course, which never expires.
     */
    public function free(): static
    {
        return $this->state(fn (array $attributes) => [
            'ends_at' => null,
            'price_paid' => 0,
        ]);
    }

    /**
     * Indicate that its period is over.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'starts_at' => now()->subDays(40),
            'ends_at' => now()->subDays(10),
        ]);
    }

    /**
     * Indicate that it was cancelled before its end.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'cancelled_at' => now()->subHour(),
        ]);
    }
}
