{{--
    Search field with a leading search icon. `class` styles the wrapper (default width w-60);
    every other attribute (name, value, placeholder…) goes to the <input>.
--}}
@props([
    'label' => 'Tìm kiếm',
    'placeholder' => 'Tìm kiếm...',
    'shape' => 'rounded',
])

<label {{ $attributes->only('class')->class([
    'flex h-12 w-60 cursor-text items-center gap-2 border border-line-default bg-surface-page px-4 text-fg-muted transition-interactive',
    'hover:border-line-hover focus-within:shadow-focus',
    'has-disabled:cursor-not-allowed has-disabled:border-line-disabled has-disabled:bg-surface-disabled has-disabled:text-fg-disabled',
    $shape === 'pill' ? 'rounded-full' : 'rounded-md',
]) }}>
    <x-icon name="search" size="md" />
    <input
        type="search"
        aria-label="{{ $label }}"
        placeholder="{{ $placeholder }}"
        {{ $attributes->except('class')->class([
            'min-w-0 flex-1 border-0 bg-transparent type-body text-fg-strong outline-none',
            'disabled:cursor-not-allowed disabled:text-fg-disabled',
        ]) }}
    >
</label>
