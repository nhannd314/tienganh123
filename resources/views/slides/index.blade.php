{{-- The members-only page to make a slideshow (images with text) and the list of the ones already made, each with its short link. --}}
<x-layouts.app title="Slide" main-class="flex flex-col gap-6 pt-6 pb-10" :noindex="true">
    <x-slot:header>
        <x-site-header />
    </x-slot:header>

    <x-breadcrumbs :items="[
        ['label' => 'Trang chủ', 'href' => route('home')],
        ['label' => 'Slide'],
    ]" class="mx-auto w-full max-w-240" />

    @if ($createdCode = session('created_deck'))
        <x-alert tone="success" title="Đã tạo slide" class="mx-auto w-full max-w-240">
            Link trình chiếu: <a href="{{ route('slides.show', $createdCode) }}" class="font-semibold text-fg-link underline">{{ route('slides.show', $createdCode) }}</a>
        </x-alert>
    @endif

    <section class="mx-auto flex w-full max-w-240 flex-col gap-6 rounded-lg bg-surface-card p-6 shadow-card sm:p-8">
        <div class="flex flex-col gap-2">
            <h1 class="type-h1 text-balance text-fg-strong">Tạo slide trình chiếu</h1>
            <p class="type-body text-fg-muted">Mỗi slide gồm một ảnh và nội dung đi kèm. Sau khi lưu, bạn nhận một link rút gọn để trình chiếu toàn màn hình, ai có link cũng xem được.</p>
        </div>

        @if ($errors->any())
            <x-alert tone="danger" title="Chưa lưu được slide">
                <ul class="list-disc pl-5">
                    @foreach (collect($errors->all())->unique() as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            </x-alert>
        @endif

        <form method="POST" action="{{ route('slides.store') }}" enctype="multipart/form-data" data-slide-form data-max-slides="{{ $maxSlides }}" class="flex flex-col gap-6">
            @csrf

            <x-text-input label="Tiêu đề" name="title" size="md" maxlength="120" required :value="old('title')" placeholder="Ví dụ: Từ vựng chủ đề gia đình" />

            <div data-slide-list class="flex flex-col gap-4"></div>

            <template data-slide-template>
                @include('slides.row', ['index' => '__INDEX__', 'slide' => null])
            </template>

            <div class="flex flex-wrap items-center justify-between gap-4">
                <x-button variant="outline" icon-left="plus" data-slide-add>Thêm slide</x-button>
                <x-button type="submit" size="lg" icon-left="presentation">Tạo slide</x-button>
            </div>
        </form>
    </section>

    <section class="mx-auto flex w-full max-w-240 flex-col gap-4 rounded-lg bg-surface-card p-6 shadow-card sm:p-8">
        <h2 class="type-h2 text-fg-strong">Slide của bạn</h2>

        @forelse ($decks as $deck)
            <div class="flex flex-wrap items-center justify-between gap-4 border-t border-line-subtle pt-4 first:border-t-0 first:pt-0">
                <div class="flex min-w-0 flex-col gap-1">
                    <b class="type-label-lg truncate text-fg-strong">{{ $deck->title }}</b>
                    <span class="type-body-sm text-fg-muted">{{ $deck->slides_count }} slide · {{ $deck->created_at->format('d/m/Y') }}</span>
                    <a href="{{ $deck->shareUrl() }}" class="type-body-sm break-all text-fg-link underline">{{ $deck->shareUrl() }}</a>
                </div>

                <div class="flex items-center gap-2">
                    <x-icon-button icon="copy" label="Sao chép link" size="sm" :data-copy="$deck->shareUrl()" />
                    <x-button variant="secondary" size="sm" icon-left="play" :href="$deck->shareUrl()">Trình chiếu</x-button>
                    <x-button variant="outline" size="sm" icon-left="pencil" :href="route('slides.edit', $deck)">Sửa</x-button>
                    <form method="POST" action="{{ route('slides.destroy', $deck) }}" onsubmit="return confirm('Xóa bộ slide này? Link sẽ không dùng được nữa.')">
                        @csrf
                        @method('DELETE')
                        <x-icon-button type="submit" icon="trash-2" label="Xóa bộ slide" size="sm" />
                    </form>
                </div>
            </div>
        @empty
            <p class="type-body text-fg-muted">Bạn chưa tạo bộ slide nào.</p>
        @endforelse
    </section>

    <x-slot:footer>
        <x-site-footer />
    </x-slot:footer>

    <x-slot:scripts>
        @vite('resources/js/slide-form.js')
    </x-slot:scripts>
</x-layouts.app>
