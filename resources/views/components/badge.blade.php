{{-- Wraps an element and pins an unread counter to its top-right corner. Hidden when count is 0. --}}
@props([
    'count' => 0,
    'inset' => false,
])

<span {{ $attributes->class(['relative inline-flex']) }}>
    {{ $slot }}

    @if ($count > 0)
        <span
            aria-label="{{ $count }} thông báo mới"
            @class([
                'pointer-events-none absolute flex h-5 min-w-5 items-center justify-center rounded-full border-2 border-white bg-danger px-1 text-xs leading-none font-semibold text-on-danger',
                'top-2 right-2' => $inset,
                '-top-1 -right-1' => ! $inset,
            ])
        >{{ $count > 9 ? '9+' : $count }}</span>
    @endif
</span>
