<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CourseController extends Controller
{
    /**
     * Show a published course with its units, lessons and the course top 10.
     */
    public function __invoke(Request $request, Course $course): View
    {
        abort_unless($course->is_published, 404);

        $user = $request->user();
        $hasPaidAccess = $user?->hasPaidAccessTo($course) ?? false;
        $hasCourseAccess = $course->isFree() || $hasPaidAccess;

        $units = $course->units()
            ->published()
            ->with(['lessons' => fn ($query) => $query->published()])
            ->get();

        $lessonIds = $units->pluck('lessons')->flatten()->pluck('id');
        $wordCounts = $this->newWordCounts($lessonIds);
        $reviewPercents = $user ? Lesson::reviewPercentsFor($user, $lessonIds) : collect();

        $lessonNumber = 0;
        $unitRows = $units->map(function ($unit, int $unitIndex) use (&$lessonNumber, $wordCounts, $reviewPercents, $course, $hasCourseAccess): array {
            return [
                'title' => 'Unit '.($unitIndex + 1).': '.$unit->title,
                'lessons' => $unit->lessons->map(function ($lesson) use (&$lessonNumber, $wordCounts, $reviewPercents, $course, $unit, $hasCourseAccess): array {
                    $lessonNumber++;
                    $access = match (true) {
                        $hasCourseAccess => 'open',
                        $lesson->is_free => 'trial',
                        default => 'locked',
                    };

                    return [
                        'number' => $lessonNumber,
                        'title' => $lesson->title,
                        'newWords' => (int) ($wordCounts[$lesson->id] ?? 0),
                        'progress' => $reviewPercents[$lesson->id] ?? 0,
                        'access' => $access,
                        'url' => $access === 'locked' ? route('courses.enroll', $course) : route('lessons.show', [$course, $unit, $lesson]),
                    ];
                })->all(),
            ];
        })->all();

        $lessonCount = $lessonNumber;

        return view('courses.show', [
            'course' => $course,
            'units' => $unitRows,
            'meta' => array_values(array_filter([
                ['icon' => 'layers', 'label' => ($course->unit_count ?? count($unitRows)).' unit'],
                ['icon' => 'book-open', 'label' => ($course->lesson_count ?? $lessonCount).' bài học'],
                filled($course->level) ? ['icon' => 'graduation-cap', 'label' => 'Trình độ: '.$course->level] : null,
            ])),
            'hasPaidAccess' => $hasPaidAccess,
            'startUrl' => $unitRows[0]['lessons'][0]['url'] ?? null,
            'topLearners' => $course->topLearners()->all(),
        ]);
    }

    /**
     * Number of distinct words introduced by each lesson, keyed by lesson id.
     *
     * @param  Collection<int, int>  $lessonIds
     * @return Collection<int, int>
     */
    private function newWordCounts(Collection $lessonIds): Collection
    {
        return DB::table('lessons')
            ->join('lesson_vocabulary', 'lesson_vocabulary.lesson_id', '=', 'lessons.id')
            ->join('vocabulary_word', 'vocabulary_word.vocabulary_id', '=', 'lesson_vocabulary.vocabulary_id')
            ->whereIn('lessons.id', $lessonIds)
            ->groupBy('lessons.id')
            ->selectRaw('lessons.id as lesson_id, count(distinct vocabulary_word.word_id) as total')
            ->pluck('total', 'lesson_id');
    }
}
