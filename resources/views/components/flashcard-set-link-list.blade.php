{{--
    Sidebar card titled by `title`: links to flashcard sets (loaded withCount('flashcards')), each title in a borderless label
    of a random tone, followed by its card count. `empty` is shown when there are none.
--}}
@props([
    'title',
    'flashcardSets',
    'empty' => 'Chưa có bộ flashcard nào.',
])

@php
    $tones = ['primary', 'secondary', 'success', 'warning', 'danger', 'info'];
@endphp

<div {{ $attributes->class(['flex flex-col gap-5 rounded-lg bg-surface-card p-6 shadow-card']) }}>
    <h2 class="type-h2 text-fg-strong">{{ $title }}</h2>

    @if ($flashcardSets->isEmpty())
        <p class="type-body text-fg-muted">{{ $empty }}</p>
    @else
        <ul class="flex flex-col gap-3">
            @foreach ($flashcardSets as $flashcardSet)
                <li>
                    <a href="{{ route('flashcards.show', $flashcardSet) }}" class="group flex items-center justify-between gap-3 no-underline hover:no-underline">
                        <x-pill :tone="Illuminate\Support\Arr::random($tones)" class="h-auto! min-w-0 border-transparent! py-1.5 whitespace-normal! transition-interactive group-hover:-translate-y-0.5 group-hover:shadow-[0_4px_10px_rgb(27_31_59/0.12)] group-active:translate-y-0">{{ $flashcardSet->title }}</x-pill>
                        <span class="flex-none type-body text-fg-muted">{{ $flashcardSet->flashcards_count }} thẻ</span>
                    </a>
                </li>
            @endforeach
        </ul>
    @endif
</div>
