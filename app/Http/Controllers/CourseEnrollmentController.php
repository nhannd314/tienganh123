<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Payment;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CourseEnrollmentController extends Controller
{
    /**
     * Show how to pay for the chosen cycle of a course (`months` in the query, see Course::cycles) by bank transfer:
     * the amount, the bank account, a QR code and the transfer content that identifies the payment.
     *
     * A pending payment is opened the first time and reused while the price stays the same. A free course needs no
     * registration.
     */
    public function __invoke(Request $request, Course $course): View|RedirectResponse
    {
        abort_unless($course->is_published, 404);

        if ($course->isFree()) {
            return redirect()->route('courses.show', $course);
        }

        $user = $request->user();
        $bank = config('payment.bank');
        $months = $this->chosenMonths($request);

        $payment = Payment::pendingBankTransfer($user, $course, $course->priceForMonths($months), $months);

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
            'months' => $months,
            'basePrice' => number_format($course->basePrice(), 0, ',', '.').'₫',
            'cycles' => collect(Course::cycles())->keys()->map(fn (int $cycleMonths): array => [
                'months' => $cycleMonths,
                'discount' => $course->discountPercentForMonths($cycleMonths),
                'price' => $course->priceLabelForMonths($cycleMonths),
                'fullPrice' => number_format($course->basePriceForMonths($cycleMonths), 0, ',', '.').'₫',
                'saving' => number_format($course->basePriceForMonths($cycleMonths) - $course->priceForMonths($cycleMonths), 0, ',', '.').'₫',
                'monthlyPrice' => $cycleMonths > 1 ? number_format((int) round($course->priceForMonths($cycleMonths) / $cycleMonths), 0, ',', '.').'₫' : null,
                'isFeatured' => $cycleMonths === max(array_keys(Course::cycles())),
                'url' => route('courses.enroll', [$course, 'months' => $cycleMonths]),
            ])->all(),
            'activeUntil' => $activeUntil ? now()->parse($activeUntil) : null,
            'qrUrl' => $payment->qrUrl($bank),
        ]);
    }

    /**
     * The months asked for, or the shortest cycle when none or an unknown one is.
     */
    private function chosenMonths(Request $request): int
    {
        $months = (int) $request->query('months');

        return array_key_exists($months, Course::cycles()) ? $months : array_key_first(Course::cycles());
    }
}
