{{-- Top 10 card, titled by `title`. `learners` is a ranked list of ['name' => ..., 'points' => ..., 'progress' => ...]. --}}
@props([
    'title' => 'Top 10 học tốt nhất',
    'learners' => [],
])

@php
    $rankStyles = [
        'border-accent-border bg-accent-bg text-accent-text',
        'border-primary-border bg-primary-bg text-primary-text',
        'border-secondary-border bg-secondary-bg text-secondary-text',
    ];
@endphp

<div {{ $attributes->class(['flex flex-col gap-5 rounded-lg bg-surface-card p-6 shadow-card']) }}>
    <h2 class="type-h2 text-fg-strong">{{ $title }}</h2>

    <ol class="flex flex-col gap-4">
        @foreach ($learners as $index => $learner)
            <li class="grid grid-cols-[40px_minmax(0,1fr)_64px] items-center gap-4">
                <span @class([
                    'flex size-10 items-center justify-center rounded-full border type-label-lg',
                    $rankStyles[$index] ?? 'border-line-default bg-surface-hover text-fg-body',
                ])>{{ $index + 1 }}</span>

                <div class="flex min-w-0 flex-col gap-2">
                    <span class="truncate type-label text-fg-strong">{{ $learner['name'] }}</span>
                    <div class="flex items-center gap-2">
                        <x-progress-bar :value="$learner['progress']" tone="success" :label="$learner['name']" class="h-1.5! min-w-0 flex-1" />
                        <span class="flex-none type-caption text-fg-muted">{{ $learner['progress'] }}%</span>
                    </div>
                </div>

                <span class="text-right type-label-lg text-primary-text">{{ $learner['points'] }}</span>
            </li>
        @endforeach
    </ol>
</div>
