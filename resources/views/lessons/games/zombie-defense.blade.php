{{--
    Zombie Defense: zombies come out on the left, close behind each other, and walk along the road to the gate of the
    house of the player, on the right, with a gun fixed to its wall. The meaning of a word is shown at the bottom
    with one box per letter; every letter typed fires the gun, but only a right letter is accepted: it shows and takes a
    point off the health of the zombie the word belongs to (one zombie per word, health = its letters). A zombie that
    gets to the house before its word is done attacks it and is gone, and the word comes back at the end of the queue.
    The zombies come out at least a little under a word's time apart and take a few words' time to cross. The game is won
    when every zombie is dead; the time of a win is ranked beside the game.

    The scene is drawn on the canvas by resources/js/games/zombie-defense.js from pictures; the HUD, the word to type
    and the start and result screens are HTML on top of it, sized in container units so they scale with the scene.
--}}
@php
    // Faces for some of the zombies: the player's own avatar, then those of learners of the course, each with the last word of the name.
    $player = auth()->user();
    $avatars = collect([$player?->avatar_url ? ['url' => $player->avatar_url, 'name' => $player->lastName()] : null, ...($learnerAvatars ?? [])])->filter()->unique('url')->values()->all();

    $config = [
        'words' => $words,
        'secondsPerWord' => $secondsPerWord,
        'saveUrl' => $saveUrl,
        'images' => [
            'background' => asset('games/zombie-defense/bg.jpg'),
            'house' => asset('games/zombie-defense/gun-home.webp'),
            'zombies' => asset('games/zombie-defense/zombies'),
        ],
        'avatars' => $avatars,
        'sounds' => asset('games/zombie-defense/sounds'),
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
        <p class="type-body-lg text-fg-muted">Gõ đúng từ tiếng Anh theo nghĩa tiếng Việt để hạ gục zombie, đừng để chúng vào nhà bạn nhé!</p>
    </div>

    @if ($words === [])
        <x-alert tone="info" title="Chưa có từ vựng">Bài học này chưa có từ vựng để chơi.</x-alert>
    @else
    <div class="flex flex-wrap items-start gap-6">
        <div data-zombie-game class="@container relative aspect-[1400/693] min-w-0 flex-[999_1_560px] overflow-hidden rounded-lg bg-[#0e1330] shadow-card select-none">
            <canvas data-canvas class="absolute inset-0 size-full"></canvas>

            {{-- HUD: the time taken, the zombies that got into the house and the ones killed, and the sound switch --}}
            <div data-hud hidden class="absolute inset-x-0 top-0 z-10 flex items-center justify-end gap-[1.5cqw] px-[2cqw] pt-[1.5cqw]">
                <span class="flex items-center gap-[0.8cqw] rounded-full bg-white/85 px-[1.4cqw] py-[0.6cqw] text-[max(12px,1.8cqw)] font-semibold text-fg-strong tabular-nums shadow-card">
                    <x-icon name="timer" class="text-[max(14px,2.2cqw)]! text-secondary" />
                    <span data-time-text>0:00</span>
                </span>
                <span class="flex items-center gap-[0.8cqw] rounded-full bg-white/85 px-[1.4cqw] py-[0.6cqw] text-[max(12px,1.8cqw)] font-semibold text-fg-strong tabular-nums shadow-card">
                    Bị tấn công: <span data-passed>0</span>
                </span>
                <span class="rounded-full bg-white/85 px-[1.4cqw] py-[0.6cqw] text-[max(12px,1.8cqw)] font-semibold text-fg-strong tabular-nums shadow-card">
                    Đã hạ: <span data-killed>0</span>/<span data-zombie-total>{{ count($words) }}</span>
                </span>
                <button type="button" data-mute aria-pressed="false" aria-label="Tắt âm thanh" class="flex size-[max(32px,4.6cqw)] cursor-pointer items-center justify-center rounded-full bg-white/85 text-fg-strong shadow-card transition-interactive hover:bg-white">
                    <x-icon name="volume-2" data-mute-on class="text-[max(16px,2.4cqw)]!" />
                    <x-icon name="volume-x" data-mute-off hidden class="text-[max(16px,2.4cqw)]!" />
                </button>
            </div>

            {{-- Typing goes into this hidden input, which is kept focused while playing. --}}
            <input type="text" data-typing autocomplete="off" autocapitalize="off" autocorrect="off" spellcheck="false" aria-label="Gõ từ tiếng Anh" class="absolute bottom-0 left-0 size-px opacity-0">

            {{-- The word to type: its meaning and one box per letter --}}
            <div data-word-panel hidden class="pointer-events-none absolute inset-x-0 bottom-0 z-10 flex flex-col items-center gap-[1.2cqw] px-[2cqw] pb-[1.5cqw]">
                <p data-meaning class="line-clamp-2 text-center text-[max(13px,2.3cqw)] leading-snug font-bold text-white [text-shadow:0_2px_6px_rgb(0_0_0/0.7)]"></p>
                <div data-slots lang="en" class="flex flex-wrap justify-center gap-x-[0.7cqw] gap-y-[0.6cqw]"></div>
            </div>

            {{-- Start screen, then the result screen --}}
            <div data-start class="absolute inset-0 z-20 flex overflow-y-auto bg-ink-900/60 p-[1.5cqw]">
                <div class="flex m-auto max-w-[min(94cqw,max(60cqw,300px))] flex-col items-center gap-[max(4px,1.4cqw)] rounded-lg bg-surface-card p-[max(8px,2.6cqw)] text-center shadow-raised">
                    <h2 class="text-[max(17px,3.4cqw)] leading-tight font-bold text-fg-strong">{{ $title }}</h2>
                    <p class="text-[max(12px,1.8cqw)] text-fg-body">Gõ đúng từ để hạ <b>{{ count($words) }} zombie</b>, {{ $secondsPerWord }} giây cho mỗi từ. Zombie nào đến nhà trước khi bạn gõ xong sẽ tấn công nhà, và từ của nó quay lại ở cuối hàng đợi.</p>
                    <x-button variant="accent" size="lg" icon-left="play" data-start-button>Bắt đầu</x-button>
                </div>
            </div>

            <div data-result hidden class="absolute inset-0 z-20 flex overflow-y-auto bg-ink-900/60 p-[1.5cqw]">
                <div class="flex m-auto max-w-[min(94cqw,max(60cqw,300px))] flex-col items-center gap-[max(4px,1.4cqw)] rounded-lg bg-surface-card p-[max(8px,2.6cqw)] text-center shadow-raised">
                    <h2 data-result-title class="text-[max(17px,3.4cqw)] leading-tight font-bold text-fg-strong"></h2>
                    <p data-result-text class="text-[max(12px,1.8cqw)] text-fg-body"></p>
                    <p data-result-note class="text-[max(11px,1.6cqw)] text-fg-muted"></p>
                    <div class="flex flex-wrap justify-center gap-3">
                        <x-button variant="accent" size="sm" icon-left="rotate-ccw" data-restart>Chơi lại</x-button>
                        <x-button variant="outline" size="sm" icon-left="gamepad-2" :href="route('lessons.games', [$course, $unit, $lesson])">Chọn game khác</x-button>
                    </div>
                </div>
            </div>

            <template data-tpl="letter"><span data-letter class="flex size-[max(24px,3.8cqw)] items-center justify-center rounded-md border-[max(2px,0.3cqw)] border-white/60 bg-ink-900/50 text-[max(14px,2.3cqw)] font-bold text-white data-[active=true]:border-accent data-[active=true]:bg-white/25 data-[state=correct]:border-success-border data-[state=correct]:bg-success data-[state=wrong]:border-danger-border data-[state=wrong]:bg-danger"></span></template>
            <template data-tpl="letter-fixed"><span class="flex h-[max(24px,3.8cqw)] items-center text-[max(14px,2.3cqw)] font-bold text-white/80"></span></template>

            <script type="application/json" data-game-config>{!! json_encode($config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
        </div>

        <aside class="flex min-w-0 max-w-full flex-[1_1_380px] flex-col gap-5 rounded-lg bg-surface-card p-6 shadow-card">
            <div class="flex items-center gap-3">
                <div class="flex size-12 items-center justify-center rounded-full border border-accent-border bg-accent-bg">
                    <x-icon name="trophy" size="lg" class="text-accent-text" />
                </div>
                <h2 class="type-h2 text-fg-strong">BXH Cao thủ</h2>
            </div>
            <ol data-best-times class="flex flex-col gap-4">
                @include('lessons.games.best-times', ['times' => $bestTimes])
            </ol>
        </aside>
    </div>
    @endif

    <x-slot:scripts>
        @if ($words !== [])
            @vite('resources/js/games/zombie-defense.js')
        @endif
    </x-slot:scripts>

    <x-slot:footer>
        <x-site-footer />
    </x-slot:footer>
</x-layouts.app>
