<?php

use App\Events\ChallengeGameUpdated;
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
    Event::fake([ChallengeGameUpdated::class]);
});

/**
 * An accepted challenge on a lesson with the given words, each meaning "nghĩa của <word>".
 *
 * @param  list<string>  $words
 */
function gameChallenge(array $words = ['cat', 'dog', 'pig']): Challenge
{
    $lesson = Lesson::factory()->published()->for(Unit::factory()->for(Course::factory()->published()))->create();
    attachWords($lesson, collect($words)->map(fn (string $word): Word => Word::factory()->create([
        'word' => $word,
        'meanings' => [['pos' => 'noun', 'meaning' => "nghĩa của {$word}"]],
    ])));

    return Challenge::factory()->accepted()->for($lesson)->create();
}

function pressStart(Challenge $challenge, User $player): array
{
    return test()->actingAs($player)->postJson(route('challenges.ready', $challenge))->assertOk()->json();
}

function startedGame(array $words = ['cat', 'dog', 'pig']): Challenge
{
    $challenge = gameChallenge($words);
    pressStart($challenge, $challenge->challenger);
    pressStart($challenge, $challenge->opponent);

    return $challenge->refresh();
}

function typeLetter(Challenge $challenge, User $player, string $letter, ?int $wordIndex = null): array
{
    return test()->actingAs($player)
        ->postJson(route('challenges.type', $challenge), ['word_index' => $wordIndex ?? $challenge->refresh()->word_index, 'letter' => $letter])
        ->assertOk()
        ->json();
}

/**
 * The player types the whole current word, right.
 */
function typeCurrentWord(Challenge $challenge, User $player): array
{
    $challenge->refresh();
    $response = [];

    foreach (str_split(preg_replace('/[^a-z]/i', '', $challenge->words[$challenge->word_index]['word'])) as $letter) {
        $response = typeLetter($challenge, $player, $letter, $challenge->word_index);
    }

    return $response;
}

it('waits for the other player after the first Start, and tells them', function () {
    $challenge = gameChallenge();

    $response = pressStart($challenge, $challenge->opponent);

    expect($response['event'])->toBe('ready')
        ->and($response['state']['status'])->toBe('waiting')
        ->and($response['state']['ready'])->toBe(['challenger' => false, 'opponent' => true])
        ->and($response['state']['word'])->toBeNull();
    Event::assertDispatched(ChallengeGameUpdated::class, fn (ChallengeGameUpdated $event): bool => $event->event === 'ready' && $event->by === 'opponent');
});

it('starts once both pressed Start, with the words of the lesson and 70% of their number as health', function (array $words, int $health) {
    $challenge = gameChallenge($words);
    pressStart($challenge, $challenge->challenger);

    $response = pressStart($challenge, $challenge->opponent);

    expect($response['event'])->toBe('start')
        ->and($response['state'])->toMatchArray([
            'status' => 'playing',
            'maxHealth' => $health,
            'health' => ['challenger' => $health, 'opponent' => $health],
            'progress' => ['challenger' => 0, 'opponent' => 0],
            'wordIndex' => 0,
            'wordCount' => count($words),
        ])
        ->and(collect($challenge->refresh()->words)->pluck('word')->sort()->values()->all())->toBe(collect($words)->sort()->values()->all())
        ->and($response['state']['word']['meaning'])->toBe('nghĩa của '.$response['state']['word']['word']);
    Event::assertDispatched(ChallengeGameUpdated::class, fn (ChallengeGameUpdated $event): bool => $event->event === 'start');
})->with([
    '10 words' => [['one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine', 'ten'], 7],
    '3 words' => [['cat', 'dog', 'pig'], 2],
    '1 word' => [['cat'], 1],
]);

it('does not start again when a player presses Start twice', function () {
    $challenge = startedGame();
    $words = $challenge->words;

    $response = pressStart($challenge, $challenge->challenger);

    expect($response['event'])->toBeNull()
        ->and($challenge->refresh()->words)->toBe($words);
});

it('ends at once in a draw when the lesson has no words left to play', function () {
    $challenge = gameChallenge([]);
    pressStart($challenge, $challenge->challenger);

    $response = pressStart($challenge, $challenge->opponent);

    expect($response['event'])->toBe('finish')
        ->and($response['state']['status'])->toBe('finished')
        ->and($response['state']['winner'])->toBeNull();
});

it('counts a right letter, whatever its case or accent, and throws a brick at the other player', function (string $letter) {
    $challenge = startedGame(['cat']);

    $response = typeLetter($challenge, $challenge->challenger, $letter);

    expect($response['event'])->toBe('letter')
        ->and($response['state']['progress'])->toBe(['challenger' => 1, 'opponent' => 0]);
    Event::assertDispatched(ChallengeGameUpdated::class, fn (ChallengeGameUpdated $event): bool => $event->event === 'letter' && $event->by === 'challenger');
})->with(['lower case' => ['c'], 'upper case' => ['C']]);

