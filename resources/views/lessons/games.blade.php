@php
    $toneClasses = [
        'primary' => 'bg-primary-bg text-primary',
        'secondary' => 'bg-secondary-bg text-secondary',
        'accent' => 'bg-accent-bg text-accent-text',
    ];
@endphp

<x-layouts.app :title="'Trò chơi '.$lesson->title" main-class="flex flex-col gap-6 pt-6 pb-10">
    <x-slot:header>
        <x-site-header active="courses" />
    </x-slot:header>

    <x-breadcrumbs :items="[
        ['label' => 'Trang chủ', 'href' => route('home')],
        ['label' => 'Khóa học', 'href' => route('courses.index')],
        ['label' => $course->title, 'href' => route('courses.show', $course)],
        ['label' => $lesson->title, 'href' => route('lessons.show', [$course, $unit, $lesson])],
        ['label' => 'Trò chơi'],
    ]" />

    <div class="flex flex-col gap-2">
        <h1 class="type-page-title tracking-tight text-balance text-fg-strong">Trò chơi {{ $lesson->title }}</h1>
        <p class="type-body-lg text-fg-muted">Vừa chơi vừa học từ vựng của bài nhé!</p>
    </div>

    <ul class="grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($games as $game)
            <li data-game class="flex flex-col overflow-hidden rounded-lg bg-surface-card shadow-card transition-interactive hover:-translate-y-0.5 hover:shadow-raised">
                <a href="{{ $game['url'] ?? '#' }}" tabindex="-1">
                    @if ($game['thumbnailUrl'])
                        <img src="{{ $game['thumbnailUrl'] }}" alt="{{ $game['title'] }}" class="aspect-video w-full object-cover" loading="lazy">
                    @else
                        <div @class(['flex aspect-video w-full items-center justify-center', $toneClasses[$game['tone']]])>
                            <x-icon :name="$game['icon']" class="text-7xl!" />
                        </div>
                    @endif
                </a>

                <div class="flex flex-1 flex-col gap-4 p-5">
                    <h2 class="type-h3 text-fg-strong">{{ $game['title'] }}</h2>
                    <x-button variant="secondary" icon-left="gamepad-2" :href="$game['url'] ?? '#'" full-width class="mt-auto">Chơi ngay</x-button>
                </div>
            </li>
        @endforeach
    </ul>

    <x-slot:footer>
        <x-site-footer />
    </x-slot:footer>
</x-layouts.app>
