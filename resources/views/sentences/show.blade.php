<x-layouts.app :title="$sentenceSet->title" main-class="flex flex-col gap-10 pt-6 pb-10">
    <x-slot:header>
        <x-site-header active="sentences" />
    </x-slot:header>

    <x-breadcrumbs :items="[
        ['label' => 'Trang chủ', 'href' => route('home')],
        ['label' => 'Câu mẫu', 'href' => route('sentences.index')],
        ['label' => $sentenceSet->title],
    ]" class="-mb-4" />

    <section class="flex flex-col items-start gap-4">
        <h1 class="type-page-title tracking-tight text-balance text-fg-strong">{{ $sentenceSet->title }}</h1>
        @if ($sentenceSet->description)
            <p class="max-w-200 type-body-lg text-pretty text-fg-body">{{ $sentenceSet->description }}</p>
        @endif
        @if ($sentenceSet->tags->isNotEmpty())
            <div class="flex flex-wrap gap-2">
                @foreach ($sentenceSet->tags as $tag)
                    <a href="{{ route('sentences.index', ['tag' => $tag->slug]) }}" class="group no-underline hover:no-underline">
                        <x-pill tone="secondary" size="sm" class="transition-interactive group-hover:-translate-y-0.5 group-hover:shadow-[0_4px_10px_rgb(27_31_59/0.12)] group-active:translate-y-0">{{ $tag->name }}</x-pill>
                    </a>
                @endforeach
            </div>
        @endif
    </section>

    <div class="flex flex-wrap items-start gap-10">
        <div class="flex min-w-0 flex-[999_1_560px] flex-col gap-10">
            <x-example-sentences :examples="$exampleSentences" />
        </div>

        <aside class="flex min-w-0 max-w-full flex-[1_1_360px] flex-col gap-6">
            <x-course-link-list :courses="$courses" />
            <x-vocabulary-link-list title="Bộ từ vựng liên quan" :vocabularies="$vocabularies" />
            <x-flashcard-set-link-list title="Flashcard liên quan" :flashcard-sets="$flashcardSets" />
            <x-sentence-set-link-list title="Bộ câu mẫu cùng chủ đề" :sentence-sets="$sameTopic" />
        </aside>
    </div>

    <x-slot:footer>
        <x-site-footer />
    </x-slot:footer>
</x-layouts.app>
