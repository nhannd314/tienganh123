<?php

namespace App\Http\Controllers;

use App\Enums\BankTransactionStatus;
use App\Enums\PaymentStatus;
use App\Models\BankTransaction;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Receives the bank transactions SePay reports (https://developer.sepay.vn/vi/sepay-webhooks) and activates the course
 * a transfer paid for.
 *
 * SePay retries until it gets a 200 with {"success": true}, so everything it can send answers that way, and a
 * transaction is handled once: its `id` is stored with a unique index. Everything runs in one database transaction, so
 * a failure half way leaves no trace and the retry starts clean.
 */
class SepayWebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $data = Validator::make($request->all(), [
            'id' => ['required', 'integer'],
            'gateway' => ['required', 'string'],
            'transactionDate' => ['required', 'date'],
            'accountNumber' => ['required', 'string'],
            'code' => ['nullable', 'string'],
            'content' => ['nullable', 'string'],
            'transferType' => ['required', 'in:in,out'],
            'transferAmount' => ['required', 'integer', 'min:0'],
            'accumulated' => ['nullable', 'integer'],
            'referenceCode' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
        ])->validate();

        DB::transaction(fn () => $this->handle($data, $request->all()));

        return response()->json(['success' => true]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $payload
     */
    private function handle(array $data, array $payload): void
    {
        $transaction = BankTransaction::query()->createOrFirst(
            ['sepay_id' => $data['id']],
            [
                'gateway' => $data['gateway'],
                'account_number' => $data['accountNumber'],
                'transaction_date' => Carbon::parse($data['transactionDate'], 'Asia/Ho_Chi_Minh')->setTimezone(config('app.timezone')),
                'transfer_type' => $data['transferType'],
                'amount' => $data['transferAmount'],
                'accumulated' => $data['accumulated'] ?? 0,
                'code' => $data['code'] ?? null,
                'content' => $data['content'] ?? '',
                'reference_code' => $data['referenceCode'] ?? null,
                'description' => $data['description'] ?? null,
                'status' => BankTransactionStatus::Unmatched,
                'payload' => $payload,
            ],
        );

        if (! $transaction->wasRecentlyCreated) {
            return;
        }

        if ($data['transferType'] !== 'in' || ! $this->isOurAccount($data['accountNumber'])) {
            $transaction->update(['status' => BankTransactionStatus::Ignored]);

            return;
        }

        $reference = $this->transferCode($data);
        $payment = $reference === null ? null : Payment::query()
            ->where('provider', 'bank_transfer')
            ->where('provider_reference', $reference)
            ->where('status', PaymentStatus::Pending)
            ->first();

        if ($payment === null) {
            return;
        }

        if ($data['transferAmount'] < $payment->amount) {
            $transaction->update(['status' => BankTransactionStatus::Underpaid, 'payment_id' => $payment->id]);

            return;
        }

        $payment->markAsPaid(['bank_transaction_id' => $transaction->id, 'sepay_id' => $data['id']]);

        $transaction->update([
            'status' => BankTransactionStatus::Matched,
            'payment_id' => $payment->id,
        ]);
    }

    /**
     * Whether the money went into the account the learners are told to pay. Accepts any account while none is configured.
     */
    private function isOurAccount(string $accountNumber): bool
    {
        $ours = config('payment.bank.account_number');

        return blank($ours) || $ours === $accountNumber;
    }

    /**
     * The transfer code of the payment: SePay's own `code` when it found one, else looked up in the transfer content,
     * whatever its case. Banks may put other words around it.
     *
     * @param  array<string, mixed>  $data
     */
    private function transferCode(array $data): ?string
    {
        $pattern = '/'.preg_quote(config('payment.transfer_prefix'), '/').'[A-Z0-9]{8}/i';

        foreach ([$data['code'] ?? null, $data['content'] ?? null] as $text) {
            if (filled($text) && preg_match($pattern, $text, $match)) {
                return strtoupper($match[0]);
            }
        }

        return null;
    }
}
