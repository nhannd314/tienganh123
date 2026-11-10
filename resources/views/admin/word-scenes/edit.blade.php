@php
    $storeItemUrl = route('admin.word-scenes.items.store', $wordScene);
@endphp

<x-layouts.app :title="$wordScene->title" noindex main-class="flex flex-col gap-6 pt-6 pb-10">
    <x-slot:header>
        <x-site-header />
    </x-slot:header>

    <section class="flex flex-col gap-5 rounded-lg bg-surface-card p-6 shadow-card">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h1 class="type-h1 text-fg-strong">{{ $wordScene->title }}</h1>
            <x-button :href="route('filament.admin.resources.word-scenes.index')" variant="outline" size="sm">Danh sách word scene</x-button>
        </div>

        @if (session('status'))
            <x-alert tone="success">{{ session('status') }}</x-alert>
        @endif

        <form method="POST" action="{{ route('admin.word-scenes.update', $wordScene) }}" enctype="multipart/form-data" class="flex flex-wrap items-end gap-4">
            @csrf
            @method('PUT')

            <div class="grid gap-1">
                <x-text-input name="title" label="Tiêu đề" :value="old('title', $wordScene->title)" required maxlength="255" size="md" />
                @error('title')
                    <p class="type-body-sm text-danger-text">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid gap-1">
                <label class="grid gap-2">
                    <span class="type-label text-fg-body">Thay ảnh (các vùng đã vẽ sẽ bị xóa)</span>
                    <input type="file" name="image" accept="image/*" class="type-body text-fg-body">
                </label>
                @error('image')
                    <p class="type-body-sm text-danger-text">{{ $message }}</p>
                @enderror
            </div>

            <x-button type="submit" size="md">Lưu</x-button>
        </form>
    </section>

    <section
        data-word-scene-editor
        data-csrf="{{ csrf_token() }}"
        data-store-url="{{ $storeItemUrl }}"
        data-items="{{ \Illuminate\Support\Js::encode($items) }}"
        class="flex flex-col gap-5 rounded-lg bg-surface-card p-6 shadow-card"
    >
        <h2 class="type-h2 text-fg-strong">Vẽ vùng cho từng từ</h2>

        <p class="type-body text-fg-muted">
            Kéo chuột trên ảnh để vẽ hình chữ nhật bao quanh một vật thể, hoặc bấm lần lượt hai góc đối diện (Esc để bỏ). Sau khi vẽ, nhập từ của vật thể đó rồi nhấn Enter: từ được lưu ngay cùng vùng vừa vẽ. Vẽ lại một từ đã có sẽ thay vùng cũ của từ đó.
        </p>

        <div class="relative w-full max-w-240 leading-none">
            <div data-editor-surface class="relative cursor-crosshair touch-none select-none">
                <img src="{{ $wordScene->image_url }}" alt="{{ $wordScene->title }}" class="pointer-events-none block h-auto w-full rounded-lg">
                <div data-editor-boxes class="pointer-events-none absolute inset-0"></div>
                <div data-editor-draft hidden class="pointer-events-none absolute border-2 border-danger bg-danger/20"></div>
                <div data-editor-corner hidden class="pointer-events-none absolute -mt-1.5 -ml-1.5 size-3 rounded-full bg-danger"></div>
            </div>

            <form data-editor-prompt hidden class="absolute z-10 flex flex-wrap items-center gap-2 rounded-md border border-line-default bg-surface-card p-2 leading-normal shadow-card">
                <input
                    data-editor-word
                    type="text"
                    maxlength="255"
                    autocomplete="off"
                    lang="en"
                    placeholder="Nhập từ của vật thể này"
                    class="h-10 w-56 rounded-sm border border-line-default bg-surface-card px-3 text-fg-strong outline-none focus:shadow-focus"
                >
                <x-button type="submit" size="sm">Thêm</x-button>
                <x-button type="button" size="sm" variant="outline" data-editor-cancel>Hủy</x-button>
                <p data-editor-error hidden class="w-full type-body-sm text-danger-text"></p>
            </form>
        </div>

        <div class="flex flex-col gap-3">
            <h3 class="type-h3 text-fg-strong">Các từ trong ảnh</h3>
            <p data-editor-empty class="type-body text-fg-muted" hidden>Chưa có từ nào.</p>
            <ul data-editor-list class="flex flex-wrap gap-2"></ul>
        </div>
    </section>

    <x-slot:scripts>
        @vite('resources/js/word-scene-editor.js')
    </x-slot:scripts>
    <x-slot:footer>
        <x-site-footer />
    </x-slot:footer>
</x-layouts.app>
