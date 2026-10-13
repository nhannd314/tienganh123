<?php

namespace App\Http\Controllers;

use App\Models\MembershipPlan;
use App\Models\Payment;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MembershipEnrollmentController extends Controller
{
    /**
     * Show how to pay for a month of membership by bank transfer. A member who already has one is sent to the courses.
     */
    public function __invoke(Request $request): View|RedirectResponse
    {
        $plan = MembershipPlan::active()->orderBy('monthly_price')->firstOrFail();
        $user = $request->user();

        if ($user->hasActiveMembership()) {
            return redirect()->route('courses.index');
        }

        $bank = config('payment.bank');
        $payment = Payment::pendingBankTransfer($user, $plan, $plan->monthly_price);

        return view('membership.enroll', [
            'plan' => $plan,
            'bank' => $bank,
            'payment' => $payment,
            'qrUrl' => $payment->qrUrl($bank),
        ]);
    }
}
