@php
    $total = count($questions);
    $firstQuestion = $questions[0] ?? null;

    $config = [
        'questions' => $questions,
        'submitUrl' => $submitUrl,
        'csrf' => csrf_token(),
        'canSave' => $canSave,
        'optionsLang' => $step === 2 ? 'en' : null,
        'autoListen' => $step === 3,
        'mode' => match ($step) {
            4 => 'spell',
            5, 6 => 'speak',
            default => 'choice',
        },
        'recordHint' => $step === 6 ? 'Bấm micro rồi đọc to từ tiếng Anh của nghĩa này nhé!' : null,
        'pronounceUrl' => $pronounceUrl,
        'pronunciationPassScore' => \App\Http\Controllers\LessonReviewController::PRONUNCIATION_PASS_SCORE,
        'passPercent' => \App\Models\LessonReview::PASS_PERCENT,
        'autoAdvanceMs' => 2000,
    ];
@endphp

<x-layouts.app :title="'Ôn tập '.$lesson->title" main-class="flex flex-col gap-6 pt-6 pb-10">
    <x-slot:header>
        <x-site-header active="courses" />
    </x-slot:header>

    <x-breadcrumbs :items="[
        ['label' => 'Trang chủ', 'href' => route('home')],
        ['label' => 'Khóa học', 'href' => route('courses.index')],
        ['label' => $course->title, 'href' => route('courses.show', $course)],
        ['label' => $lesson->title, 'href' => route('lessons.show', [$course, $unit, $lesson])],
        ['label' => 'Ôn tập'],
    ]" />

    <div class="flex flex-wrap items-start gap-5">
        <section
            @if ($firstQuestion) data-review @endif
            class="min-w-0 flex-[999_1_600px] overflow-hidden rounded-lg bg-surface-card shadow-card"
        >
            <div class="flex flex-col gap-2 px-6 pt-6 sm:px-8">
                <h1 class="type-h1 text-balance text-fg-strong">Ôn tập {{ $lesson->title }}</h1>
                <p class="type-body-lg text-fg-muted">{{ $stepInfo['hint'] }}</p>
            </div>

            <div class="overflow-x-auto border-b border-line-subtle px-6 py-5 sm:px-8">
                <x-step-tabs :steps="$steps" :active="$step - 1" :completed="$completedSteps" label="Các phần ôn tập" class="min-w-136" />
            </div>

            @if (! $isBuilt)
                <div class="p-6 sm:p-8">
                    <x-alert tone="info" title="Phần này đang được xây dựng">Bạn quay lại sau nhé, hoặc làm phần 1 trước.</x-alert>
                </div>
            @elseif ($firstQuestion === null)
                <div class="p-6 sm:p-8">
                    <x-alert tone="info" title="Chưa có câu hỏi">Bài học này chưa có từ vựng để ôn tập.</x-alert>
                </div>
            @else
                {{-- Status --}}
                <div class="flex flex-col gap-5 px-6 py-6 sm:px-8">
                    <div class="flex flex-wrap items-center justify-between gap-5">
                        <span class="type-label-lg text-fg-strong">Câu <span data-q-current>1</span> / {{ $total }}</span>

                        <div class="flex flex-wrap items-center gap-3">
                            <div
                                role="timer"
                                aria-label="Thời gian làm bài"
                                data-timer-pill
                                class="flex h-10 items-center gap-2 rounded-full border border-primary-border bg-primary-bg px-4 text-primary-text"
                            >
                                <x-icon name="timer" />
                                <span data-timer class="type-label-lg tabular-nums">00:00</span>
                            </div>

                            <x-pill tone="neutral">
                                <x-icon name="circle-check" class="text-success" />
                                Điểm: <b data-correct-count class="font-semibold text-fg-score">0</b>
                            </x-pill>
                        </div>
                    </div>

                    <x-progress-bar :value="0" :max="$total" label="Số câu đã hoàn thành" data-part-bar />
                </div>

                {{-- Question --}}
                <div class="p-6 sm:p-8">
                    <div data-play class="flex flex-col gap-8">
                        <div data-prompt class="flex justify-center">
                            @if (in_array($step, [1, 5], true))
                                <x-word-prompt :word="$firstQuestion['prompt']" :phonetic="$firstQuestion['phonetic']" :replay="false" />
                            @elseif (in_array($step, [2, 4, 6], true))
                                {{-- The meaning to find the word for; no listen button, it would give the answer away --}}
                                <div data-prompt-word class="type-h1 text-center text-balance whitespace-pre-line text-fg-strong">{{ $firstQuestion['prompt'] }}</div>
                            @else
                                {{-- The word is only heard: neither its text nor its IPA is shown --}}
                                <div class="flex flex-col items-center gap-3">
                                    <x-audio-button :text="$firstQuestion['prompt']" label="Nghe từ" tone="primary" class="size-24!" data-prompt-listen />
                                    <span class="type-body text-fg-muted">Bấm để nghe lại</span>
                                </div>
                            @endif
                        </div>

                        @if (in_array($step, [5, 6], true))
                            {{-- Record the word; the server has Azure score it out of 10 --}}
                            <div class="flex flex-col items-center gap-4 text-center">
                                <button
                                    type="button"
                                    data-record
                                    aria-label="Bấm để đọc"
                                    class="flex size-24 cursor-pointer items-center justify-center rounded-full bg-secondary text-on-secondary shadow-card transition-interactive not-disabled:hover:brightness-92 not-disabled:active:scale-94 disabled:cursor-not-allowed disabled:bg-surface-disabled disabled:text-fg-disabled data-recording:animate-pulse data-recording:bg-danger data-recording:text-on-danger"
                                >
                                    <x-icon name="mic" size="xl" />
                                </button>
                                <p data-record-status class="type-body-lg text-fg-muted">{{ $step === 6 ? 'Bấm micro rồi đọc to từ tiếng Anh của nghĩa này nhé!' : 'Bấm micro rồi đọc to từ này nhé!' }}</p>
                                <p data-record-score hidden class="type-h1"><span data-record-score-value>0</span>/10</p>
                            </div>
                        @elseif ($step === 4)
                            {{-- One box per letter, like a one-time code, graded once all are filled; spaces and hyphens of the word are shown as they are --}}
                            <div data-slots lang="en" class="flex flex-wrap justify-center gap-x-8 gap-y-3"></div>
                        @else
                            <div data-answers class="grid grid-cols-1 gap-4 sm:grid-cols-2"></div>
                        @endif

                        <div data-alert aria-live="polite" class="flex min-h-14 items-center justify-center sm:min-h-7"></div>
                    </div>

                    {{-- Shown in place of the questions once the last one has been answered --}}
                    <div data-result hidden class="flex flex-col items-center justify-center gap-6 text-center">
                        <div class="flex flex-col gap-3">
                            <h2 data-result-passed class="type-h1 text-success-text">Hoàn thành.</h2>
                            <h2 data-result-failed hidden class="type-h1 text-danger-text">Chưa đạt.</h2>
                            <p class="type-body-lg text-fg-body">Bạn đã trả lời đúng <b data-result-correct class="font-semibold text-fg-strong">0</b>/{{ $total }} câu.</p>
                            <p data-result-failed hidden class="type-body text-fg-muted">Cần trả lời đúng từ {{ \App\Models\LessonReview::PASS_PERCENT }}% số câu để hoàn thành phần này. Làm lại nhé!</p>
                        </div>

                        <div class="flex flex-wrap justify-center gap-4">
                            <x-button variant="accent" size="lg" icon-left="rotate-ccw" data-review-restart>Làm lại từ đầu</x-button>

                            @if ($nextUrl)
                                <x-button variant="secondary" size="lg" icon-right="chevrons-right" :href="$nextUrl">Phần tiếp theo</x-button>
                            @endif
                        </div>
                    </div>
                </div>

                <template data-tpl="answer"><x-answer-card :indicator="false" class="min-h-22! rounded-lg! border-3! px-5! py-4!"><span data-text class="whitespace-pre-line"></span></x-answer-card></template>
                <template data-tpl="answer-correct"><x-answer-card state="correct" :indicator="false" class="min-h-22! rounded-lg! border-3! border-success! px-5! py-4!"><span data-text class="whitespace-pre-line"></span></x-answer-card></template>
                <template data-tpl="answer-wrong"><x-answer-card state="wrong" :indicator="false" class="min-h-22! rounded-lg! border-3! border-danger! px-5! py-4!"><span data-text class="whitespace-pre-line"></span></x-answer-card></template>
                <template data-tpl="letter"><input type="text" data-letter autocomplete="off" autocapitalize="off" autocorrect="off" spellcheck="false" class="size-13 rounded-lg border-3 border-line-default bg-surface-card text-center type-h1 text-fg-strong outline-none transition-interactive focus:border-primary focus:shadow-focus disabled:cursor-default sm:size-18"></template>
                <template data-tpl="letter-fixed"><span class="flex h-13 w-5 items-center justify-center type-h1 text-fg-muted sm:h-18"></span></template>
                <template data-tpl="alert-speak-low"><p class="text-center type-label-lg text-warning-text">Cố lên, lần sau sẽ tốt hơn!</p></template>
                <template data-tpl="alert-success"><p class="text-center type-label-lg text-success-text">Chính xác! Bạn giỏi quá!</p></template>
                <template data-tpl="alert-danger"><p class="text-center type-label-lg text-danger-text">Chưa đúng rồi. Đáp án đúng là: <b data-correct-text class="font-semibold whitespace-pre-line"></b></p></template>

                <script type="application/json" data-review-config>{!! json_encode($config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) !!}</script>
            @endif
        </section>

        @if ($firstQuestion)
            <x-lesson-sidebar :enroll-course="$enrollCourse" :progress="$progress" :top-learners="$topLearners" :score="$standing['score'] ?? 0" :rank="$standing['rank'] ?? '—'" class="flex-[1_1_340px] gap-5" />
        @endif
    </div>

    <x-slot:scripts>
        @if ($firstQuestion)
            @vite('resources/js/review.js')
        @endif
    </x-slot:scripts>
</x-layouts.app>
