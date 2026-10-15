<?php

use App\Models\Course;
use App\Models\GameScore;
use App\Models\Lesson;
use App\Models\LessonScore;
use App\Models\Unit;
use App\Models\User;
use App\Models\Word;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * A published lesson with one word, in a published course of the given monthly price (0 = free).
 */
function gamesLesson(int $monthlyPrice = 0): Lesson
{
    $course = Course::factory()->published()->create(['title' => 'Tiếng Anh lớp 3', 'monthly_price' => $monthlyPrice]);
    $unit = Unit::factory()->published()->for($course)->create();
    $lesson = Lesson::factory()->published()->for($unit)->create(['title' => 'Chào hỏi bạn mới']);
    attachWords($lesson, Word::factory()->create());

    return $lesson->fresh();
}

function lessonRoute(string $name, Lesson $lesson, array $parameters = []): string
{
    return route($name, [$lesson->unit->course, $lesson->unit, $lesson, ...$parameters]);
}

it('shows the three games of the lesson, each with a thumbnail, its name and a play button', function () {
    $lesson = gamesLesson();

    $response = $this->get(lessonRoute('lessons.games', $lesson))
        ->assertOk()
        ->assertSeeInOrder(['Trang chủ', 'Tiếng Anh lớp 3', 'Chào hỏi bạn mới', 'Trò chơi'])
        ->assertSeeInOrder(['Soldier and Monster', 'Chơi ngay', 'Galaxy Defender', 'Chơi ngay', 'Zombie Defense', 'Chơi ngay'])
        ->assertSee(lessonRoute('lessons.show', $lesson), false)
        ->assertDontSee('Tiến độ học tập')
        ->assertDontSee('Top 10 bài học này');

    expect(substr_count($response->getContent(), 'data-game '))->toBe(3)
        ->and(substr_count($response->getContent(), 'aspect-video'))->toBe(3);
});

it('links every game to its page, Soldier and Monster with its preview as thumbnail', function () {
    $lesson = gamesLesson();

    $this->get(lessonRoute('lessons.games', $lesson))
        ->assertSee('href="'.lessonRoute('lessons.games.show', $lesson, ['soldier-and-monster']).'"', false)
        ->assertSee('href="'.lessonRoute('lessons.games.show', $lesson, ['galaxy-defender']).'"', false)
        ->assertSee('href="'.lessonRoute('lessons.games.show', $lesson, ['zombie-defense']).'"', false)
        ->assertSee('src="'.asset('games/soldier-and-monster/preview.png').'"', false);
});

it('plays Soldier and Monster with the review words of the lesson and six seconds per word', function () {
    $lesson = gamesLesson();
    $lesson->vocabularies()->detach();
    attachWords($lesson, [
        Word::factory()->create(['word' => 'apple', 'meanings' => [['pos' => 'n', 'meaning' => 'quả táo']]]),
        Word::factory()->create(['word' => 'ice cream', 'meanings' => [['pos' => 'n', 'meaning' => 'kem']]]),
        Word::factory()->create(['word' => 'empty', 'meanings' => []]),
    ]);

    $content = $this->get(lessonRoute('lessons.games.show', $lesson, ['soldier-and-monster']))
        ->assertOk()
        ->assertSeeInOrder(['Trò chơi', 'Soldier and Monster'])
        ->assertSee('data-soldier-game', false)
        ->assertSee('12 giây')
        ->getContent();

    preg_match('/<script type="application\/json" data-game-config>(.*?)<\/script>/s', $content, $match);

    expect(json_decode($match[1], true))->toBe([
        'words' => [['word' => 'apple', 'meaning' => 'quả táo'], ['word' => 'ice cream', 'meaning' => 'kem']],
        'secondsPerWord' => 6,
        'assetsUrl' => asset('games/soldier-and-monster'),
        'saveUrl' => null,
        'csrf' => csrf_token(),
    ]);
});

it('plays Galaxy Defender with the review words of the lesson, without a time limit and with the ranking beside it', function () {
    $lesson = gamesLesson();
    $lesson->vocabularies()->detach();
    attachWords($lesson, [
        Word::factory()->create(['word' => 'apple', 'meanings' => [['pos' => 'n', 'meaning' => 'quả táo'], ['pos' => 'v', 'meaning' => 'ăn táo']]]),
        Word::factory()->create(['word' => 'empty', 'meanings' => []]),
    ]);

    $content = $this->get(lessonRoute('lessons.games.show', $lesson, ['galaxy-defender']))
        ->assertOk()
        ->assertSeeInOrder(['Trò chơi', 'Galaxy Defender'])
        ->assertSee('data-galaxy-game', false)
        ->assertSee('Không giới hạn thời gian')
        ->assertSee('Top 10 thời gian nhanh nhất')
        ->getContent();

    preg_match('/<script type="application\/json" data-game-config>(.*?)<\/script>/s', $content, $match);

    expect(json_decode($match[1], true))->toBe([
        'words' => [['word' => 'apple', 'meaning' => 'quả táo; ăn táo']],
        'assetsUrl' => asset('games/galaxy-defender'),
        'saveUrl' => null,
        'csrf' => csrf_token(),
    ]);
});

