{{-- Site footer: brand, link columns and copyright. The support column lists the published fixed pages; the contact column comes from config/site.php. --}}
@php
    $supportLinks = \App\Models\Page::query()->published()->orderBy('sort_order')->orderBy('id')->get()
        ->mapWithKeys(fn ($page) => [$page->title => route('pages.show', $page)])
        ->all();

    $contact = config('site.contact');
    $phone = $contact['phone'];

    $footerColumns = [
        'Học tập' => ['Khóa học' => route('courses.index'), 'Từ vựng' => route('vocabularies.index'), 'Blog tiếng Anh' => route('posts.index')],
        'Hỗ trợ' => $supportLinks,
        'Liên hệ' => [
            $contact['email'] => 'mailto:'.$contact['email'],
            substr($phone, 0, 4).' '.substr($phone, 4, 3).' '.substr($phone, 7) => 'tel:'.$phone,
            'facebook.com/'.$contact['facebook'] => 'https://facebook.com/'.$contact['facebook'],
        ],
    ];
@endphp

<footer class="mt-8 border-t border-line-subtle bg-surface-header">
    <div class="mx-auto flex max-w-layout flex-col gap-10 px-10 pt-12 pb-6">
        <div class="grid grid-cols-[repeat(auto-fit,minmax(min(100%,220px),1fr))] gap-10">
            <div class="flex flex-col gap-3">
                <a href="{{ route('home') }}" aria-label="Học Tiếng Anh — Trang chủ" class="self-start no-underline hover:no-underline"><x-logo :size="56" domain /></a>
                <p class="type-body text-pretty text-fg-muted">Learn • Play • Grow — Học tiếng Anh mỗi ngày cùng bạn bè nhé!</p>
            </div>

            @foreach (array_filter($footerColumns) as $columnTitle => $links)
                <div class="flex flex-col gap-3">
                    <span class="type-label-xl text-fg-strong">{{ $columnTitle }}</span>
                    @foreach ($links as $label => $href)
                        <a href="{{ $href }}" @if (str_starts_with($href, 'http')) target="_blank" rel="noopener" @endif class="type-body text-fg-body no-underline hover:text-fg-link hover:no-underline">{{ $label }}</a>
                    @endforeach
                </div>
            @endforeach
        </div>

        <div class="border-t border-line-subtle pt-5 type-body-sm text-fg-muted">© {{ date('Y') }} hoctienganh.pro.vn</div>
    </div>
</footer>
