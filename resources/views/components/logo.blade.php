{{--
    Brand logo "Bong bóng hội thoại" (public/design/logo): the speech bubble with "Aa" and the yellow dot, drawn as inline SVG,
    then the wordmark "HọcTiếngAnh" and, with `domain`, the domain line, set in Lexend like the rest of the site.
    `size` is the height in px of the mark; the wordmark scales with it (40px for a mark of 88px). Reference proportions
    live in public/design/logo/README.md. The dot is dropped on `dark` backgrounds, where the wordmark turns white.
--}}
@props([
    'size' => 40,
    'domain' => false,
    'dark' => false,
])

<span
    {{ $attributes->class(['inline-flex flex-none items-center font-sans']) }}
    style="--u: {{ round($size / 88, 4) }}px; gap: calc(var(--u) * 6)"
>
    <svg viewBox="0 -6 94 94" aria-hidden="true" class="flex-none overflow-visible" style="width: calc(var(--u) * 94); height: calc(var(--u) * 94)">
        <rect width="88" height="72" rx="24" fill="{{ $dark ? '#1675FE' : '#0F62E0' }}" />
        <path d="M18 64H42L21.54 84.46Q18 88 18 83Z" fill="{{ $dark ? '#1675FE' : '#0F62E0' }}" />
        <text x="44" y="36" text-anchor="middle" dominant-baseline="central" font-family="Lexend, sans-serif" font-weight="700" font-size="36" letter-spacing="-1" fill="{{ $dark ? '#1B1F3B' : '#FFFFFF' }}">Aa</text>
        @unless ($dark)
            <circle cx="78" cy="10" r="14" fill="#FFD23F" stroke="#FFFFFF" stroke-width="4" />
        @endunless
    </svg>

    <span class="flex flex-col" style="gap: calc(var(--u) * 4)">
        <span class="font-bold whitespace-nowrap" style="font-size: calc(var(--u) * 40); line-height: 1; letter-spacing: calc(var(--u) * -1)">
            <span @class(['text-white' => $dark, 'text-ink-900' => ! $dark])>Học</span><span @class(['text-blue-200' => $dark, 'text-primary' => ! $dark])>TiếngAnh</span>
        </span>
        @if ($domain)
            <span class="font-medium text-fg-muted" style="font-size: calc(var(--u) * 16); line-height: 1; letter-spacing: calc(var(--u) * 0.5)">hoctienganh.pro.vn</span>
        @endif
    </span>
</span>
