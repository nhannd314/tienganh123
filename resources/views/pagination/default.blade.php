{{-- Numbered pager in the design-system style: previous/next icon buttons around a window of page links. --}}
@php
    $itemClass = 'inline-flex size-12 items-center justify-center rounded-md border-2 type-label-lg no-underline transition-interactive hover:no-underline';
@endphp

@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Phân trang" class="flex flex-wrap items-center justify-center gap-2">
        @if ($paginator->onFirstPage())
            <span aria-disabled="true" aria-label="Trang trước" @class([$itemClass, 'cursor-not-allowed border-line-disabled bg-surface-disabled text-fg-disabled'])>
                <x-icon name="chevron-left" />
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Trang trước" @class([$itemClass, 'border-primary-border bg-surface-card text-primary hover:bg-primary-bg hover:text-primary'])>
                <x-icon name="chevron-left" />
            </a>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))
                <span aria-hidden="true" class="inline-flex size-12 items-center justify-center type-label-lg text-fg-muted">{{ $element }}</span>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span aria-current="page" @class([$itemClass, 'border-primary bg-primary text-on-primary'])>{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" aria-label="Trang {{ $page }}" @class([$itemClass, 'border-line-default bg-surface-card text-fg-body hover:bg-primary-bg hover:text-primary-text'])>{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Trang sau" @class([$itemClass, 'border-primary-border bg-surface-card text-primary hover:bg-primary-bg hover:text-primary'])>
                <x-icon name="chevron-right" />
            </a>
        @else
            <span aria-disabled="true" aria-label="Trang sau" @class([$itemClass, 'cursor-not-allowed border-line-disabled bg-surface-disabled text-fg-disabled'])>
                <x-icon name="chevron-right" />
            </span>
        @endif
    </nav>
@endif
