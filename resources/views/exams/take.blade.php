{{--
    The exam paper, without the site header so nothing leads away from it: one card per group, the questions numbered
    across the exam, answered as answers[group id][key]. In the written questions, the parts wrapped in backticks are
    shown bold and underlined (see ExamQuestion::markedHtml).
    resources/js/exam.js counts the time down and submits the form when it is up, and records the words read aloud.
--}}
@php
    use App\Enums\ExamGroupType;
    use App\Models\ExamQuestion;
    use App\Services\ExamPaper;

    $formatPoints = fn (float $points): string => rtrim(rtrim(number_format($points, 2, ',', '.'), '0'), ',');
    $number = 0;
@endphp

<x-layouts.app :title="$exam->title" noindex main-class="flex flex-col gap-6 pt-6 pb-10">
    <form
        method="POST"
        action="{{ route('exams.submit', $exam) }}"
        data-exam-form
        data-pronounce-url="{{ route('exams.pronounce', $exam) }}"
        data-attempt="{{ $attempt->id }}"
        class="mx-auto flex w-full max-w-200 flex-col gap-6"
        autocomplete="off"
    >
        @csrf

        <div class="sticky top-2 z-10 flex flex-wrap items-center justify-between gap-4 rounded-lg bg-surface-card px-6 py-4 shadow-card">
            <div class="flex min-w-0 flex-col">
                <h1 class="type-h2 text-fg-strong">{{ $exam->title }}</h1>
                <span class="type-body-sm text-fg-muted">{{ $attempt->student_name }} — lớp {{ $attempt->class_name }}</span>
            </div>

            <div class="flex items-center gap-4">
                @if ($secondsLeft !== null)
                    <span data-exam-timer data-seconds="{{ $secondsLeft }}" role="timer" aria-live="off" class="inline-flex items-center gap-2 type-label-lg text-fg-strong tabular-nums data-urgent:text-danger-text">
                        <x-icon name="timer" />
                        <span data-exam-timer-text>{{ sprintf('%02d:%02d', intdiv($secondsLeft, 60), $secondsLeft % 60) }}</span>
                    </span>
                @endif

                <x-button type="submit" data-exam-submit>Nộp bài</x-button>
            </div>
        </div>

        @foreach ($sections as $section)
            @php
                $group = $section['group'];
                $isWritten = $group->type === ExamGroupType::Questions;
            @endphp

            <section class="flex flex-col gap-5 rounded-lg bg-surface-card px-6 py-8 shadow-card sm:px-10">
                <div class="flex flex-col gap-2">
                    <h2 class="type-h2 text-fg-strong">Phần {{ $loop->iteration }}. {{ $group->title }}</h2>

                    @if (filled($group->instructions_html))
                        <x-rich-text>{!! $group->instructions_html !!}</x-rich-text>
                    @endif

                    <span class="type-body-sm text-fg-muted">{{ count($section['questions']) }} câu · mỗi câu {{ $formatPoints($section['points']) }} điểm</span>
                </div>

                @if ($group->type === ExamGroupType::ListenAndFill)
                    @if ($group->audio_url)
                        <audio controls preload="metadata" src="{{ $group->audio_url }}" class="w-full"></audio>
                    @endif

                    @php
                        $passageHtml = collect($section['segments'])->map(fn (array $segment): string => $segment['blank'] === null
                            ? e($segment['text'])
                            : '<span class="inline-flex items-baseline gap-1 whitespace-nowrap"><span class="type-body-sm text-fg-muted">('.($number + $segment['blank']).')</span>'
                                .'<input type="text" name="answers['.$group->id.']['.$segment['blank'].']" aria-label="Chỗ trống số '.($number + $segment['blank']).'" spellcheck="false" autocapitalize="off"'
                                .' class="h-10 w-36 rounded-sm border border-line-default bg-surface-card px-3 text-base text-fg-strong outline-none transition-interactive hover:border-line-hover focus:shadow-focus"></span>')->implode('');
                        $number += count($section['questions']);
                    @endphp

                    <p class="text-lg leading-[3.75rem] whitespace-pre-line text-fg-strong">{!! $passageHtml !!}</p>
                @else
                    <ol class="flex flex-col gap-6">
                        @foreach ($section['questions'] as $question)
                            @php
                                $number++;
                                $name = "answers[{$group->id}][{$question['key']}]";
                            @endphp

                            <li class="flex flex-col gap-3 border-t border-line-subtle pt-5 first:border-t-0 first:pt-0">
                                <div class="flex items-start gap-3">
                                    <span class="type-label-lg text-primary">{{ $number }}.</span>

                                    <div class="flex min-w-0 flex-1 flex-wrap items-center gap-3">
                                        @if ($group->type === ExamGroupType::ListenToMeaning)
                                            <span class="flex items-center gap-3" data-exam-listen="{{ $group->id }}:{{ $question['key'] }}" data-plays-left="{{ $listenLimit }}">
                                                <x-audio-button :text="$question['prompt']" :src="$question['audio']" size="sm" :label="'Nghe từ số '.$number" />
                                                <span data-exam-listen-status class="type-body text-fg-muted">Còn {{ $listenLimit }} lượt nghe</span>
                                            </span>
                                        @elseif ($group->type === ExamGroupType::SentencePronunciation)
                                            <p class="type-h2 text-fg-strong">{{ $question['prompt'] }}</p>
                                        @elseif ($group->type === ExamGroupType::WordToMeaning)
                                            <span class="type-h2 text-fg-strong">{{ $question['prompt'] }}</span>
                                            @if ($question['phonetic'])
                                                <span class="type-body text-fg-muted">{{ $question['phonetic'] }}</span>
                                            @endif
                                        @else
                                            <p class="type-body-lg whitespace-pre-line text-fg-strong">{{ $isWritten ? ExamQuestion::markedHtml($question['prompt']) : $question['prompt'] }}</p>
                                        @endif
                                    </div>
                                </div>

                                @if ($question['kind'] === 'choice')
                                    <div class="grid gap-2 sm:grid-cols-2">
                                        @foreach ($question['options'] ?? [] as $option)
                                            <label class="flex cursor-pointer items-center gap-4 rounded-md border border-line-default px-5 py-4 text-lg leading-snug whitespace-pre-line text-fg-strong transition-interactive hover:border-line-hover has-checked:border-primary has-checked:bg-primary-bg">
                                                <input type="radio" name="{{ $name }}" value="{{ $option['value'] }}" class="size-4 flex-none accent-primary">
                                                <span>{{ $isWritten ? ExamQuestion::markedHtml($option['label']) : $option['label'] }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                @elseif ($question['kind'] === 'text')
                                    <x-text-input type="text" size="md" :name="$name" :aria-label="'Câu trả lời số '.$number" placeholder="Nhập câu trả lời" spellcheck="false" autocapitalize="off" />
                                @else
                                    @php
                                        $reading = $pronunciationScores[ExamPaper::pronunciationKey($group, (int) $question['key'])] ?? null;
                                        $attemptsLeft = $pronunciationAttempts - ($reading['attempts'] ?? 0);
                                    @endphp

                                    <div class="flex flex-wrap items-center gap-4" data-exam-reading data-group="{{ $group->id }}" data-key="{{ $question['key'] }}" data-max-recording-ms="{{ $group->type === ExamGroupType::SentencePronunciation ? $sentenceRecordingMs : $wordRecordingMs }}">
                                        <x-button variant="secondary" icon-left="mic" data-exam-record :disabled="$attemptsLeft <= 0">
                                            <span data-exam-record-label>Bấm để đọc</span>
                                        </x-button>

                                        <span data-exam-record-status class="type-body text-fg-body">
                                            @if ($reading)
                                                Điểm cao nhất: {{ $reading['best'] }}/10 · còn {{ max(0, $attemptsLeft) }} lượt đọc
                                            @else
                                                Bạn có {{ $pronunciationAttempts }} lượt đọc
                                            @endif
                                        </span>
                                    </div>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                @endif
            </section>
        @endforeach

        <x-button type="submit" size="lg" full-width data-exam-submit>Nộp bài</x-button>
    </form>

    {{-- Covers the paper until it is shown full screen, and again whenever full screen is left (see resources/js/exam.js). --}}
    <div data-exam-fullscreen hidden class="fixed inset-0 z-50 flex items-center justify-center bg-surface-page px-4">
        <div class="flex w-full max-w-120 flex-col items-center gap-5 rounded-lg bg-surface-card px-6 py-10 text-center shadow-card">
            <x-icon name="maximize" size="xl" class="text-primary" />

            <div class="flex flex-col gap-2">
                <h2 class="type-h2 text-fg-strong" data-exam-fullscreen-title>Làm bài ở chế độ toàn màn hình</h2>
                <p class="type-body text-fg-body" data-exam-fullscreen-text>Bài thi chỉ hiện khi ở chế độ toàn màn hình. Nếu thoát toàn màn hình, bài thi sẽ tạm dừng.</p>
            </div>

            <x-button size="lg" icon-left="maximize" data-exam-fullscreen-enter>Vào toàn màn hình</x-button>
        </div>
    </div>

    <x-slot:scripts>
        @vite('resources/js/exam.js')
    </x-slot:scripts>
</x-layouts.app>
