<?php

namespace App\Models;

use Database\Factories\AiServiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * An AI service that costs tokens (e.g. pronunciation scoring) and what one use costs. Edited in the admin.
 */
#[Fillable(['key', 'name', 'tokens_per_use', 'is_active'])]
class AiService extends Model
{
    /** @use HasFactory<AiServiceFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tokens_per_use' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<TokenTransaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(TokenTransaction::class);
    }

    /**
     * Scope a query to only include services that can be used.
     */
    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
