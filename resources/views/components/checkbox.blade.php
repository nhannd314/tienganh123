{{--
    Round checkbox. Renders a real <input type="checkbox"> (attributes go to the input);
    `decorative` renders a non-interactive look-alike for use inside other controls (e.g. x-answer-card).
--}}
@props([
    'size' => 'md',
    'decorative' => false,
    'checked' => false,
])

@php
    $sizes = [
        'sm' => ['box' => 'size-5', 'icon' => 'sm'],
        'md' => ['box' => 'size-6', 'icon' => 'sm'],
        'lg' => ['box' => 'size-8', 'icon' => 'md'],
    ];
    $sizeConfig = $sizes[$size] ?? $sizes['md'];
@endphp

@if ($decorative)
    <span
        aria-hidden="true"
        {{ $attributes->class([
            'inline-flex flex-none items-center justify-center rounded-full border-2 text-on-primary',
            $sizeConfig['box'],
            'border-primary bg-primary' => $checked,
            'border-line-strong bg-surface-card' => ! $checked,
        ]) }}
    >
        @if ($checked)
            <x-icon name="check" :size="$sizeConfig['icon']" />
        @endif
    </span>
@else
    <span @class(['relative inline-flex flex-none', $sizeConfig['box']])>
        <input
            type="checkbox"
            @checked($checked)
            {{ $attributes->class([
                'peer size-full cursor-pointer appearance-none rounded-full border-2 border-line-strong bg-surface-card transition-interactive',
                'hover:border-primary active:scale-92 checked:border-primary checked:bg-primary',
                'disabled:cursor-not-allowed disabled:border-line-disabled disabled:bg-surface-disabled disabled:checked:bg-fg-disabled',
            ]) }}
        >
        <x-icon
            name="check"
            :size="$sizeConfig['icon']"
            class="pointer-events-none absolute inset-0 hidden items-center justify-center text-on-primary peer-checked:flex"
        />
    </span>
@endif
