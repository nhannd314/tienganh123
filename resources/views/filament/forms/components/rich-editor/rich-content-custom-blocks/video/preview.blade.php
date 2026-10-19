@if ($url)
    <video controls preload="none" src="{{ $url }}" style="width: 100%; aspect-ratio: 16 / 9; background: #000;"></video>
@else
    <p>No video file.</p>
@endif
