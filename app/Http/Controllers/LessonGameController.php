<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\GameScore;
use App\Models\Lesson;
use App\Models\Unit;
use App\Models\Word;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LessonGameController extends Controller
{
    /**
     * The games played with the words of a lesson, by slug. `thumbnail` is a path under public/; until that image exists
     * the card shows the icon on the tone instead. Only a game with `isPlayable` has a page (lessons/games/{slug}); one
     * with `showsPartOfSpeech` shows each meaning with its part of speech, one with `hasLeaderboard` ranks the fastest players, whose time may not pass `maxSecondsPerWord` per word (which Soldier and Monster and Zombie Defense also give the player to play in). A game with `ranksAnyTime` ranks the time of every win, however slow.
     *
     * @var array<string, array{title: string, thumbnail: string, icon: string, tone: string, isPlayable: bool, hasLeaderboard: bool, maxSecondsPerWord: int, showsPartOfSpeech: bool, ranksAnyTime?: bool}>
     */
    public const GAMES = [
        'soldier-and-monster' => ['title' => 'Soldier and Monster', 'thumbnail' => 'games/soldier-and-monster/preview.png', 'icon' => 'swords', 'tone' => 'primary', 'isPlayable' => true, 'hasLeaderboard' => true, 'maxSecondsPerWord' => 6, 'showsPartOfSpeech' => false],
        'galaxy-defender' => ['title' => 'Galaxy Defender', 'thumbnail' => 'games/galaxy-defender/preview.jpg', 'icon' => 'rocket', 'tone' => 'secondary', 'isPlayable' => true, 'hasLeaderboard' => true, 'maxSecondsPerWord' => 60, 'showsPartOfSpeech' => false],
        'zombie-defense' => ['title' => 'Zombie Defense', 'thumbnail' => 'games/zombie-defense/preview.jpg', 'icon' => 'skull', 'tone' => 'accent', 'isPlayable' => true, 'hasLeaderboard' => true, 'maxSecondsPerWord' => 4, 'showsPartOfSpeech' => false, 'ranksAnyTime' => true],
    ];

    /**
     * The least time a word can honestly take to type, so a made-up time is refused.
     */
    public const MINIMUM_MILLISECONDS_PER_WORD = 500;

    /**
     * Show the games of a lesson the visitor may learn (see EnsureLessonAccess).
     */
    public function index(Course $course, Unit $unit, Lesson $lesson): View
    {
        return view('lessons.games', [
            'course' => $course,
            'unit' => $unit,
            'lesson' => $lesson,
            'games' => collect(self::GAMES)->map(fn (array $game, string $slug): array => [
                ...$game,
                'thumbnailUrl' => is_file(public_path($game['thumbnail'])) ? asset($game['thumbnail']) : null,
                'url' => $game['isPlayable'] ? route('lessons.games.show', [$course, $unit, $lesson, $slug]) : null,
            ])->values(),
        ]);
    }

    /**
     * Play a game with the words of the lesson: the ones a review asks about, each with all its meanings.
     */
    public function show(Request $request, Course $course, Unit $unit, Lesson $lesson, string $game): View
    {
        abort_unless(self::GAMES[$game]['isPlayable'] ?? false, 404);

        return view('lessons.games.'.$game, [
            'course' => $course,
            'unit' => $unit,
            'lesson' => $lesson,
            'title' => self::GAMES[$game]['title'],
            'words' => $lesson->reviewWords()
                ->map(fn (Word $word): array => ['word' => $word->word, 'meaning' => self::GAMES[$game]['showsPartOfSpeech'] ? $word->allMeaningsLabel() : $word->plainMeaningsLabel()])
                ->all(),
            'secondsPerWord' => self::GAMES[$game]['maxSecondsPerWord'],
            'learnerAvatars' => $game === 'zombie-defense' ? $course->learnerAvatars() : [],
            'bestTimes' => GameScore::leaderboard($lesson, $game, $request->user()),
            'saveUrl' => $request->user() ? route('lessons.games.score', [$course, $unit, $lesson, $game]) : null,
        ]);
    }

    /**
     * Keep the time the signed-in player won a game in when it is their best, and answer with the fastest players.
     * A time outside what the game allows (see MINIMUM_MILLISECONDS_PER_WORD, and maxSecondsPerWord unless the game ranks any time) is refused.
     */
    public function storeScore(Request $request, Course $course, Unit $unit, Lesson $lesson, string $game): JsonResponse
    {
        abort_unless(self::GAMES[$game]['hasLeaderboard'] ?? false, 404);

        $wordCount = $lesson->reviewWords()->count();

        abort_if($wordCount === 0, 404);

        $timeRules = ['required', 'integer', 'min:'.($wordCount * self::MINIMUM_MILLISECONDS_PER_WORD)];

        if (! (self::GAMES[$game]['ranksAnyTime'] ?? false)) {
            $timeRules[] = 'max:'.($wordCount * self::GAMES[$game]['maxSecondsPerWord'] * 1000);
        }

        $validated = $request->validate(['milliseconds' => $timeRules]);

        GameScore::record($lesson, $game, $request->user(), $validated['milliseconds']);

        return response()->json([
            'html' => view('lessons.games.best-times', ['times' => GameScore::leaderboard($lesson, $game, $request->user())])->render(),
        ]);
    }
}
