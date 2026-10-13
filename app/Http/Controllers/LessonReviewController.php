<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLessonReviewRequest;
use App\Http\Requests\StorePronunciationRequest;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonReview;
use App\Models\Unit;
use App\Models\Word;
use App\Services\AzurePronunciationAssessor;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class LessonReviewController extends Controller
{
    private const OPTIONS_PER_QUESTION = 4;

    /**
     * The parts that have questions so far: 1 (word → meaning), 2 (meaning → word), 3 (heard word → meaning),
     * 4 (meaning → typed word) and 5 (word → spoken, scored by Azure).
     *
     * @var list<int>
     */
    private const BUILT_STEPS = [1, 2, 3, 4, 5];

    private const PRONUNCIATION_STEP = 5;

    /**
     * A word counts as read right in part 5 from this score out of 10.
     */
    public const PRONUNCIATION_PASS_SCORE = 7;

    /**
     * How many times a word may be read in part 5; its best score counts.
     */
    public const PRONUNCIATION_ATTEMPTS = 3;

    /**
     * The review parts, in order, with the instruction shown above the questions.
     *
     * @var list<array{title: string, hint: string}>
     */
    private const STEPS = [
        ['title' => 'Trả lời nghĩa', 'hint' => 'Chọn nghĩa đúng cho mỗi từ nhé!'],
        ['title' => 'Trả lời từ', 'hint' => 'Chọn từ tiếng Anh đúng nhé!'],
        ['title' => 'Nghe và trả lời nghĩa', 'hint' => 'Nghe thật kỹ rồi chọn nghĩa đúng nhé!'],
        ['title' => 'Điền từ tiếng Anh', 'hint' => 'Gõ từ tiếng Anh đúng vào các ô nhé!'],
        ['title' => 'Chấm điểm phát âm', 'hint' => 'Nghe mẫu, rồi bấm micro và đọc to từ này nhé!'],
        ['title' => 'Nhớ phát âm', 'hint' => 'Nhớ lại cách phát âm và chọn đáp án nhé!'],
    ];

    /**
     * Show one part of the lesson review. Only the BUILT_STEPS have questions so far.
     */
    public function show(Course $course, Unit $unit, Lesson $lesson, int $step): View
    {
        $this->ensurePublished($course, $unit, $lesson);

        if ($step === self::PRONUNCIATION_STEP) {
            session()->forget($this->pronunciationKey($lesson));
        }

        return view('lessons.review', [
            'course' => $course,
            'unit' => $unit,
            'lesson' => $lesson,
            'step' => $step,
            'stepInfo' => self::STEPS[$step - 1],
            'steps' => collect(self::STEPS)->map(fn (array $info, int $index): array => [
                'label' => $info['title'],
                'href' => route('lessons.review.show', [$course, $unit, $lesson, $index + 1]),
            ])->all(),
            'completedSteps' => $this->completedSteps($lesson),
            'progress' => auth()->user() ? $lesson->reviewProgress(auth()->user()) : null,
            'enrollCourse' => $course->needsEnrollmentBy(auth()->user()) ? $course : null,
            'canSave' => auth()->check(),
            'topLearners' => $this->sampleTopLearners(),
            'questions' => match ($step) {
                1 => $this->meaningQuestions($lesson),
                2 => $this->wordQuestions($lesson),
                3 => $this->listeningQuestions($lesson),
                4 => $this->spellingQuestions($lesson),
                5 => $this->pronunciationQuestions($lesson),
                default => [],
            },
            'isBuilt' => in_array($step, self::BUILT_STEPS, true),
            'nextUrl' => $step < count(self::STEPS) ? route('lessons.review.show', [$course, $unit, $lesson, $step + 1]) : null,
            'submitUrl' => route('lessons.review.store', [$course, $unit, $lesson, $step]),
            'pronounceUrl' => route('lessons.review.pronounce', [$course, $unit, $lesson]),
        ]);
    }

    /**
     * Save the finished part. Correctness is recomputed from the stored meanings, never trusted from the browser; in
     * part 5 it comes from the scores Azure gave this session (see pronounce).
     */
    public function store(StoreLessonReviewRequest $request, Course $course, Unit $unit, Lesson $lesson, int $step): JsonResponse
    {
        $this->ensurePublished($course, $unit, $lesson);
        abort_unless(in_array($step, self::BUILT_STEPS, true), 404);

        $words = $lesson->reviewWords()->keyBy('id');
        $answers = collect($request->validated('answers'));

        if ($answers->count() !== $words->count() || $answers->contains(fn (array $answer): bool => ! $words->has($answer['word_id']))) {
            return response()->json(['message' => 'The answers do not match the questions of this lesson.'], 422);
        }

        $scores = session($this->pronunciationKey($lesson), []);

        $graded = $answers->map(function (array $answer) use ($words, $step, $scores): array {
            if ($step === self::PRONUNCIATION_STEP) {
                $best = $scores[$answer['word_id']]['best'] ?? 0;

                return ['word_id' => (int) $answer['word_id'], 'selected' => (string) $best, 'correct' => $best >= self::PRONUNCIATION_PASS_SCORE];
            }

            return [
                'word_id' => (int) $answer['word_id'],
                'selected' => $answer['selected'],
                'correct' => $this->isCorrect($words[$answer['word_id']], $step, $answer['selected']),
            ];
        })->values();

        $review = LessonReview::create([
            'lesson_id' => $lesson->id,
            'user_id' => $request->user()?->id,
            'step' => $step,
            'total' => $graded->count(),
            'correct' => $graded->where('correct', true)->count(),
            'duration_seconds' => $request->validated('duration'),
            'answers' => $graded->all(),
        ]);

        if ($step === self::PRONUNCIATION_STEP) {
            session()->forget($this->pronunciationKey($lesson));
        }

        return response()->json([
            'total' => $review->total,
            'correct' => $review->correct,
            'progress' => $lesson->reviewProgress($request->user()),
        ], 201);
    }

    /**
     * Score one reading of a word of part 5 with Azure, out of 10. A word may be read PRONUNCIATION_ATTEMPTS times;
     * its best score is kept in the session until the part is saved, so the browser never says how well it was read.
     */
    public function pronounce(StorePronunciationRequest $request, Course $course, Unit $unit, Lesson $lesson, AzurePronunciationAssessor $assessor): JsonResponse
    {
        $this->ensurePublished($course, $unit, $lesson);

        if (! $assessor->isConfigured()) {
            return response()->json(['message' => 'Chức năng chấm phát âm chưa được cấu hình.'], 503);
        }

        $word = $lesson->reviewWords()->firstWhere('id', (int) $request->validated('word_id'));

        if ($word === null) {
            return response()->json(['message' => 'Từ này không có trong bài ôn tập.'], 422);
        }

        $key = $this->pronunciationKey($lesson);
        $scores = session($key, []);
        $attempts = $scores[$word->id]['attempts'] ?? 0;

        if ($attempts >= self::PRONUNCIATION_ATTEMPTS) {
            return response()->json(['message' => 'Bạn đã đọc từ này đủ số lần.'], 422);
        }

        try {
            $score = $assessor->assess($request->file('audio')->get(), $word->word);
        } catch (RequestException|ConnectionException $exception) {
            report($exception);

            return response()->json(['message' => 'Chưa chấm được, bạn đọc lại nhé.'], 502);
        }

        $best = max($score, $scores[$word->id]['best'] ?? 0);
        $scores[$word->id] = ['best' => $best, 'attempts' => $attempts + 1];
        session([$key => $scores]);

        return response()->json([
            'score' => $score,
            'best' => $best,
            'passed' => $best >= self::PRONUNCIATION_PASS_SCORE,
            'attemptsLeft' => self::PRONUNCIATION_ATTEMPTS - $attempts - 1,
        ]);
    }

    /**
     * Part 2 takes the word itself, part 4 the word typed in any case, the other parts all the meanings of the word
     * (or, from older pages, any one).
     */
    private function isCorrect(Word $word, int $step, string $selected): bool
    {
        if ($step === 2) {
            return $selected === $word->word;
        }

        if ($step === 4) {
            return mb_strtolower(Str::squish($selected)) === mb_strtolower(Str::squish($word->word));
        }

        return in_array($selected, [$word->allMeaningsLabel(), ...$word->meaningLabels()], true);
    }

    /**
     * Session key of the part 5 scores of the lesson: word id => best score and attempts.
     */
    private function pronunciationKey(Lesson $lesson): string
    {
        return "lesson-review.pronunciation.{$lesson->id}";
    }

    private function ensurePublished(Course $course, Unit $unit, Lesson $lesson): void
    {
        abort_unless($course->is_published && $unit->is_published && $lesson->is_published, 404);
    }

    /**
     * Parts the signed-in user has already completed (see LessonReview::isPassed), as 0-based indexes.
     *
     * @return list<int>
     */
    private function completedSteps(Lesson $lesson): array
    {
        if (! auth()->check()) {
            return [];
        }

        return $lesson->reviews()
            ->passed()
            ->where('user_id', auth()->id())
            ->pluck('step')
            ->unique()
            ->map(fn (int $step): int => $step - 1)
            ->values()
            ->all();
    }

    /**
     * Part 1 questions: a word and all its meanings among the meanings of other words.
     *
     * @return list<array{id: int, prompt: string, phonetic: string|null, options: list<string>, answer: int}>
     */
    private function meaningQuestions(Lesson $lesson): array
    {
        $words = $lesson->reviewWords();

        if ($words->isEmpty()) {
            return [];
        }

        $extraLabels = Word::query()
            ->whereNotIn('id', $words->pluck('id'))
            ->inRandomOrder()
            ->limit(30)
            ->get()
            ->toBase()
            ->map(fn (Word $word): string => $word->allMeaningsLabel())
            ->filter();

        return $words->map(function (Word $word) use ($words, $extraLabels): array {
            $correct = $word->allMeaningsLabel();

            $distractors = $words
                ->toBase()
                ->reject(fn (Word $other): bool => $other->is($word))
                ->map(fn (Word $other): string => $other->allMeaningsLabel())
                ->shuffle()
                ->concat($extraLabels->shuffle())
                ->unique()
                ->reject(fn (string $label): bool => $label === $correct)
                ->take(self::OPTIONS_PER_QUESTION - 1);

            $options = $distractors->push($correct)->shuffle()->values();

            return [
                'id' => $word->id,
                'prompt' => $word->word,
                'phonetic' => $word->ipa_us,
                'options' => $options->all(),
                'answer' => $options->search($correct),
            ];
        })->all();
    }

    /**
     * Part 3 questions: those of part 1, but the word is only heard (`prompt` is what is spoken), so no IPA is given.
     *
     * @return list<array{id: int, prompt: string, phonetic: null, options: list<string>, answer: int}>
     */
    private function listeningQuestions(Lesson $lesson): array
    {
        return array_map(fn (array $question): array => [...$question, 'phonetic' => null], $this->meaningQuestions($lesson));
    }

    /**
     * Part 2 questions: all the meanings of a word and the word among other words, of the lesson first.
     *
     * @return list<array{id: int, prompt: string, phonetic: null, options: list<string>, answer: int}>
     */
    private function wordQuestions(Lesson $lesson): array
    {
        $words = $lesson->reviewWords();

        if ($words->isEmpty()) {
            return [];
        }

        $extraWords = Word::query()
            ->whereNotIn('id', $words->pluck('id'))
            ->inRandomOrder()
            ->limit(30)
            ->pluck('word');

        return $words->map(function (Word $word) use ($words, $extraWords): array {
            $distractors = $words
                ->reject(fn (Word $other): bool => $other->is($word))
                ->pluck('word')
                ->shuffle()
                ->concat($extraWords->shuffle())
                ->unique(fn (string $other): string => mb_strtolower($other))
                ->reject(fn (string $other): bool => mb_strtolower($other) === mb_strtolower($word->word))
                ->take(self::OPTIONS_PER_QUESTION - 1);

            $options = $distractors->push($word->word)->shuffle()->values();

            return [
                'id' => $word->id,
                'prompt' => $word->allMeaningsLabel(),
                'phonetic' => null,
                'options' => $options->all(),
                'answer' => $options->search($word->word),
            ];
        })->all();
    }

    /**
     * Part 4 questions: all the meanings of a word, and the word to type letter by letter.
     *
     * @return list<array{id: int, prompt: string, phonetic: null, word: string}>
     */
    private function spellingQuestions(Lesson $lesson): array
    {
        return $lesson->reviewWords()->map(fn (Word $word): array => [
            'id' => $word->id,
            'prompt' => $word->allMeaningsLabel(),
            'phonetic' => null,
            'word' => $word->word,
        ])->all();
    }

    /**
     * Part 5 questions: the word to read, with its IPA (it may be heard first).
     *
     * @return list<array{id: int, prompt: string, phonetic: string|null}>
     */
    private function pronunciationQuestions(Lesson $lesson): array
    {
        return $lesson->reviewWords()->map(fn (Word $word): array => [
            'id' => $word->id,
            'prompt' => $word->word,
            'phonetic' => $word->ipa_us,
        ])->all();
    }
}