it('ignores a wrong letter, a letter of a word already done, and letters before the start', function () {
    $waiting = gameChallenge(['cat']);
    expect(typeLetter($waiting, $waiting->challenger, 'c', 0)['event'])->toBeNull();

    $challenge = startedGame(['bee', 'fox']);
    Event::fake([ChallengeGameUpdated::class]);

    expect(typeLetter($challenge, $challenge->opponent, 'x')['event'])->toBeNull();

    typeCurrentWord($challenge, $challenge->challenger);
    $firstLetterOfOldWord = $challenge->words[0]['word'][0];

    expect(typeLetter($challenge, $challenge->opponent, $firstLetterOfOldWord, 0)['state']['progress'])->toBe(['challenger' => 0, 'opponent' => 0])
        ->and($challenge->refresh()->opponent_progress)->toBe(0);
});

it('takes a health from the slower player and moves to the next word when a word is done first', function () {
    $challenge = startedGame(['cat', 'dog', 'pig', 'cow']);
    typeLetter($challenge, $challenge->challenger, $challenge->words[0]['word'][0]);

    $response = typeCurrentWord($challenge, $challenge->opponent);

    expect($response['event'])->toBe('round')
        ->and($response['state'])->toMatchArray([
            'status' => 'playing',
            'health' => ['challenger' => 2, 'opponent' => 3],
            'progress' => ['challenger' => 0, 'opponent' => 0],
            'wordIndex' => 1,
        ])
        ->and($response['state']['word']['word'])->toBe($challenge->refresh()->words[1]['word']);
    Event::assertDispatched(ChallengeGameUpdated::class, fn (ChallengeGameUpdated $event): bool => $event->event === 'round' && $event->by === 'opponent');
});

it('ends when a player has no health left, the other one winning', function () {
    $challenge = startedGame(['cat', 'dog', 'pig']);

    typeCurrentWord($challenge, $challenge->challenger);
    $response = typeCurrentWord($challenge, $challenge->challenger);

    expect($response['event'])->toBe('finish')
        ->and($response['state'])->toMatchArray([
            'status' => 'finished',
            'health' => ['challenger' => 2, 'opponent' => 0],
            'winner' => 'challenger',
            'word' => null,
        ])
        ->and($challenge->refresh()->winner_id)->toBe($challenge->challenger_id)
        ->and($challenge->finished_at)->not->toBeNull();
    expect(typeLetter($challenge, $challenge->challenger, 'a', 2)['event'])->toBeNull();
});

it('ends when the words run out, the player with less health losing', function () {
    $challenge = startedGame(['cat', 'dog', 'pig', 'cow', 'hen']);

    foreach (['challenger', 'challenger', 'opponent', 'opponent'] as $player) {
        typeCurrentWord($challenge, $challenge->{$player});
    }

    $response = typeCurrentWord($challenge, $challenge->opponent);

    expect($response['event'])->toBe('finish')
        ->and($response['state']['health'])->toBe(['challenger' => 1, 'opponent' => 2])
        ->and($response['state']['winner'])->toBe('opponent');
});

it('is a draw when the words run out with the same health', function () {
    $challenge = startedGame(['cat', 'dog', 'pig', 'cow']);

    foreach (['challenger', 'opponent', 'challenger'] as $player) {
        typeCurrentWord($challenge, $challenge->{$player});
    }

    $response = typeCurrentWord($challenge, $challenge->opponent);

    expect($response['state'])->toMatchArray(['status' => 'finished', 'health' => ['challenger' => 1, 'opponent' => 1], 'winner' => null])
        ->and($challenge->refresh()->winner_id)->toBeNull();
});

it('pushes the game to the private channel of the challenge', function () {
    $challenge = gameChallenge();
    $event = new ChallengeGameUpdated($challenge, 'letter', 'opponent', ['status' => 'playing']);

    expect(collect($event->broadcastOn())->pluck('name')->all())->toBe(["private-challenges.{$challenge->id}"])
        ->and($event->broadcastAs())->toBe('game')
        ->and($event->broadcastWith())->toBe(['event' => 'letter', 'by' => 'opponent', 'state' => ['status' => 'playing']]);
});

it('gives the game state to a page that catches up', function () {
    $challenge = startedGame();

    $this->actingAs($challenge->opponent)
        ->getJson(route('challenges.state', $challenge))
        ->assertOk()
        ->assertJsonPath('state.status', 'playing')
        ->assertJsonPath('state.wordCount', 3);
});

it('keeps everyone but the two players out of the game', function (string $method, string $route) {
    $challenge = gameChallenge();

    $this->actingAs(User::factory()->create())
        ->{$method}(route($route, $challenge), ['word_index' => 0, 'letter' => 'c'])
        ->assertForbidden();
})->with([
    'start' => ['postJson', 'challenges.ready'],
    'type' => ['postJson', 'challenges.type'],
    'state' => ['getJson', 'challenges.state'],
]);

it('validates the typed letter', function () {
    $challenge = startedGame();

    $this->actingAs($challenge->challenger)
        ->postJson(route('challenges.type', $challenge), [])
        ->assertJsonValidationErrors(['word_index', 'letter']);
});

it('shows the game with the side of the player, the Start button and the scene', function (string $player) {
    $challenge = gameChallenge();

    $this->actingAs($challenge->{$player})
        ->get(route('challenges.show', $challenge))
        ->assertOk()
        ->assertSee('"side":"'.$player.'"', false)
        ->assertSee('data-start', false)
        ->assertSee('games/challenge/bg.webp', false)
        ->assertSee('games/challenge/boy.webp', false)
        ->assertSee('games/challenge/girl.webp', false)
        ->assertSee('games/challenge/sound', false)
        ->assertSee('data-mute', false);
})->with(['challenger', 'opponent']);
