<?php

namespace App\Filament\Resources\WordScenes;

use App\Filament\Resources\WordScenes\Pages\CreateWordScene;
use App\Filament\Resources\WordScenes\Pages\EditWordScene;
use App\Filament\Resources\WordScenes\Pages\ListWordScenes;
use App\Filament\Resources\WordScenes\Schemas\WordSceneForm;
use App\Filament\Resources\WordScenes\Tables\WordScenesTable;
use App\Models\WordScene;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class WordSceneResource extends Resource
{
    protected static ?string $model = WordScene::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static string|UnitEnum|null $navigationGroup = 'Vocabulary';

    protected static ?int $navigationSort = 6;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return WordSceneForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WordScenesTable::configure($table);
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
            'index' => ListWordScenes::route('/'),
            'create' => CreateWordScene::route('/create'),
            'edit' => EditWordScene::route('/{record}/edit'),
        ];
    }
}
