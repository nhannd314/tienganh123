{{--
    Vocabulary table of a set of words (the lesson page and the vocabulary page): tools to expand, print, copy and zoom
    it and to hide the sidebar, then the word, its UK / US audio and IPA, and its meanings with examples. The tools are
    wired by resources/js/app.js; the hide-sidebar button needs a `data-sidebar-layout` ancestor.
--}}
@props([
    'words',
    'emptyMessage' => 'Chưa có từ vựng.',
])

@php
    $headerCell = 'border-l border-line-default bg-surface-hover px-2 py-3 first:border-l-0 md:px-4 text-left text-[length:calc(var(--text-lg)*var(--vs,1))] font-medium whitespace-nowrap text-fg-body';
    $bodyCell = 'border-t border-l border-line-default px-2 py-3 first:border-l-0 md:px-4 align-middle';

    $repeatSlides = $words
        ->map(fn ($word): array => [
            'title' => $word->word,
            'ipaUk' => $word->ipa_uk,
            'ipaUs' => $word->ipa_us,
            'meaning' => $word->firstPlainMeaning(),
            'image' => $word->image_url,
        ])
        ->values()
        ->all();

    $copyItems = $words
        ->map(fn ($word): array => [
            'word' => $word->word,
            'meaning' => collect($word->meanings)
                ->map(fn (array $meaning): string => '['.\App\Models\Word::abbreviatePartOfSpeech($meaning['pos'] ?? '').'] '.($meaning['meaning'] ?? ''))
                ->implode(' '),
        ])
        ->values()
        ->all();
@endphp

