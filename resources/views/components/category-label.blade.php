{{-- Small coloured label of a post category; the colour role comes from Category::tone(). --}}
@props([
    'category',
])

@php
    $toneClass = [
        'primary' => 'bg-primary-bg text-primary-text',
        'secondary' => 'bg-secondary-bg text-secondary-text',
        'success' => 'bg-success-bg text-success-text',
        'accent' => 'bg-accent-bg text-accent-text',
    ][$category->tone()];
@endphp

<span {{ $attributes->class(['inline-flex h-8 items-center rounded-sm px-3 type-label-sm font-semibold', $toneClass]) }}>{{ $category->name }}</span>
