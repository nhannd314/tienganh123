<x-layouts.app title="Blog tiếng Anh" main-class="flex flex-col gap-10 pt-6 pb-10">
    <x-slot:header>
        <x-site-header active="posts" />
    </x-slot:header>

    <x-breadcrumbs :items="[
        ['label' => 'Trang chủ', 'href' => route('home')],
        ['label' => 'Blog tiếng Anh'],
    ]" class="-mb-4" />

    <section class="flex flex-col items-start gap-3">
        <h1 class="type-page-title tracking-tight text-balance text-fg-strong">Blog tiếng Anh</h1>
        <p class="max-w-160 type-body-lg text-pretty text-fg-muted">Mẹo học tập và kinh nghiệm giúp bạn học tiếng Anh vui và nhớ lâu hơn.</p>
    </section>

    @if ($categories->isNotEmpty())
        <nav aria-label="Lọc theo chuyên mục" class="flex flex-wrap gap-2">
            <a href="{{ route('posts.index') }}" @if (! $activeCategory) aria-current="page" @endif class="no-underline hover:no-underline">
                <x-pill :tone="$activeCategory ? 'neutral' : 'primary'">Tất cả</x-pill>
            </a>
            @foreach ($categories as $category)
                <a href="{{ route('posts.index', ['danh-muc' => $category->slug]) }}" @if ($activeCategory?->is($category)) aria-current="page" @endif class="no-underline hover:no-underline">
                    <x-pill :tone="$activeCategory?->is($category) ? 'primary' : 'neutral'">{{ $category->name }}</x-pill>
                </a>
            @endforeach
        </nav>
    @endif

    @if ($posts->isEmpty())
        <x-card padding="lg" class="flex flex-col items-center gap-3 text-center">
            <x-icon name="newspaper" size="xl" class="text-primary" />
            <h2 class="type-h3 text-fg-strong">Blog chưa có bài viết nào</h2>
            <p class="type-body text-fg-muted">Các bài viết mới sẽ sớm xuất hiện trên blog, bạn quay lại sau nhé!</p>
        </x-card>
    @else
        <div class="grid grid-cols-[repeat(auto-fill,minmax(min(100%,300px),1fr))] gap-6">
            @foreach ($posts as $post)
                <a href="{{ route('posts.show', $post) }}" class="group flex flex-col overflow-hidden rounded-lg border border-line-subtle bg-surface-card text-inherit no-underline shadow-card transition-interactive hover:-translate-y-0.5 hover:text-inherit hover:no-underline hover:shadow-raised active:translate-y-0">
                    <div class="relative aspect-[16/9] bg-primary-bg">
                        @if ($post->thumbnail_url)
                            <img src="{{ $post->thumbnail_url }}" alt="" class="size-full object-cover">
                        @endif
                        <span class="absolute right-3 bottom-3 inline-flex items-center gap-1 rounded-sm bg-surface-card/90 px-2 py-1 type-caption font-semibold text-fg-strong shadow-xs">
                            <x-icon name="calendar" size="sm" />{{ $post->publishedOn()->format('d/m/Y') }}
                        </span>
                    </div>
                    <div class="flex flex-1 flex-col gap-3 p-5">
                        <h2 class="type-h3 text-pretty text-fg-strong">{{ $post->title }}</h2>
                        @if ($post->category)
                            <div class="flex">
                                <x-category-label :category="$post->category" />
                            </div>
                        @endif
                        @if ($post->excerpt)
                            <p class="line-clamp-2 type-body text-fg-muted">{{ $post->excerpt }}</p>
                        @endif
                    </div>
                </a>
            @endforeach
        </div>

        {{ $posts->links('pagination.default') }}
    @endif

    <x-slot:footer>
        <x-site-footer />
    </x-slot:footer>
</x-layouts.app>
