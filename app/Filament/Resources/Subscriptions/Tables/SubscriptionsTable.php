<?php

namespace App\Filament\Resources\Subscriptions\Tables;

use App\Enums\SubscriptionStatus;
use App\Models\Course;
use App\Models\Subscription;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SubscriptionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['user', 'subscribable']))
            ->columns([
                TextColumn::make('user.name')
                    ->label('Khách hàng')
                    ->description(fn (Subscription $record): ?string => $record->user?->email)
                    ->searchable(['name', 'email']),
                TextColumn::make('subscribable')
                    ->label('Khóa học')
                    ->state(fn (Subscription $record): string => $record->subscribable instanceof Course ? $record->subscribable->title : '—'),
                TextColumn::make('duration')
                    ->label('Thời hạn')
                    ->state(fn (Subscription $record): string => ($months = $record->durationInMonths()) === null ? 'Không giới hạn' : "{$months} tháng"),
                TextColumn::make('starts_at')
                    ->label('Từ ngày')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('ends_at')
                    ->label('Ngày hết hạn')
                    ->date('d/m/Y')
                    ->placeholder('Không giới hạn')
                    ->sortable(),
                TextColumn::make('days_remaining')
                    ->label('Còn lại')
                    ->state(fn (Subscription $record): string => self::remainingLabel($record))
                    ->color(fn (Subscription $record): ?string => $record->currentStatus() === SubscriptionStatus::ExpiringSoon ? 'warning' : null)
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderBy('ends_at', $direction)),
                TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge()
                    ->state(fn (Subscription $record): string => self::statusLabel($record->currentStatus()))
                    ->color(fn (Subscription $record): string => match ($record->currentStatus()) {
                        SubscriptionStatus::Upcoming => 'info',
                        SubscriptionStatus::Active => 'success',
                        SubscriptionStatus::ExpiringSoon => 'warning',
                        SubscriptionStatus::Expired => 'gray',
                        SubscriptionStatus::Cancelled => 'danger',
                    }),
                TextColumn::make('price_paid')
                    ->label('Đã trả')
                    ->formatStateUsing(fn (int $state): string => number_format($state, 0, ',', '.').' ₫')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Trạng thái')
                    ->options(collect(SubscriptionStatus::cases())->mapWithKeys(fn (SubscriptionStatus $status): array => [$status->value => self::statusLabel($status)])->all())
                    ->query(fn (Builder $query, array $data): Builder => filled($data['value'])
                        ? $query->withStatus(SubscriptionStatus::from($data['value']))
                        : $query),
                SelectFilter::make('course')
                    ->label('Khóa học')
                    ->options(fn (): array => Course::query()->orderBy('title')->pluck('title', 'id')->all())
                    ->searchable()
                    ->query(fn (Builder $query, array $data): Builder => filled($data['value'])
                        ? $query->where('subscribable_type', (new Course)->getMorphClass())->where('subscribable_id', $data['value'])
                        : $query),
            ]);
    }

    public static function statusLabel(SubscriptionStatus $status): string
    {
        return match ($status) {
            SubscriptionStatus::Upcoming => 'Chưa bắt đầu',
            SubscriptionStatus::Active => 'Đang học',
            SubscriptionStatus::ExpiringSoon => 'Sắp hết hạn',
            SubscriptionStatus::Expired => 'Đã hết hạn',
            SubscriptionStatus::Cancelled => 'Đã hủy',
        };
    }

    /**
     * How long the subscription still runs; a cancelled or finished one has nothing left to count.
     */
    public static function remainingLabel(Subscription $subscription): string
    {
        return match ($subscription->currentStatus()) {
            SubscriptionStatus::Cancelled, SubscriptionStatus::Expired => '—',
            default => ($days = $subscription->daysRemaining()) === null ? 'Không giới hạn' : "Còn {$days} ngày",
        };
    }
}
