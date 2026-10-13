<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\MembershipPlan;
use App\Models\Payment;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CourseEnrollmentController extends Controller
{
    /**
     * Show how to pay for a month of a course by bank transfer: the amount, the bank account, a QR code and the
     * transfer content that identifies the payment.
     *
     * A pending payment is opened the first time and reused while the price stays the same. A free course needs no
     * registration, and a member already has every course.
     */
    public function __invoke(Request $request, Course $course): View|RedirectResponse
    {
        abort_unless($course->is_published, 404);

        if ($course->isFree()) {
            return redirect()->route('courses.show', $course);
        }

        $user = $request->user();
        $bank = config('payment.bank');

        if ($user->hasActiveMembership()) {
            return view('courses.enroll', ['course' => $course, 'bank' => $bank, 'payment' => null, 'isMember' => true, 'activeUntil' => null, 'qrUrl' => null, 'plan' => null]);
        }

        $payment = Payment::pendingBankTransfer($user, $course, $course->monthly_price);

        $activeUntil = $user->subscriptions()
            ->active()
            ->where('subscribable_type', $course->getMorphClass())
            ->where('subscribable_id', $course->id)
            ->whereNotNull('ends_at')
            ->max('ends_at');

        return view('courses.enroll', [
            'course' => $course,
            'bank' => $bank,
            'payment' => $payment,
            'isMember' => false,
            'activeUntil' => $activeUntil ? now()->parse($activeUntil) : null,
            'qrUrl' => $payment->qrUrl($bank),
            'plan' => MembershipPlan::active()->orderBy('monthly_price')->first(),
        ]);
    }
}
