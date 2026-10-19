<?php

namespace App\Models;

use Database\Factories\SentenceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;

#[Fillable(['sentence', 'translation', 'image', 'audio'])]
class Sentence extends Model
{
    /** @use HasFactory<SentenceFactory> */
    use HasFactory;

    /**
     * @return BelongsToMany<SentenceSet, $this>
     */
    public function sentenceSets(): BelongsToMany
    {
        return $this->belongsToMany(SentenceSet::class)
            ->withPivot('sort_order')
            ->withTimestamps();
    }

    /**
     * Public URL of the sentence image stored on the public disk, or null when absent.
     *
     * @return Attribute<string|null, never>
     */
    protected function imageUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->image ? Storage::disk('public')->url($this->image) : null);
    }

    /**
     * Public URL of the sentence audio stored on the public disk, or null when absent.
     *
     * @return Attribute<string|null, never>
     */
    protected function audioUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->audio ? Storage::disk('public')->url($this->audio) : null);
    }
}
