<?php

namespace App\Models;

use Database\Factories\SlideDeckFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * A slideshow a member made, shown full screen to anyone who opens its short link (see route slides.show).
 */
#[Fillable(['user_id', 'code', 'title'])]
class SlideDeck extends Model
{
    /** @use HasFactory<SlideDeckFactory> */
    use HasFactory;

    public const MAX_SLIDES = 20;

    /**
     * Delete the images of the slides with the deck (the rows go with it).
     */
    protected static function booted(): void
    {
        static::deleting(function (SlideDeck $deck): void {
            Storage::disk('public')->delete($deck->slides()->pluck('image')->all());
        });
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<Slide, $this>
     */
    public function slides(): HasMany
    {
        return $this->hasMany(Slide::class)->orderBy('position');
    }

    /**
     * The key of the short link.
     */
    public function getRouteKeyName(): string
    {
        return 'code';
    }

    /**
     * The short link anyone can open the slideshow by.
     */
    public function shareUrl(): string
    {
        return route('slides.show', $this);
    }

    /**
     * A short code nobody has used: lowercase letters and digits, e.g. k3x9qa2.
     */
    public static function newCode(): string
    {
        do {
            $code = Str::lower(Str::random(7));
        } while (static::where('code', $code)->exists());

        return $code;
    }
}
