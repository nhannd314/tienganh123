<?php

namespace App\Models;

use Database\Factories\WordSceneFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

#[Fillable(['title', 'image'])]
class WordScene extends Model
{
    /** @use HasFactory<WordSceneFactory> */
    use HasFactory;

    /**
     * The words of the picture with the rectangle around each one, in the order they are listed.
     *
     * @return HasMany<WordSceneItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(WordSceneItem::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Put the word typed for an object on the picture: the word already stored with that spelling (whatever its capitals) is reused,
     * otherwise it is created (its IPA and audio are fetched by the word observer). A word the scene already has takes the new
     * rectangle, so drawing it again redraws it. The rectangle is given as fractions (0 to 1) of the picture size.
     *
     * @param  array{x: float, y: float, width: float, height: float}  $rectangle
     */
    public function placeWord(string $spelling, array $rectangle): WordSceneItem
    {
        $spelling = trim($spelling);

        $word = Word::query()->whereRaw('lower(word) = ?', [mb_strtolower($spelling)])->first()
            ?? Word::create(['word' => $spelling, 'meanings' => []]);

        $item = $this->items()->firstOrNew(['word_id' => $word->id]);

        if (! $item->exists) {
            $item->sort_order = (int) $this->items()->max('sort_order') + 1;
        }

        $item->fill($rectangle)->save();

        return $item->setRelation('word', $word);
    }

    /**
     * @return BelongsToMany<Lesson, $this>
     */
    public function lessons(): BelongsToMany
    {
        return $this->belongsToMany(Lesson::class)->withTimestamps();
    }

    /**
     * The words that have a drawn rectangle, to make clickable on the picture.
     *
     * @return HasMany<WordSceneItem, $this>
     */
    public function drawnItems(): HasMany
    {
        return $this->items()->whereNotNull('x')->whereNotNull('y')->whereNotNull('width')->whereNotNull('height');
    }

    /**
     * Public URL of the picture stored on the public disk, or null when absent.
     *
     * @return Attribute<string|null, never>
     */
    protected function imageUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->image ? Storage::disk('public')->url($this->image) : null);
    }
}
