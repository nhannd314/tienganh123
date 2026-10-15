{{-- White card inviting the learner to play a word game: the pitch on a soft secondary panel, then the play button. `href` is where the game starts. --}}
@props([
    'href',
])

@php
    $benefits = ['Vừa chơi vừa học', 'Tăng tốc độ phản xạ', 'Tăng tốc độ đánh máy'];
@endphp

<div {{ $attributes->class(['flex flex-col gap-5 rounded-lg bg-surface-card p-6 shadow-card']) }}>
    <div class="flex flex-col gap-5 rounded-md bg-secondary-bg p-5">
        <div class="flex items-center gap-3">
            <x-icon name="gamepad-2" size="lg" class="text-secondary" />
            <h2 class="type-h3 text-fg-strong">Chơi game giải trí</h2>
        </div>

        <ul class="flex flex-col gap-3 pl-2">
            @foreach ($benefits as $benefit)
                <li class="flex items-center gap-3 type-body text-fg-muted">
                    <x-icon name="circle-check" size="md" />
                    {{ $benefit }}
                </li>
            @endforeach
        </ul>
    </div>

    <x-button variant="secondary" size="lg" icon-left="play" :href="$href" full-width>Chơi ngay</x-button>
</div>
