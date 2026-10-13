<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    private const LATEST_COURSES = 20;

    /**
     * Show the home page with the latest published courses and the weekly leaderboard.
     */
    public function __invoke(): View
    {
        $courses = Course::query()
            ->published()
            ->latest()
            ->latest('id')
            ->limit(self::LATEST_COURSES)
            ->get();

        return view('home', [
            'courses' => $courses,
            'leaderboard' => $this->leaderboard(),
        ]);
    }

    /**
     * Top 10 learners of the week.
     *
     * Sample data from the "Trang chu" design until learner points are recorded.
     *
     * @return list<array{name: string, points: int}>
     */
    private function leaderboard(): array
    {
        return [
            ['name' => 'Nguyễn Minh Anh', 'points' => 1280],
            ['name' => 'Trần Gia Bảo', 'points' => 1215],
            ['name' => 'Lê Khánh Linh', 'points' => 1190],
            ['name' => 'Phạm Đức Huy', 'points' => 1105],
            ['name' => 'Hoàng Thảo Vy', 'points' => 1060],
            ['name' => 'Vũ Quang Minh', 'points' => 1010],
            ['name' => 'Đặng Ngọc Hân', 'points' => 985],
            ['name' => 'Bùi Tuấn Kiệt', 'points' => 940],
            ['name' => 'Đỗ Phương Nhi', 'points' => 915],
            ['name' => 'Ngô Hải Đăng', 'points' => 890],
        ];
    }
}
