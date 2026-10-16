@if ($embedUrl)
    <div class="aspect-video w-full overflow-hidden rounded-md">
        <iframe src="{{ $embedUrl }}" title="YouTube video" class="size-full" loading="lazy" allow="accelerometer; encrypted-media; picture-in-picture; fullscreen" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe>
    </div>
@endif
