<?php

namespace App\Http\Middleware;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Unit;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

/**
 * Lets a request through to a lesson route only when the visitor may learn that lesson (see LessonPolicy::learn).
 *
 * A guest is sent to the login page and comes back afterwards; a signed-in user without access sees why the lesson is locked.
 */
class EnsureLessonAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Course $course */
        $course = $request->route('course');
        /** @var Unit $unit */
        $unit = $request->route('unit');
        /** @var Lesson $lesson */
        $lesson = $request->route('lesson');

        abort_unless($course->is_published && $unit->is_published && $lesson->is_published, 404);

        if (Gate::allows('learn', $lesson)) {
            return $next($request);
        }

        if ($request->user() === null) {
            return $request->isMethodSafe() ? redirect()->guest(route('login')) : abort(401);
        }

        return response()->view('lessons.locked', ['course' => $course, 'unit' => $unit, 'lesson' => $lesson], 403);
    }
}
