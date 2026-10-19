@if ($url)
    <audio controls preload="none" src="{{ $url }}" style="width: 100%;"></audio>
@else
    <p>No audio file.</p>
@endif
