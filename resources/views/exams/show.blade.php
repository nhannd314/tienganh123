<x-layouts.app :title="$exam->title" :description="$exam->description" noindex main-class="flex flex-col gap-6 pt-6 pb-10">
    <x-slot:header>
        <x-site-header active="courses" />
    </x-slot:header>

    <x-breadcrumbs :items="[
        ['label' => 'Trang chủ', 'href' => route('home')],
        ['label' => $exam->course->title, 'href' => $exam->course->is_published ? route('courses.show', $exam->course) : null],
        ['label' => $exam->title],
    ]" />

    <div class="mx-auto flex w-full max-w-160 flex-col gap-6 rounded-lg bg-surface-card px-6 py-8 shadow-card sm:px-10">
        <div class="flex flex-col gap-2">
            <h1 class="type-page-title text-balance text-fg-strong">{{ $exam->title }}</h1>

            @if (filled($exam->description))
                <p class="type-body whitespace-pre-line text-fg-body">{{ $exam->description }}</p>
            @endif
        </div>

        <ul class="flex flex-wrap gap-x-6 gap-y-2 type-body text-fg-body">
            <li class="flex items-center gap-2"><x-icon name="list-checks" class="text-primary" /> {{ $questionCount }} câu hỏi · {{ $groupCount }} phần</li>
            <li class="flex items-center gap-2"><x-icon name="timer" class="text-primary" /> {{ $exam->duration_minutes ? $exam->duration_minutes.' phút' : 'Không giới hạn thời gian' }}</li>
            <li class="flex items-center gap-2"><x-icon name="award" class="text-primary" /> Thang điểm 100</li>
        </ul>

        @if ($attemptInProgress)
            <x-alert tone="info" title="Bạn đang làm dở bài thi này">
                {{ $attemptInProgress->student_name }} — lớp {{ $attemptInProgress->class_name }}
            </x-alert>

            <x-button :href="route('exams.take', $exam)" size="lg" full-width icon-right="arrow-right">Tiếp tục làm bài</x-button>
        @elseif ($questionCount === 0)
            <x-alert tone="warning">Đề thi này chưa có câu hỏi.</x-alert>
        @else
            <form method="POST" action="{{ route('exams.start', $exam) }}" class="flex flex-col gap-5">
                @csrf

                <x-auth-field name="student_name" label="Họ và tên" placeholder="Nguyễn Văn A" autocomplete="name" />

                <x-auth-field name="class_name" label="Lớp" placeholder="3A" autocomplete="off" />

                @if ($exam->requiresPassword())
                    {{-- A text field masked by CSS: the exam password is a code shared with the class, and a password field makes the browser offer to save it. --}}
                    <x-auth-field name="password" label="Mật khẩu đề thi" placeholder="Nhập mật khẩu giáo viên cung cấp" autocomplete="off" spellcheck="false" autocapitalize="off" class="[-webkit-text-security:disc]" />
                @endif

                <x-button type="submit" size="lg" full-width icon-right="arrow-right">Bắt đầu làm bài</x-button>
            </form>
        @endif
    </div>

    <x-slot:footer>
        <x-site-footer />
    </x-slot:footer>
</x-layouts.app>
