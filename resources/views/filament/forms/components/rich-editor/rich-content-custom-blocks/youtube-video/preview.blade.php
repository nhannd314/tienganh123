@if ($embedUrl)
    <div style="aspect-ratio: 16 / 9; width: 100%;">
        <iframe src="{{ $embedUrl }}" title="YouTube video" style="width: 100%; height: 100%; border: 0;" allowfullscreen></iframe>
    </div>
@else
    <p>Invalid YouTube link.</p>
@endif
