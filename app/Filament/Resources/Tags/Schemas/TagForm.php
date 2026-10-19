<?php

namespace App\Filament\Resources\Tags\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class TagForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Get $get, Set $set, ?string $state, string $operation): void {
                        if ($operation === 'create' && blank($get('slug'))) {
                            $set('slug', Str::slug($state ?? ''));
                        }
                    }),
                TextInput::make('slug')
                    ->helperText('Leave empty to generate it from the name.')
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
            ]);
    }
}
