<?php

use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonScore;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lists the learners who scored in the lesson, paginated', function () {
    $course = Course::factory()->create(['is_published' => true]);
    $unit = Unit::factory()->for($course)->create();
    $lesson = Lesson::factory()->for($unit)->create(['is_published' => true]);
    LessonScore::factory()->count(25)->for($lesson)->create();

    $this->get(route('lessons.learners', [$course, $unit, $lesson]))
        ->assertOk()
        ->assertSee('Thành tích học')
        ->assertSee('Số câu đúng');

    $this->get(route('lessons.learners', [$course, $unit, $lesson]).'?page=2')
        ->assertOk()
        ->assertSee('21');
});

it('shows the button to the details in the lesson sidebar', function () {
    $course = Course::factory()->create(['is_published' => true]);
    $unit = Unit::factory()->for($course)->create();
    $lesson = Lesson::factory()->for($unit)->create(['is_published' => true]);

    $this->get(route('lessons.show', [$course, $unit, $lesson]))
        ->assertOk()
        ->assertSee(route('lessons.learners', [$course, $unit, $lesson]), false);
});
