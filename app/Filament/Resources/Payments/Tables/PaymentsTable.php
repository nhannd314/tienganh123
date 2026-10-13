<?php

namespace App\Filament\Resources\Payments\Tables;

use App\Enums\PaymentStatus;
use App\Filament\Resources\Payments\Actions\MarkPaymentAsPaidAction;
use App\Models\Course;
use App\Models\Payment;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PaymentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['user', 'payable']))
            ->columns([
                TextColumn::make('created_at')
                    ->label('Tạo lúc')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Khách hàng')
                    ->description(fn (Payment $record): ?string => $record->user?->email)
                    ->searchable(['name', 'email']),
                TextColumn::make('payable')
                    ->label('Mua')
                    ->state(fn (Payment $record): string => self::payableLabel($record)),
                TextColumn::make('amount')
                    ->label('Số tiền')
                    ->formatStateUsing(fn (int $state): string => number_format($state, 0, ',', '.').' ₫')
                    ->sortable(),
                TextColumn::make('provider_reference')
                    ->label('Nội dung chuyển khoản')
                    ->copyable()
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge()
                    ->formatStateUsing(fn (PaymentStatus $state): string => self::statusLabel($state))
                    ->color(fn (PaymentStatus $state): string => match ($state) {
                        PaymentStatus::Paid => 'success',
                        PaymentStatus::Pending => 'warning',
                        PaymentStatus::Failed => 'danger',
                        PaymentStatus::Refunded => 'gray',
                    }),
                TextColumn::make('paid_at')
                    ->label('Thanh toán lúc')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Trạng thái')
                    ->options(collect(PaymentStatus::cases())->mapWithKeys(fn (PaymentStatus $status): array => [$status->value => self::statusLabel($status)])->all()),
            ])
            ->recordActions([
                ViewAction::make(),
                MarkPaymentAsPaidAction::make(),
            ]);
    }

    public static function statusLabel(PaymentStatus $status): string
    {
        return match ($status) {
            PaymentStatus::Pending => 'Chờ thanh toán',
            PaymentStatus::Paid => 'Đã thanh toán',
            PaymentStatus::Failed => 'Thất bại',
            PaymentStatus::Refunded => 'Đã hoàn tiền',
        };
    }

    public static function payableLabel(Payment $payment): string
    {
        return match (true) {
            $payment->payable instanceof Course => 'Khóa học: '.$payment->payable->title,
            default => '—',
        };
    }
}
