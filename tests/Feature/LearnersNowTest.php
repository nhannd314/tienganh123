<?php

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Broadcast;

uses(RefreshDatabase::class);

function learningActivityOf(string $html): string
{
    preg_match('/<meta name="learning-activity" content="([^"]+)">/', $html, $matches);

    return decrypt(html_entity_decode($matches[1]));
}

function freeLessonForLearners(): Lesson
{
    $unit = Unit::factory()->for(Course::factory()->published())->create();

    return Lesson::factory()->published()->free()->for($unit)->create(['title' => 'Unit 2: Family']);
}

/**
 * The member id and info the `learning` presence channel gives the user when a tab of theirs joins with the given activity.
 *
 * @return array{user_id: string, user_info: array<string, mixed>}
 */
function joinLearningChannel(User $user, string $activity, string $socketId = '1234.5678'): array
{
    config([
        'broadcasting.default' => 'reverb',
        'broadcasting.connections.reverb.key' => 'test-key',
        'broadcasting.connections.reverb.secret' => 'test-secret',
        'broadcasting.connections.reverb.app_id' => 'test-app',
    ]);
    Broadcast::purge('reverb');
    require base_path('routes/channels.php');

    $response = test()->actingAs($user)
        ->post('/broadcasting/auth', ['socket_id' => $socketId, 'channel_name' => 'presence-learning', 'activity' => $activity])
        ->assertOk();

    return json_decode($response->json('channel_data'), true);
}

it('puts the encrypted activity of a learning page in the page for a signed-in user', function () {
    $lesson = freeLessonForLearners();

    $html = $this->actingAs(User::factory()->create())
        ->get(route('lessons.show', [$lesson->unit->course, $lesson->unit, $lesson]))
        ->assertOk()
        ->getContent();

    expect(learningActivityOf($html))->toBe('Đang học Unit 2: Family');
});

it('counts any other page of the site as learning, but gives no activity to a guest', function () {
    $lesson = freeLessonForLearners();

    $this->actingAs(User::factory()->create());

    expect(learningActivityOf($this->get(route('home'))->getContent()))->toBe('Đang ở trang chủ')
        ->and(learningActivityOf($this->get(route('search'))->getContent()))->toBe('Đang dạo quanh website');

    auth()->logout();

    $this->get(route('lessons.show', [$lesson->unit->course, $lesson->unit, $lesson]))->assertDontSee('name="learning-activity"', false);
});

it('lets a signed-in user join the learning channel with the activity of their page', function () {
    $user = User::factory()->create(['name' => 'Nguyễn Minh Anh']);

    expect(joinLearningChannel($user, encrypt('Đang luyện Flashcard'))['user_info'])->toMatchArray([
        'id' => $user->id,
        'socket' => '1234.5678',
        'name' => 'Nguyễn Minh Anh',
        'initials' => 'MA',
        'activity' => 'Đang luyện Flashcard',
    ]);
});

it('drops an activity that was not encrypted by the site', function () {
    expect(joinLearningChannel(User::factory()->create(), 'Đang hack')['user_info']['activity'])->toBe('Đang dạo quanh website');
});

it('makes each tab of a user a member of its own', function () {
    $user = User::factory()->create();

    expect(joinLearningChannel($user, encrypt('Đang ở trang chủ'), '1.1')['user_id'])->toBe("{$user->id}.1.1")
        ->and(joinLearningChannel($user, encrypt('Đang ở trang chủ'), '2.2')['user_id'])->toBe("{$user->id}.2.2");
});

it('shows the live list to a signed-in user on every page and asks a guest to sign in', function () {
    $this->get(route('home'))
        ->assertSee('Đăng nhập để xem các bạn đang học')
        ->assertDontSee('data-learners-now', false);

    $this->actingAs(User::factory()->create())
        ->get(route('home'))
        ->assertSee('data-learners-now', false)
        ->assertSee('Chưa có bạn nào đang học');

    $this->get(route('courses.index'))->assertSee('data-learners-now', false);
});
