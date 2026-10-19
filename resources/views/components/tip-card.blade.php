{{-- One-sentence learning tip on a soft info background. --}}
@props([
    'title' => 'Mẹo học nhanh',
    'icon' => 'lightbulb',
])

<div {{ $attributes->class(['grid gap-2 rounded-md bg-info-bg p-7']) }}>
    <div class="flex items-center gap-2 type-h3 text-fg-strong">
        <x-icon :name="$icon" size="lg" class="text-info" />
        {{ $title }}
    </div>
    <div class="type-body-sm text-fg-muted">{{ $slot }}</div>
</div>
