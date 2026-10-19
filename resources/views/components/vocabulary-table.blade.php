{{--
    Vocabulary table of a set of words (the lesson page and the vocabulary page): tools to expand, print, copy and zoom
    it, then the word, its UK / US audio and IPA, and its meanings with examples. The tools are wired by resources/js/app.js.
--}}
@props([
    'words',
    'emptyMessage' => 'Chưa có từ vựng.',
])

@php
    $headerCell = 'border-l border-line-default bg-surface-hover px-2 py-3 first:border-l-0 md:px-4 text-left text-[length:calc(var(--text-lg)*var(--vs,1))] font-medium whitespace-nowrap text-fg-body';
    $bodyCell = 'border-t border-l border-line-default px-2 py-3 first:border-l-0 md:px-4 align-middle';

    $copyText = $words
        ->map(fn ($word) => $word->word.' '.collect($word->meanings)
            ->map(fn (array $meaning): string => '('.\App\Models\Word::abbreviatePartOfSpeech($meaning['pos'] ?? '').') '.($meaning['meaning'] ?? ''))
            ->implode(' '))
        ->implode("\n");
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
            <x-icon-button icon="printer" label="In bảng từ vựng" size="sm" data-vocab-action="print" />
            <x-icon-button icon="copy" label="Sao chép từ vựng" size="sm" data-vocab-action="copy" />
            <x-icon-button icon="a-arrow-down" label="Giảm cỡ chữ" size="sm" data-vocab-action="zoom-out" />
            <x-icon-button icon="a-arrow-up" label="Tăng cỡ chữ" size="sm" data-vocab-action="zoom-in" />
        </div>
    </div>

    @if ($words->isEmpty())
        <p class="type-body text-fg-muted">{{ $emptyMessage }}</p>
    @else
        <div
            data-vocab-table
            data-copy-text="{{ $copyText }}"
            class="group/vocab overflow-x-auto rounded-sm border border-line-default"
        >
            <table class="w-full border-collapse max-md:group-data-[expanded]/vocab:min-w-190 md:min-w-190">
                <thead>
                    <tr>
                        <th scope="col" class="{{ $headerCell }}">Từ vựng</th>
                        <th scope="col" class="{{ $headerCell }}">UK</th>
                        <th scope="col" class="{{ $headerCell }}">US</th>
                        <th scope="col" class="{{ $headerCell }}">Nghĩa</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($words as $word)
                        <tr>
                            <td lang="en" class="{{ $bodyCell }} text-[length:calc(var(--text-xl)*var(--vs,1))] font-medium text-primary-text">{{ $word->word }}</td>
                            <td class="{{ $bodyCell }}">
                                <div class="flex items-center gap-2 md:gap-3">
                                    <x-audio-button size="sm" :text="$word->word" lang="en-GB" />
                                    <span class="max-md:group-not-data-[expanded]/vocab:hidden font-ipa text-[length:calc(var(--text-lg)*var(--vs,1))] whitespace-nowrap text-fg-body">{{ $word->ipa_uk }}</span>
                                </div>
                            </td>
                            <td class="{{ $bodyCell }}">
                                <div class="flex items-center gap-2 md:gap-3">
                                    <x-audio-button size="sm" tone="secondary" :text="$word->word" lang="en-US" />
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
