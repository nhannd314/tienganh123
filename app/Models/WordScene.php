<?php

namespace App\Models;

use Database\Factories\WordSceneFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
