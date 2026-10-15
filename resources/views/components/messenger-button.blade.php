{{-- Floating button, bottom right of every page, that opens a Messenger chat with the Facebook page set in config/site.php. --}}
@php
    $facebook = config('site.contact.facebook');
@endphp

@if (filled($facebook))
    <a
        href="https://m.me/{{ $facebook }}"
        target="_blank"
        rel="noopener"
        aria-label="Chat Messenger"
        title="Chat Messenger"
        {{ $attributes->class(['fixed right-4 bottom-4 z-30 flex size-14 items-center justify-center rounded-full text-white shadow-raised no-underline transition-interactive hover:-translate-y-0.5 hover:text-white hover:no-underline active:translate-y-0 print:hidden sm:right-6 sm:bottom-6']) }}
        style="background: linear-gradient(135deg, #00b2ff, #006aff)"
    >
        <svg viewBox="0 0 24 24" aria-hidden="true" class="size-7 fill-current">
            <path d="M.001 11.639C.001 4.949 5.241 0 12.001 0S24 4.95 24 11.639c0 6.689-5.24 11.638-12 11.638-1.21 0-2.38-.16-3.47-.46a.96.96 0 00-.64.05l-2.39 1.05a.96.96 0 01-1.35-.85l-.07-2.14a.97.97 0 00-.32-.68A11.39 11.39 0 01.002 11.64zm8.32-2.19l-3.52 5.6c-.35.53.32 1.139.82.75l3.79-2.87c.26-.2.6-.2.87 0l2.8 2.1c.84.63 2.04.4 2.6-.48l3.52-5.6c.35-.53-.32-1.13-.82-.75l-3.79 2.87c-.25.2-.6.2-.86 0l-2.8-2.1a1.8 1.8 0 00-2.61.48z" />
        </svg>
    </a>
@endif
