<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
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
                    ->maxLength(20),
                TextInput::make('password')
                    ->password()
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
                    ->maxLength(255),
                TextInput::make('parent_phone')
                    ->tel()
                    ->maxLength(20),
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
