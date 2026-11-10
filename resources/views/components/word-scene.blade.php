{{--
    Picture of a word scene whose objects can be clicked: the rectangle drawn around the object of each word is a transparent
    button that plays the word (the recorded audio when there is one, otherwise the browser's voice; see resources/js/app.js).
    The word is written above its rectangle; the `data-words-hidden` attribute of the picture hides these labels (x-word-scene-card).
    `accent` is the pronunciation played: "us" or "uk".
--}}
@props([
    'scene',
    'accent' => 'us',
])

@php
    $isUk = $accent === 'uk';
    $items = $scene->loadMissing('items.word')->items->filter(fn ($item): bool => $item->hasRectangle());
@endphp

@if ($scene->image_url)
    <div data-word-scene {{ $attributes->class(['group/scene relative inline-block max-w-full leading-none']) }}>
        <img src="{{ $scene->image_url }}" alt="{{ $scene->title }}" class="block h-auto max-w-full rounded-lg">

        @foreach ($items as $item)
            <button
                type="button"
                data-audio-button
                data-text="{{ $item->word->word }}"
                data-lang="{{ $isUk ? 'en-GB' : 'en-US' }}"
                @if ($src = $isUk ? $item->word->audio_uk_url : $item->word->audio_us_url) data-src="{{ $src }}" @endif
                aria-label="Nghe phát âm {{ $item->word->word }}"
                class="group/spot absolute cursor-pointer rounded-md"
                style="left: {{ $item->x * 100 }}%; top: {{ $item->y * 100 }}%; width: {{ $item->width * 100 }}%; height: {{ $item->height * 100 }}%"
            >
                <span lang="en" class="absolute bottom-full cursor-pointer left-1/2 mb-1 -translate-x-1/2 rounded-sm border border-line-default bg-white px-2 py-0.5 type-label leading-snug whitespace-nowrap text-fg-body shadow-card transition-colors group-data-[playing]/spot:border-primary group-data-[playing]/spot:bg-primary group-data-[playing]/spot:text-white group-data-[words-hidden]/scene:hidden">{{ $item->word->word }}</span>
            </button>
        @endforeach
    </div>
@endif
