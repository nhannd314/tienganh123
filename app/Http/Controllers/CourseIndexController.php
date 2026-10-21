<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Contracts\View\View;

class CourseIndexController extends Controller
{
    private const COURSES_PER_PAGE = 12;

    /**
     * Show every published course by sort order then from the newest, paginated.
     */
    public function __invoke(): View
    {
        $courses = Course::query()
            ->published()
            ->orderBy('sort_order')
            ->latest()
            ->latest('id')
            ->paginate(self::COURSES_PER_PAGE);

        return view('courses.index', ['courses' => $courses]);
    }
}
