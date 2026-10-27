<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Flashcard;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class HomeController extends Controller
{
    private const LATEST_COURSES = 20;

    private const FLASHCARD_TILES = 4;

    /**
     * Show the home page with the published courses, by sort order then from the newest.
     */
    public function __invoke(): View
    {
        $courses = Course::query()
            ->published()
            ->orderBy('sort_order')
            ->latest()
            ->latest('id')
            ->limit(self::LATEST_COURSES)
            ->get();

        return view('home', [
            'courses' => $courses,
            'flashcards' => $this->randomFlashcards(),
        ]);
    }

    /**
     * Random flashcards for the top of the page: the ones with a picture and a text to read.
     *
     * @return Collection<int, Flashcard>
     */
    private function randomFlashcards(): Collection
    {
        return Flashcard::query()
            ->with('word')
            ->where(fn (Builder $card) => $card->whereNotNull('image')->orWhereHas('word', fn (Builder $word) => $word->whereNotNull('image')))
            ->where(fn (Builder $card) => $card->where('title', '!=', '')->orWhereNotNull('word_id'))
            ->inRandomOrder()
            ->limit(self::FLASHCARD_TILES)
            ->get();
    }
}
