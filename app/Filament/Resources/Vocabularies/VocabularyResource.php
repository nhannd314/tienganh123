<?php

namespace App\Filament\Resources\Vocabularies;

use App\Filament\Resources\Vocabularies\Pages\CreateVocabulary;
use App\Filament\Resources\Vocabularies\Pages\EditVocabulary;
use App\Filament\Resources\Vocabularies\Pages\ListVocabularies;
use App\Filament\Resources\Vocabularies\RelationManagers\WordsRelationManager;
use App\Filament\Resources\Vocabularies\Schemas\VocabularyForm;
use App\Filament\Resources\Vocabularies\Tables\VocabulariesTable;
use App\Models\Vocabulary;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class VocabularyResource extends Resource
{
    protected static ?string $model = Vocabulary::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;

    protected static string|UnitEnum|null $navigationGroup = 'Vocabulary';

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return VocabularyForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VocabulariesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            WordsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVocabularies::route('/'),
            'create' => CreateVocabulary::route('/create'),
            'edit' => EditVocabulary::route('/{record}/edit'),
        ];
    }
}
