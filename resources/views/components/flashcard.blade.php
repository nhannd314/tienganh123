{{-- Vocabulary card: illustration, word, IPA, Vietnamese meaning and a listen button. --}}
@props([
    'word',
    'phonetic' => null,
    'meaning' => null,
    'image' => null,
    'tone' => 'primary',
])

@php
    $tones = [
        'primary' => ['box' => 'border-primary-border bg-primary-bg', 'placeholder' => 'border-primary-border', 'word' => 'text-primary-text'],
        'secondary' => ['box' => 'border-secondary-border bg-secondary-bg', 'placeholder' => 'border-secondary-border', 'word' => 'text-secondary-text'],
        'accent' => ['box' => 'border-accent-border bg-accent-bg', 'placeholder' => 'border-accent-border', 'word' => 'text-accent-text'],
        'success' => ['box' => 'border-success-border bg-success-bg', 'placeholder' => 'border-success-border', 'word' => 'text-success-text'],
        'danger' => ['box' => 'border-danger-border bg-danger-bg', 'placeholder' => 'border-danger-border', 'word' => 'text-danger-text'],
    ];
    $toneConfig = $tones[$tone] ?? $tones['primary'];
@endphp

<article {{ $attributes->class([
    'grid min-h-36 grid-cols-[104px_minmax(0,1fr)_auto] items-center gap-4 rounded-lg border p-5',
    $toneConfig['box'],
]) }}>
    @if ($image)
        <img src="{{ $image }}" alt="" class="size-26 object-contain">
    @else
        <span aria-hidden="true" @class([
            'flex size-26 items-center justify-center rounded-md border border-dashed bg-surface-card text-fg-disabled',
            $toneConfig['placeholder'],
        ])>
            <x-icon name="image" size="xl" />
        </span>
    @endif

    <div class="grid gap-1">
        <h3 lang="en" @class(['type-h2', $toneConfig['word']])>{{ $word }}</h3>

        @if ($phonetic)
            <div class="type-body-lg whitespace-nowrap text-primary">{{ $phonetic }}</div>
        @endif

        @if ($meaning)
            <div class="mt-2 type-body text-fg-strong">{{ $meaning }}</div>
        @endif
    </div>

    <span class="self-start">
        <x-audio-button size="sm" :text="$word" :tone="$tone" />
    </span>
</article>
