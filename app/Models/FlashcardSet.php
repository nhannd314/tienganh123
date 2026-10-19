<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use Database\Factories\FlashcardSetFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['sentence_set_id', 'title', 'slug', 'description', 'sort_order'])]
class FlashcardSet extends Model
{
    /** @use HasFactory<FlashcardSetFactory> */
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
     * @return BelongsToMany<Flashcard, $this>
     */
    public function flashcards(): BelongsToMany
    {
        return $this->belongsToMany(Flashcard::class)
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderByPivot('sort_order')
            ->orderBy('flashcards.id');
    }

    /**
     * Cards of the set that have an image, the first of which is the thumbnail of the set.
     *
     * @return BelongsToMany<Flashcard, $this>
     */
    public function imageFlashcards(): BelongsToMany
    {
        return $this->flashcards()->whereNotNull('flashcards.image');
    }

    /**
     * Public URL of the image of the first card that has one, or null when no card has an image.
     * Needs the imageFlashcards relation loaded to avoid a query per set.
     *
     * @return Attribute<string|null, never>
     */
    protected function thumbnailUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->imageFlashcards->first()?->image_url);
    }

    /**
     * @return BelongsToMany<Tag, $this>
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class)->withTimestamps();
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
     * @return BelongsTo<SentenceSet, $this>
     */
    public function sentenceSet(): BelongsTo
    {
        return $this->belongsTo(SentenceSet::class);
    }
}
