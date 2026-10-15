@php
    $thumbnailTones = ['bg-danger-bg', 'bg-primary-bg', 'bg-success-bg', 'bg-accent-bg', 'bg-secondary-bg'];
@endphp

<x-layouts.app title="Flashcard" main-class="flex flex-col gap-10 pt-6 pb-10">
    <x-slot:header>
        <x-site-header active="flashcards" />
    </x-slot:header>

    <x-breadcrumbs :items="[
        ['label' => 'Trang chủ', 'href' => route('home')],
        ['label' => 'Flashcard'],
    ]" class="-mb-4" />

    <section class="flex flex-col items-start gap-3">
        <h1 class="type-page-title tracking-tight text-balance text-fg-strong">Danh sách flashcard</h1>
        <p class="max-w-160 type-body-lg text-pretty text-fg-muted">
            Chọn một bộ flashcard để xem các thẻ và học từ vựng thật vui nhé!
        </p>
    </section>

    @if ($tags->isNotEmpty())
        <nav aria-label="Lọc theo thẻ" class="flex flex-wrap gap-2">
            <a href="{{ route('flashcards.index') }}" @if (! $activeTag) aria-current="page" @endif class="no-underline hover:no-underline">
                <x-pill :tone="$activeTag ? 'neutral' : 'primary'">Tất cả</x-pill>
            </a>
            @foreach ($tags as $tag)
                <a href="{{ route('flashcards.index', ['tag' => $tag->slug]) }}" @if ($activeTag?->is($tag)) aria-current="page" @endif class="no-underline hover:no-underline">
                    <x-pill :tone="$activeTag?->is($tag) ? 'primary' : 'neutral'">{{ $tag->name }}</x-pill>
                </a>
            @endforeach
        </nav>
    @endif

    @if ($flashcardSets->isEmpty())
        <x-card padding="lg" class="flex flex-col items-center gap-3 text-center">
            <x-icon name="layers" size="xl" class="text-primary" />
            <h2 class="type-h3 text-fg-strong">Chưa có flashcard nào</h2>
            <p class="type-body text-fg-muted">Các bộ flashcard mới sẽ sớm xuất hiện ở đây, bạn quay lại sau nhé!</p>
        </x-card>
    @else
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
            @foreach ($flashcardSets as $flashcardSet)
                <a href="{{ route('flashcards.show', $flashcardSet) }}" class="flex no-underline hover:no-underline">
                <x-card class="flex w-full flex-col gap-4 transition-interactive hover:-translate-y-0.5 hover:shadow-raised">
                    <div class="flex items-baseline justify-between gap-3">
                        <h2 class="min-w-0 truncate type-h3 text-fg-strong" title="{{ $flashcardSet->title }}">{{ $flashcardSet->title }}</h2>
                        <p class="flex-none type-label text-fg-muted">{{ $flashcardSet->flashcards_count }} thẻ</p>
                    </div>

                    <div class="flex items-center gap-4">
                        <span @class(['flex size-24 flex-none items-center justify-center rounded-xl', $thumbnailTones[$loop->index % count($thumbnailTones)]])>
                            @if ($flashcardSet->thumbnail_url)
                                <img src="{{ $flashcardSet->thumbnail_url }}" alt="" loading="lazy" class="size-20 object-contain">
                            @else
                                <x-icon name="image" size="lg" class="text-fg-disabled" />
                            @endif
                        </span>

                        <div class="flex min-w-0 flex-1 flex-col gap-3">
                            @if ($flashcardSet->tags->isNotEmpty())
                                <div class="flex flex-wrap gap-2">
                                    @foreach ($flashcardSet->tags as $tag)
                                        <x-pill tone="secondary" size="sm">{{ $tag->name }}</x-pill>
                                    @endforeach
                                </div>
                            @endif

                            @if ($flashcardSet->flashcards->isNotEmpty())
                                <div class="flex flex-wrap items-center gap-2">
                                    @foreach ($flashcardSet->flashcards as $flashcard)
                                        <x-pill tone="neutral" size="sm" class="border-transparent! bg-surface-hover!">{{ $flashcard->display_title }}</x-pill>
                                    @endforeach
                                    @if ($flashcardSet->flashcards_count > $previewCards)
                                        <span class="type-label-sm text-fg-muted">+{{ $flashcardSet->flashcards_count - $previewCards }}</span>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                </x-card>
                </a>
            @endforeach
        </div>

        {{ $flashcardSets->links('pagination.default') }}
    @endif

    <x-slot:footer>
        <x-site-footer />
    </x-slot:footer>
</x-layouts.app>
