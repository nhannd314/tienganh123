<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonReview;
use App\Models\Subscription;
use App\Models\User;
use App\Services\AvatarImage;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    private const TABS = ['achievements', 'info', 'courses'];

    private const PROFILE_FIELDS = ['name', 'phone', 'avatar', 'birthday', 'grade', 'school', 'parent_email', 'parent_phone'];

    private const RECENT_REVIEWS = 5;

    /**
     * Show the profile of the signed-in learner on the tab asked for (achievements by default).
     */
    public function show(Request $request): View
    {
        $user = $request->user();
        $tab = in_array($request->query('tab'), self::TABS, true) ? $request->query('tab') : 'achievements';

        $data = match ($tab) {
            'achievements' => $this->achievements($user),
            'courses' => ['subscriptions' => $this->courseSubscriptions($user)],
            default => ['isEditing' => $request->boolean('edit') || ($request->session()->get('errors')?->hasAny(self::PROFILE_FIELDS) ?? false)],
        };

        return view('profile.show', ['user' => $user, 'tab' => $tab, ...$data]);
    }

    /**
     * Save the personal information of the learner.
     */
    public function update(UpdateProfileRequest $request, AvatarImage $avatars): RedirectResponse
    {
        $user = $request->user();
        $data = $request->profileData();
        unset($data['avatar']);

        if ($request->hasFile('avatar')) {
            $previousAvatar = $user->avatar;
            $data['avatar'] = $avatars->store($request->file('avatar'));

            if ($previousAvatar) {
                Storage::disk('public')->delete($previousAvatar);
            }
        }

        $user->update($data);

        return redirect()
            ->route('profile.show', ['tab' => 'info'])
            ->with('status', 'Thông tin của bạn đã được cập nhật.');
    }

    /**
     * Change the password of the learner.
     */
    public function updatePassword(UpdatePasswordRequest $request): RedirectResponse
    {
        $request->user()->update(['password' => $request->validated('password')]);

        return redirect()
            ->route('profile.show', ['tab' => 'info'])
            ->with('status', 'Mật khẩu của bạn đã được đổi.');
    }

    /**
     * Totals, progress per course and the latest review attempts of the learner.
     *
     * @return array<string, mixed>
     */
    private function achievements(User $user): array
    {
        $courses = $this->courseSubscriptions($user)->pluck('subscribable')->filter()->unique('id')->values();
        $percents = Lesson::reviewPercentsFor($user, Lesson::query()->pluck('id'));
        $completedLessonIds = $percents->filter(fn (int $percent): bool => $percent === 100)->keys();

        $courseProgress = $courses->map(function (Course $course) use ($percents): array {
            $lessonIds = $course->lessons()->pluck('lessons.id');
            $done = $lessonIds->filter(fn (int $id): bool => $percents->get($id) === 100)->count();

            return [
                'course' => $course,
                'done' => $done,
                'total' => $lessonIds->count(),
                'percent' => $lessonIds->isEmpty() ? 0 : (int) round($done / $lessonIds->count() * 100),
            ];
        });

        $recentReviews = LessonReview::query()
            ->with('lesson')
            ->where('user_id', $user->id)
            ->latest('id')
            ->limit(self::RECENT_REVIEWS)
            ->get();

        return [
            'stats' => [
                'lessons' => $completedLessonIds->count(),
                'words' => Lesson::query()->whereIn('id', $completedLessonIds)->get()
                    ->sum(fn (Lesson $lesson): int => $lesson->reviewWords()->count()),
                'score' => (int) LessonReview::query()->where('user_id', $user->id)->sum('correct'),
            ],
            'courseProgress' => $courseProgress,
            'recentReviews' => $recentReviews,
            'lessonPercents' => $percents,
        ];
    }

    /**
     * The course subscriptions of the learner, newest first.
     *
     * @return Collection<int, Subscription>
     */
    private function courseSubscriptions(User $user): Collection
    {
        return $user->subscriptions()
            ->where('subscribable_type', (new Course)->getMorphClass())
            ->with('subscribable')
            ->latest('starts_at')
            ->get();
    }
}
