<?php

namespace App\Enums;

/**
 * Where a challenge stands: sent and waiting for an answer, accepted (both players go to the challenge page), declined by
 * the opponent (closing the popup counts as declining), or taken back by the challenger.
 */
enum ChallengeStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Declined = 'declined';
    case Cancelled = 'cancelled';
}
