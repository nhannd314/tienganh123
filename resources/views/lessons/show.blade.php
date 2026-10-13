@php
    $flashcardTones = ['primary', 'secondary', 'success', 'accent'];

    $statStyles = [
        'primary' => 'text-primary',
        'success' => 'text-success',
        'danger' => 'text-danger',
        'secondary' => 'text-secondary',
    ];

    $headerCell = 'bg-surface-hover px-2 py-3 md:px-4 text-left text-[length:calc(var(--text-base)*var(--vs,1))] font-medium whitespace-nowrap text-fg-muted';
    $bodyCell = 'border-t border-line-subtle px-2 py-3 md:px-4 align-middle';

    $copyText = $words
        ->map(fn ($word) => $word->word.' '.collect($word->meanings)
            ->map(fn (array $meaning): string => '('.($meaning['pos'] ?? '').') '.($meaning['meaning'] ?? ''))
            ->implode(' '))
        ->implode("\n");

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
        <h1 class="type-word tracking-tight text-balance text-fg-strong">{{ $lesson->title }}</h1>
        @if ($lesson->description)
            <p class="max-w-200 type-body-lg text-pretty text-fg-body">{{ $lesson->description }}</p>
        @endif
    </section>

    <div class="flex flex-wrap items-start gap-10">
        <div class="flex min-w-0 flex-[999_1_560px] flex-col gap-10">
            {{-- Passage --}}
            @if (filled($passageHtml) || $lesson->passage_audio_url)
                <section class="flex flex-col gap-5 rounded-lg bg-surface-card p-6 shadow-card">
                    @if ($lesson->passage_audio_url)
                        <audio data-passage-audio controls preload="none" src="{{ $lesson->passage_audio_url }}" class="w-full"></audio>
                    @endif

                    <div data-passage class="flex flex-col gap-4 type-body-lg text-pretty text-fg-body [&_a]:underline [&_strong]:text-fg-strong">
                        {!! $passageHtml !!}
                    </div>
                </section>
            @endif

            {{-- Vocabulary --}}
            <section class="flex flex-col gap-5 rounded-lg bg-surface-card p-6 shadow-card">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <h2 class="type-h2 text-fg-strong">Từ vựng</h2>

                    <div class="flex items-center gap-2">
                        <x-icon-button
                            icon="eye"
                            label="Hiển thị đầy đủ"
                            size="sm"
                            aria-pressed="false"
                            data-vocab-action="expand"
                            class="md:hidden"
                        />
                        <x-icon-button icon="printer" label="In bảng từ vựng" size="sm" data-vocab-action="print" />
                        <x-icon-button icon="copy" label="Sao chép từ vựng" size="sm" data-vocab-action="copy" />
                        <x-icon-button icon="a-arrow-down" label="Giảm cỡ chữ" size="sm" data-vocab-action="zoom-out" />
                        <x-icon-button icon="a-arrow-up" label="Tăng cỡ chữ" size="sm" data-vocab-action="zoom-in" />
                    </div>
                </div>

                @if ($words->isEmpty())
                    <p class="type-body text-fg-muted">Bài học này chưa có từ vựng.</p>
                @else
                    <div
                        data-vocab-table
                        data-copy-text="{{ $copyText }}"
                        class="group/vocab overflow-x-auto rounded-md border border-line-default"
                    >
                        <table class="w-full border-collapse max-md:group-data-[expanded]/vocab:min-w-190 md:min-w-190">
                            <thead>
                                <tr>
                                    <th scope="col" class="{{ $headerCell }}">Từ vựng</th>
                                    <th scope="col" class="{{ $headerCell }}">UK</th>
                                    <th scope="col" class="{{ $headerCell }}">US</th>
                                    <th scope="col" class="{{ $headerCell }}">Nghĩa</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($words as $word)
                                    <tr>
                                        <td lang="en" class="{{ $bodyCell }} text-[length:calc(var(--text-lg)*var(--vs,1))] font-medium text-primary-text">{{ $word->word }}</td>
                                        <td class="{{ $bodyCell }}">
                                            <div class="flex items-center gap-2 md:gap-3">
                                                <x-audio-button size="sm" :text="$word->word" lang="en-GB" />
                                                <span class="max-md:group-not-data-[expanded]/vocab:hidden text-[length:calc(var(--text-base)*var(--vs,1))] whitespace-nowrap text-fg-body">{{ $word->ipa_uk }}</span>
                                            </div>
                                        </td>
                                        <td class="{{ $bodyCell }}">
                                            <div class="flex items-center gap-2 md:gap-3">
                                                <x-audio-button size="sm" tone="secondary" :text="$word->word" lang="en-US" />
                                                <span class="max-md:group-not-data-[expanded]/vocab:hidden text-[length:calc(var(--text-base)*var(--vs,1))] whitespace-nowrap text-fg-body">{{ $word->ipa_us }}</span>
                                            </div>
                                        </td>
                                        <td class="{{ $bodyCell }} text-[length:calc(var(--text-lg)*var(--vs,1))] text-fg-strong">
                                            @foreach ($word->meanings as $meaning)
                                                <div class="font-semibold"><span class="font-normal text-fg-muted">({{ $meaning['pos'] ?? '' }})</span> {{ $meaning['meaning'] ?? '' }}</div>
                                            @endforeach

                                            @if (filled($word->examples))
                                                <div class="max-md:group-not-data-[expanded]/vocab:hidden mt-3 flex flex-col gap-1.5">
                                                    @foreach ($word->examples as $example)
                                                        <div class="text-[length:calc(var(--text-base)*var(--vs,1))]">
                                                            <p lang="en" class="text-fg-strong"><span class="text-fg-muted italic">e.g.</span> {{ $example['sentence'] ?? '' }}</p>
                                                            @if (filled($example['translation'] ?? null))
                                                                <p class="text-fg-muted">{{ $example['translation'] }}</p>
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>

            {{-- Example sentences of the lesson's new words --}}
            @if ($exampleSentences->isNotEmpty())
                <section class="flex flex-col gap-5 rounded-lg bg-surface-card p-6 shadow-card">
                    <h2 class="type-h2 text-fg-strong">Câu mẫu</h2>

                    <ul class="flex flex-col divide-y divide-line-subtle">
                        @foreach ($exampleSentences as $example)
                            @php($word = $example['word'])
                            @php($parts = $example['parts'])
                            <li data-example class="flex flex-wrap items-start gap-4 py-4 first:pt-0 last:pb-0">
                                @if ($example['audioUrl'])
                                    <audio controls preload="none" src="{{ $example['audioUrl'] }}" class="h-10 w-48 flex-none"></audio>
                                @else
                                    <x-audio-button size="sm" tone="secondary" :text="$example['sentence']" lang="en-US" />
                                @endif

                                <div class="min-w-0 flex-1">
                                    <p lang="en" class="type-body-lg text-fg-strong">
                                        {{ $parts['before'] }}@if ($parts['label'] !== null)<x-word-popover :word="$word" :label="$parts['label']" :popover-id="'example-word-'.$loop->index" />@endif{{ $parts['after'] }}
                                    </p>
                                    @if ($example['translation'])
                                        <div class="mt-2 grid">
                                            <p data-example-meaning class="invisible col-start-1 row-start-1 type-body text-fg-muted">{{ $example['translation'] }}</p>
                                            <x-button variant="ghost" size="sm" icon-left="eye" class="col-start-1 row-start-1 h-6! min-w-0! justify-self-start px-0! text-fg-muted! hover:bg-transparent! hover:text-fg-strong!" data-example-toggle>Hiện nghĩa</x-button>
                                        </div>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            {{-- Flashcards --}}
            @foreach ($flashcardSets as $flashcardSet)
            @if ($flashcardSet->flashcards->isNotEmpty())
                <section class="flex flex-col gap-5 rounded-lg bg-surface-card p-6 shadow-card">
                    <div class="flex items-center justify-between gap-4">
                        <h2 class="type-h2 text-fg-strong">{{ $flashcardSet->title }}</h2>

                        <x-icon-button icon="printer" label="In flashcard" size="sm" data-print-flashcards />
                    </div>

                    <div data-flashcards class="grid grid-cols-[repeat(auto-fill,minmax(min(100%,320px),1fr))] gap-4">
                        @foreach ($flashcardSet->flashcards as $flashcard)
                            @php($word = $flashcard->word)
                            @php($firstMeaning = $word?->meanings[0] ?? null)
                            <x-flashcard
                                :word="$flashcard->title ?? $word?->word ?? ''"
                                :phonetic="$word?->ipa_us"
                                :meaning="$firstMeaning ? '('.($firstMeaning['pos'] ?? '').') '.($firstMeaning['meaning'] ?? '') : null"
                                :image="$flashcard->image_url ?? $word?->image_url"
                                :tone="$flashcardTones[$loop->index % count($flashcardTones)]"
                            />
                        @endforeach
                    </div>
                </section>
            @endif
            @endforeach

            {{-- Content --}}
            <section class="flex flex-col gap-5 rounded-lg bg-surface-card p-6 shadow-card">
                <h2 class="type-h2 text-fg-strong">Nội dung bài học</h2>

                @if (filled($contentHtml))
                    <div class="flex flex-col gap-4 type-body-lg text-pretty text-fg-body [&_a]:underline [&_h2]:type-h2 [&_h2]:text-fg-strong [&_h3]:type-h3 [&_h3]:text-fg-strong [&_img]:h-auto [&_img]:max-w-full [&_img]:rounded-md [&_ol]:list-decimal [&_ol]:pl-5 [&_strong]:text-fg-strong [&_ul]:list-disc [&_ul]:pl-5">
                        {!! $contentHtml !!}
                    </div>
                @endif
            </section>

            {{-- Attachments --}}
            @if (count($attachments) > 0)
                <section class="flex flex-col gap-5 rounded-lg bg-surface-card p-6 shadow-card">
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
        <x-lesson-sidebar :enroll-course="$enrollCourse" :progress="$progress" :top-learners="$topLearners">
            <x-button size="lg" icon-left="book-open" full-width :href="route('lessons.review.show', [$course, $unit, $lesson, 1])">Ôn tập ngay</x-button>
        </x-lesson-sidebar>
    </div>

    <x-slot:footer>
        <x-site-footer />
    </x-slot:footer>
</x-layouts.app>
