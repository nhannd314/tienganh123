<x-layouts.app title="Từ vựng" main-class="flex flex-col gap-10 pt-6 pb-10">
    <x-slot:header>
        <x-site-header active="vocab" />
    </x-slot:header>

    <x-breadcrumbs :items="[
        ['label' => 'Trang chủ', 'href' => route('home')],
        ['label' => 'Từ vựng'],
    ]" class="-mb-4" />

    <section class="flex flex-col items-start gap-3">
        <h1 class="type-page-title tracking-tight text-balance text-fg-strong">Danh sách từ vựng</h1>
        <p class="max-w-160 type-body-lg text-pretty text-fg-muted">
            Chọn một chủ đề để xem các từ vựng và học cùng flashcard nhé!
        </p>
    </section>

    @if ($tags->isNotEmpty())
        <nav aria-label="Lọc theo thẻ" class="flex flex-wrap gap-2">
            <a href="{{ route('vocabularies.index') }}" @if (! $activeTag) aria-current="page" @endif class="no-underline hover:no-underline">
                <x-pill :tone="$activeTag ? 'neutral' : 'primary'">Tất cả</x-pill>
            </a>
            @foreach ($tags as $tag)
                <a href="{{ route('vocabularies.index', ['tag' => $tag->slug]) }}" @if ($activeTag?->is($tag)) aria-current="page" @endif class="no-underline hover:no-underline">
                    <x-pill :tone="$activeTag?->is($tag) ? 'primary' : 'neutral'">{{ $tag->name }}</x-pill>
                </a>
            @endforeach
        </nav>
    @endif

    @if ($vocabularies->isEmpty())
        <x-card padding="lg" class="flex flex-col items-center gap-3 text-center">
            <x-icon name="book-a" size="xl" class="text-primary" />
            <h2 class="type-h3 text-fg-strong">Chưa có từ vựng nào</h2>
            <p class="type-body text-fg-muted">Các bộ từ vựng mới sẽ sớm xuất hiện ở đây, bạn quay lại sau nhé!</p>
        </x-card>
    @else
        <div class="grid grid-cols-[repeat(auto-fill,minmax(min(100%,280px),1fr))] gap-6">
            @foreach ($vocabularies as $vocabulary)
                <a href="{{ route('vocabularies.show', $vocabulary) }}" class="flex no-underline hover:no-underline">
                <x-card class="flex w-full flex-col gap-4 transition-interactive hover:-translate-y-0.5 hover:shadow-raised">
                    <div class="flex flex-col gap-1">
                        <h2 class="type-h3 text-fg-strong">{{ $vocabulary->title }}</h2>
                        <p class="type-body text-fg-muted">{{ $vocabulary->words_count }} từ</p>
                    </div>

                    @if ($vocabulary->tags->isNotEmpty())
                        <div class="flex flex-wrap gap-2">
                            @foreach ($vocabulary->tags as $tag)
                                <x-pill tone="secondary" size="sm">{{ $tag->name }}</x-pill>
                            @endforeach
                        </div>
                    @endif

                    @if ($vocabulary->words->isNotEmpty())
                        <div class="flex flex-wrap items-center gap-2">
                            @foreach ($vocabulary->words as $word)
                                <x-pill tone="neutral" size="sm" class="border-transparent! bg-surface-hover!">{{ $word->word }}</x-pill>
                            @endforeach
                            @if ($vocabulary->words_count > $previewWords)
                                <span class="type-label-sm text-fg-muted">+{{ $vocabulary->words_count - $previewWords }}</span>
                            @endif
                        </div>
                    @endif
                </x-card>
                </a>
            @endforeach
        </div>

        {{ $vocabularies->links('pagination.default') }}
    @endif

    <x-slot:footer>
        <x-site-footer />
    </x-slot:footer>
</x-layouts.app>
