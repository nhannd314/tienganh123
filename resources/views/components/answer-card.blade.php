{{--
    Multiple-choice answer tile. `selected` marks the learner's pick; `state` = correct | wrong reveals the result
    (icon + screen-reader text, never colour alone). `indicator` = false drops the corner icon and relies on the card colours and the screen-reader text.
--}}
@props([
    'selected' => false,
    'state' => null,
    'indicator' => true,
])

@php
    $isCorrect = $state === 'correct';
    $isWrong = $state === 'wrong';
@endphp

<button
    type="button"
    aria-pressed="{{ $selected ? 'true' : 'false' }}"
    {{ $attributes->class([
        'relative flex min-h-40 w-full cursor-pointer items-center justify-center rounded-md border px-8 py-6 text-center type-answer text-fg-strong transition-interactive',
        'disabled:cursor-default',
        'border-success-border bg-success-bg' => $isCorrect,
        'border-danger-border bg-danger-bg' => $isWrong,
        'border-primary-border bg-primary-bg' => $selected && ! $state,
        'border-line-default bg-surface-card not-disabled:hover:-translate-y-0.5 not-disabled:hover:border-line-hover not-disabled:hover:shadow-card not-disabled:active:translate-y-0' => ! $selected && ! $state,
    ]) }}
>
    @if ($indicator)
        <span class="absolute top-3 right-3 flex">
            @if ($state)
                <span aria-hidden="true" @class([
                    'flex size-8 items-center justify-center rounded-full text-white',
                    'bg-success' => $isCorrect,
                    'bg-danger' => $isWrong,
                ])>
                    <x-icon :name="$isCorrect ? 'check' : 'x'" size="md" />
                </span>
            @else
                <x-checkbox :checked="$selected" decorative size="lg" />
            @endif
        </span>
    @endif

    {{ $slot }}

    @if ($state)
        <span class="sr-only">{{ $isCorrect ? ' — đáp án đúng' : ' — đáp án sai' }}</span>
    @endif
</button>
