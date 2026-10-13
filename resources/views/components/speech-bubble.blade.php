{{-- Short English exclamation in a bubble ("Let's practice!"). `tail` = left | right. --}}
@props([
    'tail' => 'left',
])

<span {{ $attributes->class([
    'relative inline-block rounded-xl bg-surface-card px-4 py-2 text-center type-label-lg text-primary-text shadow-card',
]) }}>
    {{ $slot }}
    <span aria-hidden="true" @class([
        'absolute -bottom-2 size-4 rotate-45 rounded-xs bg-surface-card',
        'right-5' => $tail === 'right',
        'left-5' => $tail !== 'right',
    ])></span>
</span>
