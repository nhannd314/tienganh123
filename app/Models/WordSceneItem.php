<?php

namespace App\Models;

use Database\Factories\WordSceneItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['word_scene_id', 'word_id', 'x', 'y', 'width', 'height', 'sort_order'])]
class WordSceneItem extends Model
{
    /** @use HasFactory<WordSceneItemFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'x' => 'float',
            'y' => 'float',
            'width' => 'float',
            'height' => 'float',
        ];
    }

    /**
     * @return BelongsTo<WordScene, $this>
     */
    public function wordScene(): BelongsTo
    {
        return $this->belongsTo(WordScene::class);
    }

    /**
     * @return BelongsTo<Word, $this>
     */
    public function word(): BelongsTo
    {
        return $this->belongsTo(Word::class);
    }

    /**
     * Whether the rectangle around the object has been drawn.
     */
    public function hasRectangle(): bool
    {
        return $this->x !== null && $this->y !== null && $this->width !== null && $this->height !== null;
    }
}
