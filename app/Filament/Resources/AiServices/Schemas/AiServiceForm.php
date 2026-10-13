<?php

namespace App\Filament\Resources\AiServices\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class AiServiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('key')
                    ->required()
                    ->maxLength(255)
                    ->alphaDash()
                    ->unique(ignoreRecord: true)
                    ->disabledOn('edit')
                    ->dehydrated(fn (string $operation): bool => $operation === 'create')
                    ->helperText('Identifier the code uses to find the service, e.g. pronunciation_scoring. It cannot be changed once created.'),
                TextInput::make('tokens_per_use')
                    ->required()
                    ->numeric()
                    ->integer()
                    ->minValue(0)
                    ->helperText('Tokens taken from the user each time the service is used.'),
                Toggle::make('is_active')
                    ->helperText('A switched-off service cannot be used.')
                    ->default(true),
            ]);
    }
}
