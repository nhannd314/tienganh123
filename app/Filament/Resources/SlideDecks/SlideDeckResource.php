<?php

namespace App\Filament\Resources\SlideDecks;

use App\Filament\Resources\SlideDecks\Pages\ListSlideDecks;
use App\Filament\Resources\SlideDecks\Pages\ViewSlideDeck;
use App\Filament\Resources\SlideDecks\Schemas\SlideDeckInfolist;
use App\Filament\Resources\SlideDecks\Tables\SlideDecksTable;
use App\Models\SlideDeck;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * The slideshows members made (see App\Http\Controllers\SlideDeckController): admins look at them and delete the ones
 * that should not be shared. They are made and edited by their members only.
 */
class SlideDeckResource extends Resource
{
    protected static ?string $model = SlideDeck::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPresentationChartBar;

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'Slides';

    protected static ?string $recordTitleAttribute = 'title';

    public static function infolist(Schema $schema): Schema
    {
        return SlideDeckInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SlideDecksTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSlideDecks::route('/'),
            'view' => ViewSlideDeck::route('/{record}'),
        ];
    }
}
