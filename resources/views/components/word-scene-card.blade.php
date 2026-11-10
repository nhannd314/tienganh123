{{--
    Word scene card (the lesson page): the title with the "Listen and repeat" (the words in order), "Listen and find" (the words in
    random order), hide-word and hide-sidebar buttons, then the picture whose objects play their word when clicked (x-word-scene).
    The buttons are wired by resources/js/app.js; the hide-sidebar button needs a `data-sidebar-layout` ancestor.
--}}
@props([
    'scene',
])

<section {{ $attributes->class(['flex flex-col gap-5 rounded-lg bg-surface-card p-7 shadow-card']) }}>
    <div class="flex items-center justify-between gap-4">
        <h2 class="type-h2 text-fg-strong">{{ $scene->title }}</h2>

        <div class="flex items-center gap-2">
            <x-icon-button icon="play" label="Listen and repeat" size="sm" data-word-scene-read="repeat" data-intro-src="{{ asset('audio/prompts/listen-and-repeat.mp3') }}" />
            <x-icon-button icon="shuffle" label="Listen and find" size="sm" data-word-scene-read="find" data-intro-src="{{ asset('audio/prompts/listen-and-find.mp3') }}" data-music-src="{{ asset('audio/finding.mp3') }}" />
            <x-icon-button icon="eye-off" label="Ẩn từ" size="sm" aria-pressed="false" data-word-scene-toggle />
            <x-sidebar-toggle />
        </div>
    </div>

    <x-word-scene :scene="$scene" class="self-start" />
</section>
