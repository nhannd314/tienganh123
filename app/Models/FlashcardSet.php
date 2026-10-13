<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use Database\Factories\FlashcardSetFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['title', 'slug', 'description', 'sort_order'])]
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
}
