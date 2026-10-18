{{-- Lesson card of a course: its thumbnail, title and description, tagged with its level and age groups. --}}
@props([
    'course',
    'tone' => 'primary',
])

@php
    $tags = collect($course->level ? [['label' => $course->level, 'tone' => 'primary']] : [])
        ->concat(collect($course->age_groups ?? [])->map(fn (string $ageGroup): array => ['label' => $ageGroup, 'tone' => 'secondary']))
        ->all();
@endphp

<x-lesson-card
    :title="$course->title"
    :description="$course->description"
    :thumbnail="$course->thumbnail_url"
    :href="route('courses.show', $course)"
    :tags="$tags"
    :tone="$tone"
    {{ $attributes }}
/>
