<?php

namespace App\Policies;

use App\Enums\ChallengeStatus;
use App\Models\Challenge;
use App\Models\User;

class ChallengePolicy
{
    /**
     * Only the opponent answers a challenge (accepts or declines it).
     */
    public function respond(User $user, Challenge $challenge): bool
    {
        return $user->id === $challenge->opponent_id;
    }

    /**
     * Only the challenger takes a challenge back.
     */
    public function cancel(User $user, Challenge $challenge): bool
    {
        return $user->id === $challenge->challenger_id;
    }

    /**
     * Only the two players open the challenge page, once the challenge is accepted.
     */
    public function view(User $user, Challenge $challenge): bool
    {
        return $challenge->involves($user) && $challenge->status === ChallengeStatus::Accepted;
    }
}
