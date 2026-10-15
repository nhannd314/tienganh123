<?php

namespace App\Filament\Resources\Payments\Pages;

use App\Enums\PaymentStatus;
use App\Filament\Resources\Payments\PaymentResource;
use App\Models\Course;
use App\Models\Payment;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreatePayment extends CreateRecord
{
    protected static string $resource = PaymentResource::class;

    /**
     * A bank transfer payment with a new transfer code. When the money is already in, it is confirmed at once, like a
     * transfer SePay matched: the learner gets the course.
     *
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $course = Course::findOrFail($data['course_id']);

        $payment = Payment::create([
            'user_id' => $data['user_id'],
            'payable_type' => $course->getMorphClass(),
            'payable_id' => $course->id,
            'amount' => $data['amount'],
            'provider' => 'bank_transfer',
            'provider_reference' => Payment::newTransferReference(),
            'status' => PaymentStatus::Pending,
            'meta' => ['months' => (int) $data['months'], 'created_by' => auth()->id()],
        ]);

        if ($data['is_paid']) {
            $payment->markAsPaid(['confirmed_by' => auth()->id()]);
        }

        return $payment->refresh();
    }
}
