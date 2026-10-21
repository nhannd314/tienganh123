<?php

namespace Database\Factories;

use App\Enums\AffiliateCommissionStatus;
use App\Models\AffiliateCommission;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AffiliateCommission>
 */
class AffiliateCommissionFactory extends Factory
{
    /**
     * Define the model's default state: a pending 50% commission on a 50,000 payment.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'affiliate_id' => User::factory(),
            'referred_user_id' => User::factory(),
            'payment_id' => Payment::factory()->paid(),
            'affiliate_payout_id' => null,
            'payment_amount' => 50000,
            'rate' => 50,
            'amount' => 25000,
            'status' => AffiliateCommissionStatus::Pending,
            'approved_at' => null,
        ];
    }

    /**
     * Indicate that the commission was approved and can be paid out.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AffiliateCommissionStatus::Approved,
            'approved_at' => now(),
        ]);
    }
}
