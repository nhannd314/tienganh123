<?php

use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonReview;
use App\Models\Unit;
use App\Models\User;
use App\Models\Word;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows a published course with its description and breadcrumbs', function () {
    $course = Course::factory()->published()->create([
        'title' => 'Tiếng Anh lớp 3',
        'description' => 'Học các mẫu câu giao tiếp đơn giản.',
    ]);

    $this->get(route('courses.show', $course))
        ->assertOk()
        ->assertSee('Tiếng Anh lớp 3')
        ->assertSee('Học các mẫu câu giao tiếp đơn giản.')
        ->assertSeeInOrder(['Trang chủ', 'Khóa học', 'Tiếng Anh lớp 3'])
        ->assertSee('Top 10 khóa học này');
});

it('shows the course thumbnail as the cover of the hero', function () {
    $course = Course::factory()->published()->create(['thumbnail' => 'courses/lop-3.png']);

    $this->get(route('courses.show', $course))
        ->assertSee('src="'.$course->thumbnail_url.'"', false);
});

it('shows the level and the age groups of the course as tags above its title', function () {
    $course = Course::factory()->published()->create(['title' => 'Lớp 3', 'level' => 'Cơ bản', 'age_groups' => ['Tiểu học', 'THCS']]);

    $this->get(route('courses.show', $course))
        ->assertSeeInOrder(['Cơ bản', 'Tiểu học', 'THCS', '<h1', 'Lớp 3'], false);
});

it('returns not found for an unpublished course', function () {
    $course = Course::factory()->create();

    $this->get(route('courses.show', $course))->assertNotFound();
});

it('lists the lessons of a unit by sort order, then from the oldest to the newest', function () {
    $course = Course::factory()->published()->create();
    $unit = Unit::factory()->for($course)->create();

    Lesson::factory()->published()->for($unit)->create(['title' => 'Lesson Last', 'sort_order' => 2, 'created_at' => now()->subWeek()]);
    Lesson::factory()->published()->for($unit)->create(['title' => 'Lesson Newer', 'sort_order' => 1, 'created_at' => now()]);
    Lesson::factory()->published()->for($unit)->create(['title' => 'Lesson Older', 'sort_order' => 1, 'created_at' => now()->subDay()]);

    $this->get(route('courses.show', $course))
        ->assertSeeInOrder(['Lesson Older', 'Lesson Newer', 'Lesson Last']);
});

it('numbers lessons continuously across units and skips unpublished ones', function () {
    $course = Course::factory()->published()->create();
    $firstUnit = Unit::factory()->for($course)->create(['title' => 'Hello!', 'sort_order' => 1]);
    $secondUnit = Unit::factory()->for($course)->create(['title' => 'My family', 'sort_order' => 2]);

    Lesson::factory()->published()->for($firstUnit)->create(['title' => 'Lesson A', 'sort_order' => 1]);
    Lesson::factory()->for($firstUnit)->create(['title' => 'Hidden Lesson', 'sort_order' => 2]);
    Lesson::factory()->published()->for($secondUnit)->create(['title' => 'Lesson B', 'sort_order' => 1]);

    $this->get(route('courses.show', $course))
        ->assertSeeInOrder(['Hello!', 'Lesson A', 'My family', 'Lesson B'])
        ->assertDontSee('Hidden Lesson')
        ->assertSee('2 bài')
        ->assertSee('2 unit');
});

it('shows the unit count, lesson count and level typed in for the course instead of the counted ones', function () {
    $course = Course::factory()->published()->create(['unit_count' => 12, 'lesson_count' => 60, 'level' => 'Trung cấp']);
    $unit = Unit::factory()->for($course)->create();
    Lesson::factory()->published()->for($unit)->create();

    $this->get(route('courses.show', $course))
        ->assertSee('12 unit')
        ->assertSee('60 bài')
        ->assertSeeInOrder(['Trình độ', 'Trung cấp'])
        ->assertDontSee('1 unit');
});

