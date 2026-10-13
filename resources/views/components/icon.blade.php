{{-- Lucide glyph from the lucide-static icon font. Sizes: sm 16 · md 20 · lg 24 · xl 32. --}}
@props([
    'name',
    'size' => 'md',
    'label' => null,
    'spin' => false,
])

@php
    $sizeClass = [
        'sm' => 'text-base',
        'md' => 'text-xl',
        'lg' => 'text-2xl',
        'xl' => 'text-[32px]',
    ][$size] ?? 'text-xl';
@endphp

<i
    {{ $attributes->class(["icon-{$name}", $sizeClass, 'inline-flex flex-none leading-none', 'animate-spin' => $spin]) }}
    @if ($label) role="img" aria-label="{{ $label }}" @else aria-hidden="true" @endif
    @if ($spin) data-spin @endif
></i>
