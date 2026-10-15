{{--
    Sidebar card titled by `title`: links to vocabularies (loaded withCount('words')), each title in a borderless label of a
    random tone, followed by its word count. `empty` is shown when there are none.
--}}
@props([
    'title',
    'vocabularies',
    'empty' => 'Chưa có từ vựng nào.',
])

@php
    $tones = ['primary', 'secondary', 'success', 'warning', 'danger', 'info'];
@endphp

<div {{ $attributes->class(['flex flex-col gap-5 rounded-lg bg-surface-card p-6 shadow-card']) }}>
    <h2 class="type-h2 text-fg-strong">{{ $title }}</h2>

    @if ($vocabularies->isEmpty())
        <p class="type-body text-fg-muted">{{ $empty }}</p>
    @else
        <ul class="flex flex-col gap-3">
            @foreach ($vocabularies as $vocabulary)
                <li>
                    <a href="{{ route('vocabularies.show', $vocabulary) }}" class="group flex items-center justify-between gap-3 no-underline hover:no-underline">
                        <x-pill :tone="Illuminate\Support\Arr::random($tones)" class="h-auto! min-w-0 border-transparent! py-1.5 whitespace-normal! transition-interactive group-hover:-translate-y-0.5 group-hover:shadow-[0_4px_10px_rgb(27_31_59/0.12)] group-active:translate-y-0">{{ $vocabulary->title }}</x-pill>
                        <span class="flex-none type-body text-fg-muted">{{ $vocabulary->words_count }} từ</span>
                    </a>
                </li>
            @endforeach
        </ul>
    @endif
</div>
