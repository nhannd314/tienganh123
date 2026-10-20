@php
    $firstQuestion = $questions[0] ?? null;
@endphp

<x-layouts.app :title="'Ôn tập '.$lesson->title" main-class="flex flex-col gap-6 pt-6 pb-10">
    <x-slot:header>
        <x-site-header active="courses" />
    </x-slot:header>

    <x-breadcrumbs :items="[
        ['label' => 'Trang chủ', 'href' => route('home')],
        ['label' => 'Khóa học', 'href' => route('courses.index')],
        ['label' => $course->title, 'href' => route('courses.show', $course)],
        ['label' => $lesson->title, 'href' => route('lessons.show', [$course, $unit, $lesson])],
        ['label' => 'Ôn tập'],
    ]" />

    <div class="flex flex-wrap items-start gap-5">
        <x-review-quiz
            :title="'Ôn tập '.$lesson->title"
            :hint="$stepInfo['hint']"
            :step="$step"
            :steps="$steps"
            :active-tab="$activeTab"
            :completed-steps="$completedSteps"
            :questions="$questions"
            :is-built="$isBuilt"
            :next-url="$nextUrl"
            :submit-url="$submitUrl"
            :can-save="$canSave"
            empty-message="Bài học này chưa có từ vựng để ôn tập."
            :passage="$passage"
            :passage-audio-url="$passageAudioUrl"
            :max-recording-ms="$maxRecordingMs"
            :pronounce-url="$pronounceUrl"
            class="flex-[999_1_600px]"
        />

        @if ($firstQuestion)
            <x-lesson-sidebar :enroll-course="$enrollCourse" :progress="$progress" :top-learners="$topLearners" :score="$standing['score'] ?? 0" :rank="$standing['rank'] ?? '—'" class="flex-[1_1_340px] gap-5">
                <x-slot:after-progress>
                    @if ($advancedUrl)
                        <x-advanced-review-promo :href="$advancedUrl" />
                    @endif
                    <x-game-promo :href="route('lessons.games', [$course, $unit, $lesson])" />
                </x-slot:after-progress>
            </x-lesson-sidebar>
        @endif
    </div>

    <x-slot:scripts>
        @if ($firstQuestion)
            @vite('resources/js/review.js')
        @endif
    </x-slot:scripts>
</x-layouts.app>
