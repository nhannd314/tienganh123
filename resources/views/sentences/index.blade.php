<x-layouts.app title="Câu mẫu" main-class="flex flex-col gap-10 pt-6 pb-10">
    <x-slot:header>
        <x-site-header active="sentences" />
    </x-slot:header>

    <x-breadcrumbs :items="[
        ['label' => 'Trang chủ', 'href' => route('home')],
        ['label' => 'Câu mẫu'],
    ]" class="-mb-4" />

    <section class="flex flex-col items-start gap-3">
        <h1 class="type-page-title tracking-tight text-balance text-fg-strong">Danh sách câu mẫu</h1>
        <p class="max-w-160 type-body-lg text-pretty text-fg-muted">
            Chọn một bộ câu để nghe, đọc và xem hình minh họa của từng câu nhé!
        </p>
    </section>

    @if ($tags->isNotEmpty())
        <nav aria-label="Lọc theo thẻ" class="flex flex-wrap gap-2">
            <a href="{{ route('sentences.index') }}" @if (! $activeTag) aria-current="page" @endif class="no-underline hover:no-underline">
                <x-pill :tone="$activeTag ? 'neutral' : 'primary'">Tất cả</x-pill>
            </a>
            @foreach ($tags as $tag)
                <a href="{{ route('sentences.index', ['tag' => $tag->slug]) }}" @if ($activeTag?->is($tag)) aria-current="page" @endif class="no-underline hover:no-underline">
                    <x-pill :tone="$activeTag?->is($tag) ? 'primary' : 'neutral'">{{ $tag->name }}</x-pill>
                </a>
            @endforeach
        </nav>
    @endif

    @if ($sentenceSets->isEmpty())
        <x-card padding="lg" class="flex flex-col items-center gap-3 text-center">
            <x-icon name="file-text" size="xl" class="text-primary" />
            <h2 class="type-h3 text-fg-strong">Chưa có câu mẫu nào</h2>
            <p class="type-body text-fg-muted">Các bộ câu mẫu mới sẽ sớm xuất hiện ở đây, bạn quay lại sau nhé!</p>
        </x-card>
    @else
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
            @foreach ($sentenceSets as $sentenceSet)
                <a href="{{ route('sentences.show', $sentenceSet) }}" class="flex no-underline hover:no-underline">
                <x-card class="flex w-full flex-col gap-4 transition-interactive hover:-translate-y-0.5 hover:shadow-raised">
                    <div class="flex flex-col gap-1">
                        <h2 class="type-h3 text-fg-strong">{{ $sentenceSet->title }}</h2>
                        <p class="type-body text-fg-muted">{{ $sentenceSet->sentences_count }} câu</p>
                    </div>

                    @if ($sentenceSet->tags->isNotEmpty())
                        <div class="flex flex-wrap gap-2">
                            @foreach ($sentenceSet->tags as $tag)
                                <x-pill tone="secondary" size="sm">{{ $tag->name }}</x-pill>
                            @endforeach
                        </div>
                    @endif

                    <div class="flex flex-wrap items-center gap-2">
                        @foreach ($sentenceSet->sentences as $sentence)
                            <x-pill tone="neutral" size="sm" class="max-w-full border-transparent! bg-surface-hover!"><span class="truncate">{{ \App\Models\Lesson::spokenSentence($sentence->sentence) }}</span></x-pill>
                        @endforeach
                        @if ($sentenceSet->sentences_count > $previewSentences)
                            <span class="type-label-sm text-fg-muted">+{{ $sentenceSet->sentences_count - $previewSentences }}</span>
                        @endif
                    </div>
                </x-card>
                </a>
            @endforeach
        </div>

        {{ $sentenceSets->links('pagination.default') }}
    @endif

    <x-slot:footer>
        <x-site-footer />
    </x-slot:footer>
</x-layouts.app>
