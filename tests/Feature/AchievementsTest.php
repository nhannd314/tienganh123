<?php

use App\Events\LearnerAchieved;
use App\Jobs\FetchWordPronunciation;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Unit;
use App\Models\User;
use App\Models\Word;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    Queue::fake([FetchWordPronunciation::class]);
    Event::fake([LearnerAchieved::class]);
});

function achievementLesson(): Lesson
{
    $unit = Unit::factory()->for(Course::factory()->published())->create();
    $lesson = Lesson::factory()->published()->free()->for($unit)->create(['title' => 'Unit 2: Family']);
    attachWords($lesson, Word::factory()->count(2)->create());

    return $lesson->fresh();
}

/**
 * Send part 2 of the review of the lesson, with `$rightAnswers` of its words answered right.
 */
/**
 * How many achievements of the kind were announced.
 */
function announced(string $kind): int
{
    return Event::dispatched(LearnerAchieved::class, fn (LearnerAchieved $event): bool => $event->kind === $kind)->count();
}

function sendReviewPart(Lesson $lesson, int $rightAnswers): void
{
    $answers = $lesson->words->values()->map(fn (Word $word, int $index): array => [
        'word_id' => $word->id,
        'selected' => $index < $rightAnswers ? $word->word : 'wrong',
    ])->all();

    test()->postJson(route('lessons.review.store', [$lesson->unit->course, $lesson->unit, $lesson, 2]), ['duration' => 10, 'answers' => $answers])
        ->assertCreated();
}

it('announces a review part completed for the first time, then only a better score', function () {
    $this->actingAs(User::factory()->create(['name' => 'Lê Khánh Linh']));
    $lesson = achievementLesson();

    sendReviewPart($lesson, 2);

    expect(announced('completed'))->toBe(1);
    Event::assertDispatched(fn (LearnerAchieved $event): bool => $event->kind === 'completed'
        && $event->name === 'Lê Khánh Linh'
        && $event->message === 'vừa hoàn thành phần Trả lời từ'
        && $event->value === '2/2 câu'
        && $event->subject === 'Unit 2: Family'
        && $event->broadcastOn()[0]->name === 'achievements');

    sendReviewPart($lesson, 2);

    expect(announced('completed'))->toBe(1)
        ->and(announced('record'))->toBe(0);
});

it('announces a new best score of a lesson without completing a part', function () {
    $this->actingAs(User::factory()->create());
    $lesson = achievementLesson();

    sendReviewPart($lesson, 1);

    Event::assertDispatched(fn (LearnerAchieved $event): bool => $event->kind === 'record' && str_ends_with($event->value, ' điểm'));
});

it('announces a new best time of a game, and not a slower one', function () {
    $user = User::factory()->create();
    $lesson = achievementLesson();
    $url = route('lessons.games.score', [$lesson->unit->course, $lesson->unit, $lesson, 'galaxy-defender']);

    $this->actingAs($user)->postJson($url, ['milliseconds' => 12300])->assertOk();
    $this->actingAs($user)->postJson($url, ['milliseconds' => 15000])->assertOk();

    Event::assertDispatchedTimes(LearnerAchieved::class, 1);
    Event::assertDispatched(fn (LearnerAchieved $event): bool => $event->value === '12,3 giây' && $event->subject === 'Galaxy Defender');
});

it('announces a learner who climbs the BXH, and refreshes the cached BXH at once', function () {
    $lesson = achievementLesson();
    $leader = User::factory()->create(['name' => 'Học Sinh Giỏi']);
    $lesson->scores()->create(['user_id' => $leader->id, 'score' => 1, 'percent' => 10]);
    $climber = User::factory()->create(['name' => 'Bùi Gia Anh']);

    $this->get(route('home'))->assertDontSee('Bùi Gia Anh');

    $this->actingAs($climber);
    sendReviewPart($lesson, 2);

    $points = $lesson->scores()->firstWhere('user_id', $climber->id)->score;

    Event::assertDispatched(fn (LearnerAchieved $event): bool => $event->kind === 'rank'
        && $event->name === 'Bùi Gia Anh'
        && $event->message === 'vừa leo lên hạng 1 BXH'
        && $event->value === number_format($points, 0, ',', '.').' điểm');
    $this->get(route('home'))->assertSeeInOrder(['Nhà vua', 'Bùi Gia Anh', 'Thái sư', 'Học Sinh Giỏi']);

    sendReviewPart($lesson, 2);

    expect(announced('rank'))->toBe(1);
});

it('keeps the BXH for 5 minutes when it is not refreshed by a new best score', function () {
    $lesson = achievementLesson();

    $this->get(route('home'))->assertSee('Chưa có học sinh nào ghi điểm.');

    $lesson->scores()->create(['user_id' => User::factory()->create(['name' => 'Học Sinh Giỏi'])->id, 'score' => 50, 'percent' => 100]);

    $this->get(route('home'))->assertDontSee('Học Sinh Giỏi');

    $this->travel(5)->minutes();
    $this->travel(1)->second();

    $this->get(route('home'))->assertSee('Học Sinh Giỏi');
});

it('shows the achievement toasts and the leaderboard on every page', function (string $route) {
    $this->get(route($route))
        ->assertSee('data-achievement-toasts', false)
        ->assertSee('data-toggle="leaderboard"', false);
})->with(['home', 'courses.index']);
