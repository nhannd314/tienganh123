<?php

namespace App\Events;

use App\Models\Challenge;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A challenge was sent, accepted, declined or cancelled. Pushed at once over Reverb to every tab of both players (their
 * private user channels), sent without the queue so it needs no worker: a pending one opens the popup of the opponent,
 * an answer closes it and tells the challenger, an accepted one takes the challenger to the challenge page.
 */
class ChallengeUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(public Challenge $challenge) {}

    /**
     * Broadcast it; a Reverb that is down is reported, never failing the request that changed the challenge.
     */
    public function announce(): void
    {
        rescue(fn () => event($this));
    }

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("App.Models.User.{$this->challenge->challenger_id}"),
            new PrivateChannel("App.Models.User.{$this->challenge->opponent_id}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'challenge';
    }

    /**
     * @return array{id: int, status: string, challengerId: int, opponentId: int, challenger: array{name: string, avatarUrl: string|null}, opponentName: string, lesson: string, unit: string, course: string, expiresInSeconds: int, acceptUrl: string, declineUrl: string, url: string}
     */
    public function broadcastWith(): array
    {
        $challenge = $this->challenge->loadMissing('challenger', 'opponent', 'lesson.unit.course');

        return [
            'id' => $challenge->id,
            'status' => $challenge->status->value,
            'challengerId' => $challenge->challenger_id,
            'opponentId' => $challenge->opponent_id,
            'challenger' => ['name' => $challenge->challenger->name, 'avatarUrl' => $challenge->challenger->avatar_url],
            'opponentName' => $challenge->opponent->name,
            'lesson' => $challenge->lesson->title,
            'unit' => $challenge->lesson->unit->title,
            'course' => $challenge->lesson->unit->course->title,
            'expiresInSeconds' => Challenge::EXPIRES_AFTER_SECONDS,
            'acceptUrl' => route('challenges.accept', $challenge),
            'declineUrl' => route('challenges.decline', $challenge),
            'url' => route('challenges.show', $challenge),
        ];
    }
}
