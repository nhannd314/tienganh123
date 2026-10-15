{{-- The rows of the best times list of a game. `times` is a ranked list of ['name' => ..., 'avatarUrl' => ..., 'initial' => ..., 'seconds' => ..., 'isMine' => ...]. --}}
@php
    $rankStyles = [
        'border-accent bg-accent-bg text-accent-text',
        'border-primary bg-primary-bg text-primary-text',
        'border-secondary bg-secondary-bg text-secondary-text',
    ];
@endphp

@forelse ($times as $index => $time)
    <li class="grid grid-cols-[48px_minmax(0,1fr)_auto] items-center gap-3">
        <span @class([
            'flex size-12 items-center justify-center overflow-hidden rounded-full border-2 type-label-lg',
            $rankStyles[$index] ?? 'border-line-default bg-surface-hover text-fg-body',
        ])>
            @if ($time['avatarUrl'])
                <img src="{{ $time['avatarUrl'] }}" alt="" class="size-full object-cover">
            @else
                {{ $time['initial'] }}
            @endif
        </span>

        <span class="flex min-w-0 flex-col">
            <span class="type-body-sm text-fg-muted">Top {{ $index + 1 }}</span>
            <span @class(['truncate type-body', 'text-fg-strong', 'font-bold' => $time['isMine']])>
                {{ $time['name'] }}@if ($time['isMine']) <span class="text-fg-muted">(bạn)</span>@endif
            </span>
        </span>

        <span class="text-right type-label-lg text-primary-text tabular-nums">{{ $time['seconds'] }}s</span>
    </li>
@empty
    <li class="type-body text-fg-muted">Chưa có ai về đích. Hãy là người đầu tiên nhé!</li>
@endforelse