it('plays Zombie Defense with the words of the lesson and four seconds per word', function () {
    $lesson = gamesLesson();
    $lesson->vocabularies()->detach();
    attachWords($lesson, [
        Word::factory()->create(['word' => 'apple', 'meanings' => [['pos' => 'n', 'meaning' => 'quả táo'], ['pos' => 'v', 'meaning' => 'ăn táo']]]),
        Word::factory()->create(['word' => 'empty', 'meanings' => []]),
    ]);

    $content = $this->get(lessonRoute('lessons.games.show', $lesson, ['zombie-defense']))
        ->assertOk()
        ->assertSeeInOrder(['Trò chơi', 'Zombie Defense'])
        ->assertSee('data-zombie-game', false)
        ->assertSee('Top 10 thời gian nhanh nhất')
        ->assertSee('4 giây cho mỗi từ')
        ->getContent();

    preg_match('/<script type="application\/json" data-game-config>(.*?)<\/script>/s', $content, $match);

    expect(json_decode($match[1], true))->toBe([
        'words' => [['word' => 'apple', 'meaning' => 'quả táo; ăn táo']],
        'secondsPerWord' => 4,
        'saveUrl' => null,
        'images' => [
            'background' => asset('games/zombie-defense/bg.jpg'),
            'house' => asset('games/zombie-defense/gun-home.webp'),
            'zombies' => asset('games/zombie-defense/zombies'),
        ],
        'avatars' => [],
        'sounds' => asset('games/zombie-defense/sounds'),
        'csrf' => csrf_token(),
    ]);
});

it('says so when the lesson has no word to play Zombie Defense with', function () {
    $lesson = gamesLesson();
    $lesson->vocabularies()->detach();

    $this->get(lessonRoute('lessons.games.show', $lesson, ['zombie-defense']))
        ->assertOk()
        ->assertSee('Chưa có từ vựng')
        ->assertDontSee('data-zombie-game', false);
});

it('says so when the lesson has no word to play Galaxy Defender with', function () {
    $lesson = gamesLesson();
    $lesson->vocabularies()->detach();

    $this->get(lessonRoute('lessons.games.show', $lesson, ['galaxy-defender']))
        ->assertOk()
        ->assertSee('Chưa có từ vựng')
        ->assertDontSee('data-galaxy-game', false);
});

it('says so when the lesson has no word to play with', function () {
    $lesson = gamesLesson();
    $lesson->vocabularies()->detach();

    $this->get(lessonRoute('lessons.games.show', $lesson, ['soldier-and-monster']))
        ->assertOk()
        ->assertSee('Chưa có từ vựng')
        ->assertDontSee('data-soldier-game', false);
});

it('returns not found for a game that does not exist', function (string $game) {
    $this->get(lessonRoute('lessons.games.show', gamesLesson(), [$game]))->assertNotFound();
})->with(['treasure-hunter', 'nope']);

it('keeps the games of a locked lesson from a guest and of an unpublished lesson from everyone', function () {
    $this->get(lessonRoute('lessons.games', gamesLesson(monthlyPrice: 50000)))->assertRedirect(route('login'));

    $unpublished = gamesLesson();
    $unpublished->update(['is_published' => false]);

    $this->actingAs(User::factory()->admin()->create())->get(lessonRoute('lessons.games', $unpublished))->assertNotFound();
});

it('links the game card of the lesson page and of the review to the games of the lesson', function (string $name, array $parameters) {
    $lesson = gamesLesson();
    $this->actingAs(User::factory()->create());

    $this->get(lessonRoute($name, $lesson, $parameters))
        ->assertOk()
        ->assertSeeInOrder(['Tiến độ học tập', 'Chơi game giải trí', 'Top 10 bài học này'])
        ->assertSee('href="'.lessonRoute('lessons.games', $lesson).'"', false);
})->with([
    'lesson page' => ['lessons.show', []],
    'review' => ['lessons.review.show', [1]],
]);

it('lists the 10 fastest players of the game beside it, fastest first, and marks the viewer', function () {
    $lesson = gamesLesson();
    $me = User::factory()->create(['name' => 'Me Myself']);
    GameScore::factory()->for($lesson)->for($me)->create(['milliseconds' => 8400]);
    GameScore::factory()->for($lesson)->for(User::factory()->create(['name' => 'Fast Fox']))->create(['milliseconds' => 5200]);
    GameScore::factory()->for($lesson)->count(10)->create(['milliseconds' => 59000]);
    GameScore::factory()->for(gamesLesson())->for(User::factory()->create(['name' => 'Other Lesson']))->create(['milliseconds' => 1000]);

    $content = $this->actingAs($me)->get(lessonRoute('lessons.games.show', $lesson, ['soldier-and-monster']))
        ->assertSeeInOrder(['Top 10 thời gian nhanh nhất', 'Fast Fox', '5.2s', 'Me Myself', '(bạn)', '8.4s'])
        ->assertDontSee('Other Lesson')
        ->getContent();

    expect(substr_count($content, 'tabular-nums'))->toBeGreaterThanOrEqual(10)
        ->and(substr_count($content, '59.0s'))->toBe(8);
});

