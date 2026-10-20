{{-- White card inviting the learner to the advanced review: the pitch with the four exercises, then the start button. `href` is where the advanced review starts. --}}
@props([
    'href',
])

@php
    $exercises = [
        ['icon' => 'text-cursor-input', 'label' => 'Điền từ'],
        ['icon' => 'pencil-line', 'label' => 'Viết câu'],
        ['icon' => 'languages', 'label' => 'Dịch câu'],
        ['icon' => 'mic', 'label' => 'Đọc câu'],
    ];
@endphp

<div {{ $attributes->class(['flex flex-col gap-5 rounded-lg bg-surface-card p-6 shadow-card']) }}>
    <div class="flex items-center gap-4">
        <span class="flex size-16 flex-none items-center justify-center rounded-full bg-primary-bg">
            <x-icon name="graduation-cap" size="xl" class="text-primary" />
        </span>

        <div class="flex min-w-0 flex-col gap-1">
            <h2 class="type-h2 text-fg-strong">Ôn tập nâng cao</h2>
            <p class="type-body text-fg-muted">Luyện sâu hơn để nhớ lâu</p>
        </div>
    </div>

    <ul class="grid grid-cols-2 gap-x-6 gap-y-4">
        @foreach ($exercises as $exercise)
            <li class="flex items-center gap-3 type-label-lg text-fg-strong">
                <x-icon :name="$exercise['icon']" size="lg" class="text-primary" />
                {{ $exercise['label'] }}
            </li>
        @endforeach
    </ul>

    <x-button size="lg" icon-left="book-open-check" :href="$href" full-width>Ôn tập ngay</x-button>
</div>
