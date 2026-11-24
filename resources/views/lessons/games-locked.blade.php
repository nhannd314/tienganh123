<x-layouts.app :title="'Trò chơi '.$lesson->title" noindex main-class="flex flex-col gap-6 pt-6 pb-10">
    <x-slot:header>
        <x-site-header active="courses" />
    </x-slot:header>

    <x-breadcrumbs :items="[
        ['label' => 'Trang chủ', 'href' => route('home')],
        ['label' => 'Khóa học', 'href' => route('courses.index')],
        ['label' => $course->title, 'href' => route('courses.show', $course)],
        ['label' => $lesson->title, 'href' => route('lessons.show', [$course, $unit, $lesson])],
        ['label' => 'Trò chơi'],
    ]" />

    <section class="mx-auto flex w-full max-w-160 flex-col items-center gap-6 rounded-lg bg-surface-card p-8 text-center shadow-card">
        <span class="flex size-16 items-center justify-center rounded-full bg-primary-bg text-primary">
            <x-icon name="lock" size="xl" />
        </span>

        <div class="flex flex-col gap-2">
            <h1 class="type-h2 text-fg-strong">Trò chơi chưa mở</h1>
            <p class="type-body text-pretty text-fg-body">
                Hoàn thành ít nhất {{ \App\Models\Lesson::GAMES_UNLOCK_PERCENT }}% tiến độ bài học “{{ $lesson->title }}” để chơi trò chơi.
            </p>
        </div>

        <div class="flex w-full flex-col gap-2">
            <x-progress-bar :value="$percent" size="md" />
            <p class="type-body-sm text-fg-muted">Tiến độ hiện tại: {{ $percent }}% / {{ \App\Models\Lesson::GAMES_UNLOCK_PERCENT }}%</p>
        </div>

        <x-button size="lg" icon-left="book-open" :href="route('lessons.review.show', [$course, $unit, $lesson, 1])">Tiếp tục học</x-button>
    </section>

    <x-slot:footer>
        <x-site-footer />
    </x-slot:footer>
</x-layouts.app>