it('says nobody has finished yet when there is no time', function () {
    $this->get(lessonRoute('lessons.games.show', gamesLesson(), ['soldier-and-monster']))
        ->assertSee('Chưa có ai về đích');
});

it('keeps only the best time of a player and answers with the refreshed list', function () {
    $lesson = gamesLesson();
    $user = User::factory()->create(['name' => 'Speedy']);
    $url = lessonRoute('lessons.games.score', $lesson, ['soldier-and-monster']);

    $this->actingAs($user)->postJson($url, ['milliseconds' => 4000])->assertOk()->assertJsonPath('html', fn (string $html) => str_contains($html, 'Speedy') && str_contains($html, '4.0s'));
    $this->actingAs($user)->postJson($url, ['milliseconds' => 5500])->assertOk();
    expect(GameScore::where('user_id', $user->id)->pluck('milliseconds')->all())->toBe([4000]);

    $this->actingAs($user)->postJson($url, ['milliseconds' => 3000])->assertOk();
    expect(GameScore::where('user_id', $user->id)->pluck('milliseconds')->all())->toBe([3000]);
});

it('refuses a time that is impossible or beyond the time limit of the game', function (int $milliseconds) {
    $lesson = gamesLesson();

    $this->actingAs(User::factory()->create())
        ->postJson(lessonRoute('lessons.games.score', $lesson, ['soldier-and-monster']), ['milliseconds' => $milliseconds])
        ->assertUnprocessable();

    expect(GameScore::count())->toBe(0);
})->with([100, 6001]);

it('does not take a time from a guest or for a game that does not exist', function () {
    $lesson = gamesLesson();

    $this->postJson(lessonRoute('lessons.games.score', $lesson, ['soldier-and-monster']), ['milliseconds' => 4000])->assertUnauthorized();
    $this->actingAs(User::factory()->create())
        ->postJson(lessonRoute('lessons.games.score', $lesson, ['nope']), ['milliseconds' => 4000])
        ->assertNotFound();
});

it('ranks the time of Zombie Defense however slow it is', function () {
    $lesson = gamesLesson();
    $lesson->vocabularies()->detach();
    attachWords($lesson, [
        Word::factory()->create(['word' => 'apple', 'meanings' => [['pos' => 'n', 'meaning' => 'quả táo']]]),
        Word::factory()->create(['word' => 'ice cream', 'meanings' => [['pos' => 'n', 'meaning' => 'kem']]]),
    ]);
    $user = User::factory()->create(['name' => 'Zombie Slayer']);
    $url = lessonRoute('lessons.games.score', $lesson, ['zombie-defense']);

    $this->actingAs($user)->postJson($url, ['milliseconds' => 1])->assertUnprocessable();
    $this->actingAs($user)->postJson($url, ['milliseconds' => 600000])->assertOk()->assertSee('Zombie Slayer', false);

    expect(GameScore::where('game', 'zombie-defense')->count())->toBe(1);
});

it('ranks the time of Galaxy Defender, which allows a minute per word instead of six seconds', function () {
    $lesson = gamesLesson();
    $user = User::factory()->create(['name' => 'Star Pilot']);
    $url = lessonRoute('lessons.games.score', $lesson, ['galaxy-defender']);

    $this->actingAs($user)->postJson($url, ['milliseconds' => 45000])->assertOk()->assertJsonPath('html', fn (string $html) => str_contains($html, 'Star Pilot') && str_contains($html, '45.0s'));
    $this->actingAs($user)->postJson($url, ['milliseconds' => 60001])->assertUnprocessable();

    $this->actingAs($user)->get(lessonRoute('lessons.games.show', $lesson, ['galaxy-defender']))->assertSeeInOrder(['Top 10 thời gian nhanh nhất', 'Star Pilot', '45.0s']);
    expect(GameScore::where('game', 'galaxy-defender')->count())->toBe(1);
});

it('gives some Zombie Defense zombies the avatars of the learners of the course, and of the player', function () {
    $lesson = gamesLesson();
    $learner = User::factory()->create(['name' => 'Trần Văn Bình', 'avatar' => 'avatars/learner.jpg']);
    $player = User::factory()->create(['name' => 'Lê Thị Hoa', 'avatar' => 'avatars/player.jpg']);
    $noAvatar = User::factory()->create(['avatar' => null]);
    $elsewhere = User::factory()->create(['avatar' => 'avatars/elsewhere.jpg']);

    LessonScore::factory()->for($lesson)->for($learner)->create();
    LessonScore::factory()->for($lesson)->for($noAvatar)->create();
    LessonScore::factory()->for(gamesLesson())->for($elsewhere)->create();

    $content = $this->actingAs($player)->get(lessonRoute('lessons.games.show', $lesson, ['zombie-defense']))->assertOk()->getContent();

    preg_match('/<script type="application\/json" data-game-config>(.*?)<\/script>/s', $content, $match);

    expect(json_decode($match[1], true)['avatars'])->toBe([
        ['url' => $player->avatar_url, 'name' => 'Hoa'],
        ['url' => $learner->avatar_url, 'name' => 'Bình'],
    ]);
});
