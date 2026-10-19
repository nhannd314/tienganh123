<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use Database\Factories\SentenceSetFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['title', 'slug', 'description', 'sort_order'])]
class SentenceSet extends Model
{
    /** @use HasFactory<SentenceSetFactory> */
    use HasFactory, HasSlug;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsToMany<Tag, $this>
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class)->withTimestamps();
    }

    /**
     * @return BelongsToMany<Sentence, $this>
     */
    public function sentences(): BelongsToMany
    {
        return $this->belongsToMany(Sentence::class)
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderByPivot('sort_order')
            ->orderBy('sentences.id');
    }

    /**
     * @return HasMany<Lesson, $this>
     */
    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class);
    }

    /**
     * @return HasMany<Vocabulary, $this>
     */
    public function vocabularies(): HasMany
    {
        return $this->hasMany(Vocabulary::class);
    }

    /**
     * @return HasMany<FlashcardSet, $this>
     */
    public function flashcardSets(): HasMany
    {
        return $this->hasMany(FlashcardSet::class);
    }
}
