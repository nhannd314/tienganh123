{{-- Quiz question header: large listen button, the English word and its IPA with a small replay button. The data-prompt-* hooks let the review script swap the word. --}}
@props([
    'word',
    'phonetic' => null,
    'replay' => true,
])

<div {{ $attributes->class(['flex items-center gap-5']) }}>
    <x-audio-button :text="$word" lang="en-US" />

    <div class="grid gap-2">
        <div lang="en" data-prompt-word class="type-word tracking-tight text-fg-strong">{{ $word }}</div>

        <div data-prompt-ipa-row @if (blank($phonetic)) hidden @endif class="flex items-center gap-2 type-body-lg text-fg-muted">
            <span data-prompt-ipa class="font-ipa">{{ $phonetic }}</span>
            @if ($replay)
                <x-audio-button size="sm" :text="$word" lang="en-US" />
            @endif
        </div>
    </div>
</div>
