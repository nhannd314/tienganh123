<?php

namespace App\Http\Middleware;

use App\Http\Controllers\LessonGameController;
use App\Models\Lesson;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tells the layout what a signed-in user is doing on the page (learning a lesson, playing a game, a review, flashcards, an
 * exam... or just browsing the site), as `learningActivity`. The layout puts it encrypted in a meta tag, which the page
 * sends when it joins the `learning` presence channel (see routes/channels.php), so the "Đang học bài" list shows it and
 * the user cannot change the text.
 */
class ShareLearningActivity
{
    /**
     * What a page that is about nothing in particular shows.
     */
    public const BROWSING = 'Đang dạo quanh website';

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Shared on every request, so a long-lived worker never keeps the activity of a previous user.
        View::share('learningActivity', $request->user() !== null && $request->isMethod('GET') ? $this->activity($request) : null);

        return $next($request);
    }

    /**
     * What the page of the request is about.
     */
    private function activity(Request $request): string
    {
        $lesson = $request->route('lesson');
        $lessonTitle = $lesson instanceof Lesson ? $lesson->title : null;

        return match ($request->route()?->getName()) {
            'lessons.show' => 'Đang học '.$lessonTitle,
            'lessons.games', 'lessons.games.show' => 'Đang chơi '.(LessonGameController::GAMES[$request->route('game')]['title'] ?? 'trò chơi'),
            'lessons.review.show' => 'Đang luyện tập '.$lessonTitle,
            'vocabularies.show' => 'Đang học từ vựng',
            'vocabularies.review' => 'Đang ôn tập từ vựng',
            'flashcards.show' => 'Đang luyện Flashcard',
            'sentences.show' => 'Đang học câu mẫu',
            'sentences.review' => 'Đang ôn tập câu mẫu',
            'exams.take' => 'Đang làm bài kiểm tra',
            'home' => 'Đang ở trang chủ',
            'courses.index', 'courses.show' => 'Đang xem khóa học',
            'posts.index', 'posts.show' => 'Đang đọc blog',
            default => self::BROWSING,
        };
    }
}
