@php
    $formatPoints = fn (float $points): string => rtrim(rtrim(number_format($points, 2, ',', '.'), '0'), ',');
@endphp

<x-layouts.app :title="'Kết quả '.$exam->title" noindex main-class="flex flex-col gap-6 pt-6 pb-10">
    <x-slot:header>
        <x-site-header active="courses" />
    </x-slot:header>

    <div class="mx-auto flex w-full max-w-160 flex-col gap-6 rounded-lg bg-surface-card px-6 py-8 shadow-card sm:px-10">
        <div class="flex flex-col gap-2 text-center">
            <h1 class="type-page-title text-balance text-fg-strong">{{ $exam->title }}</h1>
            <p class="type-body text-fg-muted">{{ $attempt->student_name }} — lớp {{ $attempt->class_name }}</p>
        </div>

        <div class="flex flex-col items-center gap-1 rounded-lg bg-primary-bg py-6">
            <span class="type-body text-fg-body">Điểm của bạn</span>
            <span class="text-6xl font-bold text-primary tabular-nums">{{ $formatPoints($attempt->score) }}<span class="text-2xl text-fg-muted">/100</span></span>
        </div>

        <table class="w-full type-body">
            <thead>
                <tr class="border-b border-line-subtle text-left text-fg-muted">
                    <th class="py-2 font-medium">Phần</th>
                    <th class="py-2 text-right font-medium">Đúng</th>
                    <th class="py-2 text-right font-medium">Điểm</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($groups as $group)
                    <tr class="border-b border-line-subtle text-fg-strong last:border-b-0">
                        <td class="py-3 pr-3">{{ $loop->iteration }}. {{ $group['title'] }}</td>
                        <td class="py-3 text-right tabular-nums">{{ $group['correct'] }}/{{ $group['total'] }}</td>
                        <td class="py-3 text-right tabular-nums">{{ $formatPoints($group['points']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <x-button :href="route('exams.show', $exam)" variant="outline" full-width>Về trang đề thi</x-button>
    </div>

    {{-- The paper with the answers given --}}
    <div class="mx-auto flex w-full max-w-200 flex-col gap-6">
        <h2 class="type-h2 text-fg-strong">Bài làm của bạn</h2>

        @foreach ($review as $group)
            <section class="flex flex-col gap-5 rounded-lg bg-surface-card px-6 py-8 shadow-card sm:px-10">
                <h3 class="type-h3 text-fg-strong">Phần {{ $loop->iteration }}. {{ $group['title'] }}</h3>

                <ol class="flex flex-col gap-4">
                    @foreach ($group['questions'] as $question)
                        <li @class([
                            'flex flex-col gap-2 rounded-md border px-5 py-4',
                            'border-success-border bg-success-bg' => $question['correct'],
                            'border-danger-border bg-danger-bg' => ! $question['correct'],
                        ])>
                            <div class="flex items-start gap-3">
                                <span class="type-label-lg text-primary">{{ $question['number'] }}.</span>
                                <p class="min-w-0 flex-1 type-body-lg whitespace-pre-line text-fg-strong">{{ $question['prompt'] ?? 'Chỗ trống' }}</p>
                                <x-icon :name="$question['correct'] ? 'circle-check' : 'circle-x'" :class="$question['correct'] ? 'text-success-text' : 'text-danger-text'" :label="$question['correct'] ? 'Đúng' : 'Sai'" />
                            </div>

                            @if ($question['kind'] === 'speak')
                                <p class="type-body text-fg-body">Điểm đọc: <strong>{{ $question['given'] }}</strong></p>
                            @else
                                <p class="type-body text-fg-body">Bạn trả lời: <strong>{{ $question['given'] !== '' ? $question['given'] : '(bỏ trống)' }}</strong></p>

                                @unless ($question['correct'])
                                    <p class="type-body text-fg-body">Đáp án đúng: <strong>{{ $question['expected'] }}</strong></p>
                                @endunless
                            @endif
                        </li>
                    @endforeach
                </ol>
            </section>
        @endforeach
    </div>

    <x-slot:footer>
        <x-site-footer />
    </x-slot:footer>
</x-layouts.app>
