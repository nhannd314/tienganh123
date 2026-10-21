<?php

namespace App\Filament\Resources\AffiliatePayouts\Tables;

use App\Enums\AffiliatePayoutStatus;
use App\Models\AffiliatePayout;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AffiliatePayoutsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('user')->withCount('commissions'))
            ->columns([
                TextColumn::make('created_at')
                    ->label('Tạo lúc')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Affiliate')
                    ->description(fn (AffiliatePayout $record): ?string => $record->user?->email)
                    ->searchable(['name', 'email']),
                TextColumn::make('amount')
                    ->label('Số tiền')
                    ->formatStateUsing(fn (int $state): string => number_format($state, 0, ',', '.').' ₫')
                    ->sortable(),
                TextColumn::make('commissions_count')
                    ->label('Số hoa hồng'),
                TextColumn::make('bank_account_number')
                    ->label('Tài khoản nhận')
                    ->description(fn (AffiliatePayout $record): string => trim("{$record->bank_name} · {$record->bank_account_name}", ' ·'))
                    ->copyable()
                    ->placeholder('Chưa có tài khoản'),
                TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge(),
                TextColumn::make('paid_at')
                    ->label('Chuyển lúc')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Trạng thái')
                    ->options(AffiliatePayoutStatus::class),
            ])
            ->recordActions([
                Action::make('markAsPaid')
                    ->label('Đã chuyển khoản')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->schema([
                        TextInput::make('transfer_reference')
                            ->label('Mã giao dịch')
                            ->maxLength(255),
                    ])
                    ->visible(fn (AffiliatePayout $record): bool => $record->status === AffiliatePayoutStatus::Pending)
                    ->action(function (AffiliatePayout $record, array $data): void {
                        $record->markAsPaid($data['transfer_reference'] ?? null);

                        Notification::make()->title('Đã ghi nhận chuyển khoản')->success()->send();
                    }),
                Action::make('cancel')
                    ->label('Hủy')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalDescription('Các hoa hồng trong đợt này sẽ được trả về danh sách đã duyệt.')
                    ->visible(fn (AffiliatePayout $record): bool => $record->status === AffiliatePayoutStatus::Pending)
                    ->action(fn (AffiliatePayout $record) => $record->cancel()),
            ]);
    }
}
