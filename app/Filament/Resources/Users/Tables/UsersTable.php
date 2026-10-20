<?php

namespace App\Filament\Resources\Users\Tables;

use App\Enums\Membership;
use App\Models\User;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                ImageColumn::make('avatar')
                    ->disk('public')
                    ->circular(),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->searchable(),
                TextColumn::make('phone')
                    ->searchable(),
                TextColumn::make('membership')
                    ->badge()
                    ->description(fn (User $record): ?string => $record->membership
                        ? ($record->membership_expires_on ? 'Đến '.$record->membership_expires_on->format('d/m/Y') : 'Vĩnh viễn')
                        : null)
                    ->color(fn (User $record): string => $record->isMember() ? 'success' : 'gray')
                    ->sortable(),
                TextColumn::make('grade')
                    ->toggleable(),
                TextColumn::make('school')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('grantMembership')
                        ->label('Thêm gói thành viên')
                        ->icon(Heroicon::OutlinedStar)
                        ->color('success')
                        ->schema([
                            Select::make('membership')
                                ->options(Membership::class)
                                ->default(Membership::Member)
                                ->required(),
                            DatePicker::make('membership_expires_on')
                                ->displayFormat('d/m/Y')
                                ->helperText('Last day of the membership. Leave empty for a membership that never expires.'),
                        ])
                        ->action(function (BulkAction $action, Collection $records, array $data): void {
                            $records->each(fn (User $user) => $user->update($data));
                            $action->success();
                        })
                        ->successNotificationTitle('Đã thêm gói thành viên')
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
