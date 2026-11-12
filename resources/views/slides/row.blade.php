{{-- One slide row of the slide form. $index is the array key of its fields; $slide is set when editing a saved slide. --}}
@php($slide ??= null)
<div data-slide-row class="flex flex-wrap items-start gap-4 rounded-lg border border-line-subtle p-4">
    <span data-slide-number class="flex size-10 flex-none items-center justify-center rounded-full bg-primary type-label-lg text-on-primary"></span>

    @if ($slide)
        <input type="hidden" name="slides[{{ $index }}][id]" value="{{ $slide->id }}">
    @endif

    <div class="flex min-w-0 flex-[1_1_240px] flex-col gap-3">
        <label class="flex flex-col gap-2">
            <span class="type-label text-fg-body">Ảnh{{ $slide ? ' (chọn ảnh mới nếu muốn thay)' : '' }}</span>
            <input type="file" data-slide-image name="slides[{{ $index }}][image]" accept="image/jpeg,image/png,image/webp" @required(! $slide) class="type-body-sm text-fg-body file:mr-3 file:cursor-pointer file:rounded-sm file:border-0 file:bg-primary-bg file:px-4 file:py-2 file:type-label-sm file:text-primary-text">
        </label>
        <label class="flex flex-col gap-2">
            <span class="type-label text-fg-body">Nội dung</span>
            <textarea name="slides[{{ $index }}][text]" rows="3" maxlength="1000" class="w-full rounded-md border border-line-default px-4 py-3 text-fg-strong outline-none transition-interactive focus:shadow-focus">{{ $slide?->text }}</textarea>
        </label>
    </div>

    <img data-slide-preview alt="" @if ($slide) src="{{ $slide->image_url }}" @else hidden @endif class="h-32 w-48 flex-none rounded-md border border-line-default bg-surface-sunken object-contain">

    <x-icon-button icon="trash-2" label="Xóa slide" size="sm" data-slide-remove />
</div>
