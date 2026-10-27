<?php

namespace App\Http\Controllers;

use App\Events\LearnerAchieved;
use App\Http\Requests\StoreLessonReviewRequest;
use App\Http\Requests\StorePronunciationRequest;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonReview;
use App\Models\Sentence;
use App\Models\Unit;
use App\Models\Word;
use App\Services\AdvancedReviewContent;
use App\Services\AzurePronunciationAssessor;
use App\Services\Leaderboard;
use App\Services\SentenceQuestions;
use App\Services\WordChoiceQuestions;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

class LessonReviewController extends Controller
{
    /**
     * The parts that have questions so far: 1 (word → meaning), 2 (meaning → word), 3 (heard word → meaning),
     * 4 (meaning → typed word), 5 (word → spoken, scored by Azure), 6 (meaning → spoken word, scored by Azure) and
     * 7 (sentence with its marked words typed in) 8 (meaning of a sentence → the whole sentence typed), 9 (heard sentence → the whole sentence typed) and 10
     * (sentence → read aloud, scored by Azure) and 11 (heard passage → its marked words typed in, all at once).
     *
     * @var list<int>
     */
    private const BUILT_STEPS = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11];

    /**
     * The first part of the advanced review (parts 7 to 10); the parts before it make the basic review. The tabs show one of the two groups.
     */
    private const ADVANCED_FIRST_STEP = 7;

    /**
     * The sentence parts, where the questions are the sentences of the lesson and not its words: 7 fills in the marked
     * words, 8 and 9 write the whole sentence (from its meaning, or after hearing it).
     *
     * @var list<int>
     */
    private const SENTENCE_STEPS = [7, 8, 9];

    /**
     * The parts that work on whole sentences: typed in 8 and 9, read aloud in 10.
     *
     * @var list<int>
     */
    private const WRITING_STEPS = [8, 9, 10];

    /**
     * The part where a sentence is read into the microphone: its scores are kept apart from those of the words of parts 5 and 6.
     */
    private const SENTENCE_READING_STEP = 10;

    /**
     * Joins the words typed into one sentence in the answer sent by the browser (see resources/js/review.js).
     */
    private const SENTENCE_BLANK_SEPARATOR = '|';

    /**
     * The parts where the word is read into the microphone and scored by Azure.
     *
     * @var list<int>
     */
    private const PRONUNCIATION_STEPS = [5, 6, 10];

    /**
     * A word counts as read right in parts 5 and 6 from this score out of 10.
     */
    public const PRONUNCIATION_PASS_SCORE = 7;

    /**
     * How many times a word may be read in parts 5 and 6; its best score counts.
     */
    public const PRONUNCIATION_ATTEMPTS = 3;

    /**
     * How long a reading may last: a word, or a whole sentence (the WAV must stay under 1 MB, see StorePronunciationRequest).
     */
    private const WORD_RECORDING_MS = 4000;

    private const SENTENCE_RECORDING_MS = 15000;

    /**
     * The review parts, in order, with the instruction shown above the questions.
     *
     * @var list<array{title: string, hint: string}>
     */
    public const STEPS = [
        ['title' => 'Trả lời nghĩa', 'hint' => 'Chọn nghĩa đúng cho mỗi từ nhé!'],
        ['title' => 'Trả lời từ', 'hint' => 'Chọn từ tiếng Anh đúng nhé!'],
        ['title' => 'Nghe và trả lời nghĩa', 'hint' => 'Nghe thật kỹ rồi chọn nghĩa đúng nhé!'],
        ['title' => 'Điền từ tiếng Anh', 'hint' => 'Gõ từ tiếng Anh đúng vào các ô nhé!'],
        ['title' => 'Chấm điểm phát âm', 'hint' => 'Nghe mẫu, rồi bấm micro và đọc to từ này nhé!'],
        ['title' => 'Nhớ phát âm', 'hint' => 'Nhớ lại cách phát âm và chọn đáp án nhé!'],
        ['title' => 'Điền câu', 'hint' => 'Điền từ còn thiếu vào câu nhé!'],
        ['title' => 'Viết câu', 'hint' => 'Đọc nghĩa rồi viết lại cả câu tiếng Anh nhé!'],
        ['title' => 'Nghe câu', 'hint' => 'Nghe thật kỹ rồi viết lại cả câu nhé!'],
        ['title' => 'Đọc câu', 'hint' => 'Nghe mẫu, rồi bấm micro và đọc to cả câu này nhé!'],
        ['title' => 'Nghe đoạn văn', 'hint' => 'Nghe đoạn văn rồi điền từ còn thiếu nhé!'],
    ];

    /**
     * Show one part of the lesson review. Only the BUILT_STEPS have questions so far. An advanced part the lesson has
     * nothing for redirects to the first advanced part it has (when it has one).
     */
    public function show(Course $course, Unit $unit, Lesson $lesson, int $step): View|RedirectResponse
    {
        $this->ensurePublished($course, $unit, $lesson);

        $availableSteps = $lesson->reviewSteps();
        $advancedSteps = array_values(array_filter($availableSteps, fn (int $available): bool => $available >= self::ADVANCED_FIRST_STEP));

        if ($step >= self::ADVANCED_FIRST_STEP && $advancedSteps !== [] && ! in_array($step, $advancedSteps, true)) {
            return redirect()->route('lessons.review.show', [$course, $unit, $lesson, $advancedSteps[0]]);
        }

        if (in_array($step, self::PRONUNCIATION_STEPS, true)) {
            session()->forget($this->pronunciationKey($lesson, $step));
        }

        $tabSteps = $this->tabSteps($step, $advancedSteps);
        $completedSteps = $this->completedSteps($lesson);
        $nextStep = collect($availableSteps)->first(fn (int $available): bool => $available > $step);

        return view('lessons.review', [
            'course' => $course,
            'unit' => $unit,
            'lesson' => $lesson,
            'step' => $step,
            'stepInfo' => self::STEPS[$step - 1],
            'activeTab' => (int) array_search($step, $tabSteps, true),
            'steps' => array_map(fn (int $tabStep): array => [
                'label' => self::STEPS[$tabStep - 1]['title'],
                'href' => route('lessons.review.show', [$course, $unit, $lesson, $tabStep]),
                'number' => $tabStep,
            ], $tabSteps),
            'completedSteps' => array_keys(array_filter($tabSteps, fn (int $tabStep): bool => in_array($tabStep - 1, $completedSteps, true))),
            'advancedUrl' => $advancedSteps !== [] ? route('lessons.review.show', [$course, $unit, $lesson, $advancedSteps[0]]) : null,
            'progress' => auth()->user() ? $lesson->reviewProgress(auth()->user()) : null,
            'enrollCourse' => $course->needsEnrollmentBy(auth()->user()) ? $course : null,
            'canSave' => auth()->check(),
            'topLearners' => $this->lessonTopLearners($lesson),
            'standing' => auth()->user() ? $this->lessonStanding($lesson, auth()->user()) : null,
            'questions' => match ($step) {
                1 => app(WordChoiceQuestions::class)->meaning($lesson->reviewWords()),
                2 => app(WordChoiceQuestions::class)->word($lesson->reviewWords()),
                3 => app(WordChoiceQuestions::class)->listening($lesson->reviewWords()),
                4 => $this->spellingQuestions($lesson),
                5 => $this->pronunciationQuestions($lesson),
                6 => $this->recallQuestions($lesson),
                7 => app(SentenceQuestions::class)->fillIn(app(AdvancedReviewContent::class)->markedSentences($lesson)),
                8 => app(SentenceQuestions::class)->write(app(AdvancedReviewContent::class)->writingSentences($lesson, true)),
                9 => app(SentenceQuestions::class)->dictation(app(AdvancedReviewContent::class)->writingSentences($lesson, false)),
                10 => $this->sentenceReadingQuestions($lesson),
                11 => $this->passageQuestions($lesson),
                default => [],
            },
            'isBuilt' => in_array($step, self::BUILT_STEPS, true),
            'nextUrl' => $nextStep !== null ? route('lessons.review.show', [$course, $unit, $lesson, $nextStep]) : null,
            'submitUrl' => route('lessons.review.store', [$course, $unit, $lesson, $step]),
            'passage' => $step === 11 ? app(AdvancedReviewContent::class)->passageParagraphs($lesson) : [],
            'passageAudioUrl' => $lesson->passage_audio_url,
            'maxRecordingMs' => $step === self::SENTENCE_READING_STEP ? self::SENTENCE_RECORDING_MS : self::WORD_RECORDING_MS,
            'pronounceUrl' => route('lessons.review.pronounce', [$course, $unit, $lesson]),
        ]);
    }

    /**
     * Save the finished part. Correctness is recomputed from the stored meanings, never trusted from the browser; in
     * parts 5 and 6 it comes from the scores Azure gave this session (see pronounce).
     */
    public function store(StoreLessonReviewRequest $request, Course $course, Unit $unit, Lesson $lesson, int $step, Leaderboard $leaderboard): JsonResponse
    {
        $this->ensurePublished($course, $unit, $lesson);
        abort_unless(in_array($step, self::BUILT_STEPS, true), 404);

        $isSentencePart = in_array($step, self::SENTENCE_STEPS, true);
        $words = match (true) {
            $step === 11 => collect(app(AdvancedReviewContent::class)->passageBlanks($lesson))->map(fn (string $word, int $id): array => ['id' => $id, 'word' => $word])->keyBy('id'),
            in_array($step, self::WRITING_STEPS, true) => collect(app(AdvancedReviewContent::class)->writingSentences($lesson, $step === 8))->keyBy('id'),
            $isSentencePart => collect(app(AdvancedReviewContent::class)->markedSentences($lesson))->keyBy('id'),
            default => $lesson->reviewWords()->keyBy('id'),
        };
        $answers = collect($request->validated('answers'));

        if ($answers->count() !== $words->count() || $answers->contains(fn (array $answer): bool => ! $words->has($answer['word_id']))) {
            return response()->json(['message' => 'The answers do not match the questions of this lesson.'], 422);
        }

        $scores = session($this->pronunciationKey($lesson, $step), []);

        $graded = $answers->map(function (array $answer) use ($words, $step, $scores, $isSentencePart): array {
            if (in_array($step, self::PRONUNCIATION_STEPS, true)) {
                $best = $scores[$answer['word_id']]['best'] ?? 0;

                return ['word_id' => (int) $answer['word_id'], 'selected' => (string) $best, 'correct' => $best >= self::PRONUNCIATION_PASS_SCORE];
            }

            return [
                'word_id' => (int) $answer['word_id'],
                'selected' => $answer['selected'],
                'correct' => match (true) {
                    in_array($step, self::WRITING_STEPS, true) => $this->normalizeSentence($words[$answer['word_id']]['sentence']) === $this->normalizeSentence($answer['selected']),
                    $step === 11 => $this->normalizeBlank($words[$answer['word_id']]['word']) === $this->normalizeBlank($answer['selected']),
                    $isSentencePart => $this->isSentenceCorrect($words[$answer['word_id']], $answer['selected']),
                    default => $this->isCorrect($words[$answer['word_id']], $step, $answer['selected']),
                },
            ];
        })->values();

        $user = $request->user();
        $wasPassed = $user !== null && $lesson->reviews()->passed()->where('user_id', $user->id)->where('step', $step)->exists();

        $review = LessonReview::create([
            'lesson_id' => $lesson->id,
            'user_id' => $request->user()?->id,
            'step' => $step,
            'total' => $graded->count(),
            'correct' => $graded->where('correct', true)->count(),
            'duration_seconds' => $request->validated('duration'),
            'answers' => $graded->all(),
        ]);

        if (in_array($step, self::PRONUNCIATION_STEPS, true)) {
            session()->forget($this->pronunciationKey($lesson, $step));
        }

        if ($user) {
            $best = $lesson->recordScore($user);
            $isNewBest = $best->score > 0 && ($best->wasRecentlyCreated || $best->wasChanged('score'));

            if ($review->isPassed() && ! $wasPassed) {
                LearnerAchieved::reviewCompleted($user, $lesson, $review)->announce();
            } elseif ($isNewBest) {
                LearnerAchieved::bestScore($user, $lesson, $best->score)->announce();
            }

            if ($isNewBest) {
                $leaderboard->refreshAfterScoreOf($user);
            }
        }

        return response()->json([
            'total' => $review->total,
            'correct' => $review->correct,
            'progress' => $lesson->reviewProgress($user),
            'standing' => $user ? $this->lessonStanding($lesson, $user) : null,
        ], 201);
    }

    /**
     * Score one reading of a word of part 5 or 6 with Azure, out of 10. A word may be read PRONUNCIATION_ATTEMPTS times;
     * its best score is kept in the session until the part is saved, so the browser never says how well it was read.
     */
    public function pronounce(StorePronunciationRequest $request, Course $course, Unit $unit, Lesson $lesson, AzurePronunciationAssessor $assessor): JsonResponse
    {
        $this->ensurePublished($course, $unit, $lesson);

        if (! $assessor->isConfigured()) {
            return response()->json(['message' => 'Chức năng chấm phát âm chưa được cấu hình.'], 503);
        }

        $step = (int) ($request->validated('step') ?? 5);
        $id = (int) $request->validated('word_id');

        if ($step === self::SENTENCE_READING_STEP) {
            $text = collect(app(AdvancedReviewContent::class)->writingSentences($lesson, false))->firstWhere('id', $id)['sentence'] ?? null;
        } else {
            $text = $lesson->reviewWords()->firstWhere('id', $id)?->word;
        }

        if ($text === null) {
            return response()->json(['message' => 'Nội dung này không có trong bài ôn tập.'], 422);
        }

        $key = $this->pronunciationKey($lesson, $step);
        $scores = session($key, []);
        $attempts = $scores[$id]['attempts'] ?? 0;

        if ($attempts >= self::PRONUNCIATION_ATTEMPTS) {
            return response()->json(['message' => 'Bạn đã đọc từ này đủ số lần.'], 422);
        }

        try {
            $score = $assessor->assess($request->file('audio')->get(), $text);
        } catch (RequestException|ConnectionException $exception) {
            report($exception);

            return response()->json(['message' => 'Chưa chấm được, bạn đọc lại nhé.'], 502);
        }

        $best = max($score, $scores[$id]['best'] ?? 0);
        $scores[$id] = ['best' => $best, 'attempts' => $attempts + 1];
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
     * Whether the words typed into a sentence (joined by SENTENCE_BLANK_SEPARATOR, in order) are all its marked words, in any case.
     *
     * @param  array{id: int, segments: list<array{text: string, isMarked: bool}>, translation: string|null}  $sentence
     */
    private function isSentenceCorrect(array $sentence, string $selected): bool
    {
        $expected = collect($sentence['segments'])->where('isMarked', true)->pluck('text')
            ->map(fn (string $text): string => mb_strtolower(Str::squish($text)))
            ->all();
        $typed = array_map(fn (string $text): string => mb_strtolower(Str::squish($text)), explode(self::SENTENCE_BLANK_SEPARATOR, $selected));

        return $typed === $expected;
    }

    /**
     * A word typed into a blank as it is compared: any case, one space between the words.
     */
    private function normalizeBlank(string $word): string
    {
        return mb_strtolower(Str::squish($word));
    }

    /**
     * A sentence as it is compared when written: any case, no punctuation, one space between the words.
     */
    private function normalizeSentence(string $sentence): string
    {
        $plain = str_replace(['’', '‘'], "'", Lesson::spokenSentence($sentence));

        return mb_strtolower(Str::squish(preg_replace('/[^\p{L}\p{N}\' ]+/u', ' ', $plain)));
    }

    /**
     * The parts shown as tabs: the basic parts together, or the advanced parts the lesson has (all of them when it has none, each telling it has no questions).
     *
     * @param  list<int>  $advancedSteps
     * @return list<int>
     */
    private function tabSteps(int $step, array $advancedSteps): array
    {
        if ($step < self::ADVANCED_FIRST_STEP) {
            return range(1, self::ADVANCED_FIRST_STEP - 1);
        }

        return $advancedSteps !== [] ? $advancedSteps : range(self::ADVANCED_FIRST_STEP, count(self::STEPS));
    }

    /**
     * Session key of the scores of the lesson, word id (sentence id in part 10) => best score and attempts: one for parts 5 and 6, one for part 10.
     */
    private function pronunciationKey(Lesson $lesson, int $step): string
    {
        $kind = $step === self::SENTENCE_READING_STEP ? 'sentences' : 'words';

        return "lesson-review.pronunciation.{$kind}.{$lesson->id}";
    }

    private function ensurePublished(Course $course, Unit $unit, Lesson $lesson): void
    {
        abort_unless($course->is_published && $lesson->is_published, 404);
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
     * Part 6 questions: all the meanings of a word; the English word is read from memory, so it is neither shown nor
     * played. `word` is revealed once the part is over for that word.
     *
     * @return list<array{id: int, prompt: string, phonetic: null, word: string}>
     */
    private function recallQuestions(Lesson $lesson): array
    {
        return $lesson->reviewWords()->map(fn (Word $word): array => [
            'id' => $word->id,
            'prompt' => $word->allMeaningsLabel(),
            'phonetic' => null,
            'word' => $word->word,
        ])->all();
    }

    /**
     * Part 11 questions: one per word to fill in across the passage, which is shown whole with an input for each (`word` is
     * compared on the server, and shown once the part is graded).
     *
     * @return list<array{id: int, prompt: string, phonetic: null, word: string}>
     */
    private function passageQuestions(Lesson $lesson): array
    {
        $questions = [];

        foreach (app(AdvancedReviewContent::class)->passageBlanks($lesson) as $id => $word) {
            $questions[] = ['id' => $id, 'prompt' => $word, 'phonetic' => null, 'word' => $word];
        }

        return $questions;
    }

    /**
     * Part 10 questions: the sentence to read, which may be heard first.
     *
     * @return list<array{id: int, prompt: string, phonetic: null, audio: string|null}>
     */
    private function sentenceReadingQuestions(Lesson $lesson): array
    {
        return array_map(fn (array $sentence): array => [
            'id' => $sentence['id'],
            'prompt' => $sentence['sentence'],
            'phonetic' => null,
            'audio' => $sentence['audio'],
        ], app(AdvancedReviewContent::class)->writingSentences($lesson, false));
    }

    /**
     * Part 5 questions: the word to read, with its IPA (it may be heard first).
     *
     * @return list<array{id: int, prompt: string, phonetic: string|null, audio: string|null}>
     */
    private function pronunciationQuestions(Lesson $lesson): array
    {
        return $lesson->reviewWords()->map(fn (Word $word): array => [
            'id' => $word->id,
            'prompt' => $word->word,
            'phonetic' => $word->ipa_us,
            'audio' => $word->audio_us_url,
        ])->all();
    }
}
