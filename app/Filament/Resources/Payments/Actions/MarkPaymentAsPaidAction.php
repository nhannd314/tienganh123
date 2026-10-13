<?php

namespace App\Filament\Resources\Payments\Actions;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

/**
 * Confirms a pending payment by hand, e.g. a transfer that arrived with a wrong code. It opens what was bought, like a
 * transfer SePay matched.
 */
class MarkPaymentAsPaidAction
{
    public static function make(): Action
    {
        return Action::make('markAsPaid')
            ->label('Xác nhận đã thanh toán')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->requiresConfirmation()
            ->modalDescription('Khách sẽ được mở khóa học hoặc gói thành viên tương ứng.')
            ->visible(fn (Payment $record): bool => $record->status === PaymentStatus::Pending)
            ->action(function (Payment $record): void {
                $record->markAsPaid(['confirmed_by' => auth()->id()]);

                Notification::make()->title('Đã xác nhận thanh toán')->success()->send();
            });
    }
}
