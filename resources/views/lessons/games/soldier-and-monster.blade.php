{{--
    Soldier and Monster: the soldier shoots one bullet for every right letter of the word whose meaning is shown, while
    the monster walks towards him. A word typed wrong goes to the back of the queue. The monster must be beaten, every
    word typed right, before the time runs out (secondsPerWord per word), or it reaches the soldier and the game is lost.

    The scene is drawn on the canvas by resources/js/games/soldier-and-monster.js from the files in
    public/games/soldier-and-monster; the HUD, the word to type and the start and result screens are HTML on top of it,
    sized in container units so they scale with the scene.
--}}
@php
    $config = [
        'words' => $words,
        'secondsPerWord' => $secondsPerWord,
        'assetsUrl' => asset('games/soldier-and-monster'),
        'saveUrl' => $saveUrl,
        'csrf' => csrf_token(),
    ];
@endphp

<x-layouts.app :title="$title.' — '.$lesson->title" main-class="flex flex-col gap-6 pt-6 pb-10">
    <x-slot:header>
        <x-site-header active="courses" />
    </x-slot:header>

    <x-breadcrumbs :items="[
        ['label' => 'Trang chủ', 'href' => route('home')],
        ['label' => 'Khóa học', 'href' => route('courses.index')],
        ['label' => $course->title, 'href' => route('courses.show', $course)],
        ['label' => $lesson->title, 'href' => route('lessons.show', [$course, $unit, $lesson])],
        ['label' => 'Trò chơi', 'href' => route('lessons.games', [$course, $unit, $lesson])],
        ['label' => $title],
    ]" />

    <div class="flex flex-col items-start gap-2">
        <h1 class="type-page-title tracking-tight text-balance text-fg-strong">{{ $title }}</h1>
        <p class="type-body-lg text-fg-muted">Gõ đúng từ tiếng Anh để người lính bắn hạ quái vật trước khi nó tiến đến nơi nhé!</p>
    </div>

    @if ($words === [])
        <x-alert tone="info" title="Chưa có từ vựng">Bài học này chưa có từ vựng để chơi.</x-alert>
    @else
    <div class="flex flex-wrap items-start gap-6">
        <div data-soldier-game class="@container relative aspect-[12/7] min-w-0 flex-[999_1_560px] overflow-hidden rounded-lg bg-[#1f8ff0] shadow-card select-none">
            <canvas data-canvas class="absolute inset-0 size-full"></canvas>

            {{-- HUD: time left and words done, and the sound switch --}}
            <div data-hud hidden class="absolute inset-x-0 top-0 flex items-center gap-[1.5cqw] px-[2cqw] pt-[1.5cqw]">
                <div class="flex flex-1 items-center gap-[1cqw] rounded-full bg-white/85 px-[1.2cqw] py-[0.6cqw] shadow-card">
                    <x-icon name="timer" class="text-[max(14px,2.2cqw)]! text-secondary" />
                    <div class="h-[max(6px,1cqw)] flex-1 overflow-hidden rounded-full bg-ink-100">
                        <div data-time-bar class="h-full w-0 rounded-full bg-secondary transition-[width] duration-200 ease-linear"></div>
                    </div>
                    <span data-time-text class="min-w-[6ch] text-right text-[max(12px,1.8cqw)] font-semibold text-fg-strong tabular-nums">0s</span>
                </div>
                <span class="rounded-full bg-white/85 px-[1.4cqw] py-[0.6cqw] text-[max(12px,1.8cqw)] font-semibold text-fg-strong tabular-nums shadow-card">
                    <span data-words-done>0</span>/<span data-words-total>{{ count($words) }}</span> từ
                </span>
                <button type="button" data-mute aria-pressed="false" aria-label="Tắt âm thanh" class="flex size-[max(32px,4.6cqw)] cursor-pointer items-center justify-center rounded-full bg-white/85 text-fg-strong shadow-card transition-interactive hover:bg-white">
                    <x-icon name="volume-2" data-mute-on class="text-[max(16px,2.4cqw)]!" />
                    <x-icon name="volume-x" data-mute-off hidden class="text-[max(16px,2.4cqw)]!" />
                </button>
            </div>

            {{-- The meaning and one box per letter, above the soldier and the monster. Typing goes into the hidden input. --}}
            <div data-word-panel hidden class="absolute inset-x-0 top-[max(8.5cqw,52px)] flex justify-center px-[2cqw]">
                <label class="relative flex max-w-[80cqw] cursor-text flex-col items-center gap-[1.4cqw] rounded-lg bg-white/90 px-[2.4cqw] py-[1.6cqw] text-center shadow-raised">
                    <span class="sr-only">Gõ từ tiếng Anh có nghĩa sau</span>
                    <span data-meaning class="line-clamp-3 text-[max(14px,2.3cqw)] leading-snug font-semibold text-fg-strong"></span>
                    <span data-slots lang="en" class="flex flex-wrap justify-center gap-x-[2.4cqw] gap-y-[1cqw]"></span>
                    <input type="text" data-typing autocomplete="off" autocapitalize="off" autocorrect="off" spellcheck="false" aria-label="Gõ từ tiếng Anh" class="absolute inset-0 size-full cursor-text opacity-0">
                </label>
            </div>

            {{-- Start screen, then the result screen --}}
            <div data-start class="absolute inset-0 flex overflow-y-auto bg-ink-900/40 p-[1.5cqw]">
                <div class="flex m-auto max-w-[min(94cqw,max(60cqw,300px))] flex-col items-center gap-[max(4px,1.4cqw)] rounded-lg bg-surface-card p-[max(8px,2.6cqw)] text-center shadow-raised">
                    <h2 class="text-[max(17px,3.4cqw)] leading-tight font-bold text-fg-strong">{{ $title }}</h2>
                    <p class="text-[max(12px,1.8cqw)] text-fg-body">Quái vật đang lao tới! Bạn chỉ có <b>{{ count($words) * $secondsPerWord }} giây</b> để hạ gục nó.</p>
                    <x-button variant="secondary" size="lg" icon-left="play" data-start-button disabled><span data-start-label>Đang tải...</span></x-button>
                </div>
            </div>

            <div data-result hidden class="absolute inset-0 flex overflow-y-auto bg-ink-900/50 p-[1.5cqw]">
                <div class="flex m-auto max-w-[min(94cqw,max(60cqw,300px))] flex-col items-center gap-[max(4px,1.4cqw)] rounded-lg bg-surface-card p-[max(8px,2.6cqw)] text-center shadow-raised">
                    <h2 data-result-title class="text-[max(17px,3.4cqw)] leading-tight font-bold text-fg-strong"></h2>
                    <p data-result-text class="text-[max(12px,1.8cqw)] text-fg-body"></p>
                    <p data-result-note hidden class="text-[max(11px,1.6cqw)] text-fg-muted"></p>
                    <div class="flex flex-wrap justify-center gap-3">
                        <x-button variant="secondary" size="sm" icon-left="rotate-ccw" data-restart>Chơi lại</x-button>
                        <x-button variant="outline" size="sm" icon-left="gamepad-2" :href="route('lessons.games', [$course, $unit, $lesson])">Chọn game khác</x-button>
                    </div>
                </div>
            </div>

            <template data-tpl="letter"><span data-letter class="flex size-[max(26px,4.4cqw)] items-center justify-center rounded-md border-[max(2px,0.25cqw)] border-line-default bg-surface-card text-[max(16px,2.8cqw)] font-bold text-fg-strong transition-interactive data-[state=active]:border-secondary data-[state=active]:shadow-focus data-[state=correct]:border-success-border data-[state=correct]:bg-success-bg data-[state=correct]:text-success-text data-[state=reveal]:text-fg-muted data-[state=wrong]:border-danger-border data-[state=wrong]:bg-danger-bg data-[state=wrong]:text-danger-text"></span></template>
            <template data-tpl="letter-fixed"><span class="flex h-[max(26px,4.4cqw)] items-center text-[max(16px,2.8cqw)] font-bold text-fg-muted"></span></template>

            <script type="application/json" data-game-config>{!! json_encode($config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
        </div>

        <aside class="flex min-w-0 max-w-full flex-[1_1_380px] flex-col gap-5 rounded-lg bg-surface-card p-6 shadow-card">
            <h2 class="type-h2 text-fg-strong">Top 10 thời gian nhanh nhất</h2>
            <ol data-best-times class="flex flex-col gap-4">
                @include('lessons.games.best-times', ['times' => $bestTimes])
            </ol>
        </aside>
    </div>
    @endif

    <x-slot:scripts>
        @if ($words !== [])
            @vite('resources/js/games/soldier-and-monster.js')
        @endif
    </x-slot:scripts>

    <x-slot:footer>
        <x-site-footer />
    </x-slot:footer>
</x-layouts.app>
