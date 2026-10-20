{{--
    Flashcard set card (the lesson page and the flashcard page): the title with the Chant, hide-word, print and
    hide-sidebar buttons, then a grid of flashcards. `cards` are the set's flashcards that have a title. The buttons
    are wired by resources/js/app.js; the hide-sidebar button needs a `data-sidebar-layout` ancestor.
--}}
@props([
    'title',
    'cards',
])

@php
    $cardTones = ['primary', 'secondary', 'success', 'accent'];
@endphp

<section {{ $attributes->class(['flex flex-col gap-5 rounded-lg bg-surface-card p-7 shadow-card']) }}>
    <div class="flex items-center justify-between gap-4">
        <h2 class="type-h2 text-fg-strong">{{ $title }}</h2>

        <div class="flex items-center gap-2">
            <x-chant :slides="$cards->map(fn ($card): array => ['image' => $card->image_url ?? $card->word?->image_url, 'title' => $card->display_title, 'ipa' => $card->word?->ipa_us, 'meaning' => $card->word?->firstPlainMeaning()])->all()" />
            <x-icon-button icon="eye-off" label="Ẩn từ" size="sm" aria-pressed="false" data-flashcard-toggle />
            <x-icon-button icon="printer" label="In flashcard" size="sm" data-print-flashcards />
            <x-sidebar-toggle />
        </div>
    </div>

    <div data-flashcards class="grid grid-cols-[repeat(auto-fill,minmax(min(100%,max(260px,calc((100%_-_2rem)/3))),1fr))] gap-4 group-data-[sidebar-hidden]/layout:grid-cols-[repeat(auto-fill,minmax(min(100%,max(220px,calc((100%_-_3rem)/4))),1fr))]">
        @foreach ($cards as $flashcard)
            @php($word = $flashcard->word)
            <x-flashcard
                :word="$flashcard->display_title"
                :phonetic="$word?->ipa_us"
                :meaning="$word?->firstPlainMeaning()"
                :image="$flashcard->image_url ?? $word?->image_url"
                :audio="$flashcard->audio_url"
                :tone="$cardTones[$loop->index % count($cardTones)]"
            />
        @endforeach
    </div>
</section>
