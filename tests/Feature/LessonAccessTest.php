<?php

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Subscription;
use App\Models\Unit;
use App\Models\User;
use App\Models\Word;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * A published lesson in a published course of the given monthly price (0 = free).
 *
 * @param  array<string, mixed>  $lessonAttributes
 */
function lessonOfCourse(int $monthlyPrice = 50000, array $lessonAttributes = []): Lesson
{
    $course = Course::factory()->published()->create(['monthly_price' => $monthlyPrice]);
    $unit = Unit::factory()->published()->for($course)->create();
    $lesson = Lesson::factory()->published()->for($unit)->create($lessonAttributes);
    attachWords($lesson, Word::factory()->create());

    return $lesson->fresh();
}

function lessonPage(Lesson $lesson): string
{
    return route('lessons.show', [$lesson->unit->course, $lesson->unit, $lesson]);
}

function lessonReview(Lesson $lesson): string
{
    return route('lessons.review.show', [$lesson->unit->course, $lesson->unit, $lesson, 1]);
}

it('lets a guest learn the lessons of a free course', function () {
    $lesson = lessonOfCourse(0);

    $this->get(lessonPage($lesson))->assertOk();
    $this->get(lessonReview($lesson))->assertOk();
});

it('lets a guest learn a free lesson of a paid course', function () {
    $lesson = lessonOfCourse(50000, ['is_free' => true]);

    $this->get(lessonPage($lesson))->assertOk();
    $this->get(lessonReview($lesson))->assertOk();
});

it('sends a guest to the login page for a lesson that is not free, and back afterwards', function () {
    $lesson = lessonOfCourse();

    $this->get(lessonPage($lesson))->assertRedirect(route('login'));
    expect(session('url.intended'))->toBe(lessonPage($lesson));

    $this->get(lessonReview($lesson))->assertRedirect(route('login'));
});

it('tells a signed-in user without a subscription why the lesson is locked', function () {
    $lesson = lessonOfCourse();

    $this->actingAs(User::factory()->create())->get(lessonPage($lesson))
        ->assertForbidden()
        ->assertSee('Bài học này chưa mở cho bạn')
        ->assertSee(route('courses.show', $lesson->unit->course), false);

    $this->get(lessonReview($lesson))->assertForbidden();
});

it('opens every lesson of the course to a user subscribed to it', function () {
    $lesson = lessonOfCourse();
    $user = User::factory()->create();
    Subscription::factory()->for($user)->create(['subscribable_type' => $lesson->unit->course->getMorphClass(), 'subscribable_id' => $lesson->unit->course_id]);

    $this->actingAs($user)->get(lessonPage($lesson))->assertOk();
    $this->get(lessonReview($lesson))->assertOk();
});

it('opens everything to an admin', function () {
    $this->actingAs(User::factory()->admin()->create())->get(lessonPage(lessonOfCourse()))->assertOk();
});

it('does not open a course through the subscription to another course, an expired or a cancelled one', function (string $case) {
    $lesson = lessonOfCourse();
    $user = User::factory()->create();

    match ($case) {
        'other course' => Subscription::factory()->for($user)->create(),
        'expired' => Subscription::factory()->for($user)->expired()->create(['subscribable_type' => $lesson->unit->course->getMorphClass(), 'subscribable_id' => $lesson->unit->course_id]),
        'cancelled' => Subscription::factory()->for($user)->cancelled()->create(['subscribable_type' => $lesson->unit->course->getMorphClass(), 'subscribable_id' => $lesson->unit->course_id]),
    };

    $this->actingAs($user)->get(lessonPage($lesson))->assertForbidden();
})->with(['other course', 'expired', 'cancelled']);

it('keeps a hidden lesson not found, even behind a paywall', function () {
    $lesson = lessonOfCourse();
    $lesson->update(['is_published' => false]);

    $this->get(lessonPage($lesson))->assertNotFound();
    $this->actingAs(User::factory()->create())->get(lessonPage($lesson))->assertNotFound();
});

it('does not save results of a lesson the user cannot learn', function () {
    $lesson = lessonOfCourse();

    $this->actingAs(User::factory()->create())
        ->postJson(route('lessons.review.store', [$lesson->unit->course, $lesson->unit, $lesson, 1]), [
            'duration' => 5,
            'answers' => [['word_id' => $lesson->words->first()->id, 'selected' => 'x']],
        ])
        ->assertForbidden();
});

