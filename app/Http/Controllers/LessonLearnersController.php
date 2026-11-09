<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonScore;
use App\Models\Unit;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class LessonLearnersController extends Controller
{
    private const LEARNERS_PER_PAGE = 20;

    /**
     * Show the learning results of every learner who scored in a published lesson, best first, paginated.
     */
    public function __invoke(Request $request, Course $course, Unit $unit, Lesson $lesson): View
    {
        abort_unless($course->is_published && $lesson->is_published, 404);

        $scores = $lesson->scores()
            ->with('user')
            ->orderByDesc('score')
            ->orderBy('id')
            ->paginate(self::LEARNERS_PER_PAGE)
            ->withQueryString();

        $learners = $scores->getCollection()->map(function (LessonScore $score) use ($lesson): array {
            $progress = $lesson->reviewProgress($score->user);

            return [
                'name' => $score->user->name,
                'percent' => $score->percent,
                'points' => $score->score,
                'correct' => $progress['correct'],
                'wrong' => $progress['wrong'],
                'pronunciation' => $progress['pronunciation'],
                'seconds' => $progress['seconds'],
            ];
        });

        return view('lessons.learners', [
            'course' => $course,
            'unit' => $unit,
            'lesson' => $lesson,
            'scores' => $scores,
            'learners' => $learners,
            'firstRank' => $scores->firstItem() ?? 1,
        ]);
    }
}
