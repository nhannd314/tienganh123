{{--
    Large text field used for typing answers. `state` = correct | wrong shows a status icon and
    tints the field like x-alert. `class` styles the wrapper; other attributes go to the <input>.
--}}
@props([
    'label' => null,
    'hint' => null,
    'state' => null,
])

@php
    $isCorrect = $state === 'correct';
    $isWrong = $state === 'wrong';
    $hintId = $hint ? 'hint-'.Str::random(8) : null;
@endphp

<label {{ $attributes->only('class')->class(['grid gap-2']) }}>
    @if ($label)
        <span class="type-label text-fg-body">{{ $label }}</span>
    @endif

    <span class="relative block">
        <input
            @if ($isWrong) aria-invalid="true" @endif
            @if ($hintId) aria-describedby="{{ $hintId }}" @endif
            {{ $attributes->except('class')->class([
                'h-14 w-full rounded-md border px-4 text-xl leading-snug text-fg-strong outline-none transition-interactive focus:shadow-focus',
                'disabled:cursor-not-allowed disabled:border-line-disabled disabled:bg-surface-disabled disabled:text-fg-disabled',
                'pr-12' => $state,
                'border-success-border bg-success-bg' => $isCorrect,
                'border-danger-border bg-danger-bg' => $isWrong,
                'border-line-default bg-surface-card hover:border-line-hover' => ! $state,
            ]) }}
        >

        @if ($state)
            <x-icon
                :name="$isCorrect ? 'circle-check' : 'circle-x'"
                size="lg"
                :class="Arr::toCssClasses(['absolute top-1/2 right-4 -translate-y-1/2', 'text-success-text' => $isCorrect, 'text-danger-text' => $isWrong])"
            />
        @endif
    </span>

    @if ($hint)
        <span id="{{ $hintId }}" @class([
            'type-body-sm',
            'text-success-text' => $isCorrect,
            'text-danger-text' => $isWrong,
            'text-fg-muted' => ! $state,
        ])>{{ $hint }}</span>
    @endif
</label>
