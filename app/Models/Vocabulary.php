<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use Database\Factories\VocabularyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['sentence_set_id', 'title', 'slug', 'description', 'sort_order'])]
class Vocabulary extends Model
{
    /** @use HasFactory<VocabularyFactory> */
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
     * @return BelongsToMany<Word, $this>
     */
    public function words(): BelongsToMany
    {
        return $this->belongsToMany(Word::class)
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    /**
     * The words the review of the vocabulary asks about: all of those that have at least one meaning.
     *
     * @return Collection<int, Word>
     */
    public function reviewWords(): Collection
    {
        return $this->words
            ->filter(fn (Word $word): bool => $word->meaningLabels() !== [])
            ->values();
    }

    /**
     * @return BelongsToMany<Lesson, $this>
     */
    public function lessons(): BelongsToMany
    {
        return $this->belongsToMany(Lesson::class)
            ->withPivot('sort_order')
            ->withTimestamps();
    }

    /**
     * @return BelongsToMany<Tag, $this>
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class)->withTimestamps();
    }

    /**
     * @return BelongsTo<SentenceSet, $this>
     */
    public function sentenceSet(): BelongsTo
    {
        return $this->belongsTo(SentenceSet::class);
    }
}
