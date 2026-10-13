<?php

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state: a payment that has not gone through yet.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'payable_type' => null,
            'payable_id' => null,
            'amount' => 50000,
            'currency' => 'VND',
            'provider' => 'bank_transfer',
            'provider_reference' => fake()->unique()->bothify('PAY-########'),
            'status' => PaymentStatus::Pending,
            'paid_at' => null,
            'meta' => null,
        ];
    }

    /**
     * Indicate that the payment went through.
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::Paid,
            'paid_at' => now(),
        ]);
    }

    /**
     * Indicate that the payment failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::Failed,
        ]);
    }
}
