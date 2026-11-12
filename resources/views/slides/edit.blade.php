{{-- The members-only page to edit a slideshow the member made: title, slides (replace an image, change the text, remove, add). The short link stays. --}}
<x-layouts.app title="Sửa slide" main-class="flex flex-col gap-6 pt-6 pb-10" :noindex="true">
    <x-slot:header>
        <x-site-header />
    </x-slot:header>

    <x-breadcrumbs :items="[
        ['label' => 'Trang chủ', 'href' => route('home')],
        ['label' => 'Slide', 'href' => route('slides.index')],
        ['label' => 'Sửa slide'],
    ]" class="mx-auto w-full max-w-240" />

    <section class="mx-auto flex w-full max-w-240 flex-col gap-6 rounded-lg bg-surface-card p-6 shadow-card sm:p-8">
        <div class="flex flex-col gap-2">
            <h1 class="type-h1 text-balance text-fg-strong">Sửa slide</h1>
            <p class="type-body text-fg-muted">Link trình chiếu giữ nguyên: <a href="{{ $deck->shareUrl() }}" class="break-all text-fg-link underline">{{ $deck->shareUrl() }}</a></p>
        </div>

        @if ($errors->any())
            <x-alert tone="danger" title="Chưa lưu được thay đổi">
                <ul class="list-disc pl-5">
                    @foreach (collect($errors->all())->unique() as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            </x-alert>
        @endif

        <form method="POST" action="{{ route('slides.update', $deck) }}" enctype="multipart/form-data" data-slide-form data-max-slides="{{ $maxSlides }}" data-next-index="{{ $deck->slides->count() }}" class="flex flex-col gap-6">
            @csrf
            @method('PUT')

            <x-text-input label="Tiêu đề" name="title" size="md" maxlength="120" required :value="old('title', $deck->title)" placeholder="Ví dụ: Từ vựng chủ đề gia đình" />

            <div data-slide-list class="flex flex-col gap-4">
                @foreach ($deck->slides as $position => $slide)
                    @include('slides.row', ['index' => $position, 'slide' => $slide])
                @endforeach
            </div>

            <template data-slide-template>
                @include('slides.row', ['index' => '__INDEX__'])
            </template>

            <div class="flex flex-wrap items-center justify-between gap-4">
                <x-button variant="outline" icon-left="plus" data-slide-add>Thêm slide</x-button>
                <div class="flex items-center gap-2">
                    <x-button variant="ghost" :href="route('slides.index')">Hủy</x-button>
                    <x-button type="submit" size="lg" icon-left="check">Lưu thay đổi</x-button>
                </div>
            </div>
        </form>
    </section>

    <x-slot:footer>
        <x-site-footer />
    </x-slot:footer>

    <x-slot:scripts>
        @vite('resources/js/slide-form.js')
    </x-slot:scripts>
</x-layouts.app>
