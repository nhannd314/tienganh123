<x-layouts.app title="Tạo word scene" noindex main-class="flex flex-col gap-6 pt-6 pb-10">
    <x-slot:header>
        <x-site-header />
    </x-slot:header>

    <section class="flex max-w-2xl flex-col gap-5 rounded-lg bg-surface-card p-6 shadow-card">
        <h1 class="type-h1 text-fg-strong">Tạo word scene</h1>

        <form method="POST" action="{{ route('admin.word-scenes.store') }}" enctype="multipart/form-data" class="flex flex-col gap-5">
            @csrf

            <div class="grid gap-1">
                <x-text-input name="title" label="Tiêu đề" :value="old('title')" required maxlength="255" />
                @error('title')
                    <p class="type-body-sm text-danger-text">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid gap-1">
                <label class="grid gap-2">
                    <span class="type-label text-fg-body">Ảnh</span>
                    <input type="file" name="image" accept="image/*" required class="type-body text-fg-body">
                </label>
                @error('image')
                    <p class="type-body-sm text-danger-text">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex gap-3">
                <x-button type="submit">Tạo và vẽ vùng cho các từ</x-button>
                <x-button :href="route('filament.admin.resources.word-scenes.index')" variant="outline">Quay lại</x-button>
            </div>
        </form>
    </section>
</x-layouts.app>
