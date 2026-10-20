{{--
    Example sentences card: the title, the Chant and the show-meaning buttons, then every sentence with its picture, audio button
    and Vietnamese meaning, shown unless the button hides it. A word in backticks is labelled, and opens its popover when it matches a stored word. Shared by the lesson page and the
    sentence sets page. `examples` is the list built by App\Services\ExampleSentences; `idPrefix` keeps the popover ids of two
    cards on one page apart.
--}}
@props([
    'examples',
    'title' => 'Câu mẫu',
    'idPrefix' => 'example-word-',
])

<section {{ $attributes->class(['flex flex-col gap-5 rounded-lg bg-surface-card p-7 shadow-card']) }}>
    <div class="flex items-center justify-between gap-4">
        <h2 class="type-h2 text-fg-strong">{{ $title }}</h2>

        <div class="flex items-center gap-2">
            <x-chant kind="sentence" :slides="$examples->map(fn (array $example): array => ['image' => $example['imageUrl'], 'title' => $example['sentence'], 'meaning' => $example['translation']])->all()" />

            @if ($examples->contains(fn (array $example): bool => filled($example['translation'])))
                <x-icon-button icon="eye-off" label="Ẩn nghĩa" size="sm" aria-pressed="true" data-example-toggle />
            @endif
        </div>
    </div>

    {{ $slot }}

    <ul class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($examples as $example)
            <li data-example class="flex flex-col gap-4">
                @if ($example['imageUrl'])
                    {{-- Clicking anywhere on the picture plays the sentence, as the audio button beside it does (see resources/js/app.js) --}}
                    <div data-example-image class="relative cursor-pointer overflow-hidden rounded-lg">
                        <img src="{{ $example['imageUrl'] }}" alt="" loading="lazy" class="aspect-video w-full object-cover">
                    </div>
                @endif

                <div class="flex items-start gap-4">
                    <x-audio-button size="sm" tone="success-soft" :text="$example['sentence']" :src="$example['audioUrl']" lang="en-US" />

                    <div class="min-w-0 flex-1">
                        <p lang="en" class="text-xl leading-snug font-semibold text-fg-strong">
                            @foreach ($example['segments'] as $segment)@if ($segment['word'])<x-word-popover :word="$segment['word']" :label="$segment['text']" :popover-id="$idPrefix.$loop->parent->index.'-'.$loop->index" />@elseif ($segment['isMarked'])<x-word-popover :label="$segment['text']" />@else{{ $segment['text'] }}@endif{{-- no space between segments --}}@endforeach
                        </p>
                        @if ($example['translation'])
                            <p data-example-meaning class="mt-1 type-body text-fg-muted">{{ $example['translation'] }}</p>
                        @endif
                    </div>
                </div>
            </li>
        @endforeach
    </ul>
</section>
