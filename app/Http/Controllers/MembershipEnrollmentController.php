<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\MembershipPlan;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class MembershipEnrollmentController extends Controller
{
    /**
     * Show how to pay for the chosen cycle of the membership (`months` in the query, see MembershipPlan::cycles) by bank
     * transfer: the amount, the bank account, a QR code and the transfer content that identifies the payment.
     *
     * A pending payment is opened the first time and reused while the price stays the same.
     */
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $bank = config('payment.bank');
        $months = MembershipPlan::hasCycle((int) $request->query('months')) ? (int) $request->query('months') : MembershipPlan::defaultMonths();

        $payment = Payment::pendingMembershipBankTransfer($user, $months);

        return view('membership.enroll', [
            'bank' => $bank,
            'payment' => $payment,
            'months' => $months,
            'regularPrice' => MembershipPlan::format(MembershipPlan::regularPrice()),
            'cycles' => collect(MembershipPlan::cycles())->keys()->map(fn (int $cycleMonths): array => [
                'months' => $cycleMonths,
                'discount' => MembershipPlan::discountPercentForMonths($cycleMonths),
                'price' => MembershipPlan::format(MembershipPlan::priceForMonths($cycleMonths)),
                'fullPrice' => MembershipPlan::format(MembershipPlan::regularPriceForMonths($cycleMonths)),
                'saving' => MembershipPlan::format(MembershipPlan::regularPriceForMonths($cycleMonths) - MembershipPlan::priceForMonths($cycleMonths)),
                'monthlyPrice' => $cycleMonths > 1 ? MembershipPlan::format((int) round(MembershipPlan::priceForMonths($cycleMonths) / $cycleMonths)) : null,
                'isFeatured' => $cycleMonths === max(array_keys(MembershipPlan::cycles())),
                'url' => route('membership.enroll', ['months' => $cycleMonths]),
            ])->all(),
            'activeUntil' => $user->activeMembership() ? $user->membership_expires_on : null,
            'isLifetime' => $user->activeMembership() !== null && $user->membership_expires_on === null,
            'qrUrl' => $payment->qrUrl($bank),
        ]);
    }
}
