{{-- Vocabulary card: illustration, word, IPA, Vietnamese meaning and a listen button. --}}
@props([
    'word',
    'phonetic' => null,
    'meaning' => null,
    'image' => null,
    'audio' => null,
    'tone' => 'primary',
])

@php
    $tones = [
        'primary' => ['box' => 'border-primary-border bg-primary-bg', 'word' => 'text-primary-text'],
        'secondary' => ['box' => 'border-secondary-border bg-secondary-bg', 'word' => 'text-secondary-text'],
        'accent' => ['box' => 'border-accent-border bg-accent-bg', 'word' => 'text-accent-text'],
        'success' => ['box' => 'border-success-border bg-success-bg', 'word' => 'text-success-text'],
        'danger' => ['box' => 'border-danger-border bg-danger-bg', 'word' => 'text-danger-text'],
    ];
    $toneConfig = $tones[$tone] ?? $tones['primary'];
@endphp

<article {{ $attributes->class([
    'group/card relative grid min-h-36 grid-cols-[104px_minmax(0,1fr)] items-center gap-4 rounded-lg border p-5',
    'data-[word-hidden]:grid-cols-1',
    $toneConfig['box'],
]) }}>
    <div class="flex justify-center">
        @if ($image)
            <img data-flashcard-image src="{{ $image }}" alt="" class="size-26 object-contain group-data-[word-hidden]/card:mx-auto group-data-[word-hidden]/card:h-40 group-data-[word-hidden]/card:w-full">
        @else
            {{-- Same placeholder as a course card without a thumbnail: a plain tile with the image icon --}}
            <span data-flashcard-image aria-hidden="true" class="flex size-26 items-center justify-center rounded-md bg-surface-card text-fg-disabled group-data-[word-hidden]/card:h-40 group-data-[word-hidden]/card:w-full">
                <x-icon name="image" size="xl" />
            </span>
        @endif
    </div>

    <div class="grid gap-1 pr-10 group-data-[word-hidden]/card:hidden">
        <h3 lang="en" @class(['type-h2', $toneConfig['word']])>{{ $word }}</h3>

        @if ($phonetic)
            <div class="type-body-lg font-ipa whitespace-nowrap text-primary" style="font-family: var(--font-ipa)">{{ $phonetic }}</div>
        @endif

        @if ($meaning)
            <div class="mt-2 type-body text-fg-strong">{{ $meaning }}</div>
        @endif
    </div>

    <span class="absolute right-3 top-3">
        <x-audio-button size="sm" :text="$word" :src="$audio" lang="en-US" :tone="$tone" />
    </span>
</article>
