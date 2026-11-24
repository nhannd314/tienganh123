<?php

namespace App\Http\Middleware;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Unit;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Lets a request through to the games of a lesson only when the user finished at least Lesson::GAMES_UNLOCK_PERCENT of its review
 * (an admin always may). Run it after EnsureLessonAccess.
 *
 * A guest is sent to the login page; a user who is not there yet sees how far they are.
 */
class EnsureLessonGamesUnlocked
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return $request->isMethodSafe() ? redirect()->guest(route('login')) : abort(401);
        }

        /** @var Course $course */
        $course = $request->route('course');
        /** @var Unit $unit */
        $unit = $request->route('unit');
        /** @var Lesson $lesson */
        $lesson = $request->route('lesson');

        if ($user->isAdmin()) {
            return $next($request);
        }

        $percent = $lesson->reviewProgress($user)['percent'];

        if ($percent >= Lesson::GAMES_UNLOCK_PERCENT) {
            return $next($request);
        }

        abort_unless($request->isMethodSafe(), 403);

        return response()->view('lessons.games-locked', ['course' => $course, 'unit' => $unit, 'lesson' => $lesson, 'percent' => $percent], 403);
    }
}
