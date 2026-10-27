@php
    $tileTones = [
        'border-danger-border bg-danger-bg',
        'border-primary-border bg-primary-bg',
        'border-success-border bg-success-bg',
        'border-accent-border bg-accent-bg',
        'border-secondary-border bg-secondary-bg',
    ];

    $features = [
        ['icon' => 'clapperboard', 'title' => 'Hình ảnh, video sinh động', 'text' => 'Mỗi từ mới đều có hình minh họa và video vui nhộn, giúp bạn nhớ lâu hơn đó!', 'box' => 'border-primary-border bg-primary-bg', 'color' => 'text-primary'],
        ['icon' => 'clipboard-check', 'title' => 'Bài tập phong phú', 'text' => 'Trắc nghiệm, nghe và chọn, điền từ — luyện tập theo nhiều cách khác nhau.', 'box' => 'border-secondary-border bg-secondary-bg', 'color' => 'text-secondary'],
        ['icon' => 'gamepad-2', 'title' => 'Trò chơi ghi nhớ', 'text' => 'Vừa chơi vừa học, ôn lại từ vựng mà không thấy chán nhé!', 'box' => 'border-accent-border bg-accent-bg', 'color' => 'text-accent-text'],
        ['icon' => 'chart-line', 'title' => 'Theo dõi tiến độ', 'text' => 'Tích điểm, xem kết quả từng bài và leo lên bảng xếp hạng cùng bạn bè.', 'box' => 'border-success-border bg-success-bg', 'color' => 'text-success'],
    ];

    $courseTones = ['danger', 'primary', 'success', 'accent', 'secondary'];
@endphp

<x-layouts.app
    page-title="TiếngAnhPro — Học tiếng Anh vui cho bé qua video, flashcard và bài tập"
    description="Học tiếng Anh vui mỗi ngày cùng TiếngAnhPro: bài học video sinh động, từ vựng có hình minh họa, flashcard và bài tập đa dạng giúp bé nhớ lâu, học giỏi."
>
    <x-slot:header>
        <x-site-header active="home" />
    </x-slot:header>

    <div class="flex flex-col gap-16">
        {{-- Hero --}}
        <section class="flex flex-col items-center gap-12 px-[clamp(0px,4vw,48px)] py-[clamp(24px,6vw,64px)] text-center">
            <div class="flex w-full flex-col items-center gap-6">
                <h1 class="w-full text-[clamp(36px,calc(4vw+12px),55px)] leading-tight font-bold tracking-tight text-balance text-fg-strong">
                    Học tiếng Anh<br><span class="text-primary">bằng tất cả niềm vui</span>
                </h1>
                <p class="max-w-180 type-body-lg text-pretty text-fg-body">
                    Tạo hứng thú, giúp trẻ nhớ bài tốt hơn thông qua các hình ảnh, video sinh động, các dạng bài tập và trò chơi phong phú.
                </p>
                <div class="flex w-full flex-col justify-center gap-4 sm:flex-row sm:flex-wrap">
                    <x-button href="#" size="lg" icon-left="book-a" class="w-full sm:w-auto">Học từ vựng</x-button>
                    <x-button href="#" variant="secondary" size="lg" icon-left="pencil-line" class="w-full sm:w-auto">Thực hành nhiều</x-button>
                    <x-button href="#" variant="accent" size="lg" icon-left="gamepad-2" class="w-full sm:w-auto">Chơi và ghi nhớ</x-button>
                </div>
            </div>

            @if ($flashcards->isNotEmpty())
                <div class="grid w-full max-w-220 grid-cols-[repeat(auto-fit,minmax(min(100%,130px),1fr))] gap-5">
                    @foreach ($flashcards as $flashcard)
                        @php $text = $flashcard->display_title; @endphp
                        <button
                            type="button"
                            data-audio-button
                            data-text="{{ $text }}"
                            data-lang="en-US"
                            @if ($flashcard->audio_url) data-src="{{ $flashcard->audio_url }}" @endif
                            aria-label="Nghe phát âm {{ $text }}"
                            class="flex cursor-pointer flex-col items-center gap-2 rounded-lg border px-2 py-2 transition-interactive hover:brightness-95 {{ $tileTones[$loop->index % count($tileTones)] }}"
                        >
                            <img data-flashcard-image src="{{ $flashcard->image_url ?? $flashcard->word?->image_url }}" alt="" class="aspect-square w-full max-w-28 object-contain">
                            <span lang="en" class="type-label-lg text-fg-strong">{{ $text }}</span>
                        </button>
                    @endforeach
                </div>
            @endif
        </section>

        {{-- Why choose us --}}
        <section class="flex flex-col gap-6">
            <div class="flex flex-col items-center gap-3 text-center">
                <x-pill tone="primary" icon="sparkle">Why choose us?</x-pill>
                <h2 class="type-h1 text-balance text-fg-strong">Vì sao bạn sẽ thích FunEnglish</h2>
                <p class="max-w-160 type-body-lg text-pretty text-fg-muted">
                    FunEnglish biến mỗi bài học thành một cuộc phiêu lưu nhỏ — học bằng hình ảnh, luyện bằng bài tập và nhớ lâu nhờ trò chơi. Mỗi ngày một chút, tiến bộ thật nhiều nhé!
                </p>
            </div>

            <div class="grid grid-cols-[repeat(auto-fit,minmax(min(100%,260px),1fr))] gap-6">
                @foreach ($features as $feature)
                    <div class="flex flex-col gap-4 rounded-lg bg-surface-card p-6 shadow-card">
                        <div @class(['flex size-14 items-center justify-center rounded-md border', $feature['box']])>
                            <x-icon :name="$feature['icon']" size="xl" :class="$feature['color']" />
                        </div>
                        <h3 class="type-h3 text-fg-strong">{{ $feature['title'] }}</h3>
                        <p class="type-body text-pretty text-fg-body">{{ $feature['text'] }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- Courses --}}
        <section id="khoa-hoc" class="flex scroll-mt-24 flex-col gap-6">
            <div class="flex flex-col items-start gap-3">
                <x-pill tone="success" icon="sparkle">Khóa học</x-pill>
                <div class="flex w-full flex-wrap items-center justify-between gap-4">
                    <h2 class="type-h1 text-fg-strong">Danh sách khóa học</h2>
                    <x-button :href="route('courses.index')" variant="outline" icon-right="arrow-right">Xem tất cả</x-button>
                </div>
                <p class="max-w-160 type-body-lg text-pretty text-fg-muted">
                    Lộ trình từ lớp 1 đến ôn thi vào lớp 10 — chọn khóa học phù hợp và bắt đầu hành trình của bạn nhé!
                </p>
            </div>

            @if ($courses->isEmpty())
                <x-card padding="lg" class="flex flex-col items-center gap-3 text-center">
                    <x-icon name="book-open" size="xl" class="text-primary" />
                    <h3 class="type-h3 text-fg-strong">Chưa có khóa học nào</h3>
                    <p class="type-body text-fg-muted">Các khóa học mới sẽ sớm xuất hiện ở đây, bạn quay lại sau nhé!</p>
                </x-card>
            @else
                <div class="grid grid-cols-[repeat(auto-fill,minmax(min(100%,280px),1fr))] gap-6">
                    @foreach ($courses as $course)
                        <x-course-card :course="$course" :tone="$courseTones[$loop->index % count($courseTones)]" />
                    @endforeach
                </div>
            @endif
        </section>
    </div>

    <x-slot:footer>
        <x-site-footer />
    </x-slot:footer>
</x-layouts.app>
