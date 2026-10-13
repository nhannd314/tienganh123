<?php

namespace App\Filament\Resources\AiServices;

use App\Filament\Resources\AiServices\Pages\CreateAiService;
use App\Filament\Resources\AiServices\Pages\EditAiService;
use App\Filament\Resources\AiServices\Pages\ListAiServices;
use App\Filament\Resources\AiServices\Schemas\AiServiceForm;
use App\Filament\Resources\AiServices\Tables\AiServicesTable;
use App\Models\AiService;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class AiServiceResource extends Resource
{
    protected static ?string $model = AiService::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static string|UnitEnum|null $navigationGroup = 'Pricing';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'AI services';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return AiServiceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AiServicesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAiServices::route('/'),
            'create' => CreateAiService::route('/create'),
            'edit' => EditAiService::route('/{record}/edit'),
        ];
    }
}
