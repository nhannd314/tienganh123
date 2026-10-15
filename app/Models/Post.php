<?php

namespace App\Models;

use App\Models\Concerns\HasRichContent;
use App\Models\Concerns\HasSlug;
use Carbon\CarbonInterface;
use Database\Factories\PostFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable(['category_id', 'title', 'slug', 'excerpt', 'thumbnail', 'content', 'is_published', 'published_at'])]
class Post extends Model
{
    /** @use HasFactory<PostFactory> */
    use HasFactory, HasRichContent, HasSlug;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Public URL of the thumbnail stored on the public disk, or null when absent.
     *
     * @return Attribute<string|null, never>
     */
    protected function thumbnailUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->thumbnail ? Storage::disk('public')->url($this->thumbnail) : null);
    }

    /**
     * The day the post is shown as published: its publish date, or the day it was written.
     */
    public function publishedOn(): CarbonInterface
    {
        return $this->published_at ?? $this->created_at;
    }

    /**
     * Minutes needed to read the post, at 200 words a minute, at least one.
     */
    public function readMinutes(): int
    {
        $words = preg_split('/\s+/u', trim(strip_tags($this->content_html)), -1, PREG_SPLIT_NO_EMPTY);

        return max(1, (int) ceil(count($words) / 200));
    }

    /**
     * Scope a query to the published posts whose publish date has come (a post without a date is published at once).
     */
    #[Scope]
    protected function published(Builder $query): Builder
    {
        return $query
            ->where('is_published', true)
            ->where(fn (Builder $query) => $query->whereNull('published_at')->orWhere('published_at', '<=', now()));
    }
}
