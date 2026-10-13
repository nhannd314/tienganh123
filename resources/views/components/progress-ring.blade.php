{{-- Circular completion meter (0–100) with the percentage in the middle, and the `label` under it unless `caption` is false. --}}
@props([
    'value' => 0,
    'size' => 176,
    'stroke' => 16,
    'label' => 'Hoàn thành',
    'caption' => true,
])

@php
    $radius = ($size - $stroke) / 2;
    $circumference = 2 * M_PI * $radius;
    $offset = $circumference * (1 - max(0, min(100, $value)) / 100);
@endphp

<div
    role="img"
    aria-label="{{ $label }} {{ $value }}%"
    {{ $attributes->class(['relative']) }}
    style="width: {{ $size }}px; height: {{ $size }}px"
>
    <svg width="{{ $size }}" height="{{ $size }}" class="-rotate-90" aria-hidden="true">
        <circle cx="{{ $size / 2 }}" cy="{{ $size / 2 }}" r="{{ $radius }}" fill="none" stroke-width="{{ $stroke }}" class="stroke-primary-border" />
        <circle
            cx="{{ $size / 2 }}"
            cy="{{ $size / 2 }}"
            r="{{ $radius }}"
            fill="none"
            stroke-width="{{ $stroke }}"
            stroke-linecap="round"
            stroke-dasharray="{{ $circumference }}"
            stroke-dashoffset="{{ $offset }}"
            data-ring-bar
            data-circumference="{{ $circumference }}"
            class="stroke-primary transition-[stroke-dashoffset] duration-400 ease-out"
        />
    </svg>

    <div aria-hidden="true" class="absolute inset-0 flex flex-col items-center justify-center gap-2">
        <div class="leading-none font-bold text-fg-strong" style="font-size: {{ round($size * 0.27) }}px">
            <span data-ring-value>{{ $value }}</span><span style="font-size: {{ round($size * 0.16) }}px">%</span>
        </div>
        @if ($caption)
            <div class="type-body-sm text-fg-body">{{ $label }}</div>
        @endif
    </div>
</div>