it('counts the published units and lessons when the course has no typed counts, and hides an empty level', function () {
    $course = Course::factory()->published()->create(['unit_count' => null, 'lesson_count' => null, 'level' => null]);
    $unit = Unit::factory()->for($course)->create();
    Lesson::factory()->published()->count(3)->for($unit)->create();

    $this->get(route('courses.show', $course))
        ->assertSee('1 unit')
        ->assertSee('3 bài')
        ->assertDontSee('Trình độ');
});

it('no longer shows a duration for the course', function () {
    $this->get(route('courses.show', Course::factory()->published()->create()))
        ->assertDontSee('Khoảng')
        ->assertDontSee('giờ');
});

it('shows an empty state when the course has no lessons', function () {
    $course = Course::factory()->published()->create();

    $this->get(route('courses.show', $course))->assertSee('Chưa có bài học nào');
});

it('shows a guest a free course without asking to register', function () {
    $course = Course::factory()->published()->create(['monthly_price' => 0, 'title' => 'Lớp 1']);
    $unit = Unit::factory()->for($course)->create();
    $lesson = Lesson::factory()->published()->for($unit)->create();

    $response = $this->get(route('courses.show', $course));

    $response
        ->assertSee('Thông tin khóa học')
        ->assertSee(route('lessons.show', [$course, $unit, $lesson]), false)
        ->assertDontSee('Đăng ký khóa học');
});

it('shows the enroll button of a paid course without its price', function () {
    $course = Course::factory()->published()->priced(100000)->onSale(200000)->create();

    $this->get(route('courses.show', $course))
        ->assertSeeInOrder(['Đăng ký khóa học', '<h3', 'Học tiếng Anh vui mà nhớ lâu</h3>', 'Hoàn tiền 100%'], false)
        ->assertDontSee('100.000₫')
        ->assertDontSee('200.000₫')
        ->assertDontSee('Chỉ với');
});

it('lists the age groups of the course under its level in the course info', function () {
    $course = Course::factory()->published()->create(['level' => 'Sơ cấp', 'age_groups' => ['Mầm non', 'Tiểu học']]);

    $this->get(route('courses.show', $course))
        ->assertSeeInOrder(['Thông tin khóa học', 'Trình độ', 'Sơ cấp', 'Độ tuổi', 'Mầm non, Tiểu học']);
});

it('leaves the age groups out of the course info when the course has none', function () {
    $course = Course::factory()->published()->create(['age_groups' => null]);

    $this->get(route('courses.show', $course))->assertDontSee('Độ tuổi');
});

it('promises no refund on a free course', function () {
    $course = Course::factory()->published()->create(['monthly_price' => 0]);

    $this->get(route('courses.show', $course))
        ->assertSee('Hoàn toàn miễn phí')
        ->assertDontSee('Hoàn tiền 100%');
});

it('opens only the free lessons of a paid course and sends the rest to enroll', function () {
    $course = Course::factory()->priced()->published()->create();
    $unit = Unit::factory()->for($course)->create();
    $free = Lesson::factory()->published()->free()->for($unit)->create(['sort_order' => 1]);
    $locked = Lesson::factory()->published()->for($unit)->create(['sort_order' => 2]);

    $this->get(route('courses.show', $course))
        ->assertSee(route('lessons.show', [$course, $unit, $free]), false)
        ->assertDontSee(route('lessons.show', [$course, $unit, $locked]), false)
        ->assertSee('href="'.route('courses.enroll', $course).'"', false);
});

it('counts the new words of each lesson', function () {
    $course = Course::factory()->published()->create();
    $unit = Unit::factory()->for($course)->create();
    $lesson = Lesson::factory()->published()->for($unit)->create();

    $words = Word::factory()->count(3)->create();
    attachWords($lesson, $words->pluck('id'));

    $this->get(route('courses.show', $course))
        ->assertSee('3 từ mới')
        ->assertSeeInOrder(['3', 'Từ vựng']);
});

