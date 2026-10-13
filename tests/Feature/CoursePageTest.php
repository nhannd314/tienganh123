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

it('does not show the course thumbnail on the detail page', function () {
    $course = Course::factory()->published()->create(['thumbnail' => 'courses/lop-3.png']);

    $this->get(route('courses.show', $course))
        ->assertDontSee($course->thumbnail_url, false)
        ->assertDontSee('teacher-kids.png');
});

it('returns not found for an unpublished course', function () {
    $course = Course::factory()->create();

    $this->get(route('courses.show', $course))->assertNotFound();
});

it('numbers lessons continuously across units and skips unpublished ones', function () {
    $course = Course::factory()->published()->create();
    $firstUnit = Unit::factory()->published()->for($course)->create(['title' => 'Hello!', 'sort_order' => 1]);
    $secondUnit = Unit::factory()->published()->for($course)->create(['title' => 'My family', 'sort_order' => 2]);

    Lesson::factory()->published()->for($firstUnit)->create(['title' => 'Lesson A', 'sort_order' => 1]);
    Lesson::factory()->for($firstUnit)->create(['title' => 'Hidden Lesson', 'sort_order' => 2]);
    Lesson::factory()->published()->for($secondUnit)->create(['title' => 'Lesson B', 'sort_order' => 1]);

    $this->get(route('courses.show', $course))
        ->assertSeeInOrder(['Unit 1: Hello!', 'Lesson A', 'Unit 2: My family', 'Lesson B'])
        ->assertDontSee('Hidden Lesson')
        ->assertSee('2 bài học')
        ->assertSee('2 unit');
});

it('shows the unit count, lesson count and level typed in for the course instead of the counted ones', function () {
    $course = Course::factory()->published()->create(['unit_count' => 12, 'lesson_count' => 60, 'level' => 'Trung cấp']);
    $unit = Unit::factory()->published()->for($course)->create();
    Lesson::factory()->published()->for($unit)->create();

    $this->get(route('courses.show', $course))
        ->assertSee('12 unit')
        ->assertSee('60 bài học')
        ->assertSee('Trình độ: Trung cấp')
        ->assertDontSee('1 bài học');
});

it('counts the published units and lessons when the course has no typed counts, and hides an empty level', function () {
    $course = Course::factory()->published()->create(['unit_count' => null, 'lesson_count' => null, 'level' => null]);
    $unit = Unit::factory()->published()->for($course)->create();
    Lesson::factory()->published()->count(3)->for($unit)->create();

    $this->get(route('courses.show', $course))
        ->assertSee('1 unit')
        ->assertSee('3 bài học')
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

it('lets a guest start a free course at its first lesson without registering', function () {
    $course = Course::factory()->published()->create(['monthly_price' => 0]);
    $unit = Unit::factory()->published()->for($course)->create();
    $lesson = Lesson::factory()->published()->for($unit)->create();

    $this->get(route('courses.show', $course))
        ->assertSee('Bắt đầu học')
        ->assertSee(route('lessons.show', [$course, $unit, $lesson]), false)
        ->assertDontSee('Đăng ký khóa học');
});

it('shows the price of a paid course above the enroll button', function () {
    $course = Course::factory()->published()->priced(50000)->create();

    $this->get(route('courses.show', $course))
        ->assertSeeInOrder(['50.000₫', 'Đăng ký khóa học', 'Chỉ với', '50.000₫/tháng', 'Hoàn tiền 100%']);
});

it('shows the price as a sale off a struck-through regular price', function (int $regular, string $original, int $percent) {
    $course = Course::factory()->published()->priced(100000)->onSale($regular)->create();

    $this->get(route('courses.show', $course))
        ->assertSeeInOrder(["<del class=\"type-body-lg text-fg-muted\">{$original}</del>", "-{$percent}%", '100.000₫', 'Đăng ký khóa học'], false);
})->with([
    'half off' => [200000, '200.000₫', 50],
    'rounded percent' => [143000, '143.000₫', 30],
]);

it('shows the price alone when there is no regular price above it', function (?int $regular) {
    $course = Course::factory()->published()->priced(100000)->create(['regular_price' => $regular]);

    $this->get(route('courses.show', $course))->assertSee('100.000₫')->assertDontSee('<del', false);
})->with([
    'none' => [null],
    'equal to the price' => [100000],
    'below the price' => [80000],
]);

it('promises no refund on a free course', function () {
    $course = Course::factory()->published()->create(['monthly_price' => 0]);

    $this->get(route('courses.show', $course))
        ->assertSee('Hoàn toàn miễn phí')
        ->assertDontSee('Hoàn tiền 100%');
});

it('offers a trial only on free lessons of a paid course and locks the rest', function () {
    $course = Course::factory()->priced()->published()->create();
    $unit = Unit::factory()->published()->for($course)->create();
    Lesson::factory()->published()->free()->for($unit)->create(['sort_order' => 1]);
    Lesson::factory()->published()->for($unit)->create(['sort_order' => 2]);

    $response = $this->get(route('courses.show', $course))->assertSee('Học thử');

    expect(substr_count($response->getContent(), 'Học thử'))->toBe(1)
        ->and(substr_count($response->getContent(), 'aria-label="Đã khóa"'))->toBe(1);
});

it('counts the new words of each lesson', function () {
    $course = Course::factory()->published()->create();
    $unit = Unit::factory()->published()->for($course)->create();
    $lesson = Lesson::factory()->published()->for($unit)->create();

    $words = Word::factory()->count(3)->create();
    attachWords($lesson, $words->pluck('id'));

    $this->get(route('courses.show', $course))->assertSee('3 từ mới');
});

it('links each course on the home page to its detail page', function () {
    $course = Course::factory()->published()->create();

    $this->get(route('home'))->assertSee(route('courses.show', $course), false);
});

it('shows on each lesson the share of parts passed from 80%, as the lesson sidebar does, and 0% to a guest', function () {
    $course = Course::factory()->published()->create(['monthly_price' => 0]);
    $unit = Unit::factory()->published()->for($course)->create();
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

    $this->actingAs($user)->get(route('courses.show', $course))
        ->assertSeeInOrder(['Reviewed lesson', '50%', 'New lesson', '0%']);

    auth()->logout();

    $this->get(route('courses.show', $course))
        ->assertSeeInOrder(['Reviewed lesson', '0%', 'New lesson', '0%'])
        ->assertDontSee('50%');
});

it('ranks the learners of a course by the total score of its lessons', function () {
    $course = Course::factory()->published()->create();
    $unit = Unit::factory()->published()->for($course)->create();
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
