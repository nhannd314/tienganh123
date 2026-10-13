<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Flashcard;
use App\Models\Lesson;
use App\Models\LessonScore;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class HomeController extends Controller
{
    private const LATEST_COURSES = 20;

    private const LEADERBOARD_SIZE = 10;

    private const FLASHCARD_TILES = 5;

    /**
     * Show the home page with the latest published courses and the leaderboard.
     */
    public function __invoke(): View
    {
        $courses = Course::query()
            ->published()
            ->latest()
            ->latest('id')
            ->limit(self::LATEST_COURSES)
            ->get();

        return view('home', [
            'courses' => $courses,
            'flashcards' => $this->randomFlashcards(),
            'leaderboard' => $this->leaderboard(),
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

    /**
     * Top 10 learners by the total of their best scores over all the published lessons, best first (ties go to whoever
     * has the lowest user id).
     *
     * @return list<array{name: string, points: int}>
     */
    private function leaderboard(): array
    {
        $lessonIds = Lesson::query()
            ->published()
            ->whereHas('unit', fn (Builder $unit) => $unit->published()->whereHas('course', fn (Builder $course) => $course->published()))
            ->select('lessons.id');

        return LessonScore::query()
            ->whereIn('lesson_id', $lessonIds)
            ->groupBy('user_id')
            ->selectRaw('user_id, sum(score) as points')
            ->orderByDesc('points')
            ->orderBy('user_id')
            ->limit(self::LEADERBOARD_SIZE)
            ->with('user')
            ->get()
            ->map(fn (LessonScore $row): array => ['name' => $row->user->name, 'points' => (int) $row->points])
            ->all();
    }
}
