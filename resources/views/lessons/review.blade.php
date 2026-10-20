@php
    $total = count($questions);
    $firstQuestion = $questions[0] ?? null;

    $config = [
        'questions' => $questions,
        'submitUrl' => $submitUrl,
        'csrf' => csrf_token(),
        'canSave' => $canSave,
        'optionsLang' => $step === 2 ? 'en' : null,
        'autoListen' => in_array($step, [3, 9], true),
        'mode' => match ($step) {
            4 => 'spell',
            5, 6, 10 => 'speak',
            7 => 'sentence',
            11 => 'passage',
            8, 9 => 'write',
            default => 'choice',
        },
        'recordHint' => match ($step) {
            6 => 'Bấm micro rồi đọc to từ tiếng Anh của nghĩa này nhé!',
            10 => 'Bấm micro rồi đọc to cả câu này nhé!',
            default => null,
        },
        'step' => $step,
        'maxRecordingMs' => $maxRecordingMs,
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
                <h1 class="type-page-title tracking-tight text-balance text-fg-strong">Ôn tập {{ $lesson->title }}</h1>
                <p class="type-body-lg text-fg-muted">{{ $stepInfo['hint'] }}</p>
            </div>

            <div class="overflow-x-auto border-b border-line-subtle px-6 py-5 sm:px-8">
                <x-step-tabs :steps="$steps" :active="$activeTab" :completed="$completedSteps" label="Các phần ôn tập" :class="count($steps) > 4 ? 'min-w-136' : 'min-w-96'" />
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
                        <span class="type-label-lg text-fg-strong">
                            @if ($step === 11)
                                Điền {{ $total }} từ còn thiếu
                            @else
                                Câu <span data-q-current>1</span> / {{ $total }}
                            @endif
                        </span>

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
                            @if ($step === 11)
                                {{-- The whole passage, with an input in place of each marked word, under its audio player --}}
                                <div class="flex w-full flex-col gap-6">
                                    @if ($passageAudioUrl)
                                        <audio controls preload="none" src="{{ $passageAudioUrl }}" class="w-full"></audio>
                                    @else
                                        {{-- No recording: the passage is read by the browser --}}
                                        <div class="flex items-center gap-3">
                                            <x-audio-button :text="collect($passage)->map(fn (array $segments): string => collect($segments)->pluck('text')->implode(''))->implode(' ')" label="Nghe đoạn văn" tone="primary" />
                                            <span class="type-body text-fg-muted">Bấm để nghe cả đoạn văn</span>
                                        </div>
                                    @endif

                                    <div data-passage lang="en" class="flex flex-col gap-4 type-body-lg leading-loose text-fg-body">
                                        @foreach ($passage as $segments)
                                            <p>
                                                @foreach ($segments as $segment)
                                                    @if ($segment['isMarked'])
                                                        <input
                                                            type="text"
                                                            data-blank
                                                            data-id="{{ $segment['id'] }}"
                                                            size="{{ max(mb_strlen($segment['text']), 3) }}"
                                                            aria-label="Từ cần điền số {{ $segment['id'] }}"
                                                            autocomplete="off"
                                                            autocapitalize="off"
                                                            autocorrect="off"
                                                            spellcheck="false"
                                                            class="mx-1 h-11 min-w-20 rounded-lg border-3 border-line-default bg-surface-card px-3 text-center text-fg-strong outline-none transition-interactive focus:border-primary focus:shadow-focus disabled:cursor-default"
                                                        ><span data-correct-hint hidden class="mr-1 type-label text-danger-text"></span>
                                                    @else
                                                        {{ $segment['text'] }}
                                                    @endif
                                                @endforeach
                                            </p>
                                        @endforeach
                                    </div>
                                </div>
                            @elseif ($step === 7)
                                {{-- The sentence with an input in place of each marked word, and its meaning under it; filled in by review.js --}}
                                <div class="flex w-full flex-col items-center gap-3 text-center">
                                    <p data-sentence lang="en" class="flex flex-wrap items-center justify-center gap-x-2 gap-y-3 type-h1 text-fg-strong"></p>
                                    <p data-sentence-translation class="type-body-lg text-fg-muted"></p>
                                </div>
                            @elseif ($step === 10)
                                {{-- The sentence to read, with a listen button for the model reading --}}
                                <div class="flex items-center gap-5">
                                    <x-audio-button :text="$firstQuestion['prompt']" lang="en-US" />
                                    <div lang="en" data-prompt-word class="type-h1 text-balance tracking-tight text-fg-strong">{{ $firstQuestion['prompt'] }}</div>
                                </div>
                            @elseif (in_array($step, [1, 5], true))
                                <x-word-prompt :word="$firstQuestion['prompt']" :phonetic="$firstQuestion['phonetic']" :replay="false" />
                            @elseif (in_array($step, [2, 4, 6, 8], true))
                                {{-- The meaning to find the word for; no listen button, it would give the answer away --}}
                                <div data-prompt-word class="type-h1 text-center text-balance whitespace-pre-line text-fg-strong">{{ $firstQuestion['prompt'] }}</div>
                            @else
                                {{-- The word is only heard: neither its text nor its IPA is shown --}}
                                <div class="flex flex-col items-center gap-3">
                                    <x-audio-button :text="$firstQuestion['prompt']" :label="$step === 9 ? 'Nghe câu' : 'Nghe từ'" tone="primary" class="size-24!" data-prompt-listen />
                                    <span class="type-body text-fg-muted">Bấm để nghe lại</span>
                                </div>
                            @endif
                        </div>

                        @if (in_array($step, [5, 6, 10], true))
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
                                <p data-record-status class="type-body-lg text-fg-muted">{{ $config['recordHint'] ?? 'Bấm micro rồi đọc to từ này nhé!' }}</p>
                                <p data-record-score hidden class="type-h1"><span data-record-score-value>0</span>/10</p>
                            </div>
                        @elseif (in_array($step, [8, 9], true))
                            {{-- The whole sentence is typed into one box and graded when "Kiểm tra" is pressed or Enter is hit --}}
                            <div class="flex flex-col items-center gap-5">
                                <input
                                    type="text"
                                    data-write
                                    lang="en"
                                    aria-label="Câu tiếng Anh"
                                    autocomplete="off"
                                    autocapitalize="off"
                                    autocorrect="off"
                                    spellcheck="false"
                                    class="h-16 w-full rounded-lg border-3 border-line-default bg-surface-card px-5 text-center type-h2 text-fg-strong outline-none transition-interactive focus:border-primary focus:shadow-focus disabled:cursor-default"
                                >
                                <x-button size="lg" icon-left="check" data-check>Kiểm tra</x-button>
                            </div>
                        @elseif (in_array($step, [7, 11], true))
                            <div class="flex justify-center">
                                <x-button size="lg" icon-left="check" data-check>Kiểm tra</x-button>
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
                <template data-tpl="blank"><input type="text" data-blank autocomplete="off" autocapitalize="off" autocorrect="off" spellcheck="false" class="h-14 min-w-20 rounded-lg border-3 border-line-default bg-surface-card px-3 text-center text-fg-strong outline-none transition-interactive focus:border-primary focus:shadow-focus disabled:cursor-default sm:h-16"></template>
                <template data-tpl="letter-fixed"><span class="flex h-13 w-5 items-center justify-center type-h1 text-fg-muted sm:h-18"></span></template>
                <template data-tpl="alert-speak-low"><p class="text-center type-label-lg text-warning-text">Cố lên, lần sau sẽ tốt hơn!</p></template>
                <template data-tpl="alert-success"><p class="text-center type-label-lg text-success-text">Chính xác! Bạn giỏi quá!</p></template>
                <template data-tpl="alert-danger"><p class="text-center type-label-lg text-danger-text">Chưa đúng rồi. Đáp án đúng là: <b data-correct-text class="font-semibold whitespace-pre-line"></b></p></template>

                <script type="application/json" data-review-config>{!! json_encode($config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) !!}</script>
            @endif
        </section>

        @if ($firstQuestion)
            <x-lesson-sidebar :enroll-course="$enrollCourse" :progress="$progress" :top-learners="$topLearners" :score="$standing['score'] ?? 0" :rank="$standing['rank'] ?? '—'" class="flex-[1_1_340px] gap-5">
                <x-slot:after-progress>
                    @if ($advancedUrl)
                        <x-advanced-review-promo :href="$advancedUrl" />
                    @endif
                    <x-game-promo :href="route('lessons.games', [$course, $unit, $lesson])" />
                </x-slot:after-progress>
            </x-lesson-sidebar>
        @endif
    </div>

    <x-slot:scripts>
        @if ($firstQuestion)
            @vite('resources/js/review.js')
        @endif
    </x-slot:scripts>
</x-layouts.app>
