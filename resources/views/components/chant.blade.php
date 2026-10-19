{{--
    "Chant" icon button and its full screen slideshow: the slides (picture, text, IPA and meaning) are gone through by hand (wide side zones) while a song
    from public/chant plays, and the picture of a flashcard moves. The player is wired by resources/js/app.js.
    `slides` is a list of {image, title, ipa, meaning}, each part optional but the title. `kind` is "flashcard" (word, IPA, meaning) or
    "sentence" (a lighter sentence with a smaller meaning).
--}}
@props([
    'slides' => [],
    'kind' => 'flashcard',
])

@php
    $slides = array_values($slides);

    $isSentence = $kind === 'sentence';

    $tracks = collect(glob(public_path('chant').'/*.{mp3,ogg,wav,m4a}', GLOB_BRACE) ?: [])
        ->sort()
        ->map(fn (string $path): string => asset('chant/'.basename($path)))
        ->values()
        ->all();
@endphp

<span {{ $attributes->class(['contents']) }} data-chant>
    <x-icon-button icon="music" label="Chant" size="sm" data-chant-open :disabled="$slides === []" />

    @if ($slides !== [])
        <dialog
            data-chant-dialog
            data-slides="{{ \Illuminate\Support\Js::encode($slides) }}"
            data-tracks="{{ \Illuminate\Support\Js::encode($tracks) }}"
            aria-label="Chant"
            class="m-0 size-full max-h-none max-w-none overflow-hidden bg-fg-strong p-0 text-white backdrop:bg-black open:flex"
        >
            <div class="relative flex size-full flex-col">
                <div class="relative z-20 flex flex-none items-center justify-between gap-4 px-4 py-3 md:px-8">
                    <span data-chant-counter class="type-body tabular-nums text-white/70"></span>

                    <div class="flex items-center gap-2">
                        @foreach ([['close', 'x', 'Đóng']] as [$action, $icon, $label])
                            <button type="button" data-chant-{{ $action }} aria-label="{{ $label }}" title="{{ $label }}" class="inline-flex size-11 cursor-pointer items-center justify-center rounded-full bg-white/10 text-white transition-colors hover:bg-white/25">
                                <x-icon :name="$icon" size="lg" data-chant-icon />
                            </button>
                        @endforeach
                    </div>
                </div>

                <div data-chant-stage class="flex min-h-0 flex-1 cursor-pointer flex-col items-center justify-center gap-6 px-6 pb-10 text-center md:gap-8">
                    <img data-chant-image @if (! $isSentence) data-chant-animate @endif alt="" class="max-h-[55dvh] w-auto max-w-[min(100%,64rem)] rounded-lg object-contain" hidden>

                    <div class="flex max-w-5xl flex-col items-center gap-3">
                        <p data-chant-title lang="en" @class(['text-4xl leading-tight text-balance md:text-6xl', 'font-bold' => ! $isSentence, 'font-medium' => $isSentence])></p>
                        <p data-chant-ipa class="font-ipa text-2xl text-white/80 md:text-4xl" style="font-family: var(--font-ipa)" hidden></p>
                        <p data-chant-meaning @class(['text-balance text-white/90', 'text-2xl md:text-4xl' => ! $isSentence, 'text-xl md:text-2xl' => $isSentence]) hidden></p>
                    </div>
                </div>

                {{-- Wide side zones under the header: a click anywhere on them goes to the previous or the next slide --}}
                @foreach ([['prev', 'chevron-left', 'Slide trước', 'left-0'], ['next', 'chevron-right', 'Slide sau', 'right-0']] as [$action, $icon, $label, $side])
                    <button type="button" data-chant-{{ $action }} aria-label="{{ $label }}" title="{{ $label }}" class="group absolute {{ $side }} top-16 bottom-0 z-10 flex w-1/5 min-w-20 cursor-pointer items-center justify-center text-white/60">
                        <span class="inline-flex size-14 items-center justify-center rounded-full bg-white/10 transition-colors group-hover:bg-white/25">
                            <x-icon :name="$icon" size="lg" />
                        </span>
                    </button>
                @endforeach
            </div>
        </dialog>
    @endif
</span>
