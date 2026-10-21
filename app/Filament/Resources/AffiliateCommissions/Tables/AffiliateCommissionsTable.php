<?php

namespace App\Filament\Resources\AffiliateCommissions\Tables;

use App\Enums\AffiliateCommissionStatus;
use App\Models\AffiliateCommission;
use App\Models\AffiliatePayout;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AffiliateCommissionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['affiliate', 'referredUser']))
            ->columns([
                TextColumn::make('created_at')
                    ->label('Tạo lúc')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('affiliate.name')
                    ->label('Affiliate')
                    ->description(fn (AffiliateCommission $record): ?string => $record->affiliate?->email)
                    ->searchable(['name', 'email']),
                TextColumn::make('referredUser.name')
                    ->label('Người được giới thiệu')
                    ->searchable(['name', 'email']),
                TextColumn::make('payment_amount')
                    ->label('Thanh toán')
                    ->formatStateUsing(fn (int $state): string => self::money($state))
                    ->sortable(),
                TextColumn::make('rate')
                    ->label('Tỷ lệ')
                    ->suffix('%'),
                TextColumn::make('amount')
                    ->label('Hoa hồng')
                    ->formatStateUsing(fn (int $state): string => self::money($state))
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Trạng thái')
                    ->options(AffiliateCommissionStatus::class),
                SelectFilter::make('affiliate')
                    ->label('Affiliate')
                    ->relationship('affiliate', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('Duyệt')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (AffiliateCommission $record): bool => $record->status === AffiliateCommissionStatus::Pending)
                    ->action(fn (AffiliateCommission $record) => $record->update([
                        'status' => AffiliateCommissionStatus::Approved,
                        'approved_at' => now(),
                    ])),
                Action::make('reject')
                    ->label('Từ chối')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (AffiliateCommission $record): bool => in_array($record->status, [AffiliateCommissionStatus::Pending, AffiliateCommissionStatus::Approved], true) && $record->affiliate_payout_id === null)
                    ->action(fn (AffiliateCommission $record) => $record->update(['status' => AffiliateCommissionStatus::Rejected])),
                Action::make('openPayout')
                    ->label('Tạo đợt chi trả')
                    ->icon('heroicon-o-banknotes')
                    ->requiresConfirmation()
                    ->modalDescription('Gom mọi hoa hồng đã duyệt của affiliate này vào một đợt chi trả.')
                    ->visible(fn (AffiliateCommission $record): bool => $record->status === AffiliateCommissionStatus::Approved && $record->affiliate_payout_id === null)
                    ->action(function (AffiliateCommission $record): void {
                        $payout = AffiliatePayout::openFor($record->affiliate);

                        Notification::make()
                            ->title($payout === null ? 'Không có hoa hồng nào để chi trả' : 'Đã tạo đợt chi trả '.self::money($payout->amount))
                            ->color($payout === null ? 'warning' : 'success')
                            ->send();
                    }),
            ]);
    }

    public static function money(int $amount): string
    {
        return number_format($amount, 0, ',', '.').' ₫';
    }
}
