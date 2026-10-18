<x-layouts.app :title="$course->title" main-class="flex flex-col gap-8 pt-6 pb-16">
    <x-slot:header>
        <x-site-header active="courses" />
    </x-slot:header>

    <x-breadcrumbs :items="[
        ['label' => 'Trang chủ', 'href' => route('home')],
        ['label' => 'Khóa học', 'href' => route('courses.index')],
        ['label' => $course->title],
    ]" />

    {{-- Course hero --}}
    <section @class([
        'grid overflow-hidden rounded-xl bg-surface-card shadow-card',
        'xl:grid-cols-[minmax(0,3fr)_minmax(0,2fr)]' => $course->thumbnail_url,
    ])>
        <div class="flex min-w-0 flex-col items-start gap-5 p-8">
            @if (filled($course->level) || filled($course->age_groups))
                <div class="flex flex-wrap gap-2">
                    @if (filled($course->level))
                        <x-pill tone="success" size="sm">{{ $course->level }}</x-pill>
                    @endif
                    @foreach ($course->age_groups ?? [] as $ageGroup)
                        <x-pill tone="primary" size="sm">{{ $ageGroup }}</x-pill>
                    @endforeach
                </div>
            @endif

            <h1 class="type-word tracking-tight text-balance text-fg-strong">{{ $course->title }}</h1>

            @if ($course->description)
                <p class="text-[17px] leading-body text-pretty text-fg-body">{{ $course->description }}</p>
            @endif

            <ul class="flex flex-wrap gap-x-12 gap-y-5">
                @foreach ($stats as $stat)
                    <li class="flex items-center gap-3">
                        <span class="flex size-11 items-center justify-center rounded-md bg-primary-bg text-primary">
                            <x-icon :name="$stat['icon']" />
                        </span>
                        <span class="flex flex-col">
                            <span class="type-h3 text-fg-strong">{{ $stat['value'] }}</span>
                            <span class="type-body-sm text-fg-muted">{{ $stat['label'] }}</span>
                        </span>
                    </li>
                @endforeach
            </ul>
        </div>

        @if ($course->thumbnail_url)
            <div class="relative min-h-70 min-w-0 overflow-hidden">
                <img src="{{ $course->thumbnail_url }}" alt="Ảnh bìa khóa học {{ $course->title }}" class="absolute inset-0 size-full object-cover">
            </div>
        @endif
    </section>

    <div class="flex flex-wrap items-start gap-8">
        {{-- Units --}}
        <section class="flex min-w-0 flex-[999_1_640px] flex-col gap-8">
            @php
                $statusLabels = ['done' => 'Hoàn thành', 'doing' => 'Đang học', 'todo' => 'Chưa học'];
            @endphp
            @forelse ($units as $unit)
                <div class="flex flex-col gap-4">
                    <div class="flex flex-col gap-0.5">
                        <h2 class="type-h2 text-fg-strong">{{ $unit['title'] }}</h2>
                        @if (filled($unit['description']))
                            <p class="type-body text-pretty text-fg-muted">{{ $unit['description'] }}</p>
                        @endif
                    </div>

                    <ol class="grid grid-cols-[repeat(auto-fill,minmax(max(260px,calc((100%_-_1rem)/2)),1fr))] gap-4">
                        @foreach ($unit['lessons'] as $lesson)
                            @php
                                $status = $lesson['status'];
                                $isLocked = $lesson['access'] === 'locked' && $status === 'todo';
                                $actionIcon = match (true) {
                                    $status === 'done' => 'check',
                                    $isLocked => 'lock',
                                    default => 'play',
                                };
                            @endphp
                            <li class="flex min-w-0">
                                <a href="{{ $lesson['url'] }}" class="flex min-w-0 flex-1 items-center gap-4 rounded-lg border border-line-subtle bg-surface-card p-4 text-inherit no-underline shadow-xs transition-interactive hover:border-line-hover hover:text-inherit hover:no-underline hover:shadow-card">
                                    <span @class([
                                        'flex size-14 flex-none items-center justify-center rounded-md type-h2 tabular-nums',
                                        'bg-success-bg text-success-text' => $status === 'done',
                                        'bg-primary-bg text-primary-text' => $status === 'doing',
                                        'bg-secondary-bg text-secondary-text' => $status === 'todo',
                                    ])>{{ $lesson['number'] }}</span>

                                    <span class="flex min-w-0 flex-1 flex-col gap-1">
                                        <span class="truncate type-label-lg text-fg-strong" title="{{ $lesson['title'] }}">{{ $lesson['title'] }}</span>
                                        <span class="flex items-center gap-3 type-body-sm text-fg-muted">
                                            <span class="inline-flex items-center gap-1">
                                                <x-icon name="tags" />{{ $lesson['newWords'] }} từ mới
                                            </span>
                                            <span @class([
                                                'font-semibold',
                                                'text-success-text' => $status === 'done',
                                                'text-primary-text' => $status === 'doing',
                                            ])>{{ $statusLabels[$status] }}</span>
                                        </span>
                                        @if ($status === 'doing')
                                            <span class="block max-w-50 pt-1">
                                                <x-progress-bar :value="$lesson['progress']" :label="$lesson['title']" class="h-1.5!" />
                                            </span>
                                        @endif
                                    </span>

                                    <span @class([
                                        'flex size-10 flex-none items-center justify-center rounded-full transition-interactive',
                                        'bg-success-bg text-success-text' => $status === 'done',
                                        'bg-surface-page text-fg-muted hover:text-secondary' => $isLocked,
                                        'bg-primary-bg text-primary' => $status !== 'done' && ! $isLocked,
                                    ])>
                                        <x-icon :name="$actionIcon" :label="$isLocked ? 'Cần đăng ký khóa học' : $statusLabels[$status]" />
                                    </span>
                                </a>
                            </li>
                        @endforeach
                    </ol>
                </div>
            @empty
                <x-card padding="lg" class="flex flex-col items-center gap-3 text-center">
                    <x-icon name="book-open" size="xl" class="text-primary" />
                    <h3 class="type-h3 text-fg-strong">Chưa có bài học nào</h3>
                    <p class="type-body text-fg-muted">Các bài học sẽ sớm xuất hiện ở đây, bạn quay lại sau nhé!</p>
                </x-card>
            @endforelse
        </section>

        {{-- Sidebar --}}
        <aside class="sticky top-21 flex min-w-0 flex-[1_1_360px] flex-col gap-5 xl:top-6">
            <div class="flex flex-col gap-5 rounded-lg bg-surface-card p-6 shadow-card">
                @unless ($course->isFree() || $hasPaidAccess)
                    <x-course-enroll :course="$course" :with-price="false" />
                @endunless

                <h2 class="type-h2 text-fg-strong">Thông tin khóa học</h2>

                <dl class="grid grid-cols-2 gap-3">
                    @foreach ($info as $item)
                        <div class="flex flex-col gap-2 rounded-md bg-surface-page p-4">
                            <x-icon :name="$item['icon']" class="text-primary" />
                            <dt class="type-caption text-fg-muted">{{ $item['label'] }}</dt>
                            <dd class="type-label text-fg-strong">{{ $item['value'] }}</dd>
                        </div>
                    @endforeach
                </dl>

                <x-course-benefits :course="$course" :with-price="false" class="border-t border-line-subtle pt-5" />
            </div>

            <x-top-learners title="Top 10 khóa học này" :learners="$topLearners" />
        </aside>
    </div>

    <x-slot:footer>
        <x-site-footer />
    </x-slot:footer>
</x-layouts.app>
