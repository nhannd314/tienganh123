{{--
    Button with a hard bottom edge (no blurred shadow). Hover darkens filled variants or tints
    outline/ghost; press sinks the button onto its edge. Renders an <a> when `href` is given.
    Pass `icon` (without a slot) for an icon-only square/circle button — see x-icon-button.
--}}
@props([
    'variant' => 'primary',
    'size' => 'md',
    'icon' => null,
    'iconLeft' => null,
    'iconRight' => null,
    'shape' => 'square',
    'fullWidth' => false,
    'loading' => false,
    'href' => null,
    'type' => 'button',
])

@php
    $variantClasses = [
        'primary' => 'border-primary bg-primary text-on-primary edge-primary-strong not-disabled:hover:brightness-92',
        'secondary' => 'border-secondary bg-secondary text-on-secondary edge-secondary-strong not-disabled:hover:brightness-92',
        'accent' => 'border-accent bg-accent text-on-accent edge-accent-strong not-disabled:hover:brightness-92',
        'danger' => 'border-danger bg-danger text-on-danger edge-danger-strong not-disabled:hover:brightness-92',
        'outline' => 'border-primary-border bg-surface-card text-primary edge-primary-border [--edge:2px] not-disabled:hover:bg-primary-bg',
        'soft' => 'border-primary-bg-hover bg-primary-bg-hover text-primary-text edge-primary-border not-disabled:hover:brightness-92',
        'ghost' => 'border-transparent bg-transparent text-primary not-disabled:hover:bg-primary-bg',
    ][$variant] ?? '';

    $pressClasses = match ($variant) {
        'ghost' => 'not-disabled:active:scale-97',
        'outline' => 'not-disabled:active:translate-y-[3px] not-disabled:active:[--edge:0px]',
        default => 'not-disabled:active:translate-y-[3px] not-disabled:active:[--edge:1px]',
    };

    $sizes = [
        'sm' => ['base' => 'h-10 min-w-10 gap-2 type-label', 'padding' => 'px-4', 'square' => 'w-10', 'radius' => 'rounded-sm', 'icon' => 'md'],
        'md' => ['base' => 'h-12 min-w-12 gap-2 type-label-lg', 'padding' => 'px-6', 'square' => 'w-12', 'radius' => 'rounded-md', 'icon' => 'md'],
        'lg' => ['base' => 'h-14 min-w-14 gap-3 type-label-xl', 'padding' => 'px-8', 'square' => 'w-14', 'radius' => 'rounded-md', 'icon' => 'lg'],
    ];
    $sizeConfig = $sizes[$size] ?? $sizes['md'];

    $isIconOnly = $icon !== null;

    $classes = [
        'inline-flex shrink-0 cursor-pointer items-center justify-center border-2 whitespace-nowrap no-underline transition-interactive',
        'disabled:cursor-not-allowed disabled:border-line-disabled disabled:bg-surface-disabled disabled:text-fg-disabled disabled:shadow-none',
        $variantClasses,
        $pressClasses,
        $sizeConfig['base'],
        $isIconOnly ? $sizeConfig['square'] : $sizeConfig['padding'],
        $shape === 'circle' ? 'rounded-full' : $sizeConfig['radius'],
        'w-full' => $fullWidth,
        'cursor-progress' => $loading,
    ];

    $tag = $href ? 'a' : 'button';
@endphp

<{{ $tag }}
    @if ($href) href="{{ $href }}" @else type="{{ $type }}" @endif
    @if ($loading) aria-busy="true" @endif
    {{ $attributes->class($classes) }}
>
    @if ($loading)
        <x-icon name="loader-circle" :size="$sizeConfig['icon']" spin />
    @elseif ($isIconOnly)
        <x-icon :name="$icon" :size="$sizeConfig['icon']" />
    @elseif ($iconLeft)
        <x-icon :name="$iconLeft" :size="$sizeConfig['icon']" />
    @endif

    {{ $slot }}

    @if (! $loading && $iconRight)
        <x-icon :name="$iconRight" :size="$sizeConfig['icon']" />
    @endif
</{{ $tag }}>
