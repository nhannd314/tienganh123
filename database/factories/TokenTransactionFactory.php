<?php

namespace Database\Factories;

use App\Enums\TokenTransactionType;
use App\Models\TokenTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TokenTransaction>
 */
class TokenTransactionFactory extends Factory
{
    /**
     * Define the model's default state: a top-up of 100 tokens.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => TokenTransactionType::TopUp,
            'amount' => 100,
            'balance_after' => 100,
            'ai_service_id' => null,
            'reference_type' => null,
            'reference_id' => null,
            'description' => 'Nạp token',
            'meta' => null,
        ];
    }

    /**
     * Indicate that it spends tokens on an AI service.
     */
    public function usage(int $tokens = 1): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => TokenTransactionType::Usage,
            'amount' => -$tokens,
            'balance_after' => max(0, $attributes['balance_after'] - $tokens),
            'description' => 'Dùng dịch vụ AI',
        ]);
    }
}
