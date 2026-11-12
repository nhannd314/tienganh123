<?php

namespace App\Models;

use Database\Factories\SlideFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * One slide of a slideshow: an image and the text shown with it.
 */
#[Fillable(['slide_deck_id', 'position', 'image', 'text'])]
class Slide extends Model
{
    /** @use HasFactory<SlideFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<SlideDeck, $this>
     */
    public function deck(): BelongsTo
    {
        return $this->belongsTo(SlideDeck::class, 'slide_deck_id');
    }

    /**
     * Public URL of the image stored on the public disk.
     *
     * @return Attribute<string, never>
     */
    protected function imageUrl(): Attribute
    {
        return Attribute::get(fn (): string => Storage::disk('public')->url($this->image));
    }
}
