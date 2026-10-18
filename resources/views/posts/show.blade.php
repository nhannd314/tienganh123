@php
    $courseTones = ['danger', 'primary', 'success'];
    $thumbBackground = ['primary' => 'bg-primary-bg', 'secondary' => 'bg-secondary-bg', 'success' => 'bg-success-bg', 'accent' => 'bg-accent-bg'];
    $thumbText = ['primary' => 'text-primary-text', 'secondary' => 'text-secondary-text', 'success' => 'text-success-text', 'accent' => 'text-accent-text'];
    $shareUrl = route('posts.show', $post);
    $shares = [
        ['label' => 'Facebook', 'icon' => 'facebook', 'href' => 'https://www.facebook.com/sharer/sharer.php?u='.urlencode($shareUrl), 'class' => 'bg-primary shadow-[0_3px_0_var(--color-primary-strong)] hover:bg-primary-strong'],
        ['label' => 'Email', 'icon' => 'mail', 'href' => 'mailto:?subject='.rawurlencode($post->title).'&body='.rawurlencode($shareUrl), 'class' => 'bg-success shadow-[0_3px_0_var(--color-success-strong)] hover:bg-success-strong'],
    ];
@endphp

<x-layouts.app :title="$post->title" main-class="flex flex-col gap-6 pt-6 pb-10">
    <x-slot:header>
        <x-site-header active="posts" />
    </x-slot:header>

    <x-breadcrumbs :items="[
        ['label' => 'Trang chủ', 'href' => route('home')],
        ['label' => 'Blog tiếng Anh', 'href' => route('posts.index')],
        ['label' => $post->title],
    ]" />

    <div class="flex flex-wrap items-start gap-10">
        <article class="min-w-0 flex-[999_1_560px] overflow-hidden rounded-lg bg-surface-card shadow-card">
            @if ($post->thumbnail_url)
                <div class="aspect-[16/8] bg-primary-bg">
                    <img src="{{ $post->thumbnail_url }}" alt="" class="size-full object-cover">
                </div>
            @endif

            <div class="flex flex-col gap-5 px-6 pt-8 pb-10 sm:px-12 sm:pb-12">
                <h1 class="type-h1 text-pretty text-fg-strong max-sm:text-3xl">{{ $post->title }}</h1>

                <div class="flex flex-wrap items-center gap-3">
                    @if ($post->category)
                        <x-category-label :category="$post->category" />
                    @endif
                    <span class="inline-flex items-center gap-1 type-body-sm text-fg-muted"><x-icon name="calendar" size="sm" />{{ $post->publishedOn()->format('d/m/Y') }}</span>
                    <span class="inline-flex items-center gap-1 type-body-sm text-fg-muted"><x-icon name="clock" size="sm" />{{ $post->readMinutes() }} phút đọc</span>
                </div>

                @if ($post->excerpt)
                    <p class="type-body-lg text-pretty text-fg-body">{{ $post->excerpt }}</p>
                @endif

                <x-rich-text>{!! $post->content_html !!}</x-rich-text>

                <div class="flex flex-wrap items-center justify-between gap-4 border-t border-line-subtle pt-5">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="mr-1 type-label-sm text-fg-muted">Chia sẻ:</span>
                        @foreach ($shares as $share)
                            <a
                                href="{{ $share['href'] }}"
                                target="_blank"
                                rel="noopener"
                                aria-label="{{ $share['label'] }}"
                                title="{{ $share['label'] }}"
                                class="inline-flex size-9 items-center justify-center rounded-full sm:size-10 text-on-primary no-underline transition-interactive hover:text-on-primary hover:no-underline {{ $share['class'] }}"
                            >
                                @if ($share['icon'] === 'facebook')
                                    {{-- Lucide 1.x dropped its brand icons; this is the old Lucide `facebook` glyph. --}}
                                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z" /></svg>
                                @else
                                    <x-icon :name="$share['icon']" />
                                @endif
                            </a>
                        @endforeach
                    </div>

                    <x-button variant="outline" size="sm" icon-left="link" :data-copy="$shareUrl"><span data-copy-label data-copied="Đã sao chép">Sao chép link</span></x-button>
                </div>
            </div>
        </article>

        <aside class="sticky top-21 flex min-w-0 max-w-full flex-[1_1_420px] flex-col gap-6 xl:top-5">
            <section class="flex flex-col gap-5 rounded-lg bg-surface-card p-6 shadow-card">
                <h2 class="type-h2 text-fg-strong">Bài viết liên quan</h2>

                @if ($relatedPosts->isEmpty())
                    <p class="type-body text-fg-muted">Chưa có bài viết liên quan.</p>
                @else
                    <div class="flex flex-col">
                        @foreach ($relatedPosts as $related)
                            @php($tone = $related->category?->tone() ?? 'primary')
                            <a href="{{ route('posts.show', $related) }}" @class(['flex items-center gap-4 py-3 text-inherit no-underline transition-interactive hover:text-inherit hover:no-underline hover:opacity-85', 'border-t border-line-subtle' => ! $loop->first])>
                                <span @class(['aspect-4/3 w-24 flex-none overflow-hidden rounded-md border-2 border-secondary-border', $thumbBackground[$tone]])>
                                    @if ($related->thumbnail_url)
                                        <img src="{{ $related->thumbnail_url }}" alt="" class="size-full object-cover">
                                    @endif
                                </span>
                                <span class="flex min-w-0 flex-col gap-1">
                                    <span class="line-clamp-2 type-label-lg text-pretty text-fg-strong">{{ $related->title }}</span>
                                    <span class="flex items-center gap-2 type-body-sm text-fg-muted">
                                        @if ($related->category)
                                            <span class="font-semibold {{ $thumbText[$tone] }}">{{ $related->category->name }}</span>
                                            <span aria-hidden="true">·</span>
                                        @endif
                                        {{ $related->publishedOn()->format('d/m/Y') }}
                                    </span>
                                </span>
                            </a>
                        @endforeach
                    </div>
                @endif
            </section>
        </aside>
    </div>

    @if ($courses->isNotEmpty())
        <section class="flex flex-col gap-5 pt-4">
            <div class="flex flex-wrap items-baseline justify-between gap-3">
                <h2 class="type-h2 text-fg-strong">Khóa học dành cho bạn</h2>
                <a href="{{ route('courses.index') }}" class="inline-flex items-center gap-1 type-label no-underline hover:no-underline">Xem tất cả <x-icon name="arrow-right" size="sm" /></a>
            </div>

            <div class="grid grid-cols-[repeat(auto-fill,minmax(min(100%,280px),1fr))] gap-6">
                @foreach ($courses as $course)
                    <x-course-card :course="$course" :tone="$courseTones[$loop->index % count($courseTones)]" />
                @endforeach
            </div>
        </section>
    @endif

    <x-slot:footer>
        <x-site-footer />
    </x-slot:footer>
</x-layouts.app>
