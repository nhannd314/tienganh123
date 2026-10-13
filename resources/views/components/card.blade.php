{{-- Surface container. Tones: default (raised white card) · accent (quiz yellow) · info (soft blue). --}}
@props([
    'padding' => 'md',
    'tone' => 'default',
])

@php
    $paddingClass = [
        'none' => 'p-0',
        'sm' => 'p-4',
        'md' => 'p-6',
        'lg' => 'p-12',
    ][$padding] ?? 'p-6';

    $toneClass = [
        'default' => 'border border-line-subtle bg-surface-card shadow-card',
        'accent' => 'bg-surface-quiz',
        'info' => 'bg-info-bg',
    ][$tone] ?? 'border border-line-subtle bg-surface-card shadow-card';
@endphp

<div {{ $attributes->class(['rounded-lg', $paddingClass, $toneClass]) }}>
    {{ $slot }}
</div>
