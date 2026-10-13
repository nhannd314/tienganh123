{{--
    Sidebar shared by the lesson page and its review: the learning progress of the user and the top learners.

    `progress` is the review progress of the signed-in user (see Lesson::reviewProgress), or null for a guest, who gets a
    note that results are only saved after signing in. The slot goes under the progress (the review button on the lesson
    page). The data-* hooks are updated by resources/js/review.js. `score` and `rank` are placeholders until the way
    they are worked out is decided.

    `enrollCourse` is the course to offer on top when the visitor has not enrolled in it yet (see Course::needsEnrollmentBy).
--}}
@props([
    'enrollCourse' => null,
    'progress' => null,
    'topLearners' => [],
    'rank' => '15/100',
    'score' => 100,
])

@php
    $stats = [
        ['key' => 'done', 'icon' => 'list-checks', 'label' => 'Số câu đã làm', 'tone' => 'primary', 'class' => 'text-primary'],
        ['key' => 'correct', 'icon' => 'circle-check', 'label' => 'Số câu trả lời đúng', 'tone' => 'success', 'class' => 'text-success'],
        ['key' => 'wrong', 'icon' => 'circle-x', 'label' => 'Số câu trả lời sai', 'tone' => 'danger', 'class' => 'text-danger'],
    ];
@endphp

<aside {{ $attributes->class(['flex min-w-0 max-w-full flex-[1_1_360px] flex-col gap-6']) }}>
    @if ($enrollCourse)
        <div class="flex flex-col gap-5 rounded-lg bg-surface-card p-6 shadow-card">
            <x-course-enroll :course="$enrollCourse" />
            <x-course-benefits :course="$enrollCourse" />
        </div>
    @endif

    <div class="flex flex-col gap-5 rounded-lg bg-surface-card p-6 shadow-card">
        <h2 class="type-h2 text-fg-strong">Tiến độ học tập</h2>

        @if ($progress === null)
            <p class="type-body text-fg-body"><a href="{{ route('login') }}" class="font-semibold">Đăng nhập</a> để lưu kết quả học tập.</p>
        @else
            <div class="flex flex-wrap items-center justify-around gap-5">
                <x-progress-ring :value="$progress['percent']" :size="140" :caption="false" data-ring />

                <div class="flex flex-col items-center gap-4 text-center">
                    <span class="flex flex-col items-center">
                        <span class="type-caption text-fg-muted">Điểm</span>
                        <span data-score class="type-h1 text-accent-text">{{ $score }}</span>
                    </span>
                    <span class="flex flex-col items-center">
                        <span class="type-caption text-fg-muted">Thứ hạng</span>
                        <span class="type-h2 text-primary-text">{{ $rank }}</span>
                    </span>
                </div>
            </div>

            <div class="flex flex-col gap-4">
                @foreach ($stats as $stat)
                    <div class="grid grid-cols-[40px_minmax(0,1fr)] items-center gap-3">
                        <span class="flex justify-center">
                            <x-icon :name="$stat['icon']" size="lg" :class="$stat['class']" />
                        </span>
                        <div class="grid gap-2">
                            <div class="flex justify-between gap-3 type-body text-fg-strong">
                                <span>{{ $stat['label'] }}</span>
                                <span class="tabular-nums"><span data-stat-text="{{ $stat['key'] }}">{{ $progress[$stat['key']] }}</span>/<span data-stat-total>{{ $progress['total'] }}</span></span>
                            </div>
                            <x-progress-bar :value="$progress[$stat['key']]" :max="$progress['total']" :tone="$stat['tone']" size="md" :label="$stat['label']" data-stat-bar="{{ $stat['key'] }}" />
                        </div>
                    </div>
                @endforeach

                <div class="grid grid-cols-[40px_minmax(0,1fr)] items-center gap-3">
                    <span class="flex justify-center"><x-icon name="timer" size="lg" class="text-secondary" /></span>
                    <div class="flex justify-between gap-3 type-body text-fg-strong">
                        <span>Thời gian làm bài</span>
                        <span data-total-time class="tabular-nums">{{ sprintf('%02d:%02d', intdiv($progress['seconds'], 60), $progress['seconds'] % 60) }}</span>
                    </div>
                </div>
            </div>
        @endif

        {{ $slot }}
    </div>

    <x-top-learners :learners="$topLearners" />
</aside>
