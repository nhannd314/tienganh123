@php
    $cardTones = ['primary', 'secondary', 'success', 'accent'];
@endphp

<x-layouts.app :title="$flashcardSet->title" main-class="flex flex-col gap-10 pt-6 pb-10">
    <x-slot:header>
        <x-site-header active="flashcards" />
    </x-slot:header>

    <x-breadcrumbs :items="[
        ['label' => 'Trang chủ', 'href' => route('home')],
        ['label' => 'Flashcard', 'href' => route('flashcards.index')],
        ['label' => $flashcardSet->title],
    ]" class="-mb-4" />

    <section class="flex flex-col items-start gap-4">
        <h1 class="type-page-title tracking-tight text-balance text-fg-strong">{{ $flashcardSet->title }}</h1>
        @if ($flashcardSet->description)
            <p class="max-w-200 type-body-lg text-pretty text-fg-body">{{ $flashcardSet->description }}</p>
        @endif
        @if ($flashcardSet->tags->isNotEmpty())
            <div class="flex flex-wrap gap-2">
                @foreach ($flashcardSet->tags as $tag)
                    <a href="{{ route('flashcards.index', ['tag' => $tag->slug]) }}" class="group no-underline hover:no-underline">
                        <x-pill tone="secondary" size="sm" class="transition-interactive group-hover:-translate-y-0.5 group-hover:shadow-[0_4px_10px_rgb(27_31_59/0.12)] group-active:translate-y-0">{{ $tag->name }}</x-pill>
                    </a>
                @endforeach
            </div>
        @endif
    </section>

    <div data-sidebar-layout class="group/layout flex flex-wrap items-start gap-10">
        <div class="flex min-w-0 flex-[999_1_560px] flex-col gap-10">
            @php($cards = $flashcards->filter(fn ($card): bool => filled($card->display_title)))
            @if ($cards->isEmpty())
                <x-card padding="lg" class="flex flex-col items-center gap-3 text-center">
                    <x-icon name="layers" size="xl" class="text-primary" />
                    <p class="type-body text-fg-muted">Bộ flashcard này chưa có thẻ nào.</p>
                </x-card>
            @else
                <section class="flex flex-col gap-5 rounded-lg bg-surface-card p-6 shadow-card">
                    <div class="flex items-center justify-between gap-4">
                        <h2 class="type-h2 text-fg-strong">{{ $flashcardSet->title }}</h2>

                        <div class="flex items-center gap-2">
                            <x-icon-button icon="eye-off" label="Ẩn từ" size="sm" aria-pressed="false" data-flashcard-toggle />
                            <x-icon-button icon="printer" label="In flashcard" size="sm" data-print-flashcards />
                            <x-icon-button icon="panel-right-close" label="Ẩn thanh bên" size="sm" aria-pressed="false" data-sidebar-toggle />
                        </div>
                    </div>

                    <div data-flashcards class="grid grid-cols-[repeat(auto-fill,minmax(min(100%,max(260px,calc((100%_-_2rem)/3))),1fr))] gap-4 group-data-[sidebar-hidden]/layout:grid-cols-[repeat(auto-fill,minmax(min(100%,max(220px,calc((100%_-_3rem)/4))),1fr))]">
                        @foreach ($cards as $flashcard)
                            @php($word = $flashcard->word)
                            <x-flashcard
                                :word="$flashcard->display_title"
                                :phonetic="$word?->ipa_us"
                                :meaning="$word?->firstPlainMeaning()"
                                :image="$flashcard->image_url ?? $word?->image_url"
                                :audio="$flashcard->audio_url"
                                :tone="$cardTones[$loop->index % count($cardTones)]"
                            />
                        @endforeach
                    </div>
                </section>
            @endif
        </div>

        <aside class="flex min-w-0 max-w-full flex-[1_1_360px] flex-col gap-6 group-data-[sidebar-hidden]/layout:hidden">
            <x-course-link-list :courses="$courses" />
            <x-flashcard-set-link-list title="Flashcard cùng chủ đề" :flashcard-sets="$sameTopic" />
            <x-flashcard-set-link-list title="Flashcard liên quan" :flashcard-sets="$sameCourse" />
        </aside>
    </div>

    <x-slot:footer>
        <x-site-footer />
    </x-slot:footer>
</x-layouts.app>
