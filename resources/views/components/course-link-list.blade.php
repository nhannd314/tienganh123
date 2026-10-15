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
            <x-lesson-card
                :title="$course->title"
                :description="$course->description"
                :thumbnail="$course->thumbnail_url"
                :href="route('courses.show', $course)"
                :price="$course->priceLabel()"
                :price-tone="$course->isFree() ? 'success' : 'danger'"
                :price-icon="$course->isFree() ? 'gift' : 'tag'"
                :original-price="$course->originalPriceLabel()"
                :discount="$course->displayDiscountPercent()"
                :tone="$tones[$loop->index % count($tones)]"
            />
        @endforeach
    @endif
</div>
