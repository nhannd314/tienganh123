<?php

namespace App\Http\Controllers;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Tells the payment page whether the transfer has already arrived. The page asks each time it subscribes to the
 * payment's channel, so a `paid` event broadcast before that (or while it was offline) is not missed.
 */
class PaymentStatusController extends Controller
{
    public function __invoke(Request $request, Payment $payment): JsonResponse
    {
        abort_unless($payment->user_id === $request->user()->id, 404);

        $isPaid = $payment->status === PaymentStatus::Paid;

        return response()->json([
            'paid' => $isPaid,
            'redirect' => $isPaid ? $payment->successUrl() : null,
        ]);
    }
}
