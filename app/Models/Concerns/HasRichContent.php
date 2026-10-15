<?php

namespace App\Models\Concerns;

use Filament\Forms\Components\RichEditor\RichContentRenderer;
use Illuminate\Database\Eloquent\Casts\Attribute;

trait HasRichContent
{
    /**
     * The `content` column (a rich editor document) as HTML.
     *
     * @return Attribute<string, never>
     */
    protected function contentHtml(): Attribute
    {
        return Attribute::get(fn (): string => blank($this->content) ? '' : RichContentRenderer::make($this->content)
            ->fileAttachmentsDisk('public')
            ->fileAttachmentsVisibility('public')
            ->toHtml());
    }
}
