{{--
    Labelled field of the login / sign-up forms, in the x-text-input look. The error message comes from the validation
    errors of `name`; resources/js/auth.js shows and clears it while the visitor types.
    `password` makes it a password field and `toggle` adds the show / hide button (one toggle drives every password
    field of the form). `hint` is the help text shown under the field until there is an error. The `aside` slot sits on
    the right of the label (e.g. "Quên mật khẩu?").
--}}
@props([
    'name',
    'label',
    'type' => 'text',
    'placeholder' => null,
    'autocomplete' => null,
    'password' => false,
    'toggle' => false,
    'hint' => null,
])

@php
    $error = $errors->first($name);
    $message = $error ?: $hint;
@endphp

<div class="grid gap-2" data-field="{{ $name }}">
    <div class="flex items-baseline justify-between gap-3">
        <label for="{{ $name }}" class="type-label text-fg-body">{{ $label }}</label>
        {{ $aside ?? '' }}
    </div>

    <div class="relative">
        <input
            id="{{ $name }}"
            name="{{ $name }}"
            type="{{ $password ? 'password' : $type }}"
            @if ($placeholder) placeholder="{{ $placeholder }}" @endif
            @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
            @if ($error) aria-invalid="true" @endif
            @if ($message) aria-describedby="{{ $name }}-hint" @endif
            data-input
            @if ($password) data-password-input @else value="{{ old($name) }}" @endif
            {{ $attributes->class([
                'h-14 w-full rounded-md border px-4 text-xl leading-snug text-fg-strong outline-none transition-interactive placeholder:text-fg-muted focus:shadow-focus',
                'pr-12' => $toggle || ! $password,
                'border-danger-border bg-danger-bg' => $error,
                'border-line-default bg-surface-card hover:border-line-hover' => ! $error,
            ]) }}
        >

        @if ($toggle)
            <button
                type="button"
                data-password-toggle
                aria-label="Hiện mật khẩu"
                aria-pressed="false"
                class="absolute top-1/2 right-1 flex size-12 -translate-y-1/2 cursor-pointer items-center justify-center rounded-sm text-fg-muted transition-interactive hover:bg-primary-bg hover:text-primary-text"
            >
                <x-icon name="eye" />
            </button>
        @elseif (! $password)
            <x-icon name="circle-x" size="lg" data-field-icon :hidden="! $error" class="absolute top-1/2 right-4 -translate-y-1/2 text-danger-text" />
        @endif
    </div>

    <span
        id="{{ $name }}-hint"
        data-field-hint
        @if ($hint) data-default-hint="{{ $hint }}" @endif
        @class([
            'type-body-sm',
            'text-danger-text' => $error,
            'text-fg-muted' => ! $error,
            'hidden' => ! $message,
        ])
    >{{ $message }}</span>
</div>
