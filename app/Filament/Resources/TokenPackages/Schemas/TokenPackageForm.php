<?php

namespace App\Filament\Resources\TokenPackages\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class TokenPackageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('tokens')
                    ->required()
                    ->numeric()
                    ->integer()
                    ->minValue(1)
                    ->helperText('Tokens the user receives when buying this pack.'),
                TextInput::make('price')
                    ->required()
                    ->numeric()
                    ->integer()
                    ->minValue(0)
                    ->suffix('₫')
                    ->helperText('Price of the pack in VND.'),
                TextInput::make('sort_order')
                    ->required()
                    ->numeric()
                    ->integer()
                    ->minValue(0)
                    ->default(0),
                Toggle::make('is_active')
                    ->helperText('Inactive packs cannot be bought.')
                    ->default(true),
            ]);
    }
}
