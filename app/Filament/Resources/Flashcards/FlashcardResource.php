<?php

namespace App\Filament\Resources\Flashcards;

use App\Filament\Resources\Flashcards\Pages\CreateFlashcard;
use App\Filament\Resources\Flashcards\Pages\EditFlashcard;
use App\Filament\Resources\Flashcards\Pages\ListFlashcards;
use App\Filament\Resources\Flashcards\Schemas\FlashcardForm;
use App\Filament\Resources\Flashcards\Tables\FlashcardsTable;
use App\Models\Flashcard;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class FlashcardResource extends Resource
{
    protected static ?string $model = Flashcard::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static string|UnitEnum|null $navigationGroup = 'Vocabulary';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return FlashcardForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FlashcardsTable::configure($table);
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
            'index' => ListFlashcards::route('/'),
            'create' => CreateFlashcard::route('/create'),
            'edit' => EditFlashcard::route('/{record}/edit'),
        ];
    }
}
