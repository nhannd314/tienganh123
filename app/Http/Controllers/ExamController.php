<?php

namespace App\Http\Controllers;

use App\Http\Requests\StartExamRequest;
use App\Http\Requests\StoreExamPronunciationRequest;
use App\Http\Requests\SubmitExamRequest;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamGroup;
use App\Services\AzurePronunciationAssessor;
use App\Services\ExamPaper;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

/**
 * An exam is started with the name and class of the student (and its password when it has one); the attempt is then
 * kept in the session until it is submitted, by the student or by the browser when the time is up. Answers that come
 * later than the grace period after the time is up are not taken.
 */
class ExamController extends Controller
{
    /**
     * How long a word, or a sentence, may be read into the microphone (the WAV must stay under 1 MB, see StoreExamPronunciationRequest).
     */
    private const WORD_RECORDING_MS = 4000;

    private const SENTENCE_RECORDING_MS = 15000;

    public function __construct(private ExamPaper $paper) {}

    /**
     * The exam with the form to start it, or a link to go on with the attempt in progress.
     */
    public function show(Exam $exam): View
    {
        $this->ensurePublished($exam);

        $attempt = $this->currentAttempt($exam);
        $sections = $this->paper->sections($exam, withOptions: false);

        return view('exams.show', [
            'exam' => $exam->loadMissing('course'),
            'attemptInProgress' => $attempt && ! $attempt->isSubmitted() && ! $attempt->isOverdue() ? $attempt : null,
            'groupCount' => count($sections),
            'questionCount' => collect($sections)->sum(fn (array $section): int => count($section['questions'])),
        ]);
    }

    public function start(StartExamRequest $request, Exam $exam): RedirectResponse
    {
        $this->ensurePublished($exam);

        $attempt = $this->currentAttempt($exam);

        if (! $attempt || $attempt->isSubmitted() || $attempt->isOverdue()) {
            $attempt = $exam->attempts()->create([
                'user_id' => $request->user()?->id,
                'student_name' => $request->validated('student_name'),
                'class_name' => $request->validated('class_name'),
                'started_at' => now(),
            ]);

            session([$this->attemptKey($exam) => $attempt->id]);
        }

        return redirect()->route('exams.take', $exam);
    }

    /**
     * The questions of the exam, with the time left. An attempt whose time is over is submitted as it is.
     */
    public function take(Exam $exam): View|RedirectResponse
    {
        $this->ensurePublished($exam);

        $attempt = $this->currentAttempt($exam);

        if (! $attempt) {
            return redirect()->route('exams.show', $exam);
        }

        if ($attempt->isOverdue() && ! $attempt->isSubmitted()) {
            $this->finish($attempt, []);
        }

        if ($attempt->isSubmitted()) {
            return redirect()->route('exams.result', $exam);
        }

        return view('exams.take', [
            'exam' => $exam,
            'attempt' => $attempt,
            'sections' => $this->paper->sections($exam),
            'secondsLeft' => $attempt->secondsLeft(),
            'pronunciationScores' => $attempt->pronunciation_scores ?? [],
            'pronunciationAttempts' => ExamPaper::PRONUNCIATION_ATTEMPTS,
            'listenLimit' => ExamPaper::LISTEN_LIMIT,
            'wordRecordingMs' => self::WORD_RECORDING_MS,
            'sentenceRecordingMs' => self::SENTENCE_RECORDING_MS,
        ]);
    }

    /**
     * Grade the answers and save the score. When the grace period is over the answers sent are not taken.
     */
    public function submit(SubmitExamRequest $request, Exam $exam): RedirectResponse
    {
        $this->ensurePublished($exam);

        $attempt = $this->currentAttempt($exam);

        if (! $attempt) {
            return redirect()->route('exams.show', $exam);
        }

        if (! $attempt->isSubmitted()) {
            $this->finish($attempt, $attempt->isOverdue() ? [] : ($request->validated('answers') ?? []));
        }

        return redirect()->route('exams.result', $exam);
    }

