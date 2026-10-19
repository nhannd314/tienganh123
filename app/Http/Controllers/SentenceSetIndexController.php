<?php

namespace App\Http\Controllers;

use App\Models\SentenceSet;
use App\Models\Tag;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class SentenceSetIndexController extends Controller
{
    private const SENTENCE_SETS_PER_PAGE = 12;

    /**
     * The sentences shown on a sentence set card; the rest are summed up as "+N".
     */
    public const PREVIEW_SENTENCES = 3;

    /**
     * Show every sentence set that has sentences, paginated, with the tags above it to filter by.
     */
    public function __invoke(Request $request): View
    {
        $tags = Tag::query()->whereHas('sentenceSets', fn ($query) => $query->has('sentences'))->orderBy('name')->get();
        $activeTag = $tags->firstWhere('slug', $request->query('tag'));

        $sentenceSets = SentenceSet::query()
            ->has('sentences')
            ->when($activeTag, fn ($query) => $query->whereRelation('tags', 'tags.id', $activeTag->id))
            ->with([
                'tags' => fn ($query) => $query->orderBy('name'),
                'sentences' => fn ($query) => $query->limit(self::PREVIEW_SENTENCES),
            ])
            ->withCount('sentences')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate(self::SENTENCE_SETS_PER_PAGE)
            ->withQueryString();

        return view('sentences.index', [
            'sentenceSets' => $sentenceSets,
            'tags' => $tags,
            'activeTag' => $activeTag,
            'previewSentences' => self::PREVIEW_SENTENCES,
        ]);
    }
}
