<?php

use App\Enums\ChallengeStatus;
use App\Events\ChallengeUpdated;
use App\Models\Challenge;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Unit;
use App\Models\User;
use App\Models\Word;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function () {
    Event::fake([ChallengeUpdated::class]);
});

function challengeLesson(string $title = 'Lesson 1: Hello'): Lesson
{
    $unit = Unit::factory()->for(Course::factory()->published()->create(['title' => 'Tiếng Anh lớp 3']))->create(['title' => 'Unit 1: Greetings']);

    $lesson = Lesson::factory()->published()->for($unit)->create(['title' => $title]);
    attachWords($lesson, Word::factory()->create());

    return $lesson;
}

it('lists the published lessons with words of the published courses by course and unit for the popup', function () {
    $lesson = challengeLesson();
    Lesson::factory()->published()->for($lesson->unit)->create(['title' => 'Bài chưa có từ']);
    Lesson::factory()->for($lesson->unit)->create(['title' => 'Bài nháp']);
    Unit::factory()->for($lesson->unit->course)->create(['title' => 'Unit trống']);
    Lesson::factory()->published()->for(Unit::factory()->for(Course::factory()))->create(['title' => 'Bài của khóa nháp']);

    $this->actingAs(User::factory()->create())
        ->getJson(route('challenges.lessons'))
        ->assertOk()
        ->assertExactJson(['courses' => [[
            'id' => $lesson->unit->course->id,
            'title' => 'Tiếng Anh lớp 3',
            'units' => [['id' => $lesson->unit->id, 'title' => 'Unit 1: Greetings', 'lessons' => [['id' => $lesson->id, 'title' => 'Lesson 1: Hello']]]],
        ]]]);
});

it('keeps guests away from every challenge action', function (string $method, string $route) {
    $challenge = Challenge::factory()->create();

    $this->{$method}(route($route, $route === 'challenges.lessons' || $route === 'challenges.store' ? [] : $challenge))
        ->assertRedirect(route('login'));
})->with([
    'lessons' => ['get', 'challenges.lessons'],
    'send' => ['post', 'challenges.store'],
    'accept' => ['post', 'challenges.accept'],
    'decline' => ['post', 'challenges.decline'],
    'cancel' => ['post', 'challenges.cancel'],
    'page' => ['get', 'challenges.show'],
]);

it('sends a challenge and pushes it to both players', function () {
    $challenger = User::factory()->create();
    $opponent = User::factory()->create();
    $lesson = challengeLesson();

    $response = $this->actingAs($challenger)
        ->postJson(route('challenges.store'), ['opponent_id' => $opponent->id, 'lesson_id' => $lesson->id])
        ->assertCreated()
        ->assertJsonPath('expiresInSeconds', Challenge::EXPIRES_AFTER_SECONDS);

    $challenge = Challenge::sole();

    expect($challenge->challenger_id)->toBe($challenger->id)
        ->and($challenge->opponent_id)->toBe($opponent->id)
        ->and($challenge->lesson_id)->toBe($lesson->id)
        ->and($challenge->status)->toBe(ChallengeStatus::Pending);
    $response->assertJsonPath('cancelUrl', route('challenges.cancel', $challenge));
    Event::assertDispatched(ChallengeUpdated::class, fn (ChallengeUpdated $event): bool => $event->challenge->is($challenge));
});