it('only says results need a login in the sidebar of a guest, on the lesson page and on the review', function (string $page) {
    $lesson = lessonOfCourse(0);

    $this->get($page === 'lesson' ? lessonPage($lesson) : lessonReview($lesson))
        ->assertSee('Tiến độ học tập')
        ->assertSee('để lưu kết quả học tập.')
        ->assertSee('href="'.route('login').'" class="font-semibold">Đăng nhập</a>', false)
        ->assertDontSee('data-stat-text', false)
        ->assertDontSee('data-total-time', false);
})->with(['lesson', 'review']);

it('shows the same sidebar with the real progress to a signed-in user on the lesson page and on the review', function (string $page) {
    $lesson = lessonOfCourse(0);
    $user = User::factory()->create(['name' => 'Nguyễn Văn An']);
    $lesson->reviews()->create(['user_id' => $user->id, 'step' => 1, 'total' => 1, 'correct' => 1, 'duration_seconds' => 65, 'answers' => []]);
    $lesson->scores()->create(['user_id' => $user->id, 'score' => 100, 'percent' => 17]);

    $this->actingAs($user)->get($page === 'lesson' ? lessonPage($lesson) : lessonReview($lesson))
        ->assertSee('Tiến độ học tập')
        ->assertSee('Top 10 bài học này')
        ->assertDontSee('để lưu kết quả học tập.')
        ->assertSee('<span data-stat-text="done">1</span>/<span data-stat-total>6</span>', false)
        ->assertSee('data-total-time class="tabular-nums">01:05<', false)
        ->assertSee('aria-label="Hoàn thành 17%"', false)
        ->assertSee('<span data-score class="type-h1 text-accent-text">100</span>', false)
        ->assertDontSee('>NA<', false);
})->with(['lesson', 'review']);

it('opens the lesson of a free course from the course page for everyone, and shows a lock on a locked one', function () {
    $free = lessonOfCourse(0);
    $paid = lessonOfCourse();

    $this->get(route('courses.show', $free->unit->course))
        ->assertSee(route('lessons.show', [$free->unit->course, $free->unit, $free]), false)
        ->assertDontSee('aria-label="Đã khóa"', false)
        ->assertDontSee('Học ngay');

    $this->get(route('courses.show', $paid->unit->course))
        ->assertSee('aria-label="Đã khóa"', false)
        ->assertSee('href="'.route('courses.enroll', $paid->unit->course).'"', false)
        ->assertDontSee(lessonPage($paid), false);
});

it('shows a subscriber the lessons of the paid course as open on the course page', function () {
    $lesson = lessonOfCourse();
    $user = User::factory()->create();
    Subscription::factory()->for($user)->create(['subscribable_type' => $lesson->unit->course->getMorphClass(), 'subscribable_id' => $lesson->unit->course_id]);

    $this->actingAs($user)->get(route('courses.show', $lesson->unit->course))
        ->assertDontSee('aria-label="Đã khóa"', false)
        ->assertDontSee('Học thử');
});

it('works out the access of a user to a course from the subscriptions', function () {
    $course = Course::factory()->priced()->create();
    $user = User::factory()->create();

    expect($user->isSubscribedTo($course))->toBeFalse();

    Subscription::factory()->for($user)->create(['subscribable_type' => $course->getMorphClass(), 'subscribable_id' => $course->id]);
    expect($user->isSubscribedTo($course))->toBeTrue();
});

it('offers to enroll in the paid course beside a trial lesson and its review', function (string $page) {
    $lesson = lessonOfCourse(50000, ['is_free' => true]);
    $url = $page === 'lesson' ? lessonPage($lesson) : lessonReview($lesson);

    $this->get($url)
        ->assertOk()
        ->assertSee(route('courses.enroll', $lesson->unit->course), false)
        ->assertSee('Hoàn tiền 100%');
})->with(['lesson', 'review']);

it('offers no enrollment to a subscriber or on a free course', function (string $page) {
    $paid = lessonOfCourse(50000, ['is_free' => true]);
    $free = lessonOfCourse(0);
    $user = User::factory()->create();
    Subscription::factory()->for($user)->create([
        'subscribable_type' => $paid->unit->course->getMorphClass(),
        'subscribable_id' => $paid->unit->course->id,
    ]);

    foreach ([$paid, $free] as $lesson) {
        $this->actingAs($user)->get($page === 'lesson' ? lessonPage($lesson) : lessonReview($lesson))
            ->assertOk()
            ->assertDontSee('Đăng ký khóa học');
    }
})->with(['lesson', 'review']);
