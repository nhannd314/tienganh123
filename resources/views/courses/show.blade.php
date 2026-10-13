<x-layouts.app :title="$course->title" main-class="flex flex-col gap-10 pt-6 pb-10">
    <x-slot:header>
        <x-site-header active="courses" />
    </x-slot:header>

    <x-breadcrumbs :items="[
        ['label' => 'Trang chủ', 'href' => route('home')],
        ['label' => 'Khóa học', 'href' => route('courses.index')],
        ['label' => $course->title],
    ]" class="-mb-4" />

    {{-- Course header --}}
    <section class="flex flex-col items-start gap-4">
        <h1 class="type-word tracking-tight text-balance text-fg-strong">{{ $course->title }}</h1>
        @if ($course->description)
            <p class="max-w-200 type-body-lg text-pretty text-fg-body">{{ $course->description }}</p>
        @endif
    </section>

    <div class="flex flex-wrap items-start gap-10">
        {{-- Units --}}
        <section class="flex min-w-0 flex-[999_1_560px] flex-col gap-5">
            @forelse ($units as $unit)
                <div class="overflow-hidden rounded-lg bg-surface-card shadow-card">
                    <h3 class="px-6 py-5 type-h2 text-fg-strong">{{ $unit['title'] }}</h3>

                    <ol class="flex flex-col gap-3 px-6 pb-5">
                        @foreach ($unit['lessons'] as $lesson)
                            @php
                                $isDone = $lesson['progress'] === 100;
                                $percentColor = match (true) {
                                    $isDone => 'text-success-text',
                                    $lesson['progress'] > 0 => 'text-primary-text',
                                    default => 'text-fg-muted',
                                };
                            @endphp
                            <li>
                                <a
                                    href="{{ $lesson['url'] }}"
                                    class="grid grid-cols-[48px_minmax(0,1fr)_auto] items-center gap-4 rounded-md border border-line-default bg-surface-card p-4 text-inherit no-underline transition-interactive hover:bg-primary-bg hover:text-inherit hover:no-underline"
                                >
                                    <span class="flex size-12 items-center justify-center rounded-full bg-primary-bg type-label-lg text-primary-text">{{ $lesson['number'] }}</span>

                                    <div class="flex min-w-0 flex-col gap-2">
                                        <div class="flex flex-col gap-1">
                                            <span class="type-label-lg text-fg-strong">{{ $lesson['title'] }}</span>
                                            <span class="type-body-sm text-fg-muted">{{ $lesson['newWords'] }} từ mới</span>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <x-progress-bar
                                                :value="$lesson['progress']"
                                                :tone="$isDone ? 'success' : 'primary'"
                                                :label="$lesson['title']"
                                                class="max-w-105 min-w-0 flex-1"
                                            />
                                            <span @class(['min-w-10 flex-none type-label-sm', $percentColor])>{{ $lesson['progress'] }}%</span>
                                        </div>
                                    </div>

                                    @if ($lesson['access'] === 'trial')
                                        <span class="inline-flex h-10 items-center justify-center gap-2 rounded-sm border-2 border-primary bg-primary px-4 type-label whitespace-nowrap text-on-primary edge-primary-strong">
                                            <x-icon name="play" />
                                            Học thử
                                        </span>
                                    @elseif ($lesson['access'] === 'locked')
                                        <x-icon name="lock" size="lg" label="Đã khóa" class="text-fg-muted" />
                                    @endif
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
        <aside class="flex min-w-0 flex-[1_1_360px] flex-col gap-6">
            <div class="flex flex-col gap-5 rounded-lg bg-surface-card p-6 shadow-card">
                @if ($course->isFree() || $hasPaidAccess)
                    @if ($startUrl)
                        <x-button variant="primary" size="lg" icon-left="play" full-width :href="$startUrl">Bắt đầu học</x-button>
                    @endif
                @else
                    <x-course-enroll :course="$course" />
                @endif

                <x-course-benefits :course="$course" class="border-b border-line-subtle pb-5" />

                <ul class="flex flex-col gap-3">
                    @foreach ($meta as $item)
                        <li class="flex items-center gap-3 type-body text-fg-body">
                            <x-icon :name="$item['icon']" class="text-primary" />
                            {{ $item['label'] }}
                        </li>
                    @endforeach
                </ul>
            </div>

            <x-top-learners :learners="$topLearners" />
        </aside>
    </div>

    <x-slot:footer>
        <x-site-footer />
    </x-slot:footer>
</x-layouts.app>
