@php
    $flashcardTones = ['primary', 'secondary', 'success', 'accent'];

    $statStyles = [
        'primary' => 'text-primary',
        'success' => 'text-success',
        'danger' => 'text-danger',
        'secondary' => 'text-secondary',
    ];
@endphp

<x-layouts.app :title="$lesson->title" main-class="flex flex-col gap-10 pt-6 pb-10">
    <x-slot:header>
        <x-site-header active="courses" />
    </x-slot:header>

    <x-breadcrumbs :items="[
        ['label' => 'Trang chủ', 'href' => route('home')],
        ['label' => 'Khóa học', 'href' => route('courses.index')],
        ['label' => $course->title, 'href' => route('courses.show', $course)],
        ['label' => $lesson->title],
    ]" class="-mb-4" />

    {{-- Lesson header --}}
    <section class="flex flex-col items-start gap-4">
        <h1 class="type-page-title tracking-tight text-balance text-fg-strong">{{ $lesson->title }}</h1>
        @if ($lesson->description)
            <p class="max-w-200 type-body-lg text-pretty text-fg-body">{{ $lesson->description }}</p>
        @endif
    </section>

    <div data-sidebar-layout class="group/layout flex flex-wrap items-start gap-10">
        <div class="flex min-w-0 flex-[999_1_560px] flex-col gap-10">
            {{-- Passage --}}
            @if (filled($passageHtml) || $lesson->passage_audio_url)
                <section class="flex flex-col gap-5 rounded-lg bg-surface-card p-7 shadow-card sm:p-10">
                    @if ($lesson->passage_audio_url)
                        <audio data-passage-audio controls preload="none" src="{{ $lesson->passage_audio_url }}" class="w-full"></audio>
                    @endif

                    <div data-passage class="flex flex-col gap-4 type-body-lg text-pretty text-fg-body [&_a]:underline [&_li+li]:mt-2 [&_li>ol]:mt-2 [&_li>ul]:mt-2 [&_ol]:list-decimal [&_ol]:pl-5 [&_strong]:text-fg-strong [&_ul]:list-disc [&_ul]:pl-5">
                        {!! $passageHtml !!}
                    </div>
                </section>
            @endif

            {{-- Vocabulary --}}
            <x-vocabulary-table :words="$words" empty-message="Bài học này chưa có từ vựng." />

            {{-- Flashcards --}}
            @foreach ($flashcardSets as $flashcardSet)
            @php($cards = $flashcardSet->flashcards->filter(fn ($card): bool => filled($card->display_title)))
            @if ($cards->isNotEmpty())
                <section class="flex flex-col gap-5 rounded-lg bg-surface-card p-7 shadow-card">
                    <div class="flex items-center justify-between gap-4">
                        <h2 class="type-h2 text-fg-strong">{{ $flashcardSet->title }}</h2>

                        <div class="flex items-center gap-2">
                            <x-chant :slides="$cards->map(fn ($card): array => ['image' => $card->image_url ?? $card->word?->image_url, 'title' => $card->display_title, 'ipa' => $card->word?->ipa_us, 'meaning' => $card->word?->firstPlainMeaning()])->all()" />
                            <x-icon-button icon="eye-off" label="Ẩn từ" size="sm" aria-pressed="false" data-flashcard-toggle />
                            <x-icon-button icon="printer" label="In flashcard" size="sm" data-print-flashcards />
                            <x-icon-button icon="panel-right-close" label="Ẩn thanh bên" size="sm" aria-pressed="false" class="max-lg:hidden" data-sidebar-toggle />
                        </div>
                    </div>

                    <div data-flashcards class="grid grid-cols-[repeat(auto-fill,minmax(min(100%,max(260px,calc((100%_-_2rem)/3))),1fr))] gap-4 group-data-[sidebar-hidden]/layout:grid-cols-[repeat(auto-fill,minmax(min(100%,max(220px,calc((100%_-_3rem)/4))),1fr))]">
                        @foreach ($cards as $flashcard)
                            @php($word = $flashcard->word)
                            <x-flashcard
                                :word="$flashcard->display_title"
                                :phonetic="$word?->ipa_us"
                                :meaning="$word?->firstPlainMeaning()"
                                :image="$flashcard->image_url ?? $word?->image_url"
                                :audio="$flashcard->audio_url"
                                :tone="$flashcardTones[$loop->index % count($flashcardTones)]"
                            />
                        @endforeach
                    </div>
                </section>
            @endif
            @endforeach

            {{-- Example sentences of the lesson; a word in backticks that matches a stored word gets its popover --}}
            @if ($exampleSentences->isNotEmpty())
                <x-example-sentences :examples="$exampleSentences" />
            @endif

            {{-- Content --}}
            <section class="flex flex-col gap-5 rounded-lg bg-surface-card p-7 shadow-card sm:p-10">
                <h2 class="type-h2 text-fg-strong">Nội dung bài học</h2>

                @if (filled($contentHtml))
                    <div class="flex flex-col gap-4 type-body-lg text-pretty text-fg-body [&_a]:underline [&_h2]:type-h2 [&_h2]:text-fg-strong [&_h3]:type-h3 [&_h3]:text-fg-strong [&_img]:h-auto [&_img]:max-w-full [&_img]:rounded-md [&_li+li]:mt-2 [&_li>ol]:mt-2 [&_li>ul]:mt-2 [&_ol]:list-decimal [&_ol]:pl-5 [&_strong]:text-fg-strong [&_ul]:list-disc [&_ul]:pl-5">
                        {!! $contentHtml !!}
                    </div>
                @endif
            </section>

            {{-- Attachments --}}
            @if (count($attachments) > 0)
                <section class="flex flex-col gap-5 rounded-lg bg-surface-card p-7 shadow-card">
                    <h2 class="type-h2 text-fg-strong">Tài liệu đính kèm</h2>

                    <ul class="flex flex-col gap-3">
                        @foreach ($attachments as $attachment)
                            <li class="flex flex-col gap-3 rounded-md border border-line-default p-4">
                                <div class="flex items-center gap-3">
                                    <x-icon :name="$attachment['isAudio'] ? 'music' : 'file-text'" class="text-primary" />
                                    <span class="min-w-0 flex-1 truncate type-label text-fg-strong">{{ $attachment['name'] }}</span>
                                    <a href="{{ $attachment['url'] }}" download aria-label="Tải {{ $attachment['name'] }}" class="inline-flex size-10 shrink-0 items-center justify-center rounded-md text-primary hover:bg-primary-bg">
                                        <x-icon name="download" />
                                    </a>
                                </div>

                                @if ($attachment['isAudio'])
                                    <audio controls preload="none" src="{{ $attachment['url'] }}" class="w-full"></audio>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            {{-- Review --}}
            <x-tip-card title="Mẹo để nhớ bài tốt hơn" class="gap-4">
                <div class="flex flex-col items-start gap-4">
                    <p class="type-body text-fg-body">Nghe và đọc từ vựng hoặc flashcard nhiều lần, sau đó đọc lại bài hội thoại và cố gắng nhớ nghĩa, hiểu câu.</p>
                    <x-button size="lg" icon-left="book-open" :href="route('lessons.review.show', [$course, $unit, $lesson, 1])">Ôn tập ngay</x-button>
                </div>
            </x-tip-card>
        </div>

        {{-- Sidebar --}}
        <x-lesson-sidebar class="group-data-[sidebar-hidden]/layout:hidden" :enroll-course="$enrollCourse" :progress="$progress" :top-learners="$topLearners" :score="$standing['score'] ?? 0" :rank="$standing['rank'] ?? '—'">
            <x-button size="lg" icon-left="book-open" full-width :href="route('lessons.review.show', [$course, $unit, $lesson, 1])">Ôn tập ngay</x-button>

            <x-slot:after-progress>
                <x-game-promo :href="route('lessons.games', [$course, $unit, $lesson])" />
            </x-slot:after-progress>
        </x-lesson-sidebar>
    </div>

    <x-slot:footer>
        <x-site-footer />
    </x-slot:footer>
</x-layouts.app>
