<x-layouts.app :title="$page->title" main-class="flex flex-col gap-6 pt-6 pb-10">
    <x-slot:header>
        <x-site-header />
    </x-slot:header>

    <x-breadcrumbs :items="[
        ['label' => 'Trang chủ', 'href' => route('home')],
        ['label' => $page->title],
    ]" />

    <div class="flex flex-wrap items-start gap-10">
        <article class="min-w-0 flex-[999_1_560px] rounded-lg bg-surface-card shadow-card">
            <div class="flex flex-col gap-5 px-6 pt-8 pb-10 sm:px-12 sm:pb-12">
                <h1 class="type-page-title text-balance text-fg-strong">{{ $page->title }}</h1>

                <x-rich-text>{!! $page->content_html !!}</x-rich-text>
            </div>
        </article>

        <aside class="sticky top-21 flex min-w-0 max-w-full flex-[1_1_420px] flex-col gap-6 xl:top-5">
            @if ($otherPages->isNotEmpty())
                <section class="flex flex-col gap-5 rounded-lg bg-surface-card p-6 shadow-card">
                    <h2 class="type-h2 text-fg-strong">Xem thêm</h2>

                    <ul class="flex flex-col">
                        @foreach ($otherPages as $other)
                            <li @class(['border-line-subtle', 'border-t' => ! $loop->first])>
                                <a href="{{ route('pages.show', $other) }}" class="flex items-center justify-between gap-3 py-3 type-label text-fg-strong no-underline transition-interactive hover:text-primary hover:no-underline">
                                    {{ $other->title }}
                                    <x-icon name="arrow-right" size="sm" class="text-fg-muted" />
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif
        </aside>
    </div>

    <x-slot:footer>
        <x-site-footer />
    </x-slot:footer>
</x-layouts.app>
