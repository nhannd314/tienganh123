<?php

namespace App\Http\Controllers;

use App\Models\Vocabulary;
use App\Services\VocabularySidebar;
use App\Services\WordChoiceQuestions;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class VocabularyReviewController extends Controller
{
    /**
     * The parts of the vocabulary review: the multiple-choice parts of the lesson review (see LessonReviewController::STEPS).
     *
     * @var list<int>
     */
    public const STEPS = [1, 2, 3];

    /**
     * Show one part of the review of a vocabulary, for members (and admins) only. The answers are graded in the browser
     * and only shown: nothing is saved.
     */
    public function __invoke(Request $request, Vocabulary $vocabulary, int $step, WordChoiceQuestions $questions, VocabularySidebar $sidebar): Response
    {
        if (! $request->user()->hasMemberAccess()) {
            return response()->view('review-locked', [
                'title' => 'Ôn tập '.$vocabulary->title,
                'breadcrumbs' => [
                    ['label' => 'Trang chủ', 'href' => route('home')],
                    ['label' => 'Từ vựng', 'href' => route('vocabularies.index')],
                    ['label' => $vocabulary->title, 'href' => route('vocabularies.show', $vocabulary)],
                    ['label' => 'Ôn tập'],
                ],
                'headerActive' => 'vocab',
                'heading' => 'Ôn tập từ vựng dành cho thành viên',
                'message' => "Bạn cần gói thành viên để ôn tập bộ từ vựng “{$vocabulary->title}”.",
                'backUrl' => route('vocabularies.show', $vocabulary),
                'backLabel' => 'Xem bộ từ vựng',
            ], 403);
        }

        $words = $vocabulary->reviewWords();
        $nextStep = collect(self::STEPS)->first(fn (int $available): bool => $available > $step);

        return response()->view('vocabularies.review', [
            'vocabulary' => $vocabulary,
            'step' => $step,
            'hint' => LessonReviewController::STEPS[$step - 1]['hint'],
            'activeTab' => (int) array_search($step, self::STEPS, true),
            'steps' => array_map(fn (int $tabStep): array => [
                'label' => LessonReviewController::STEPS[$tabStep - 1]['title'],
                'href' => route('vocabularies.review', [$vocabulary, $tabStep]),
                'number' => $tabStep,
            ], self::STEPS),
            'questions' => match ($step) {
                1 => $questions->meaning($words),
                2 => $questions->word($words),
                3 => $questions->listening($words),
            },
            'nextUrl' => $nextStep !== null ? route('vocabularies.review', [$vocabulary, $nextStep]) : null,
            'sidebar' => $sidebar->for($vocabulary),
        ]);
    }
}
