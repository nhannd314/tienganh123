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
    :description="filled($course->description) ? trim(html_entity_decode(strip_tags(str_replace('</p>', '</p> ', $course->description)))) : null"
    :thumbnail="$course->thumbnail_url"
    :href="route('courses.show', $course)"
    :tags="$tags"
    :tone="$tone"
    {{ $attributes }}
/>
