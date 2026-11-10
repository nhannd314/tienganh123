<?php

namespace App\Filament\Resources\WordScenes;

use App\Filament\Resources\WordScenes\Pages\ListWordScenes;
use App\Filament\Resources\WordScenes\Tables\WordScenesTable;
use App\Models\WordScene;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * The list of the word scenes only: a scene is created and edited (picture, rectangles and words) on the pages of
 * App\Http\Controllers\Admin\WordScenesController.
 */
class WordSceneResource extends Resource
{
    protected static ?string $model = WordScene::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static string|UnitEnum|null $navigationGroup = 'Vocabulary';

    protected static ?int $navigationSort = 6;

    protected static ?string $recordTitleAttribute = 'title';

    public static function table(Table $table): Table
    {
        return WordScenesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWordScenes::route('/'),
        ];
    }
}
