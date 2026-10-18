{{--
    Sidebar block titled by `title`: one course tile (same as the home page) per course, stacked. `empty` is shown when
    there are none.
--}}
@props([
    'title' => 'Khóa học liên quan',
    'courses',
    'empty' => 'Chưa có khóa học nào.',
])

@php
    $tones = ['primary', 'secondary', 'accent', 'success', 'danger', 'info'];
@endphp

<div {{ $attributes->class(['flex flex-col gap-5']) }}>
    <h2 class="type-h2 text-fg-strong">{{ $title }}</h2>

    @if ($courses->isEmpty())
        <p class="type-body text-fg-muted">{{ $empty }}</p>
    @else
        @foreach ($courses as $course)
            <x-course-card :course="$course" :tone="$tones[$loop->index % count($tones)]" />
        @endforeach
    @endif
</div>
