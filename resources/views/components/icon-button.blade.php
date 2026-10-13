{{-- Icon-only button. `label` is required for screen readers; `active` makes it a pressed primary toggle. --}}
@props([
    'icon',
    'label',
    'size' => 'md',
    'variant' => 'outline',
    'shape' => 'square',
    'active' => null,
])

<x-button
    :icon="$icon"
    :size="$size"
    :variant="$active ? 'primary' : $variant"
    :shape="$shape"
    :aria-label="$label"
    :title="$label"
    :aria-pressed="$active === null ? null : ($active ? 'true' : 'false')"
    {{ $attributes }}
/>
