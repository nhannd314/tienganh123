{{-- Status message. Tones: success · warning · danger · info. Status is shown by icon + text, never colour alone. --}}
@props([
    'tone' => 'info',
    'title' => null,
    'icon' => null,
])

@php
    $tones = [
        'success' => ['box' => 'border-success-border bg-success-bg', 'text' => 'text-success-text', 'icon' => 'circle-check'],
        'warning' => ['box' => 'border-warning-border bg-warning-bg', 'text' => 'text-warning-text', 'icon' => 'triangle-alert'],
        'danger' => ['box' => 'border-danger-border bg-danger-bg', 'text' => 'text-danger-text', 'icon' => 'circle-x'],
        'info' => ['box' => 'border-info-border bg-info-bg', 'text' => 'text-info-text', 'icon' => 'info'],
    ];
    $toneConfig = $tones[$tone] ?? $tones['info'];
@endphp

<div
    role="{{ in_array($tone, ['danger', 'warning']) ? 'alert' : 'status' }}"
    {{ $attributes->class(['flex items-start gap-3 rounded-md border px-5 py-4', $toneConfig['box']]) }}
>
    <x-icon :name="$icon ?? $toneConfig['icon']" size="lg" :class="$toneConfig['text']" />

    <div class="grid gap-1">
        @if ($title)
            <b @class(['text-base leading-snug font-semibold', $toneConfig['text']])>{{ $title }}</b>
        @endif

        @if ($slot->isNotEmpty())
            <span class="type-body-sm text-fg-body">{{ $slot }}</span>
        @endif
    </div>
</div>
