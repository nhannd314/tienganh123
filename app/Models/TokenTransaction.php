<?php

namespace App\Models;

use App\Enums\TokenTransactionType;
use Database\Factories\TokenTransactionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * One entry of the token ledger of a user: a positive amount adds tokens, a negative one spends them.
 */
#[Fillable(['user_id', 'type', 'amount', 'balance_after', 'ai_service_id', 'reference_type', 'reference_id', 'description', 'meta'])]
class TokenTransaction extends Model
{
    /** @use HasFactory<TokenTransactionFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => TokenTransactionType::class,
            'amount' => 'integer',
            'balance_after' => 'integer',
            'meta' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<AiService, $this>
     */
    public function aiService(): BelongsTo
    {
        return $this->belongsTo(AiService::class);
    }

    /**
     * What caused the entry: the payment of a top-up, the review an AI scoring was used for.
     *
     * @return MorphTo<Model, $this>
     */
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
