{{-- A slideshow shown full screen. Open to anyone with the short link; resources/js/slide-presenter.js moves between the slides. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $deck->title }} — TiếngAnhPro</title>
        <meta name="robots" content="noindex, nofollow">
        <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Lexend:wght@400;500;600;700&display=swap&subset=vietnamese">
        @vite(['resources/css/app.css', 'resources/js/slide-presenter.js'])
    </head>
    <body class="m-0 h-dvh overflow-hidden">
        <div data-slide-presenter class="relative flex h-dvh w-screen select-none flex-col" tabindex="0" aria-roledescription="carousel" aria-label="{{ $deck->title }}">
            @foreach ($deck->slides as $slide)
                <figure data-slide @unless ($loop->first) hidden @endunless class="m-0 flex min-h-0 flex-1 flex-col items-center justify-center gap-4 px-4 pt-4 pb-24 sm:px-16 lg:pt-12">
                    <div class="flex min-h-0 w-full flex-1 items-center justify-center">
                        <img src="{{ $slide->image_url }}" alt="" class="max-h-full max-w-full rounded-lg border-4 border-line-default object-contain">
                    </div>
                    @if (filled($slide->text))
                        <figcaption class="max-w-5xl text-center text-xl leading-relaxed text-balance whitespace-pre-line sm:text-3xl">{{ $slide->text }}</figcaption>
                    @endif
                </figure>
            @endforeach

            <button type="button" data-slide-prev aria-label="Slide trước" class="absolute top-1/2 left-2 sm:left-4 z-10 flex size-12 -translate-y-1/2 cursor-pointer items-center justify-center rounded-full border border-line-default bg-surface-card text-primary shadow-card transition-colors hover:bg-primary-bg disabled:cursor-default disabled:opacity-30 sm:size-14">
                <x-icon name="chevron-left" size="xl" />
            </button>
            <button type="button" data-slide-next aria-label="Slide sau" class="absolute top-1/2 right-2 sm:right-4 z-10 flex size-12 -translate-y-1/2 cursor-pointer items-center justify-center rounded-full border border-line-default bg-surface-card text-primary shadow-card transition-colors hover:bg-primary-bg disabled:cursor-default disabled:opacity-30 sm:size-14">
                <x-icon name="chevron-right" size="xl" />
            </button>

            <div class="absolute inset-x-0 bottom-0 flex items-center justify-between gap-4 px-4 py-4 sm:px-8">
                <span class="max-w-[40%] truncate text-sm text-fg-muted">{{ $deck->title }}</span>

                <span data-slide-counter class="min-w-16 text-center text-sm tabular-nums text-fg-muted" aria-live="polite"></span>

                <button type="button" data-slide-fullscreen aria-label="Toàn màn hình" class="flex size-12 cursor-pointer items-center justify-center rounded-full border border-line-default bg-surface-card text-primary shadow-card transition-colors hover:bg-primary-bg">
                    <x-icon name="maximize" size="lg" />
                </button>
            </div>
        </div>
    </body>
</html>
