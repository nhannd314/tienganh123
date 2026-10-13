<?php

namespace Database\Factories;

use App\Enums\BankTransactionStatus;
use App\Models\BankTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BankTransaction>
 */
class BankTransactionFactory extends Factory
{
    /**
     * Define the model's default state: money received that matched nothing.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sepay_id' => fake()->unique()->numberBetween(1, 999999999),
            'gateway' => 'Vietcombank',
            'account_number' => '0123456789',
            'transaction_date' => now(),
            'transfer_type' => 'in',
            'amount' => 50000,
            'accumulated' => 0,
            'code' => null,
            'content' => fake()->sentence(),
            'reference_code' => fake()->bothify('FT###########'),
            'description' => null,
            'payment_id' => null,
            'status' => BankTransactionStatus::Unmatched,
            'payload' => [],
        ];
    }
}
