{{-- Rounded label chip. Tones: neutral · primary · secondary · success · warning · danger · info. --}}
@props([
    'tone' => 'primary',
    'size' => 'md',
    'icon' => null,
])

@php
    $toneClass = [
        'neutral' => 'border-line-default bg-surface-card text-fg-strong',
        'primary' => 'border-primary-border bg-primary-bg text-primary-text',
        'secondary' => 'border-secondary-border bg-secondary-bg text-secondary-text',
        'success' => 'border-success-border bg-success-bg text-success-text',
        'warning' => 'border-warning-border bg-warning-bg text-warning-text',
        'danger' => 'border-danger-border bg-danger-bg text-danger-text',
        'info' => 'border-info-border bg-info-bg text-info-text',
    ][$tone] ?? 'border-primary-border bg-primary-bg text-primary-text';

    $isSmall = $size === 'sm';
@endphp

<span {{ $attributes->class([
    'inline-flex items-center gap-2 rounded-full border whitespace-nowrap',
    $toneClass,
    $isSmall ? 'h-8 px-3 type-label-sm' : 'h-10 px-4 type-label',
]) }}>
    @if ($icon)
        <x-icon :name="$icon" :size="$isSmall ? 'sm' : 'md'" />
    @endif
    {{ $slot }}
</span>
