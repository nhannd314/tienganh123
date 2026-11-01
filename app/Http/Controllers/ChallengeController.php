<?php

namespace App\Http\Controllers;

use App\Enums\ChallengeStatus;
use App\Events\ChallengeGameUpdated;
use App\Events\ChallengeUpdated;
use App\Http\Requests\StoreChallengeRequest;
use App\Models\Challenge;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Unit;
use App\Services\ChallengeGame;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Challenges between two learners on the site: the challenger picks a lesson in the "Đang học bài" list, the opponent
 * gets a popup and accepts or declines it, and once accepted both go to the challenge page.
 */
class ChallengeController extends Controller
{
    /**
     * The published courses, each with its units and their published lessons, for the selects of the challenge popup.
     * Only lessons with vocabulary (words to play) are listed; a unit without one, and a course without such a unit, is left out.
     */
    public function lessons(): JsonResponse
    {
        $courses = Course::query()
            ->published()
            ->orderBy('sort_order')
            ->with(['units.lessons' => fn ($query) => $query->published()->whereHas('vocabularies')])
            ->get()
            ->map(fn (Course $course): array => [
                'id' => $course->id,
                'title' => $course->title,
                'units' => $course->units
                    ->filter(fn (Unit $unit): bool => $unit->lessons->isNotEmpty())
                    ->map(fn (Unit $unit): array => [
                        'id' => $unit->id,
                        'title' => $unit->title,
                        'lessons' => $unit->lessons->map(fn (Lesson $lesson): array => ['id' => $lesson->id, 'title' => $lesson->title])->values()->all(),
                    ])
                    ->values()
                    ->all(),
            ])
            ->filter(fn (array $course): bool => $course['units'] !== [])
            ->values();

        return response()->json(['courses' => $courses]);
    }

    /**
     * Send a challenge: the opponent gets the popup in every tab they have open.
     */
    public function store(StoreChallengeRequest $request): JsonResponse
    {
        $challenge = Challenge::create([
            'challenger_id' => $request->user()->id,
            'opponent_id' => $request->validated('opponent_id'),
            'lesson_id' => $request->validated('lesson_id'),
            'status' => ChallengeStatus::Pending,
        ]);

        (new ChallengeUpdated($challenge))->announce();

        return response()->json([
            'id' => $challenge->id,
            'cancelUrl' => route('challenges.cancel', $challenge),
            'expiresInSeconds' => Challenge::EXPIRES_AFTER_SECONDS,
        ], 201);
    }

    /**
     * Accept the challenge: both players go to the challenge page. A challenge already answered, cancelled or expired
     * can no longer be accepted.
     */
    public function accept(Challenge $challenge): JsonResponse
    {
        if (! $challenge->isOpen()) {
            return response()->json(['message' => 'Lời thách đấu này đã hết hạn hoặc đã bị hủy.'], 409);
        }

        $challenge->respond(ChallengeStatus::Accepted);
        (new ChallengeUpdated($challenge))->announce();

        return response()->json(['redirect' => route('challenges.show', $challenge)]);
    }

    /**
     * Decline the challenge (closing the popup does the same). A challenge already closed stays as it is.
     */
    public function decline(Challenge $challenge): JsonResponse
    {
        return $this->close($challenge, ChallengeStatus::Declined);
    }

    /**
     * Take the challenge back before the opponent answers: their popup closes.
     */
    public function cancel(Challenge $challenge): JsonResponse
    {
        return $this->close($challenge, ChallengeStatus::Cancelled);
    }

    /**
     * The challenge page (the brick-throwing game), for the two players of an accepted challenge.
     */
    public function show(Request $request, Challenge $challenge, ChallengeGame $game): View
    {
        $challenge->load('challenger', 'opponent', 'lesson.unit.course');

        return view('challenges.show', [
            'challenge' => $challenge,
            'side' => $challenge->sideOf($request->user()),
            'state' => $game->state($challenge),
        ]);
    }

    /**
     * The game as it stands, for a page that lost its connection and catches up.
     */
    public function state(Challenge $challenge, ChallengeGame $game): JsonResponse
    {
        return response()->json(['state' => $game->state($challenge)]);
    }

    /**
     * The player pressed Start: the other one is told, and the game starts once both have.
     */
    public function ready(Request $request, Challenge $challenge, ChallengeGame $game): JsonResponse
    {
        return $this->play($request, $challenge, $game->ready($challenge, $request->user()));
    }

    /**
     * The player typed a letter of the current word: a right one throws a brick, the last one wins the round.
     */
    public function type(Request $request, Challenge $challenge, ChallengeGame $game): JsonResponse
    {
        $validated = $request->validate([
            'word_index' => ['required', 'integer', 'min:0'],
            'letter' => ['required', 'string', 'max:4'],
        ]);

        return $this->play($request, $challenge, $game->type($challenge, $request->user(), (int) $validated['word_index'], $validated['letter']));
    }

    /**
     * Tell both players what happened, if anything, and answer with the game as it now stands.
     *
     * @param  array{state: array<string, mixed>, event: string|null}  $result
     */
    private function play(Request $request, Challenge $challenge, array $result): JsonResponse
    {
        if ($result['event'] !== null) {
            (new ChallengeGameUpdated($challenge, $result['event'], $challenge->sideOf($request->user()), $result['state']))->announce();
        }

        return response()->json(['event' => $result['event'], 'state' => $result['state']]);
    }

    private function close(Challenge $challenge, ChallengeStatus $status): JsonResponse
    {
        if ($challenge->status === ChallengeStatus::Pending) {
            $challenge->respond($status);
            (new ChallengeUpdated($challenge))->announce();
        }

        return response()->json(['status' => $challenge->status->value]);
    }
}
