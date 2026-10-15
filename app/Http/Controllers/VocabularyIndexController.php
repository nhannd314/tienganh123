<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use App\Models\Vocabulary;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class VocabularyIndexController extends Controller
{
    private const VOCABULARIES_PER_PAGE = 12;

    /**
     * The words shown on a vocabulary card; the rest are summed up as "+N".
     */
    public const PREVIEW_WORDS = 3;

    /**
     * Show every vocabulary, paginated, with the tags above it to filter by.
     */
    public function __invoke(Request $request): View
    {
        $tags = Tag::query()->has('vocabularies')->orderBy('name')->get();
        $activeTag = $tags->firstWhere('slug', $request->query('tag'));

        $vocabularies = Vocabulary::query()
            ->when($activeTag, fn ($query) => $query->whereRelation('tags', 'tags.id', $activeTag->id))
            ->with([
                'tags' => fn ($query) => $query->orderBy('name'),
                'words' => fn ($query) => $query->limit(self::PREVIEW_WORDS),
            ])
            ->withCount('words')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate(self::VOCABULARIES_PER_PAGE)
            ->withQueryString();

        return view('vocabularies.index', [
            'vocabularies' => $vocabularies,
            'tags' => $tags,
            'activeTag' => $activeTag,
            'previewWords' => self::PREVIEW_WORDS,
        ]);
    }
}
