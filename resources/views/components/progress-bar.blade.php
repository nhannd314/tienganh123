{{-- Horizontal progress track. Sizes: sm 8px (default) · md 12px · lg 16px. --}}
@props([
    'value' => 0,
    'max' => 100,
    'tone' => 'primary',
    'size' => 'sm',
    'label' => null,
])

@php
    $percent = $max > 0 ? max(0, min(100, $value / $max * 100)) : 0;

    $fillClass = [
        'primary' => 'bg-primary',
        'secondary' => 'bg-secondary',
        'accent' => 'bg-accent',
        'success' => 'bg-success',
        'warning' => 'bg-warning',
        'danger' => 'bg-danger',
        'info' => 'bg-info',
    ][$tone] ?? 'bg-primary';

    $heightClass = [
        'sm' => 'h-2',
        'md' => 'h-3',
        'lg' => 'h-4',
    ][$size] ?? 'h-2';
@endphp

<div
    role="progressbar"
    aria-valuenow="{{ $value }}"
    aria-valuemin="0"
    aria-valuemax="{{ $max }}"
    @if ($label) aria-label="{{ $label }}" @endif
    {{ $attributes->class(['overflow-hidden rounded-full bg-surface-track', $heightClass]) }}
>
    <div
        data-progress-fill
        @class(['h-full rounded-full transition-[width] duration-400 ease-out', $fillClass])
        style="width: {{ $percent }}%"
    ></div>
</div>
