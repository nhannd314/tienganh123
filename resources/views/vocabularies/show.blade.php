<x-layouts.app :title="$vocabulary->title" main-class="flex flex-col gap-10 pt-6 pb-10">
    <x-slot:header>
        <x-site-header active="vocab" />
    </x-slot:header>

    <x-breadcrumbs :items="[
        ['label' => 'Trang chủ', 'href' => route('home')],
        ['label' => 'Từ vựng', 'href' => route('vocabularies.index')],
        ['label' => $vocabulary->title],
    ]" class="-mb-4" />

    <section class="flex flex-col items-start gap-4">
        <h1 class="type-page-title tracking-tight text-balance text-fg-strong">{{ $vocabulary->title }}</h1>
        @if ($vocabulary->description)
            <p class="max-w-200 type-body-lg text-pretty text-fg-body">{{ $vocabulary->description }}</p>
        @endif
        @if ($vocabulary->tags->isNotEmpty())
            <div class="flex flex-wrap gap-2">
                @foreach ($vocabulary->tags as $tag)
                    <a href="{{ route('vocabularies.index', ['tag' => $tag->slug]) }}" class="group no-underline hover:no-underline">
                        <x-pill tone="secondary" size="sm" class="transition-interactive group-hover:-translate-y-0.5 group-hover:shadow-[0_4px_10px_rgb(27_31_59/0.12)] group-active:translate-y-0">{{ $tag->name }}</x-pill>
                    </a>
                @endforeach
            </div>
        @endif
    </section>

    <div data-sidebar-layout class="group/layout flex flex-wrap items-start gap-10">
        <div class="flex min-w-0 flex-[999_1_560px] flex-col gap-10">
            <x-vocabulary-table :words="$words" empty-message="Bộ từ vựng này chưa có từ nào." />

            @if ($exampleSentences->isNotEmpty())
                <x-example-sentences :examples="$exampleSentences" />
            @endif
        </div>

        <x-vocabulary-sidebar :sidebar="$sidebar" class="flex-[1_1_360px] group-data-[sidebar-hidden]/layout:hidden">
            @if ($words->contains(fn ($word): bool => $word->meaningLabels() !== []))
                <x-quick-review title="Ôn tập từ vựng" source="từ vựng" :href="route('vocabularies.review', [$vocabulary, 1])" />
            @endif
        </x-vocabulary-sidebar>
    </div>

    <x-slot:footer>
        <x-site-footer />
    </x-slot:footer>
</x-layouts.app>
