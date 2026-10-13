<?php

namespace App\Filament\Resources\FlashcardSets;

use App\Filament\Resources\FlashcardSets\Pages\CreateFlashcardSet;
use App\Filament\Resources\FlashcardSets\Pages\EditFlashcardSet;
use App\Filament\Resources\FlashcardSets\Pages\ListFlashcardSets;
use App\Filament\Resources\FlashcardSets\RelationManagers\FlashcardsRelationManager;
use App\Filament\Resources\FlashcardSets\Schemas\FlashcardSetForm;
use App\Filament\Resources\FlashcardSets\Tables\FlashcardSetsTable;
use App\Models\FlashcardSet;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class FlashcardSetResource extends Resource
{
    protected static ?string $model = FlashcardSet::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquare2Stack;

    protected static string|UnitEnum|null $navigationGroup = 'Vocabulary';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return FlashcardSetForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FlashcardSetsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            FlashcardsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFlashcardSets::route('/'),
            'create' => CreateFlashcardSet::route('/create'),
            'edit' => EditFlashcardSet::route('/{record}/edit'),
        ];
    }
}
