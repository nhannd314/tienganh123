<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\User;

abstract class Controller
{
    /**
     * The score and the rank label (e.g. "3/12", or "—" before scoring) the lesson sidebar shows for the user.
     *
     * @return array{score: int, rank: string}
     */
    protected function lessonStanding(Lesson $lesson, User $user): array
    {
        $standing = $lesson->standingOf($user);

        return [
            'score' => $standing['score'],
            'rank' => $standing['rank'] ? $standing['rank'].'/'.$standing['learners'] : '—',
        ];
    }

    /**
     * Top 10 learners of a lesson by score, in the shape of the top learners card.
     *
     * @return list<array{name: string, points: int, progress: int}>
     */
    protected function lessonTopLearners(Lesson $lesson): array
    {
        return $lesson->topScores()
            ->map(fn ($score): array => ['name' => $score->user->name, 'points' => $score->score, 'progress' => $score->percent])
            ->all();
    }
}
