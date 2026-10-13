<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

trait HasSlug
{
    /**
     * Generate a unique slug from the source attribute when it is empty on create/update.
     */
    protected static function bootHasSlug(): void
    {
        static::saving(function (self $model): void {
            if (blank($model->slug)) {
                $model->slug = $model->generateUniqueSlug();
            }
        });
    }

    /**
     * Name of the attribute the slug is generated from. Override in the model if it is not "title".
     */
    protected function slugSourceAttribute(): string
    {
        return 'title';
    }

    /**
     * Columns within which the slug must be unique. Override when the unique index is composite,
     * e.g. ['course_id'] for a unique (course_id, slug) index.
     *
     * @return list<string>
     */
    protected function slugScopeColumns(): array
    {
        return [];
    }

    /**
     * Build a slug and append -1, -2, -3 ... until it does not collide with an existing record.
     */
    protected function generateUniqueSlug(): string
    {
        $base = Str::slug((string) $this->{$this->slugSourceAttribute()});

        $query = static::query()
            ->where(fn ($query) => $query->where('slug', $base)->orWhere('slug', 'like', $base.'-%'));

        foreach ($this->slugScopeColumns() as $column) {
            $query->where($column, $this->{$column});
        }

        if ($this->exists) {
            $query->whereKeyNot($this->getKey());
        }

        $existing = $query->pluck('slug')->all();

        $slug = $base;
        $suffix = 0;

        while (in_array($slug, $existing, true)) {
            $slug = $base.'-'.(++$suffix);
        }

        return $slug;
    }
}
