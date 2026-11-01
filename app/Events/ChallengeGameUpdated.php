<?php

namespace App\Events;

use App\Models\Challenge;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Something happened in the game of a challenge (see App\Services\ChallengeGame). Pushed at once over Reverb to both
 * players on the private channel of the challenge, sent without the queue so it needs no worker.
 *
 * `event` is what happened: `ready` (a player pressed Start), `start`, `letter` (a right letter: `by` throws a brick),
 * `round` (`by` finished the word first) or `finish`. `state` is the whole game after it.
 */
class ChallengeGameUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    /**
     * @param  array<string, mixed>  $state
     */
    public function __construct(
        public Challenge $challenge,
        public string $event,
        public string $by,
        public array $state,
    ) {}

    /**
     * Broadcast it; a Reverb that is down is reported, never failing the request that changed the game.
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
        return [new PrivateChannel("challenges.{$this->challenge->id}")];
    }

    public function broadcastAs(): string
    {
        return 'game';
    }

    /**
     * @return array{event: string, by: string, state: array<string, mixed>}
     */
    public function broadcastWith(): array
    {
        return ['event' => $this->event, 'by' => $this->by, 'state' => $this->state];
    }
}
