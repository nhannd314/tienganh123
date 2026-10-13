<?php

namespace App\Filament\Resources\Payments\Schemas;

use App\Enums\PaymentStatus;
use App\Filament\Resources\Payments\Tables\PaymentsTable;
use App\Models\Payment;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PaymentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Thanh toán')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('user.name')->label('Khách hàng'),
                        TextEntry::make('user.email')->label('Email'),
                        TextEntry::make('payable')
                            ->label('Mua')
                            ->state(fn (Payment $record): string => PaymentsTable::payableLabel($record)),
                        TextEntry::make('amount')
                            ->label('Số tiền')
                            ->formatStateUsing(fn (int $state): string => number_format($state, 0, ',', '.').' ₫'),
                        TextEntry::make('provider_reference')->label('Nội dung chuyển khoản')->copyable(),
                        TextEntry::make('status')
                            ->label('Trạng thái')
                            ->badge()
                            ->formatStateUsing(fn (PaymentStatus $state): string => PaymentsTable::statusLabel($state)),
                        TextEntry::make('created_at')->label('Tạo lúc')->dateTime('d/m/Y H:i'),
                        TextEntry::make('paid_at')->label('Thanh toán lúc')->dateTime('d/m/Y H:i')->placeholder('—'),
                        TextEntry::make('meta.confirmed_by')->label('Xác nhận thủ công bởi (ID admin)')->placeholder('—'),
                        TextEntry::make('meta.sepay_id')->label('Mã giao dịch SePay')->placeholder('—'),
                    ]),
            ]);
    }
}