    public function result(Exam $exam): View|RedirectResponse
    {
        $this->ensurePublished($exam);

        $attempt = $this->currentAttempt($exam);

        if (! $attempt?->isSubmitted()) {
            return redirect()->route('exams.show', $exam);
        }

        $answersByGroup = collect($attempt->answers ?? [])->groupBy('group_id');

        return view('exams.result', [
            'exam' => $exam,
            'attempt' => $attempt,
            'groups' => $exam->groups->map(fn (ExamGroup $group): array => [
                'title' => $group->title,
                'total' => $answersByGroup->get($group->id)?->count() ?? 0,
                'correct' => $answersByGroup->get($group->id)?->where('correct', true)->count() ?? 0,
                'points' => round((float) $answersByGroup->get($group->id)?->sum('points'), 2),
            ])->filter(fn (array $group): bool => $group['total'] > 0)->values(),
        ]);
    }

    /**
     * Score one reading of a word (or a sentence) of a pronunciation group with Azure, out of 10. Each may be read
     * ExamPaper::PRONUNCIATION_ATTEMPTS times; its best score is kept in the attempt and counts when it is graded.
     */
    public function pronounce(StoreExamPronunciationRequest $request, Exam $exam, AzurePronunciationAssessor $assessor): JsonResponse
    {
        $this->ensurePublished($exam);

        $attempt = $this->currentAttempt($exam);

        if (! $attempt || $attempt->isSubmitted() || $attempt->isOverdue()) {
            return response()->json(['message' => 'Bài thi đã kết thúc.'], 422);
        }

        if (! $assessor->isConfigured()) {
            return response()->json(['message' => 'Chức năng chấm phát âm chưa được cấu hình.'], 503);
        }

        $group = $exam->groups()->whereKey($request->validated('group_id'))->first();
        $questionKey = (int) $request->validated('key');
        $text = $group ? $this->paper->spokenText($group, $questionKey) : null;

        if ($text === null) {
            return response()->json(['message' => 'Nội dung này không có trong bài thi.'], 422);
        }

        $key = ExamPaper::pronunciationKey($group, $questionKey);
        $attempts = $attempt->pronunciation_scores[$key]['attempts'] ?? 0;

        if ($attempts >= ExamPaper::PRONUNCIATION_ATTEMPTS) {
            return response()->json(['message' => 'Bạn đã đọc đủ số lần.'], 422);
        }

        try {
            $score = $assessor->assess($request->file('audio')->get(), $text);
        } catch (RequestException|ConnectionException $exception) {
            report($exception);

            return response()->json(['message' => 'Chưa chấm được, bạn đọc lại nhé.'], 502);
        }

        $saved = DB::transaction(function () use ($attempt, $key, $score): array {
            $attempt = ExamAttempt::query()->lockForUpdate()->findOrFail($attempt->id);
            $scores = $attempt->pronunciation_scores ?? [];
            $scores[$key] = [
                'best' => max($score, $scores[$key]['best'] ?? 0),
                'attempts' => ($scores[$key]['attempts'] ?? 0) + 1,
            ];
            $attempt->update(['pronunciation_scores' => $scores]);

            return $scores[$key];
        });

        return response()->json([
            'score' => $score,
            'best' => $saved['best'],
            'attemptsLeft' => max(0, ExamPaper::PRONUNCIATION_ATTEMPTS - $saved['attempts']),
        ]);
    }

    /**
     * @param  array<int|string, array<int|string, string|null>>  $answers
     */
    private function finish(ExamAttempt $attempt, array $answers): void
    {
        $graded = $this->paper->grade($attempt, $answers);

        $attempt->update([
            'score' => $graded['score'],
            'answers' => $graded['answers'],
            'submitted_at' => now(),
        ]);
    }

    /**
     * The attempt of this exam started in the session, if any.
     */
    private function currentAttempt(Exam $exam): ?ExamAttempt
    {
        $id = session($this->attemptKey($exam));

        return $id ? $exam->attempts()->find($id)?->setRelation('exam', $exam) : null;
    }

    private function attemptKey(Exam $exam): string
    {
        return "exams.{$exam->id}.attempt";
    }

    private function ensurePublished(Exam $exam): void
    {
        abort_unless($exam->is_published, 404);
    }
}
