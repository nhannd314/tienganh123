<?php

namespace Database\Factories;

use App\Enums\AffiliatePayoutStatus;
use App\Models\AffiliatePayout;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AffiliatePayout>
 */
class AffiliatePayoutFactory extends Factory
{
    /**
     * Define the model's default state: a payout waiting for the bank transfer.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'amount' => 100000,
            'status' => AffiliatePayoutStatus::Pending,
            'bank_name' => 'Vietcombank',
            'bank_account_number' => fake()->numerify('##########'),
            'bank_account_name' => fake()->name(),
            'transfer_reference' => null,
            'note' => null,
            'paid_at' => null,
        ];
    }
}
