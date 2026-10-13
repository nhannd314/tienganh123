<?php

namespace App\Models;

use Database\Factories\FlashcardFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;

#[Fillable(['word_id', 'title', 'image', 'audio'])]
class Flashcard extends Model
{
    /** @use HasFactory<FlashcardFactory> */
    use HasFactory;

    /**
     * @return BelongsToMany<FlashcardSet, $this>
     */
    public function flashcardSets(): BelongsToMany
    {
        return $this->belongsToMany(FlashcardSet::class)
            ->withPivot('sort_order')
            ->withTimestamps();
    }

    /**
     * @return BelongsTo<Word, $this>
     */
    public function word(): BelongsTo
    {
        return $this->belongsTo(Word::class);
    }

    /**
     * Public URL of the card image stored on the public disk, or null when absent.
     *
     * @return Attribute<string|null, never>
     */
    protected function imageUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->image ? Storage::disk('public')->url($this->image) : null);
    }

    /**
     * Public URL of the card audio stored on the public disk, or null when absent.
     *
     * @return Attribute<string|null, never>
     */
    protected function audioUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->audio ? Storage::disk('public')->url($this->audio) : null);
    }

    /**
     * The text of the card: its own title, or the word it is linked to when the title is empty.
     *
     * @return Attribute<string|null, never>
     */
    protected function displayTitle(): Attribute
    {
        return Attribute::get(fn (): ?string => filled($this->title) ? $this->title : $this->word?->word);
    }
}
