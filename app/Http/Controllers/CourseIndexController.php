<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Contracts\View\View;

class CourseIndexController extends Controller
{
    private const COURSES_PER_PAGE = 12;

    /**
     * Show every published course, paginated.
     */
    public function __invoke(): View
    {
        $courses = Course::query()
            ->published()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate(self::COURSES_PER_PAGE);

        return view('courses.index', ['courses' => $courses]);
    }
}
