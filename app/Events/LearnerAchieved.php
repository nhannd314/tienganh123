<?php

namespace App\Events;

use App\Http\Controllers\LessonGameController;
use App\Http\Controllers\LessonReviewController;
use App\Models\Lesson;
use App\Models\LessonReview;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A learner did something worth cheering: completed a part of a lesson review for the first time, beat their best
 * (score of a lesson, time of a game) or climbed the BXH. Pushed at once over Reverb on the public `achievements` channel
 * to every page, which shows it as a toast; sent without the queue so it needs no worker.
 *
 * `kind` is `completed`, `record` or `rank`; `value` is what was reached (e.g. "1.250 điểm") and `subject` where (the lesson, the game).
 */
class LearnerAchieved implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public string $kind,
        public string $title,
        public string $name,
        public string $message,
        public string $value,
        public string $subject,
    ) {}

    /**
     * The learner completed a part of the review of the lesson for the first time.
     */
    public static function reviewCompleted(User $user, Lesson $lesson, LessonReview $review): self
    {
        $part = LessonReviewController::STEPS[$review->step - 1]['title'];

        return new self('completed', 'Hoàn thành bài ôn tập', $user->name, "vừa hoàn thành phần {$part}", "{$review->correct}/{$review->total} câu", $lesson->title);
    }

    /**
     * The learner beat their best score in the lesson.
     */
    public static function bestScore(User $user, Lesson $lesson, int $score): self
    {
        return new self('record', 'Điểm cao mới', $user->name, 'vừa phá kỷ lục cá nhân', number_format($score, 0, ',', '.').' điểm', $lesson->title);
    }

    /**
     * The learner beat their best time in a game of the lesson.
     */
    public static function bestTime(User $user, string $game, int $milliseconds): self
    {
        return new self('record', 'Kỷ lục mới', $user->name, 'vừa phá kỷ lục cá nhân', number_format($milliseconds / 1000, 1, ',', '.').' giây', LessonGameController::GAMES[$game]['title']);
    }

    /**
     * The learner climbed to (or came into) the given rank of the BXH (see App\Services\Leaderboard).
     */
    public static function leaderboardRank(User $user, int $rank, int $points): self
    {
        return new self('rank', 'Thay đổi BXH', $user->name, "vừa leo lên hạng {$rank} BXH", number_format($points, 0, ',', '.').' điểm', 'Bảng xếp hạng');
    }

    /**
     * Broadcast it; a Reverb that is down is reported, never failing the request that saved the score.
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
        return [new Channel('achievements')];
    }

    public function broadcastAs(): string
    {
        return 'achieved';
    }
}
