{{--
    Clickable lesson/course tile with a 3:2 thumbnail, title, optional tags (each a pill of `label`, `tone`, `icon`; `end` pushes it to the right),
    a two-line description and optional meta (slot). The `overlay` slot is laid over the thumbnail (badges); `placeholderIcon` (null for none) shows while there is no thumbnail; the `progress` slot sits between thumbnail and title. `horizontal` puts the thumbnail on the left and the text on the right, the title cut to one line. `solid` fills the thumbnail with the strong tone instead of the pale one.
--}}
@props([
    'title',
    'href' => '#',
    'description' => null,
    'thumbnail' => null,
    'thumbnailFit' => 'cover',
    'placeholderIcon' => 'image',
    'tone' => 'primary',
    'horizontal' => false,
    'solid' => false,
    'tags' => [],
])

@php
    $thumbnailBackground = [
        'primary' => 'bg-primary-bg',
        'secondary' => 'bg-secondary-bg',
        'accent' => 'bg-accent-bg',
        'success' => 'bg-success-bg',
        'danger' => 'bg-danger-bg',
        'info' => 'bg-info-bg',
        'neutral' => 'bg-surface-hover',
    ][$tone] ?? 'bg-primary-bg';

    if ($solid) {
        $thumbnailBackground = [
            'primary' => 'bg-primary',
            'secondary' => 'bg-secondary',
            'accent' => 'bg-accent',
            'success' => 'bg-success',
            'danger' => 'bg-danger',
            'info' => 'bg-info',
            'neutral' => 'bg-ink-400',
        ][$tone] ?? 'bg-primary';
    }
@endphp

<a href="{{ $href }}" {{ $attributes->class([
    'group flex w-full overflow-hidden rounded-lg border border-line-subtle bg-surface-card text-left text-inherit no-underline shadow-card transition-interactive',
    'flex-col' => ! $horizontal,
    'hover:-translate-y-0.5 hover:text-inherit hover:no-underline hover:shadow-raised active:translate-y-0',
]) }}>
    <div @class([
        'relative flex items-center justify-center overflow-hidden',
        'aspect-3/2 w-full' => ! $horizontal,
        'w-24 flex-none sm:w-30' => $horizontal,
        $thumbnailBackground,
    ])>
        @if ($thumbnail)
            <img src="{{ $thumbnail }}" alt="" @class([
                'size-full',
                'object-contain p-6' => $thumbnailFit === 'contain',
                'object-cover' => $thumbnailFit !== 'contain',
            ])>
        @elseif ($placeholderIcon)
            <x-icon :name="$placeholderIcon" size="xl" class="text-fg-disabled" />
        @endif

        {{ $overlay ?? '' }}
    </div>

    <div @class(['grid gap-2 p-5', 'min-w-0 flex-1 grid-cols-1 content-center' => $horizontal])>
        {{ $progress ?? '' }}

        <h3 @class(['type-h3 text-fg-strong transition-interactive group-hover:text-primary-text', 'truncate' => $horizontal]) @if ($horizontal) title="{{ $title }}" @endif>{{ $title }}</h3>

        @if ($tags)
            <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                @foreach ($tags as $tag)
                    <x-pill :tone="$tag['tone'] ?? 'primary'" :icon="$tag['icon'] ?? null" size="sm" :class="($tag['end'] ?? false) ? 'h-6! rounded-sm! px-2! text-xs! ml-auto' : 'h-6! rounded-sm! px-2! text-xs!'">{{ $tag['label'] }}</x-pill>
                @endforeach
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
