<?php

namespace App\Policies;

use App\Models\Lesson;
use App\Models\User;

class LessonPolicy
{
    /**
     * Whether the visitor may learn the lesson, signed in or not.
     *
     * Anyone may learn a free lesson and the lessons of a free course. The rest needs an active subscription to the
     * course; admins may learn everything.
     */
    public function learn(?User $user, Lesson $lesson): bool
    {
        $course = $lesson->unit->course;

        if ($lesson->is_free || $course->isFree()) {
            return true;
        }

        return $user?->hasPaidAccessTo($course) ?? false;
    }
}
