<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\Membership;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->disabledOn('edit'),
                TextInput::make('phone')
                    ->tel()
                    ->autocomplete(false)
                    ->maxLength(20),
                // "new-password" keeps the browser from filling in the admin's own saved login here (and in the field before it).
                TextInput::make('password')
                    ->password()
                    ->autocomplete('new-password')
                    ->revealable()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->minLength(8)
                    ->helperText('Leave empty to keep the current password.'),
                DatePicker::make('birthday'),
                TextInput::make('grade')
                    ->maxLength(50),
                TextInput::make('school')
                    ->maxLength(255),
                TextInput::make('parent_email')
                    ->email()
                    ->autocomplete(false)
                    ->maxLength(255),
                TextInput::make('parent_phone')
                    ->tel()
                    ->autocomplete(false)
                    ->maxLength(20),
                Select::make('membership')
                    ->options(Membership::class)
                    ->placeholder('Không')
                    ->helperText('A member can learn every lesson of every course.'),
                DatePicker::make('membership_expires_on')
                    ->displayFormat('d/m/Y')
                    ->helperText('Last day of the membership. Leave empty for a membership that never expires.'),
                TextInput::make('affiliate_commission_rate')
                    ->label('Affiliate commission rate')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(100)
                    ->suffix('%')
                    ->placeholder(fn (): string => config('affiliate.commission_rate').' (default)')
                    ->helperText('Leave empty to use the default rate.'),
                TextInput::make('affiliate_bank_name')
                    ->label('Affiliate bank')
                    ->maxLength(255),
                TextInput::make('affiliate_bank_account_number')
                    ->label('Affiliate bank account number')
                    ->maxLength(50),
                TextInput::make('affiliate_bank_account_name')
                    ->label('Affiliate bank account name')
                    ->maxLength(255),
                FileUpload::make('avatar')
                    ->image()
                    ->avatar()
                    ->disk('public')
                    ->directory('avatars')
                    ->visibility('public')
                    ->maxSize(2048),
            ]);
    }
}
