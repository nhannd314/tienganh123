{{-- Breadcrumb trail. `items` is a list of ['label' => ..., 'href' => ...]; the last item is the current page. --}}
@props([
    'items' => [],
])

<nav aria-label="Breadcrumb" {{ $attributes }}>
    <ol class="flex flex-wrap items-center gap-3 type-body">
        @foreach ($items as $item)
            <li class="flex items-center gap-3">
                @if ($loop->last)
                    <span aria-current="page" class="type-label text-fg-strong">{{ $item['label'] }}</span>
                @else
                    <a href="{{ $item['href'] ?? '#' }}" class="inline-flex items-center transition-interactive">{{ $item['label'] }}</a>
                    <span aria-hidden="true" class="text-fg-disabled">/</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
