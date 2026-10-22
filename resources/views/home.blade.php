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

    $rankTitles = [
        ['title' => 'Nhà vua', 'color' => 'text-accent-text'],
        ['title' => 'Thái sư', 'color' => 'text-primary-text'],
        ['title' => 'Đô đốc', 'color' => 'text-secondary-text'],
        ['title' => 'Đại tướng', 'color' => 'text-success-text'],
    ];
    $podiumStyles = [
        ['row' => 'border-accent-border bg-accent-bg', 'rank' => 'bg-accent text-on-accent'],
        ['row' => 'border-primary-border bg-primary-bg', 'rank' => 'bg-primary text-on-primary'],
        ['row' => 'border-secondary-border bg-secondary-bg', 'rank' => 'bg-secondary text-on-secondary'],
    ];
    $avatarStyles = [
        'bg-primary-bg-hover text-primary-text',
        'bg-secondary-bg-hover text-secondary-text',
        'bg-success-bg-hover text-success-text',
        'bg-accent-bg-hover text-accent-text',
    ];
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

    {{-- Leaderboard --}}
    <button
        type="button"
        aria-expanded="false"
        aria-controls="leaderboard"
        data-toggle="leaderboard"
        class="fixed top-1/2 right-0 z-30 flex -translate-y-1/2 cursor-pointer flex-col items-center gap-2 rounded-l-md bg-accent px-3 py-4 type-label-sm text-on-accent shadow-[inset_4px_0_0_var(--color-accent-strong)] transition-interactive hover:bg-accent-strong"
    >
        <x-icon name="trophy" size="lg" />
        <span>BXH</span>
    </button>

    <div id="leaderboard" hidden>
        <div data-toggle-close="leaderboard" class="fixed inset-0 z-40 bg-ink-900/32"></div>

        <aside role="dialog" aria-label="BXH" class="fixed inset-y-0 right-0 z-50 flex w-full max-w-100 flex-col gap-4 overflow-y-auto bg-surface-card p-6 shadow-raised">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="flex size-12 items-center justify-center rounded-full border border-accent-border bg-accent-bg">
                        <x-icon name="trophy" size="lg" class="text-accent-text" />
                    </div>
                    <div class="flex flex-col">
                        <h2 class="type-h2 text-fg-strong">BXH</h2>
                        <span class="type-body-sm text-fg-muted">Top 10 học sinh điểm cao nhất</span>
                    </div>
                </div>
                <x-icon-button icon="x" label="Đóng" variant="ghost" size="sm" data-toggle-close="leaderboard" />
            </div>

            @if ($leaderboard === [])
                <p class="type-body text-fg-muted">Chưa có học sinh nào ghi điểm.</p>
            @endif

            <ol class="flex flex-col gap-1">
                @foreach ($leaderboard as $index => $learner)
                    @php
                        $nameParts = explode(' ', $learner['name']);
                        $initials = mb_strtoupper(mb_substr($nameParts[count($nameParts) - 2] ?? '', 0, 1).mb_substr(end($nameParts), 0, 1));
                        $rankTitle = $rankTitles[$index] ?? ['title' => 'Dũng sĩ', 'color' => 'text-fg-strong'];
                        $podium = $podiumStyles[$index] ?? ['row' => 'border-transparent', 'rank' => 'bg-surface-hover text-fg-body'];
                    @endphp
                    <li @class(['grid grid-cols-[36px_40px_minmax(0,1fr)_auto] items-center gap-3 rounded-md border px-3 py-2', $podium['row']])>
                        <span @class(['flex size-8 items-center justify-center rounded-full type-label font-bold', $podium['rank']])>{{ $index + 1 }}</span>
                        <span @class(['flex size-10 items-center justify-center overflow-hidden rounded-full type-label-sm', $avatarStyles[$index % count($avatarStyles)]])>
                            @if ($learner['avatarUrl'])
                                <img src="{{ $learner['avatarUrl'] }}" alt="" class="size-full object-cover">
                            @else
                                {{ $initials }}
                            @endif
                        </span>
                        <span class="flex min-w-0 flex-col">
                            <span @class(['type-label font-semibold', $rankTitle['color']])>{{ $rankTitle['title'] }}</span>
                            <span class="truncate type-body-sm text-fg-muted">{{ $learner['name'] }}</span>
                        </span>
                        <span class="flex items-center gap-1 type-label font-semibold text-fg-score">
                            <x-icon name="star" size="sm" class="text-accent-strong" />
                            {{ number_format($learner['points'], 0, ',', '.') }}
                        </span>
                    </li>
                @endforeach
            </ol>
        </aside>
    </div>

    <x-slot:footer>
        <x-site-footer />
    </x-slot:footer>
</x-layouts.app>
