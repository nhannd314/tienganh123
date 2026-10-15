{{-- The rows of the best times list of a game. `times` is a ranked list of ['name' => ..., 'seconds' => ..., 'isMine' => ...]. --}}
@php
    $rankStyles = [
        'border-accent-border bg-accent-bg text-accent-text',
        'border-primary-border bg-primary-bg text-primary-text',
        'border-secondary-border bg-secondary-bg text-secondary-text',
    ];
@endphp

@forelse ($times as $index => $time)
    <li class="grid grid-cols-[40px_minmax(0,1fr)_auto] items-center gap-3">
        <span @class([
            'flex size-10 items-center justify-center rounded-full border type-label-lg',
            $rankStyles[$index] ?? 'border-line-default bg-surface-hover text-fg-body',
        ])>{{ $index + 1 }}</span>

        <span @class(['truncate type-label', 'text-fg-strong', 'font-bold' => $time['isMine']])>
            {{ $time['name'] }}@if ($time['isMine']) <span class="text-fg-muted">(bạn)</span>@endif
        </span>

        <span class="text-right type-label-lg text-primary-text tabular-nums">{{ $time['seconds'] }}s</span>
    </li>
@empty
    <li class="type-body text-fg-muted">Chưa có ai về đích. Hãy là người đầu tiên nhé!</li>
@endforelse
