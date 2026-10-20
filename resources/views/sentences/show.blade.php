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

    <div data-sidebar-layout class="group/layout flex flex-wrap items-start gap-10">
        <div class="flex min-w-0 flex-[999_1_560px] flex-col gap-10">
            <x-example-sentences :examples="$exampleSentences" />
        </div>

        <x-sentence-set-sidebar :sidebar="$sidebar" class="flex-[1_1_360px] group-data-[sidebar-hidden]/layout:hidden">
            {{-- The review starts at the first part the set has sentences for --}}
            <x-quick-review title="Ôn tập câu mẫu" source="câu mẫu" :href="route('sentences.review', [$sentenceSet, 7])" />
        </x-sentence-set-sidebar>
    </div>

    <x-slot:footer>
        <x-site-footer />
    </x-slot:footer>
</x-layouts.app>
