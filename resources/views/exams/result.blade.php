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

    <x-slot:footer>
        <x-site-footer />
    </x-slot:footer>
</x-layouts.app>