it('refuses a challenge to oneself, to nobody, or on a lesson that is not open', function (Closure $input, array $errors) {
    $challenger = User::factory()->create();

    $this->actingAs($challenger)
        ->postJson(route('challenges.store'), $input($challenger))
        ->assertUnprocessable()
        ->assertJsonValidationErrors($errors);

    expect(Challenge::count())->toBe(0);
    Event::assertNotDispatched(ChallengeUpdated::class);
})->with([
    'nothing' => [fn (User $challenger): array => [], ['opponent_id' => 'Bạn chưa chọn bạn để thách đấu.', 'lesson_id' => 'Bạn chưa chọn bài học.']],
    'oneself' => [fn (User $challenger): array => ['opponent_id' => $challenger->id, 'lesson_id' => challengeLesson()->id], ['opponent_id' => 'Bạn không thể tự thách đấu chính mình.']],
    'unknown opponent' => [fn (User $challenger): array => ['opponent_id' => 999999, 'lesson_id' => challengeLesson()->id], ['opponent_id' => 'Không tìm thấy bạn này.']],
    'draft lesson' => [fn (User $challenger): array => ['opponent_id' => User::factory()->create()->id, 'lesson_id' => Lesson::factory()->for(challengeLesson()->unit)->create()->id], ['lesson_id' => 'Bài học này không có hoặc chưa mở.']],
    'lesson of a draft course' => [fn (User $challenger): array => ['opponent_id' => User::factory()->create()->id, 'lesson_id' => Lesson::factory()->published()->for(Unit::factory()->for(Course::factory()))->create()->id], ['lesson_id' => 'Bài học này không có hoặc chưa mở.']],
]);

it('lets the opponent accept, sending both players to the challenge page', function () {
    $challenge = Challenge::factory()->create();

    $this->actingAs($challenge->opponent)
        ->postJson(route('challenges.accept', $challenge))
        ->assertOk()
        ->assertExactJson(['redirect' => route('challenges.show', $challenge)]);

    expect($challenge->refresh()->status)->toBe(ChallengeStatus::Accepted)
        ->and($challenge->responded_at)->not->toBeNull();
    Event::assertDispatched(ChallengeUpdated::class, fn (ChallengeUpdated $event): bool => $event->challenge->is($challenge) && $event->broadcastWith()['status'] === 'accepted');
});

it('cannot accept a challenge that expired or is already closed', function (Closure $makeChallenge) {
    $challenge = $makeChallenge();
    $statusBefore = $challenge->status;

    $this->actingAs($challenge->opponent)
        ->postJson(route('challenges.accept', $challenge))
        ->assertConflict()
        ->assertJsonPath('message', 'Lời thách đấu này đã hết hạn hoặc đã bị hủy.');

    expect($challenge->refresh()->status)->toBe($statusBefore);
    Event::assertNotDispatched(ChallengeUpdated::class);
})->with([
    'expired' => [fn (): Challenge => Challenge::factory()->create(['created_at' => now()->subSeconds(Challenge::EXPIRES_AFTER_SECONDS + 1)])],
    'declined' => [fn (): Challenge => Challenge::factory()->declined()->create()],
    'cancelled' => [fn (): Challenge => Challenge::factory()->create(['status' => ChallengeStatus::Cancelled])],
]);

it('lets the opponent decline, which the challenger is told about', function () {
    $challenge = Challenge::factory()->create();

    $this->actingAs($challenge->opponent)
        ->postJson(route('challenges.decline', $challenge))
        ->assertOk();

    expect($challenge->refresh()->status)->toBe(ChallengeStatus::Declined);
    Event::assertDispatched(ChallengeUpdated::class, fn (ChallengeUpdated $event): bool => $event->broadcastWith()['status'] === 'declined');
});

it('lets the challenger take the challenge back', function () {
    $challenge = Challenge::factory()->create();

    $this->actingAs($challenge->challenger)
        ->postJson(route('challenges.cancel', $challenge))
        ->assertOk();

    expect($challenge->refresh()->status)->toBe(ChallengeStatus::Cancelled);
    Event::assertDispatched(ChallengeUpdated::class, fn (ChallengeUpdated $event): bool => $event->broadcastWith()['status'] === 'cancelled');
});

