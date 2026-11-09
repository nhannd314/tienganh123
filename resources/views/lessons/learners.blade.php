@php
    $headerCell = 'border-l border-line-default bg-surface-hover px-3 py-3 first:border-l-0 text-left type-label whitespace-nowrap text-fg-body';
    $bodyCell = 'border-t border-l border-line-default px-3 py-3 first:border-l-0 align-middle type-body text-fg-strong';
@endphp

<x-layouts.app :title="'Thành tích học '.$lesson->title" main-class="flex flex-col gap-6 pt-6 pb-10">
    <x-slot:header>
        <x-site-header active="courses" />
    </x-slot:header>

    <x-breadcrumbs :items="[
        ['label' => 'Trang chủ', 'href' => route('home')],
        ['label' => 'Khóa học', 'href' => route('courses.index')],
        ['label' => $course->title, 'href' => route('courses.show', $course)],
        ['label' => $lesson->title, 'href' => route('lessons.show', [$course, $unit, $lesson])],
        ['label' => 'Thành tích học'],
    ]" />

    <div class="flex flex-col gap-5 rounded-lg bg-surface-card p-6 shadow-card">
        <h1 class="type-h2 text-fg-strong">Thành tích học: {{ $lesson->title }}</h1>

        @if ($learners->isEmpty())
            <p class="type-body text-fg-muted">Chưa có học sinh nào ghi điểm.</p>
        @else
            <div class="overflow-x-auto rounded-sm border border-line-default">
                <table class="w-full min-w-190 border-collapse">
                    <thead>
                        <tr>
                            <th scope="col" class="{{ $headerCell }} w-16 text-center">STT</th>
                            <th scope="col" class="{{ $headerCell }}">Tên</th>
                            <th scope="col" class="{{ $headerCell }}">% hoàn thành</th>
                            <th scope="col" class="{{ $headerCell }}">Điểm số</th>
                            <th scope="col" class="{{ $headerCell }}">Số câu đúng</th>
                            <th scope="col" class="{{ $headerCell }}">Số câu sai</th>
                            <th scope="col" class="{{ $headerCell }}">Điểm phát âm</th>
                            <th scope="col" class="{{ $headerCell }}">Thời gian làm bài</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($learners as $learner)
                            <tr>
                                <td class="{{ $bodyCell }} text-center text-fg-muted">{{ $firstRank + $loop->index }}</td>
                                <td class="{{ $bodyCell }} font-medium">{{ $learner['name'] }}</td>
                                <td class="{{ $bodyCell }}">{{ $learner['percent'] }}%</td>
                                <td class="{{ $bodyCell }} font-semibold text-primary-text">{{ number_format($learner['points'], 0, ',', '.') }}</td>
                                <td class="{{ $bodyCell }} text-success-text">{{ $learner['correct'] }}</td>
                                <td class="{{ $bodyCell }} text-danger-text">{{ $learner['wrong'] }}</td>
                                <td class="{{ $bodyCell }}">{{ $learner['pronunciation'] }}/10</td>
                                <td class="{{ $bodyCell }} tabular-nums">{{ sprintf('%02d:%02d', intdiv($learner['seconds'], 60), $learner['seconds'] % 60) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{ $scores->links('pagination.default') }}
        @endif
    </div>

    <x-slot:footer>
        <x-site-footer />
    </x-slot:footer>
</x-layouts.app>
