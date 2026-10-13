{{--
    Round "listen" button that speaks `text` with the browser's speech synthesis (see resources/js/app.js).
    Sizes: lg (64px) · sm (40px). Optional `tone` fills it with a role colour. `label` replaces the default
    "Nghe phát âm {text}", e.g. when the word must stay hidden.
--}}
@props([
    'text' => '',
    'label' => null,
    'size' => 'lg',
    'lang' => 'en-US',
    'tone' => null,
])

@php
    $isLarge = $size === 'lg';

    $toneClass = [
        'primary' => 'bg-primary text-white not-disabled:hover:brightness-92',
        'secondary' => 'bg-secondary text-white not-disabled:hover:brightness-92',
        'accent' => 'bg-accent text-on-accent not-disabled:hover:brightness-92',
        'warning' => 'bg-warning text-on-accent not-disabled:hover:brightness-92',
        'success' => 'bg-success text-white not-disabled:hover:brightness-92',
        'danger' => 'bg-danger text-white not-disabled:hover:brightness-92',
        'info' => 'bg-info text-white not-disabled:hover:brightness-92',
    ][$tone] ?? 'bg-primary-bg-hover text-primary not-disabled:hover:bg-primary-border';
@endphp

<button
    type="button"
    data-audio-button
    data-text="{{ $text }}"
    data-lang="{{ $lang }}"
    aria-label="{{ $label ?? 'Nghe phát âm '.$text }}"
    {{ $attributes->class([
        'inline-flex flex-none cursor-pointer items-center justify-center rounded-full transition-interactive',
        'not-disabled:active:scale-94 data-playing:scale-106 data-playing:shadow-halo',
        'disabled:cursor-not-allowed disabled:bg-surface-disabled disabled:text-fg-disabled',
        $isLarge ? 'size-16' : 'size-10',
        $toneClass,
    ]) }}
>
    <x-icon name="volume-2" :size="$isLarge ? 'xl' : 'md'" />
</button>
