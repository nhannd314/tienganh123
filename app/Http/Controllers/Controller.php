<?php

namespace App\Http\Controllers;

abstract class Controller
{
    /**
     * Top 10 learners of a course.
     *
     * Sample data from the "Chi tiet khoa hoc" and "Chi tiet bai hoc" designs until learner scores are recorded.
     *
     * @return list<array{name: string, points: int, progress: int}>
     */
    protected function sampleTopLearners(): array
    {
        return [
            ['name' => 'Nguyễn Minh Anh', 'points' => 980, 'progress' => 100],
            ['name' => 'Trần Gia Bảo', 'points' => 945, 'progress' => 100],
            ['name' => 'Lê Khánh Linh', 'points' => 910, 'progress' => 94],
            ['name' => 'Phạm Đức Huy', 'points' => 875, 'progress' => 88],
            ['name' => 'Võ Ngọc Hân', 'points' => 840, 'progress' => 81],
            ['name' => 'Đặng Quốc Khánh', 'points' => 812, 'progress' => 76],
            ['name' => 'Bùi Thảo Vy', 'points' => 790, 'progress' => 72],
            ['name' => 'Hoàng Nhật Minh', 'points' => 765, 'progress' => 69],
            ['name' => 'Ngô Bảo Châu', 'points' => 730, 'progress' => 63],
            ['name' => 'Đỗ Tuấn Kiệt', 'points' => 705, 'progress' => 58],
        ];
    }
}
