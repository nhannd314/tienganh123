{{--
    Picture of a word scene whose objects can be clicked: the rectangle drawn around the object of each word is a transparent
    button that plays the word (the recorded audio when there is one, otherwise the browser's voice; see resources/js/app.js).
    `accent` is the pronunciation played: "us" or "uk".
--}}
@props([
    'scene',
    'accent' => 'us',
])

@php
    $isUk = $accent === 'uk';
    $items = $scene->drawnItems()->with('word')->get();
@endphp

@if ($scene->image_url)
    <div {{ $attributes->class(['relative inline-block max-w-full leading-none']) }}>
        <img src="{{ $scene->image_url }}" alt="{{ $scene->title }}" class="block h-auto max-w-full rounded-lg">

        @foreach ($items as $item)
            <button
                type="button"
                data-audio-button
                data-text="{{ $item->word->word }}"
                data-lang="{{ $isUk ? 'en-GB' : 'en-US' }}"
                @if ($src = $isUk ? $item->word->audio_uk_url : $item->word->audio_us_url) data-src="{{ $src }}" @endif
                aria-label="Nghe phát âm {{ $item->word->word }}"
                title="{{ $item->word->word }}"
                class="absolute cursor-pointer rounded-md border-2 border-transparent transition-interactive hover:border-primary hover:bg-primary/15 data-playing:border-primary data-playing:bg-primary/25"
                style="left: {{ $item->x * 100 }}%; top: {{ $item->y * 100 }}%; width: {{ $item->width * 100 }}%; height: {{ $item->height * 100 }}%"
            ></button>
        @endforeach
    </div>
@endif
