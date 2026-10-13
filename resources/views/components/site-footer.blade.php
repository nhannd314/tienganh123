{{-- Site footer: brand, link columns and copyright. --}}
@php
    $footerColumns = [
        'Học tập' => ['Khóa học', 'Từ vựng', 'Bài kiểm tra', 'Trò chơi'],
        'Hỗ trợ' => ['Hướng dẫn học', 'Câu hỏi thường gặp', 'Dành cho phụ huynh'],
        'Liên hệ' => ['hello@funenglish.vn', '1900 1234', 'Facebook'],
    ];
@endphp

<footer class="mt-8 border-t border-line-subtle bg-surface-header">
    <div class="mx-auto flex max-w-layout flex-col gap-10 px-10 pt-12 pb-6">
        <div class="grid grid-cols-[repeat(auto-fit,minmax(min(100%,220px),1fr))] gap-10">
            <div class="flex flex-col gap-3">
                <span class="text-3xl leading-none font-bold tracking-tight text-primary">FunEnglish</span>
                <p class="type-body text-pretty text-fg-muted">Learn • Play • Grow — Học tiếng Anh mỗi ngày cùng bạn bè nhé!</p>
            </div>

            @foreach ($footerColumns as $columnTitle => $links)
                <div class="flex flex-col gap-3">
                    <span class="type-label-xl text-fg-strong">{{ $columnTitle }}</span>
                    @foreach ($links as $link)
                        <a href="#" class="type-body text-fg-body no-underline hover:text-fg-link hover:no-underline">{{ $link }}</a>
                    @endforeach
                </div>
            @endforeach
        </div>

        <div class="border-t border-line-subtle pt-5 type-body-sm text-fg-muted">© {{ date('Y') }} FunEnglish</div>
    </div>
</footer>
