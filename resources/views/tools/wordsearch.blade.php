<x-layouts.app
    title="Tạo ô chữ tìm kiếm"
    description="Công cụ tạo ô chữ tìm kiếm (word search) 15x15 từ danh sách từ vựng và gợi ý của bạn, xuất ra ảnh PNG."
    main-class="flex flex-col gap-6 pt-6 pb-10"
>
    <x-slot:header>
        <x-site-header />
    </x-slot:header>

    <x-breadcrumbs :items="[
        ['label' => 'Trang chủ', 'href' => route('home')],
        ['label' => 'Tạo ô chữ tìm kiếm'],
    ]" />

    <div class="flex flex-wrap items-start gap-8" data-wordsearch>
        <section class="flex min-w-0 flex-[1_1_320px] flex-col gap-5 rounded-lg bg-surface-card p-6 shadow-card">
            <h1 class="type-h1 text-fg-strong">Tạo ô chữ tìm kiếm</h1>

            <label class="grid gap-2">
                <span class="type-label text-fg-body">Mỗi dòng một từ, dạng: từ | gợi ý</span>
                <textarea
                    data-wordsearch-words
                    rows="12"
                    placeholder="apple | quả táo&#10;banana | quả chuối&#10;orange | quả cam"
                    class="w-full rounded-md border border-line-default bg-surface-page p-4 text-lg text-fg-strong outline-none transition-interactive focus:shadow-focus"
                ></textarea>
            </label>

            <label class="flex cursor-pointer items-center gap-3 type-body text-fg-body select-none">
                <x-checkbox data-wordsearch-answers />
                Hiện đáp án
            </label>

            <p class="hidden type-body-sm text-danger-text" data-wordsearch-warning></p>

            <div class="flex flex-wrap gap-3">
                <x-button type="button" data-wordsearch-generate>Regenerate</x-button>
                <x-button type="button" variant="outline" data-wordsearch-export>Xuất ra file PNG</x-button>
            </div>
        </section>

        <section class="flex min-w-0 flex-[1_1_480px] flex-col gap-5 rounded-lg bg-surface-card p-6 shadow-card">
            <canvas data-wordsearch-canvas class="h-auto w-full max-w-200 self-center" aria-label="Ô chữ tìm kiếm"></canvas>
        </section>
    </div>

    <x-slot:scripts>
        @vite('resources/js/wordsearch.js')
    </x-slot:scripts>
</x-layouts.app>
