<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use Database\Factories\TagFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['name', 'slug'])]
class Tag extends Model
{
    /** @use HasFactory<TagFactory> */
    use HasFactory, HasSlug;

    /**
     * @return BelongsToMany<Vocabulary, $this>
     */
    public function vocabularies(): BelongsToMany
    {
        return $this->belongsToMany(Vocabulary::class)->withTimestamps();
    }

    /**
     * @return BelongsToMany<SentenceSet, $this>
     */
    public function sentenceSets(): BelongsToMany
    {
        return $this->belongsToMany(SentenceSet::class)->withTimestamps();
    }

    /**
     * @return BelongsToMany<FlashcardSet, $this>
     */
    public function flashcardSets(): BelongsToMany
    {
        return $this->belongsToMany(FlashcardSet::class)->withTimestamps();
    }

    /**
     * Name of the attribute the slug is generated from.
     */
    protected function slugSourceAttribute(): string
    {
        return 'name';
    }
}
