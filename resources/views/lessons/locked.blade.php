<x-layouts.app :title="$lesson->title" main-class="flex flex-col gap-6 pt-6 pb-10">
    <x-slot:header>
        <x-site-header active="courses" />
    </x-slot:header>

    <x-breadcrumbs :items="[
        ['label' => 'Trang chủ', 'href' => route('home')],
        ['label' => 'Khóa học', 'href' => route('courses.index')],
        ['label' => $course->title, 'href' => route('courses.show', $course)],
        ['label' => $lesson->title],
    ]" />

    <section class="mx-auto flex w-full max-w-160 flex-col items-center gap-6 rounded-lg bg-surface-card p-8 text-center shadow-card">
        <span class="flex size-16 items-center justify-center rounded-full bg-primary-bg text-primary">
            <x-icon name="lock" size="xl" />
        </span>

        <div class="flex flex-col gap-2">
            <h1 class="type-h2 text-fg-strong">Bài học này chưa mở cho bạn</h1>
            <p class="type-body text-pretty text-fg-body">
                Bạn cần đăng ký khóa học “{{ $course->title }}” hoặc gói thành viên để học bài “{{ $lesson->title }}”.
            </p>
        </div>

        <x-button size="lg" icon-left="book-open" :href="route('courses.show', $course)">Xem khóa học</x-button>
    </section>

    <x-slot:footer>
        <x-site-footer />
    </x-slot:footer>
</x-layouts.app>
