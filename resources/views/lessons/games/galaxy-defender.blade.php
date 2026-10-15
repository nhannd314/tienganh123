{{--
    Galaxy Defender: small stars fall towards the bottom of the screen, each with the Vietnamese meaning of a word of the
    lesson. The player types the English word of a meteor; every right letter makes the spaceship at the bottom shoot a
    bullet at it, and the last letter brings the meteor down. There is no time limit: every word typed means a win, and
    the time it took is ranked beside the game. A meteor that reaches the bottom edge explodes, shaking the screen, and its
    word comes back later.

    The sky, spaceship (public/games/galaxy-defender/space-ship.png), bullets and explosions are drawn on the canvas by
    resources/js/games/galaxy-defender.js; each meteor's label (meaning and one box per letter) and the HUD, start and result screens are
    HTML on top of it, sized in container units so they scale with the scene.
--}}
@php
    $config = [
        'words' => $words,
        'assetsUrl' => asset('games/galaxy-defender'),
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
        <p class="type-body-lg text-fg-muted">Gõ đúng từ tiếng Anh của từng thiên thạch để tàu vũ trụ bắn hạ nó, bảo vệ dải ngân hà nhé!</p>
    </div>

    @if ($words === [])
        <x-alert tone="info" title="Chưa có từ vựng">Bài học này chưa có từ vựng để chơi.</x-alert>
    @else
    <div class="flex flex-wrap items-start gap-6">
        <div data-galaxy-game class="@container relative aspect-[12/7] min-w-0 flex-[999_1_560px] overflow-hidden rounded-lg bg-[#03050f] shadow-card select-none">
            <canvas data-canvas class="absolute inset-0 size-full"></canvas>

            {{-- HUD: the time taken, words done and the sound switch --}}
            <div data-hud hidden class="absolute inset-x-0 top-0 z-10 flex items-center justify-end gap-[1.5cqw] px-[2cqw] pt-[1.5cqw]">
                <span class="flex items-center gap-[0.8cqw] rounded-full bg-white/85 px-[1.4cqw] py-[0.6cqw] text-[max(12px,1.8cqw)] font-semibold text-fg-strong tabular-nums shadow-card">
                    <x-icon name="timer" class="text-[max(14px,2.2cqw)]! text-secondary" />
                    <span data-time-text>0:00</span>
                </span>
                <span class="rounded-full bg-white/85 px-[1.4cqw] py-[0.6cqw] text-[max(12px,1.8cqw)] font-semibold text-fg-strong tabular-nums shadow-card">
                    <span data-words-done>0</span>/<span data-words-total>{{ count($words) }}</span> từ
                </span>
                <button type="button" data-mute aria-pressed="false" aria-label="Tắt âm thanh" class="flex size-[max(32px,4.6cqw)] cursor-pointer items-center justify-center rounded-full bg-white/85 text-fg-strong shadow-card transition-interactive hover:bg-white">
                    <x-icon name="volume-2" data-mute-on class="text-[max(16px,2.4cqw)]!" />
                    <x-icon name="volume-x" data-mute-off hidden class="text-[max(16px,2.4cqw)]!" />
                </button>
            </div>

            {{-- Typing goes into this hidden input, which is kept focused while playing. --}}
            <input type="text" data-typing autocomplete="off" autocapitalize="off" autocorrect="off" spellcheck="false" aria-label="Gõ từ tiếng Anh của thiên thạch" class="absolute bottom-0 left-0 size-px opacity-0">

            {{-- Start screen, then the result screen --}}
            <div data-start class="absolute inset-0 z-20 flex overflow-y-auto bg-ink-900/50 p-[1.5cqw]">
                <div class="flex m-auto max-w-[min(94cqw,max(60cqw,300px))] flex-col items-center gap-[max(4px,1.4cqw)] rounded-lg bg-surface-card p-[max(8px,2.6cqw)] text-center shadow-raised">
                    <h2 class="text-[max(17px,3.4cqw)] leading-tight font-bold text-fg-strong">{{ $title }}</h2>
                    <p class="text-[max(12px,1.8cqw)] text-fg-body">Gõ đúng từ của từng thiên thạch để bắn hạ nó. Không giới hạn thời gian, hạ hết <b>{{ count($words) }} thiên thạch</b> càng nhanh càng tốt.</p>
                    <x-button variant="secondary" size="lg" icon-left="play" data-start-button><span data-start-label>Bắt đầu</span></x-button>
                </div>
            </div>

            <div data-result hidden class="absolute inset-0 z-20 flex overflow-y-auto bg-ink-900/60 p-[1.5cqw]">
                <div class="flex m-auto max-w-[min(94cqw,max(60cqw,300px))] flex-col items-center gap-[max(4px,1.4cqw)] rounded-lg bg-surface-card p-[max(8px,2.6cqw)] text-center shadow-raised">
                    <h2 data-result-title class="text-[max(17px,3.4cqw)] leading-tight font-bold text-fg-strong"></h2>
                    <p data-result-text class="text-[max(12px,1.8cqw)] text-fg-body"></p>
                    <p data-result-note class="text-[max(11px,1.6cqw)] text-fg-muted"></p>
                    <div class="flex flex-wrap justify-center gap-3">
                        <x-button variant="secondary" size="sm" icon-left="rotate-ccw" data-restart>Chơi lại</x-button>
                        <x-button variant="outline" size="sm" icon-left="gamepad-2" :href="route('lessons.games', [$course, $unit, $lesson])">Chọn game khác</x-button>
                    </div>
                </div>
            </div>

            {{-- The label under a meteor: its meaning and one box per letter of the word --}}
            <template data-tpl="label">
                <div data-label class="group pointer-events-none absolute z-10 flex w-[18cqw] -translate-x-1/2 -translate-y-1/2 flex-col items-center gap-[0.35cqw] data-[state=wrong]:animate-pulse">
                    <span data-meaning class="line-clamp-2 rounded-md border-[max(1px,0.12cqw)] border-white/30 bg-ink-900/60 px-[0.8cqw] py-[0.3cqw] text-center text-[max(10px,1.35cqw)] leading-snug font-semibold text-white group-data-[state=locked]:border-accent group-data-[state=wrong]:border-danger-border group-data-[state=wrong]:bg-danger-bg group-data-[state=wrong]:text-danger-text"></span>
                    <span data-slots lang="en" class="flex flex-wrap justify-center gap-x-[0.8cqw] gap-y-[0.3cqw]"></span>
                </div>
            </template>
            <template data-tpl="letter"><span data-letter class="flex size-[max(13px,1.7cqw)] items-center justify-center rounded-sm border border-white/50 bg-ink-900/60 text-[max(9px,1.2cqw)] font-bold text-white data-[state=correct]:border-success-border data-[state=correct]:bg-success"></span></template>
            <template data-tpl="letter-fixed"><span class="flex h-[max(13px,1.7cqw)] items-center text-[max(9px,1.2cqw)] font-bold text-white/80"></span></template>

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
            @vite('resources/js/games/galaxy-defender.js')
        @endif
    </x-slot:scripts>

    <x-slot:footer>
        <x-site-footer />
    </x-slot:footer>
</x-layouts.app>
