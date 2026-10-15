<?php

namespace App\Http\Controllers;

use App\Models\FlashcardSet;
use App\Models\Tag;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class FlashcardSetIndexController extends Controller
{
    private const FLASHCARD_SETS_PER_PAGE = 12;

    /**
     * The cards shown on a flashcard set card; the rest are summed up as "+N".
     */
    public const PREVIEW_CARDS = 3;

    /**
     * Show every flashcard set, paginated, with the tags above it to filter by.
     */
    public function __invoke(Request $request): View
    {
        $tags = Tag::query()->has('flashcardSets')->orderBy('name')->get();
        $activeTag = $tags->firstWhere('slug', $request->query('tag'));

        $flashcardSets = FlashcardSet::query()
            ->when($activeTag, fn ($query) => $query->whereRelation('tags', 'tags.id', $activeTag->id))
            ->with([
                'tags' => fn ($query) => $query->orderBy('name'),
                'flashcards' => fn ($query) => $query->with('word')->limit(self::PREVIEW_CARDS),
                'imageFlashcards' => fn ($query) => $query->limit(1),
            ])
            ->withCount('flashcards')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate(self::FLASHCARD_SETS_PER_PAGE)
            ->withQueryString();

        return view('flashcards.index', [
            'flashcardSets' => $flashcardSets,
            'tags' => $tags,
            'activeTag' => $activeTag,
            'previewCards' => self::PREVIEW_CARDS,
        ]);
    }
}
