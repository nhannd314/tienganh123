{{--
    Clickable lesson/course tile with a 16:9 thumbnail, title, an optional price label, a two-line description and optional
    meta (slot). With `discount` (a percentage) the thumbnail carries a sale badge and `originalPrice` is struck through
    beside the price.
--}}
@props([
    'title',
    'href' => '#',
    'description' => null,
    'thumbnail' => null,
    'thumbnailFit' => 'cover',
    'tone' => 'primary',
    'price' => null,
    'priceTone' => 'primary',
    'priceIcon' => 'tag',
    'originalPrice' => null,
    'discount' => null,
])

@php
    $thumbnailBackground = [
        'primary' => 'bg-primary-bg',
        'secondary' => 'bg-secondary-bg',
        'accent' => 'bg-accent-bg',
        'success' => 'bg-success-bg',
        'danger' => 'bg-danger-bg',
        'info' => 'bg-info-bg',
    ][$tone] ?? 'bg-primary-bg';
@endphp

<a href="{{ $href }}" {{ $attributes->class([
    'group flex w-full flex-col overflow-hidden rounded-lg border border-line-subtle bg-surface-card text-left text-inherit no-underline shadow-card transition-interactive',
    'hover:-translate-y-0.5 hover:text-inherit hover:no-underline hover:shadow-raised active:translate-y-0',
]) }}>
    <div @class(['relative flex aspect-video w-full items-center justify-center overflow-hidden', $thumbnailBackground])>
        @if ($discount)
            <span class="absolute top-3 left-3 z-1 rounded-sm bg-danger px-2 py-0.5 text-sm font-semibold text-on-danger shadow-xs">Sale {{ $discount }}%</span>
        @endif

        @if ($thumbnail)
            <img src="{{ $thumbnail }}" alt="" @class([
                'size-full',
                'object-contain p-6' => $thumbnailFit === 'contain',
                'object-cover' => $thumbnailFit !== 'contain',
            ])>
        @else
            <x-icon name="image" size="xl" class="text-fg-disabled" />
        @endif
    </div>

    <div class="grid gap-2 p-5">
        <h3 class="type-h3 text-fg-strong transition-interactive group-hover:text-primary-text">{{ $title }}</h3>

        @if ($price)
            <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                <x-pill :tone="$priceTone" :icon="$priceIcon" size="sm" class="h-7! rounded-sm! px-2.5!">{{ $price }}</x-pill>
                @if ($originalPrice)
                    <del class="type-body-sm text-fg-muted">{{ $originalPrice }}</del>
                @endif
            </div>
        @endif

        @if ($description)
            <p class="line-clamp-2 type-body-sm text-fg-muted">{{ $description }}</p>
        @endif

        @if ($slot->isNotEmpty())
            <div class="mt-2 flex flex-wrap gap-2">{{ $slot }}</div>
        @endif
    </div>
</a>