it('marks a finished lesson as done and a locked lesson with a lock', function () {
    $course = Course::factory()->priced()->published()->create();
    $unit = Unit::factory()->for($course)->create();
    $finished = Lesson::factory()->published()->free()->for($unit)->create(['title' => 'Finished lesson', 'sort_order' => 1]);
    Lesson::factory()->published()->for($unit)->create(['title' => 'Locked lesson', 'sort_order' => 2]);
    $user = User::factory()->create();
    LessonReview::factory()->for($finished)->for($user)->count(Lesson::REVIEW_PARTS)->sequence(fn ($sequence) => ['step' => $sequence->index + 1])->create(['total' => 10, 'correct' => 10]);

    expect($finished->reviewProgress($user)['percent'])->toBe(100);

    $this->actingAs($user)->get(route('courses.show', $course))
        ->assertSeeInOrder(['Finished lesson', 'Hoàn thành', 'icon-check', 'Locked lesson', 'Chưa học', 'icon-lock'], false);
});

it('links each course on the home page to its detail page', function () {
    $course = Course::factory()->published()->create();

    $this->get(route('home'))->assertSee(route('courses.show', $course), false);
});

it('shows the share of parts passed from 80%, as the lesson sidebar does, only on lessons already studied', function () {
    $course = Course::factory()->published()->create(['monthly_price' => 0]);
    $unit = Unit::factory()->for($course)->create();
    $reviewed = Lesson::factory()->published()->for($unit)->create(['title' => 'Reviewed lesson', 'sort_order' => 1]);
    Lesson::factory()->published()->for($unit)->create(['title' => 'New lesson', 'sort_order' => 2]);
    $user = User::factory()->create();
    LessonReview::factory()->for($reviewed)->for($user)->count(5)->sequence(
        ['step' => 1, 'correct' => 9],
        ['step' => 1, 'correct' => 5],
        ['step' => 2, 'correct' => 10],
        ['step' => 3, 'correct' => 9],
        ['step' => 4, 'correct' => 7],
    )->create(['total' => 10]);
    LessonReview::factory()->for($reviewed)->for(User::factory())->count(6)->sequence(fn ($sequence) => ['step' => $sequence->index + 1])->create(['total' => 10, 'correct' => 10]);

    expect($reviewed->reviewProgress($user)['percent'])->toBe(50);

    $response = $this->actingAs($user)->get(route('courses.show', $course))
        ->assertSeeInOrder(['Reviewed lesson', 'Đang học', 'aria-valuenow="50"', 'New lesson', 'Chưa học'], false);

    expect(substr_count($response->getContent(), 'role="progressbar"'))->toBe(1);

    auth()->logout();

    $this->get(route('courses.show', $course))
        ->assertSee('Reviewed lesson')
        ->assertDontSee('role="progressbar"', false);
});

it('ranks the learners of a course by the total score of its lessons', function () {
    $course = Course::factory()->published()->create();
    $unit = Unit::factory()->for($course)->create();
    [$first, $second] = Lesson::factory()->published()->for($unit)->count(2)->create()->all();
    $other = Lesson::factory()->published()->create();

    $top = User::factory()->create(['name' => 'Học Sinh Giỏi']);
    $runnerUp = User::factory()->create(['name' => 'Học Sinh Khá']);

    $first->scores()->create(['user_id' => $top->id, 'score' => 40, 'percent' => 100]);
    $second->scores()->create(['user_id' => $top->id, 'score' => 30, 'percent' => 50]);
    $first->scores()->create(['user_id' => $runnerUp->id, 'score' => 60, 'percent' => 100]);
    $other->scores()->create(['user_id' => $runnerUp->id, 'score' => 500, 'percent' => 100]);

    expect($course->topLearners()->all())->toBe([
        ['name' => 'Học Sinh Giỏi', 'points' => 70, 'progress' => 75],
        ['name' => 'Học Sinh Khá', 'points' => 60, 'progress' => 50],
    ]);

    $this->get(route('courses.show', $course))->assertSeeInOrder(['Top 10 khóa học này', 'Học Sinh Giỏi', '70', 'Học Sinh Khá', '60']);
});

it('shows the description of a unit under its title', function () {
    $course = Course::factory()->published()->create();
    $unit = Unit::factory()->for($course)->create(['title' => 'Hello!', 'description' => 'Greet new friends']);
    Lesson::factory()->published()->for($unit)->create(['title' => 'Lesson A']);

    $this->get(route('courses.show', $course))
        ->assertSeeInOrder(['Hello!', 'Greet new friends', 'Lesson A']);
});