<section {{ $attributes->class(['flex flex-col gap-5 rounded-lg bg-surface-card p-7 shadow-card']) }}>
    <div class="flex flex-wrap items-center justify-between gap-4">
        <h2 class="type-h2 text-fg-strong">Từ vựng</h2>

        <div class="flex items-center gap-2">
            <x-icon-button
                icon="eye"
                label="Hiển thị đầy đủ"
                size="sm"
                aria-pressed="false"
                data-vocab-action="expand"
                class="md:hidden"
            />
            @unless ($words->isEmpty())
                <x-icon-button icon="headphones" label="Nghe và đọc theo" size="sm" data-vocab-action="listen-repeat" />
            @endunless
            <x-icon-button icon="printer" label="In bảng từ vựng" size="sm" data-vocab-action="print" />
            <x-icon-button icon="copy" label="Sao chép từ vựng" size="sm" data-vocab-action="copy" />
            <x-icon-button icon="a-arrow-down" label="Giảm cỡ chữ" size="sm" data-vocab-action="zoom-out" />
            <x-icon-button icon="a-arrow-up" label="Tăng cỡ chữ" size="sm" data-vocab-action="zoom-in" />
            <x-sidebar-toggle />
        </div>
    </div>

    @unless ($words->isEmpty())
        <dialog
            data-repeat-dialog
            data-intro-src="{{ asset('audio/prompts/listen-and-repeat.mp3') }}"
            data-slides="{{ \Illuminate\Support\Js::encode($repeatSlides) }}"
            aria-label="Nghe và đọc theo"
            class="m-0 size-full max-h-none max-w-none overflow-hidden bg-fg-strong p-0 text-white backdrop:bg-black open:flex"
        >
            <div class="relative flex size-full flex-col">
                <div class="flex flex-none items-center justify-between gap-4 px-4 py-3 md:px-8">
                    <span data-repeat-counter class="type-body tabular-nums text-white/70"></span>

                    <button type="button" data-repeat-close aria-label="Đóng" title="Đóng" class="inline-flex size-11 cursor-pointer items-center justify-center rounded-full bg-white/10 text-white transition-colors hover:bg-white/25">
                        <x-icon name="x" size="lg" />
                    </button>
                </div>

                <div data-repeat-stage class="flex min-h-0 flex-1 flex-col items-center justify-center gap-6 px-6 pb-10 text-center md:gap-8">
                    <p data-repeat-intro lang="en" class="text-5xl leading-tight font-bold md:text-7xl">Listen and repeat</p>

                    <div data-repeat-word hidden class="flex flex-col items-center gap-6 md:gap-8">
                        <div data-repeat-picture hidden class="rounded-xl bg-white p-6 md:p-8">
                            <img data-repeat-image alt="" class="max-h-[40dvh] w-auto max-w-[min(100%,64rem)] rounded-lg object-contain">
                        </div>

                        <div class="flex max-w-5xl flex-col items-center gap-3">
                            <p data-repeat-title lang="en" class="text-5xl leading-tight font-bold text-balance md:text-7xl"></p>
                            <div class="flex items-center justify-center gap-3">
                                <span data-repeat-accent class="rounded-full bg-secondary px-2.5 py-0.5 text-sm font-semibold text-white"></span>
                                <p data-repeat-ipa class="font-ipa text-2xl text-white/80 md:text-4xl" style="font-family: var(--font-ipa)" hidden></p>
                            </div>
                            <p data-repeat-meaning class="text-2xl text-balance text-white/90 md:text-4xl" hidden></p>
                        </div>
                    </div>
                </div>
            </div>
        </dialog>
    @endunless

    @unless ($words->isEmpty())
        <dialog
            data-copy-dialog
            data-items="{{ \Illuminate\Support\Js::encode($copyItems) }}"
            aria-label="Sao chép từ vựng"
            class="m-auto w-[min(92vw,36rem)] rounded-lg bg-surface-card p-6 text-fg-body shadow-card backdrop:bg-black/50"
        >
            <div class="flex flex-col gap-4">
                <div class="flex items-center justify-between gap-4">
                    <h3 class="type-h3 text-fg-strong">Sao chép từ vựng</h3>
                    <x-icon-button icon="x" label="Đóng" size="sm" data-copy-close />
                </div>

                <label class="flex cursor-pointer items-center gap-2 type-body">
                    <x-checkbox size="sm" checked data-copy-meaning-toggle />
                    Hiển thị nghĩa
                </label>

                <textarea data-copy-output readonly rows="12" aria-label="Danh sách từ vựng" class="w-full resize-y rounded-md border border-line-subtle bg-surface-hover p-3 type-body text-fg-strong"></textarea>

                <div class="flex justify-end">
                    <x-button type="button" icon-left="copy" data-copy=""><span data-copy-label data-copied="Đã sao chép">Sao chép</span></x-button>
                </div>
            </div>
        </dialog>
    @endunless

    @if ($words->isEmpty())
        <p class="type-body text-fg-muted">{{ $emptyMessage }}</p>
    @else
        <div
            data-vocab-table
            class="group/vocab overflow-x-auto rounded-sm border border-line-default"
        >
            <table class="w-full border-collapse max-md:group-data-[expanded]/vocab:min-w-190 md:min-w-190">
                <thead>
                    <tr>
                        <th scope="col" class="{{ $headerCell }} w-12 text-center">STT</th>
                        <th scope="col" class="{{ $headerCell }}">Từ vựng</th>
                        <th scope="col" class="{{ $headerCell }}">UK</th>
                        <th scope="col" class="{{ $headerCell }}">US</th>
                        <th scope="col" class="{{ $headerCell }}">Nghĩa</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($words as $word)
                        <tr>
                            <td class="{{ $bodyCell }} w-12 text-center text-[length:calc(var(--text-lg)*var(--vs,1))] text-fg-muted">{{ $loop->iteration }}</td>
                            <td lang="en" class="{{ $bodyCell }} text-[length:calc(var(--text-xl)*var(--vs,1))] font-medium text-primary-text">{{ $word->word }}</td>
                            <td class="{{ $bodyCell }}">
                                <div class="flex items-center gap-2 md:gap-3">
                                    <x-audio-button size="sm" :text="$word->word" lang="en-GB" :src="$word->audio_uk_url" />
                                    <span class="max-md:group-not-data-[expanded]/vocab:hidden font-ipa text-[length:calc(var(--text-lg)*var(--vs,1))] whitespace-nowrap text-fg-body">{{ $word->ipa_uk }}</span>
                                </div>
                            </td>
                            <td class="{{ $bodyCell }}">
                                <div class="flex items-center gap-2 md:gap-3">
                                    <x-audio-button size="sm" tone="secondary" :text="$word->word" lang="en-US" :src="$word->audio_us_url" />
                                    <span class="max-md:group-not-data-[expanded]/vocab:hidden font-ipa text-[length:calc(var(--text-lg)*var(--vs,1))] whitespace-nowrap text-fg-body">{{ $word->ipa_us }}</span>
                                </div>
                            </td>
                            <td class="{{ $bodyCell }} text-[length:calc(var(--text-xl)*var(--vs,1))] text-fg-strong">
                                @foreach ($word->meanings as $meaning)
                                    <div class="font-semibold"><span class="font-normal text-fg-muted">({{ \App\Models\Word::abbreviatePartOfSpeech($meaning['pos'] ?? '') }})</span> {{ $meaning['meaning'] ?? '' }}</div>
                                @endforeach

                                @if (filled($word->examples))
                                    <div class="max-md:group-not-data-[expanded]/vocab:hidden mt-3 flex flex-col gap-1.5">
                                        @foreach ($word->examples as $example)
                                            <div class="text-[length:calc(var(--text-lg)*var(--vs,1))]">
                                                <p lang="en" class="text-fg-strong"><span class="text-fg-muted italic">e.g.</span> {{ $example['sentence'] ?? '' }}</p>
                                                @if (filled($example['translation'] ?? null))
                                                    <p class="text-fg-muted">{{ $example['translation'] }}</p>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</section>
