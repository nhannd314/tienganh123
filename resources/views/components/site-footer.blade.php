{{--
    Site footer: brand, link columns and copyright. The support column lists the published fixed pages, except the legal
    ones (seeded by LegalPageSeeder), which sit at the right of the copyright line; the contact links under the brand come from config/site.php.
--}}
@php
    [$legalPages, $supportPages] = \App\Models\Page::query()->published()->orderBy('sort_order')->orderBy('id')->get()
        ->partition(fn ($page) => in_array($page->slug, ['chinh-sach-bao-mat', 'dieu-khoan-su-dung'], true));
    $supportLinks = $supportPages->mapWithKeys(fn ($page) => [$page->title => route('pages.show', $page)])->all();

    $contact = config('site.contact');
    $phone = $contact['phone'];

    $footerColumns = [
        'Học tập' => ['Khóa học' => route('courses.index'), 'Từ vựng' => route('vocabularies.index'), 'Flashcard' => route('flashcards.index'), 'Câu mẫu' => route('sentences.index')],
        'Hỗ trợ' => ['Blog tiếng Anh' => route('posts.index'), ...$supportLinks],
        'Thư viện' => ['Crossword' => route('tools.crossword'), 'Wordsearch' => route('tools.wordsearch')],
    ];

    $contactLinks = [
        $contact['email'] => 'mailto:'.$contact['email'],
        substr($phone, 0, 4).' '.substr($phone, 4, 3).' '.substr($phone, 7) => 'tel:'.$phone,
        'facebook.com/'.$contact['facebook'] => 'https://facebook.com/'.$contact['facebook'],
    ];
@endphp

<footer class="mt-8 border-t border-line-subtle bg-surface-header">
    <div class="mx-auto flex max-w-layout flex-col gap-10 px-10 pt-12 pb-6">
        <div class="grid grid-cols-[repeat(auto-fit,minmax(min(100%,220px),1fr))] gap-10 lg:grid-cols-[minmax(0,2fr)_repeat(3,minmax(0,1fr))]">
            <div class="flex flex-col gap-3">
                <a href="{{ route('home') }}" aria-label="TiếngAnhPro — Trang chủ" class="self-start no-underline hover:no-underline"><x-logo :size="56" domain /></a>
                <p class="type-body text-pretty text-fg-muted">Học tiếng Anh như này mà còn không học được nữa thì thôi!</p>
                <div class="flex flex-col gap-1">
                    @foreach ($contactLinks as $label => $href)
                        <a href="{{ $href }}" @if (str_starts_with($href, 'http')) target="_blank" rel="noopener" @endif class="type-body text-fg-body no-underline hover:text-fg-link hover:no-underline">{{ $label }}</a>
                    @endforeach
                </div>
            </div>

            @foreach (array_filter($footerColumns) as $columnTitle => $links)
                <div class="flex flex-col gap-3">
                    <span class="type-label-xl text-fg-strong lg:mb-8">{{ $columnTitle }}</span>
                    @foreach ($links as $label => $href)
                        <a href="{{ $href }}" @if (str_starts_with($href, 'http')) target="_blank" rel="noopener" @endif class="type-body text-fg-body no-underline hover:text-fg-link hover:no-underline">{{ $label }}</a>
                    @endforeach
                </div>
            @endforeach
        </div>

        <div class="flex flex-wrap items-center justify-between gap-x-6 gap-y-2 border-t border-line-subtle pt-5 type-body-sm text-fg-muted">
            <span>© {{ date('Y') }} hoctienganh.pro.vn</span>
            @if ($legalPages->isNotEmpty())
                <nav aria-label="Pháp lý" class="flex flex-wrap gap-x-5 gap-y-2">
                    @foreach ($legalPages as $legalPage)
                        <a href="{{ route('pages.show', $legalPage) }}" class="text-fg-muted no-underline hover:text-fg-link hover:no-underline">{{ $legalPage->title }}</a>
                    @endforeach
                </nav>
            @endif
        </div>
    </div>
</footer>
