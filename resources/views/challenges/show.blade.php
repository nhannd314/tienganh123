{{--
    The challenge page: the brick-throwing game of the two players (see App\Services\ChallengeGame and
    resources/js/games/challenge.js). The challenger is the boy on the left, the opponent the girl on the right. Both press
    Start; then each types the current word from its Vietnamese meaning, and the page follows the game live.
--}}
@php
    $lesson = $challenge->lesson;
    $players = [
        'challenger' => ['user' => $challenge->challenger, 'image' => asset('games/challenge/boy.webp')],
        'opponent' => ['user' => $challenge->opponent, 'image' => asset('games/challenge/girl.webp')],
    ];
    $config = [
        'side' => $side,
        'state' => $state,
        'names' => ['challenger' => $challenge->challenger->name, 'opponent' => $challenge->opponent->name],
        'channel' => "challenges.{$challenge->id}",
        'csrf' => csrf_token(),
        'sounds' => asset('games/challenge/sound'),
        'urls' => [
            'ready' => route('challenges.ready', $challenge),
            'type' => route('challenges.type', $challenge),
            'state' => route('challenges.state', $challenge),
        ],
    ];
@endphp

<x-layouts.app title="Thách đấu" noindex main-class="flex flex-col gap-6 pt-6 pb-10">
    <x-slot:header>
        <x-site-header />
    </x-slot:header>

    <x-breadcrumbs :items="[
        ['label' => 'Trang chủ', 'href' => route('home')],
        ['label' => 'Thách đấu'],
    ]" />

    <div class="mx-auto flex w-full max-w-4xl flex-col gap-4" data-challenge-game>
        <script type="application/json" data-game-config>{!! json_encode($config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>

        <div class="flex flex-wrap items-baseline justify-between gap-2">
            <h1 class="type-h1 text-fg-strong">Thách đấu</h1>
            <p class="type-body text-fg-muted">{{ $lesson->unit->course->title }} · {{ $lesson->unit->title }} · <b class="text-fg-strong">{{ $lesson->title }}</b></p>
        </div>

        {{-- The scene: the background of the design, the health of both players on top and the two characters. --}}
        <div data-stage class="relative aspect-video w-full overflow-hidden rounded-lg bg-cover bg-center shadow-card select-none" style="background-image: url('{{ asset('games/challenge/bg.webp') }}')">
            <div class="absolute inset-x-0 top-0 flex items-start gap-2 p-2 sm:gap-4 sm:p-4">
                @foreach ($players as $playerSide => $player)
                    <div @class(['flex min-w-0 flex-1 items-center gap-2 sm:gap-3', 'flex-row-reverse' => $playerSide === 'opponent', 'order-3' => $playerSide === 'opponent'])>
                        <span @class([
                            'flex size-10 flex-none items-center justify-center overflow-hidden rounded-full border-4 bg-white sm:size-16',
                            'border-primary' => $playerSide === 'challenger',
                            'border-secondary' => $playerSide === 'opponent',
                        ])>
                            @if ($player['user']->avatar_url)
                                <img src="{{ $player['user']->avatar_url }}" alt="" class="size-full object-cover">
                            @else
                                <img src="{{ $player['image'] }}" alt="" class="size-full object-cover object-top">
                            @endif
                        </span>
                        <div @class(['flex min-w-0 flex-1 flex-col gap-1', 'items-end' => $playerSide === 'opponent'])>
                            <span class="max-w-full truncate rounded-full bg-black/45 px-2 type-label-sm font-semibold text-white sm:type-label">
                                {{ $player['user']->name }}@if ($playerSide === $side) (bạn)@endif
                            </span>
                            <div role="img" class="relative h-3 w-full overflow-hidden rounded-full border-2 border-white bg-black/40 sm:h-5">
                                <div data-health-bar="{{ $playerSide }}" @class(['absolute inset-y-0 h-full rounded-full bg-success transition-[width] duration-300', 'left-0' => $playerSide === 'challenger', 'right-0' => $playerSide === 'opponent']) style="width: 100%"></div>
                            </div>
                        </div>
                    </div>
                @endforeach

                <div class="order-2 flex flex-none flex-col items-center gap-1">
                    <span class="text-2xl font-black text-accent drop-shadow-[0_2px_0_rgba(0,0,0,0.6)] sm:text-5xl">VS</span>
                    <span data-word-counter class="rounded-full bg-primary-strong px-3 py-0.5 type-label-sm font-semibold text-white tabular-nums sm:type-label-lg"></span>
                </div>
            </div>

            {{--
                Each character is its body and, over it, its raised arm (cut out of the same picture, on a layer of the
                same size) holding a brick. resources/js/games/challenge.js swings the arm around the shoulder and throws
                the brick. The origin of the arm layer is the shoulder, in shares of the picture.
            --}}
            @foreach ([
                'challenger' => ['name' => 'boy', 'placement' => 'left-[8%] aspect-[519/600]', 'shoulder' => 'origin-[26.6%_46%]', 'hand' => 'left-[1%] top-[13%]'],
                'opponent' => ['name' => 'girl', 'placement' => 'right-[8%] aspect-[509/600]', 'shoulder' => 'origin-[63.3%_50.3%]', 'hand' => 'left-[70%] top-[26%] -scale-x-100'],
            ] as $playerSide => $character)
                <div data-character="{{ $playerSide }}" class="absolute bottom-[24%] h-[40%] {{ $character['placement'] }}">
                    <img src="{{ asset("games/challenge/{$character['name']}-body.webp") }}" alt="{{ $players[$playerSide]['user']->name }}" class="size-full">
                    <div data-arm class="absolute inset-0 {{ $character['shoulder'] }}">
                        <img src="{{ asset("games/challenge/{$character['name']}-arm.webp") }}" alt="" class="size-full">
                        <img data-hand-brick src="{{ asset('games/challenge/brick.svg') }}" alt="" class="absolute {{ $character['hand'] }} w-[24%]">
                    </div>
                </div>
            @endforeach

            {{-- The word to type, at the bottom of the scene: its meaning, and a box per letter that shows the letter once it is typed right. --}}
            <section data-typing hidden class="absolute inset-x-0 bottom-[3%] mx-auto flex w-[min(90%,28rem)] flex-col items-center gap-1.5 rounded-lg bg-surface-card/90 px-3 py-2 text-center shadow-raised sm:gap-2 sm:px-5 sm:py-3">
                <p data-meaning class="type-label text-balance text-fg-strong sm:type-h3"></p>

                <label class="relative flex cursor-text flex-wrap justify-center" data-letters-box>
                    <span class="sr-only">Gõ từ tiếng Anh</span>
                    <input
                        data-letter-input
                        type="text"
                        autocomplete="off"
                        autocapitalize="off"
                        autocorrect="off"
                        spellcheck="false"
                        enterkeyhint="next"
                        class="absolute inset-0 size-full cursor-text opacity-0"
                    >
                    <span data-letters class="pointer-events-none flex flex-wrap justify-center gap-1 sm:gap-1.5"></span>
                </label>
            </section>

            {{-- Start, waiting for the other player, the winner of a round and the end of the game. --}}
            <div data-overlay class="absolute inset-0 flex items-center justify-center bg-black/35 p-4">
                <div class="flex max-w-md flex-col items-center gap-4 rounded-lg bg-surface-card px-6 py-6 text-center shadow-raised sm:px-10">
                    <div data-panel="start" class="flex flex-col items-center gap-3">
                        <p class="type-body text-pretty text-fg-body">Gõ đúng mỗi chữ cái để ném gạch vào đối thủ. Ai gõ xong từ trước sẽ thắng lượt đó!</p>
                        <x-button type="button" size="lg" icon-left="play" data-start>Start</x-button>
                        <p data-opponent-ready hidden class="type-body-sm font-semibold text-success-text">Đối thủ đã sẵn sàng!</p>
                    </div>
                    <div data-panel="waiting" hidden class="flex items-center gap-3 type-body text-fg-body">
                        <x-icon name="loader-circle" spin />
                        Đang chờ đối thủ sẵn sàng…
                    </div>
                    <div data-panel="finished" hidden class="flex flex-col items-center gap-3">
                        <p data-result class="type-h1 text-fg-strong"></p>
                        <p data-result-detail class="type-body text-fg-muted"></p>
                        <x-button :href="route('home')" variant="outline" icon-left="house">Về trang chủ</x-button>
                    </div>
                </div>
            </div>

            <p data-round-message hidden class="absolute inset-x-0 top-[30%] mx-auto w-fit rounded-full bg-black/60 px-5 py-2 type-label-lg font-semibold text-white"></p>

            <button type="button" data-mute aria-pressed="false" aria-label="Tắt âm thanh" title="Âm thanh" class="absolute right-2 bottom-2 z-20 flex size-9 cursor-pointer items-center justify-center rounded-full bg-white/85 text-fg-strong shadow-card transition-interactive hover:bg-white sm:right-3 sm:bottom-3 sm:size-11">
                <x-icon name="volume-2" data-mute-on />
                <x-icon name="volume-x" data-mute-off hidden />
            </button>
        </div>

    </div>

    <x-slot:scripts>
        @vite('resources/js/games/challenge.js')
    </x-slot:scripts>
    <x-slot:footer>
        <x-site-footer />
    </x-slot:footer>
</x-layouts.app>
