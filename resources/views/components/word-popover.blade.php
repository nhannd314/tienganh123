{{--
    Inline word label inside lesson content. `label` overrides the text of the label (e.g. an inflected form of the word). Clicking it opens a popover with the IPA, listen buttons and meanings.
    Uses the native Popover API; resources/js/app.js positions it and points the arrow at the label.
    Spans only, so it stays valid inside a paragraph.
--}}
@props([
    'word',
    'popoverId',
    'label' => null,
])

<span class="inline">
    <button
        type="button"
        popovertarget="{{ $popoverId }}"
        {{ $attributes->class('inline cursor-pointer rounded-xs border border-primary-border bg-primary-bg px-1.5 font-medium text-primary-text transition-interactive hover:bg-primary-bg-hover') }}
    >{{ $label ?? $word->word }}</button>

    <span
        id="{{ $popoverId }}"
        popover
        data-word-popover
        data-placement="bottom"
        class="group m-0 w-72 max-w-[calc(100vw-1rem)] flex-col gap-3 overflow-visible rounded-lg border border-line-default bg-surface-card p-4 text-left shadow-raised [&:popover-open]:flex"
    >
        <span aria-hidden="true" class="absolute -top-1.5 left-(--arrow-x) -ml-1.5 size-3 rotate-45 border-t border-l border-line-default bg-surface-card group-data-[placement=top]:top-auto group-data-[placement=top]:-bottom-1.5 group-data-[placement=top]:border-t-0 group-data-[placement=top]:border-l-0 group-data-[placement=top]:border-r group-data-[placement=top]:border-b"></span>

        <span lang="en" class="type-h3 text-primary-text">{{ $word->word }}</span>

        @foreach (['UK' => [$word->ipa_uk, 'en-GB'], 'US' => [$word->ipa_us, 'en-US']] as $region => [$ipa, $lang])
            @if (filled($ipa))
                <span class="flex items-center gap-3">
                    <x-audio-button size="sm" :text="$word->word" :lang="$lang" />
                    <span class="type-caption text-fg-muted">{{ $region }}</span>
                    <span class="type-body font-ipa whitespace-nowrap text-fg-body">{{ $ipa }}</span>
                </span>
            @endif
        @endforeach

        @if (! empty($word->meanings))
            <span class="flex flex-col gap-1 border-t border-line-subtle pt-3 type-body text-fg-strong">
                @foreach ($word->meanings as $meaning)
                    <span><span class="text-fg-muted">({{ $meaning['pos'] ?? '' }})</span> {{ $meaning['meaning'] ?? '' }}</span>
                @endforeach
            </span>
        @endif
    </span>
</span>