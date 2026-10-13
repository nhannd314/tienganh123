{{-- Progress line: icon · label + value/max with a bar · done marker. Pass `icon` as a Lucide name or an `icon` slot. --}}
@props([
    'label',
    'value' => 0,
    'max' => 100,
    'icon' => null,
    'tone' => 'primary',
    'done' => false,
])

<div {{ $attributes->class(['grid grid-cols-[40px_1fr_24px] items-center gap-3']) }}>
    <span class="flex justify-center">
        @if ($icon instanceof \Illuminate\View\ComponentSlot)
            {{ $icon }}
        @elseif ($icon)
            <x-icon :name="$icon" size="lg" class="text-primary" />
        @endif
    </span>

    <div class="grid gap-2">
        <div class="flex justify-between type-body text-fg-strong">
            <span>{{ $label }}</span>
            <span>{{ $value }}/{{ $max }}</span>
        </div>
        <x-progress-bar :value="$value" :max="$max" :tone="$tone" :label="$label" />
    </div>

    <span
        role="img"
        aria-label="{{ $done ? 'Đã hoàn thành' : 'Chưa hoàn thành' }}"
        @class([
            'flex size-6 items-center justify-center rounded-full border-2',
            'border-success bg-success text-white' => $done,
            'border-line-strong bg-surface-card' => ! $done,
        ])
    >
        @if ($done)
            <x-icon name="check" size="sm" />
        @endif
    </span>
</div>
