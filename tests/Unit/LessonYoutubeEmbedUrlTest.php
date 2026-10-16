<?php

use App\Models\Lesson;

it('turns YouTube links into an embed url', function (string $url) {
    expect(Lesson::youtubeEmbedUrl($url))->toBe('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ');
})->with([
    'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
    'https://www.youtube.com/watch?list=PL1&v=dQw4w9WgXcQ&t=10s',
    'https://youtu.be/dQw4w9WgXcQ?si=abc',
    'https://www.youtube.com/shorts/dQw4w9WgXcQ',
    'https://www.youtube.com/embed/dQw4w9WgXcQ',
]);

it('rejects links that are not YouTube videos', function (?string $url) {
    expect(Lesson::youtubeEmbedUrl($url))->toBeNull();
})->with([null, '', 'https://example.com/watch?v=dQw4w9WgXcQ', 'https://www.youtube.com/watch?v=short', 'javascript:alert(1)']);
