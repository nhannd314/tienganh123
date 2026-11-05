<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class SentenceSentenceSet extends Pivot
{
    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;

    /**
     * A sentence added to a set without a place goes to the end of the set.
     */
    protected static function booted(): void
    {
        static::creating(function (self $pivot): void {
            if ($pivot->sort_order === null) {
                $pivot->sort_order = (int) self::query()->where('sentence_set_id', $pivot->sentence_set_id)->max('sort_order') + 1;
            }
        });
    }
}
