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
                <x-flashcard-set-card :title="$flashcardSet->title" :cards="$cards" />
            @endif

            @if ($exampleSentences->isNotEmpty())
                <x-example-sentences :examples="$exampleSentences" />
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
