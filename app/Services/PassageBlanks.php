<?php

namespace App\Services;

use App\Filament\Forms\Components\RichEditor\RichContentCustomBlocks\AudioBlock;
use App\Filament\Forms\Components\RichEditor\RichContentCustomBlocks\VideoBlock;
use App\Filament\Forms\Components\RichEditor\RichContentCustomBlocks\YoutubeVideoBlock;
use DOMDocument;
use DOMNode;
use DOMXPath;
use Filament\Forms\Components\RichEditor\RichContentRenderer;

/**
 * Cuts the passage of a lesson into its paragraphs, the words the author wrapped in inline code (`apple`, the same marks
 * that show a word popover on the lesson page) being the words to fill in.
 */
class PassageBlanks
{
    /**
     * The paragraphs (and list items) of the passage that have any text, each cut into its plain text and its marked
     * words, in order.
     *
     * @return list<list<array{text: string, isMarked: bool}>>
     */
    public function paragraphs(?string $passage): array
    {
        if (blank($passage)) {
            return [];
        }

        $html = RichContentRenderer::make($passage)
            ->customBlocks([YoutubeVideoBlock::class, VideoBlock::class, AudioBlock::class])
            ->fileAttachmentsDisk('public')
            ->fileAttachmentsVisibility('public')
            ->toHtml();

        $document = new DOMDocument;
        $document->loadHTML('<?xml encoding="utf-8"?><div id="rich-content">'.$html.'</div>', LIBXML_NOERROR | LIBXML_NOWARNING);

        $paragraphs = [];

        foreach ((new DOMXPath($document))->query('//p | //li[not(.//p)]') as $block) {
            $segments = $this->segmentsOf($block);

            if ($segments !== []) {
                $paragraphs[] = $segments;
            }
        }

        return $paragraphs;
    }

    /**
     * @return list<array{text: string, isMarked: bool}>
     */
    private function segmentsOf(DOMNode $node): array
    {
        $segments = [];

        foreach ($node->childNodes as $child) {
            if ($child->nodeName === 'code') {
                $word = trim($child->textContent);

                if ($word !== '') {
                    $segments[] = ['text' => $word, 'isMarked' => true];
                }

                continue;
            }

            $nested = $child->hasChildNodes()
                ? $this->segmentsOf($child)
                : [['text' => (string) preg_replace('/\s+/u', ' ', str_replace("\u{A0}", ' ', $child->textContent)), 'isMarked' => false]];

            array_push($segments, ...$nested);
        }

        return $this->mergePlainText($segments);
    }

    /**
     * Joins neighbouring plain segments, so a styled word in the middle of a sentence does not split it.
     *
     * @param  list<array{text: string, isMarked: bool}>  $segments
     * @return list<array{text: string, isMarked: bool}>
     */
    private function mergePlainText(array $segments): array
    {
        $merged = [];

        foreach ($segments as $segment) {
            $last = array_key_last($merged);

            if ($last !== null && ! $segment['isMarked'] && ! $merged[$last]['isMarked']) {
                $merged[$last]['text'] .= $segment['text'];
            } elseif ($segment['text'] !== '') {
                $merged[] = $segment;
            }
        }

        return $merged;
    }
}