it('leaves an accepted challenge as it is when a late decline or cancel comes in', function (string $route, string $player) {
    $challenge = Challenge::factory()->accepted()->create();

    $this->actingAs($challenge->{$player})->postJson(route($route, $challenge))->assertOk();

    expect($challenge->refresh()->status)->toBe(ChallengeStatus::Accepted);
    Event::assertNotDispatched(ChallengeUpdated::class);
})->with([
    'decline' => ['challenges.decline', 'opponent'],
    'cancel' => ['challenges.cancel', 'challenger'],
]);

it('lets only the opponent answer and only the challenger cancel', function (string $route, string $player) {
    $challenge = Challenge::factory()->create();
    $user = $player === 'stranger' ? User::factory()->create() : $challenge->{$player};

    $this->actingAs($user)->postJson(route($route, $challenge))->assertForbidden();

    expect($challenge->refresh()->status)->toBe(ChallengeStatus::Pending);
})->with([
    'challenger accepts' => ['challenges.accept', 'challenger'],
    'stranger accepts' => ['challenges.accept', 'stranger'],
    'challenger declines' => ['challenges.decline', 'challenger'],
    'opponent cancels' => ['challenges.cancel', 'opponent'],
]);

it('pushes the challenge to the private channels of both players with what the popups show', function () {
    $challenger = User::factory()->create(['name' => 'Trần Văn Bình']);
    $opponent = User::factory()->create(['name' => 'Lê Thị Hoa']);
    $challenge = Challenge::factory()->for($challenger, 'challenger')->for($opponent, 'opponent')->for(challengeLesson(), 'lesson')->create();
    $event = new ChallengeUpdated($challenge);

    expect(collect($event->broadcastOn())->pluck('name')->all())->toBe(["private-App.Models.User.{$challenger->id}", "private-App.Models.User.{$opponent->id}"])
        ->and($event->broadcastAs())->toBe('challenge')
        ->and($event->broadcastWith())->toMatchArray([
            'id' => $challenge->id,
            'status' => 'pending',
            'challengerId' => $challenger->id,
            'opponentId' => $opponent->id,
            'challenger' => ['name' => 'Trần Văn Bình', 'avatarUrl' => null],
            'opponentName' => 'Lê Thị Hoa',
            'lesson' => 'Lesson 1: Hello',
            'unit' => 'Unit 1: Greetings',
            'course' => 'Tiếng Anh lớp 3',
            'acceptUrl' => route('challenges.accept', $challenge),
            'declineUrl' => route('challenges.decline', $challenge),
            'url' => route('challenges.show', $challenge),
        ]);
});

it('shows the challenge page to both players of an accepted challenge, names escaped', function (string $player) {
    $challenger = User::factory()->create(['name' => '<script>alert(1)</script>']);
    $challenge = Challenge::factory()->accepted()->for($challenger, 'challenger')->for(challengeLesson(), 'lesson')->create();

    $this->actingAs($challenge->{$player})
        ->get(route('challenges.show', $challenge))
        ->assertOk()
        ->assertSee($challenge->opponent->name)
        ->assertSee('Lesson 1: Hello')
        ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
        ->assertDontSee('<script>alert(1)</script>', false);
})->with(['challenger', 'opponent']);

it('keeps others away from the challenge page, and the players until it is accepted', function () {
    $accepted = Challenge::factory()->accepted()->create();
    $pending = Challenge::factory()->create();

    $this->actingAs(User::factory()->create())->get(route('challenges.show', $accepted))->assertForbidden();
    $this->actingAs($pending->challenger)->get(route('challenges.show', $pending))->assertForbidden();
});

it('gives a signed-in learner the challenge popups in the learners list, and a guest none', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('courses.index'))
        ->assertSee('data-challenge-dialog', false)
        ->assertSee('data-challenge-incoming', false)
        ->assertSee('data-user-id="'.$user->id.'"', false)
        ->assertSee(route('challenges.lessons'), false);

    auth()->logout();

    $this->get(route('courses.index'))->assertDontSee('data-challenge-dialog', false);
});
