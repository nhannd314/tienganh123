@php
    $courseTones = ['danger', 'primary', 'success', 'accent', 'secondary'];
@endphp

<x-layouts.app title="Khóa học" main-class="flex flex-col gap-10 pt-6 pb-10">
    <x-slot:header>
        <x-site-header active="courses" />
    </x-slot:header>

    <x-breadcrumbs :items="[
        ['label' => 'Trang chủ', 'href' => route('home')],
        ['label' => 'Khóa học'],
    ]" class="-mb-4" />

    <section class="flex flex-col items-start gap-3">
        <h1 class="type-page-title tracking-tight text-balance text-fg-strong">Tất cả khóa học</h1>
        <p class="max-w-160 type-body-lg text-pretty text-fg-muted">
            Lộ trình từ lớp 1 đến ôn thi vào lớp 10 — chọn khóa học phù hợp và bắt đầu hành trình của bạn nhé!
        </p>
    </section>

    @if ($courses->isEmpty())
        <x-card padding="lg" class="flex flex-col items-center gap-3 text-center">
            <x-icon name="book-open" size="xl" class="text-primary" />
            <h2 class="type-h3 text-fg-strong">Chưa có khóa học nào</h2>
            <p class="type-body text-fg-muted">Các khóa học mới sẽ sớm xuất hiện ở đây, bạn quay lại sau nhé!</p>
        </x-card>
    @else
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

        {{ $courses->links('pagination.default') }}
    @endif

    <x-slot:footer>
        <x-site-footer />
    </x-slot:footer>
</x-layouts.app>
