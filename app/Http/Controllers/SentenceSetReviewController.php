<?php

namespace App\Http\Controllers;

use App\Models\SentenceSet;
use App\Services\AdvancedReviewContent;
use App\Services\SentenceQuestions;
use App\Services\SentenceSetSidebar;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SentenceSetReviewController extends Controller
{
    /**
     * The parts of the sentence set review: the sentence parts of the lesson review (see LessonReviewController::STEPS).
     *
     * @var list<int>
     */
    public const STEPS = [7, 8, 9];

    /**
     * Show one part of the review of a sentence set, for members (and admins) only. The answers are graded in the browser
     * and only shown: nothing is saved. Only the parts the set has sentences for are offered; another part redirects
     * to the first of them.
     */
    public function __invoke(Request $request, SentenceSet $sentenceSet, int $step, AdvancedReviewContent $content, SentenceQuestions $sentenceQuestions, SentenceSetSidebar $sidebar): Response|RedirectResponse
    {
        if (! $request->user()->hasMemberAccess()) {
            return response()->view('review-locked', [
                'title' => 'Ôn tập '.$sentenceSet->title,
                'breadcrumbs' => [
                    ['label' => 'Trang chủ', 'href' => route('home')],
                    ['label' => 'Câu mẫu', 'href' => route('sentences.index')],
                    ['label' => $sentenceSet->title, 'href' => route('sentences.show', $sentenceSet)],
                    ['label' => 'Ôn tập'],
                ],
                'headerActive' => 'sentences',
                'heading' => 'Ôn tập câu mẫu dành cho thành viên',
                'message' => "Bạn cần gói thành viên để ôn tập bộ câu mẫu “{$sentenceSet->title}”.",
                'backUrl' => route('sentences.show', $sentenceSet),
                'backLabel' => 'Xem bộ câu mẫu',
            ], 403);
        }

        $sentences = $sentenceSet->sentences;
        $questionsByStep = array_filter([
            7 => $sentenceQuestions->fillIn($content->markedSentencesOf($sentences)),
            8 => $sentenceQuestions->write($content->writingSentencesOf($sentences, true)),
            9 => $sentenceQuestions->dictation($content->writingSentencesOf($sentences, false)),
        ]);
        $availableSteps = array_keys($questionsByStep);

        abort_if($availableSteps === [], 404);

        if (! isset($questionsByStep[$step])) {
            return redirect()->route('sentences.review', [$sentenceSet, $availableSteps[0]]);
        }

        $nextStep = collect($availableSteps)->first(fn (int $available): bool => $available > $step);

        return response()->view('sentences.review', [
            'sentenceSet' => $sentenceSet,
            'step' => $step,
            'hint' => LessonReviewController::STEPS[$step - 1]['hint'],
            'activeTab' => (int) array_search($step, $availableSteps, true),
            'steps' => array_map(fn (int $tabStep): array => [
                'label' => LessonReviewController::STEPS[$tabStep - 1]['title'],
                'href' => route('sentences.review', [$sentenceSet, $tabStep]),
                'number' => $tabStep,
            ], $availableSteps),
            'questions' => $questionsByStep[$step],
            'nextUrl' => $nextStep !== null ? route('sentences.review', [$sentenceSet, $nextStep]) : null,
            'sidebar' => $sidebar->for($sentenceSet),
        ]);
    }
}
