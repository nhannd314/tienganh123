<?php

namespace App\View\Components;

use App\Services\Leaderboard as LeaderboardService;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * The BXH button, fixed on the right side of every page, and the panel it opens with the top learners (cached, see
 * App\Services\Leaderboard).
 */
class Leaderboard extends Component
{
    public function __construct(private LeaderboardService $leaderboard) {}

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.leaderboard', ['leaderboard' => $this->leaderboard->top()]);
    }
}
