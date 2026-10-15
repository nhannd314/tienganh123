@php
    $courseTones = ['danger', 'primary', 'success', 'accent', 'secondary'];
    $hasResults = $courses->isNotEmpty() || $vocabularies->isNotEmpty() || $flashcardSets->isNotEmpty();
@endphp

<x-layouts.app title="Tìm kiếm" main-class="flex flex-col gap-10 pt-6 pb-10">
    <x-slot:header>
        <x-site-header />
    </x-slot:header>

    <x-breadcrumbs :items="[
        ['label' => 'Trang chủ', 'href' => route('home')],
        ['label' => 'Tìm kiếm'],
    ]" class="-mb-4" />

    <section class="flex flex-col items-start gap-3">
        <h1 class="type-page-title tracking-tight text-balance text-fg-strong">
            @if ($term !== '')
                Kết quả cho “{{ $term }}”
            @else
                Tìm kiếm
            @endif
        </h1>
        <p class="max-w-160 type-body-lg text-pretty text-fg-muted">Tìm khóa học, bộ từ vựng hoặc bộ flashcard bạn muốn học.</p>
    </section>

    <nav aria-label="Lọc theo loại" class="flex flex-wrap gap-2">
        <a href="{{ route('search', ['q' => $term]) }}" @if (! $type) aria-current="page" @endif class="no-underline hover:no-underline">
            <x-pill :tone="$type ? 'neutral' : 'primary'">Tất cả</x-pill>
        </a>
        @foreach ($types as $key => $label)
            <a href="{{ route('search', ['q' => $term, 'loai' => $key]) }}" @if ($type === $key) aria-current="page" @endif class="no-underline hover:no-underline">
                <x-pill :tone="$type === $key ? 'primary' : 'neutral'">{{ $label }}</x-pill>
            </a>
        @endforeach
    </nav>

    @if ($term === '')
        <x-card padding="lg" class="flex flex-col items-center gap-3 text-center">
            <x-icon name="search" size="xl" class="text-primary" />
            <h2 class="type-h3 text-fg-strong">Nhập từ khóa để tìm kiếm</h2>
            <p class="type-body text-fg-muted">Gõ vào ô tìm kiếm phía trên rồi nhấn Enter nhé!</p>
        </x-card>
    @elseif (! $hasResults)
        <x-card padding="lg" class="flex flex-col items-center gap-3 text-center">
            <x-icon name="search" size="xl" class="text-primary" />
            <h2 class="type-h3 text-fg-strong">Không tìm thấy kết quả nào</h2>
            <p class="type-body text-fg-muted">Thử một từ khóa khác hoặc kiểm tra lại chính tả nhé!</p>
        </x-card>
    @else
        @if ($courses->isNotEmpty())
            <section class="flex flex-col gap-5">
                <h2 class="type-h2 text-fg-strong">Khóa học</h2>
                <div class="grid grid-cols-[repeat(auto-fill,minmax(min(100%,280px),1fr))] gap-6">
                    @foreach ($courses as $course)
                        <x-lesson-card
                            :title="$course->title"
                            :description="$course->description"
                            :thumbnail="$course->thumbnail_url"
                            :href="route('courses.show', $course)"
                            :price="$course->priceLabel()"
                            :price-tone="$course->isFree() ? 'success' : 'danger'"
                            :price-icon="$course->isFree() ? 'gift' : 'tag'"
                            :original-price="$course->originalPriceLabel()"
                            :discount="$course->displayDiscountPercent()"
                            :tone="$courseTones[$loop->index % count($courseTones)]"
                        />
                    @endforeach
                </div>

                @if ($type)
                    {{ $courses->links('pagination.default') }}
                @else
                    <a href="{{ route('search', ['q' => $term, 'loai' => 'khoa-hoc']) }}" class="self-start type-label text-fg-link">Xem tất cả</a>
                @endif
            </section>
        @endif

        @if ($vocabularies->isNotEmpty())
            <section class="flex flex-col gap-5">
                <h2 class="type-h2 text-fg-strong">Từ vựng</h2>
                <div class="grid grid-cols-[repeat(auto-fill,minmax(min(100%,280px),1fr))] gap-6">
                    @foreach ($vocabularies as $vocabulary)
                        <a href="{{ route('vocabularies.show', $vocabulary) }}" class="flex no-underline hover:no-underline">
                            <x-card class="flex w-full flex-col gap-1 transition-interactive hover:-translate-y-0.5 hover:shadow-raised">
                                <h3 class="type-h3 text-fg-strong">{{ $vocabulary->title }}</h3>
                                <p class="type-body text-fg-muted">{{ $vocabulary->words_count }} từ</p>
                            </x-card>
                        </a>
                    @endforeach
                </div>

                @if ($type)
                    {{ $vocabularies->links('pagination.default') }}
                @else
                    <a href="{{ route('search', ['q' => $term, 'loai' => 'tu-vung']) }}" class="self-start type-label text-fg-link">Xem tất cả</a>
                @endif
            </section>
        @endif

        @if ($flashcardSets->isNotEmpty())
            <section class="flex flex-col gap-5">
                <h2 class="type-h2 text-fg-strong">Flashcard</h2>
                <div class="grid grid-cols-[repeat(auto-fill,minmax(min(100%,280px),1fr))] gap-6">
                    @foreach ($flashcardSets as $flashcardSet)
                        <a href="{{ route('flashcards.show', $flashcardSet) }}" class="flex no-underline hover:no-underline">
                            <x-card class="flex w-full flex-col gap-1 transition-interactive hover:-translate-y-0.5 hover:shadow-raised">
                                <h3 class="type-h3 text-fg-strong">{{ $flashcardSet->title }}</h3>
                                <p class="type-body text-fg-muted">{{ $flashcardSet->flashcards_count }} thẻ</p>
                            </x-card>
                        </a>
                    @endforeach
                </div>

                @if ($type)
                    {{ $flashcardSets->links('pagination.default') }}
                @else
                    <a href="{{ route('search', ['q' => $term, 'loai' => 'flashcard']) }}" class="self-start type-label text-fg-link">Xem tất cả</a>
                @endif
            </section>
        @endif
    @endif

    <x-slot:footer>
        <x-site-footer />
    </x-slot:footer>
</x-layouts.app>
