<?php

namespace App\Filament\Resources\SentenceSets;

use App\Filament\Resources\SentenceSets\Pages\CreateSentenceSet;
use App\Filament\Resources\SentenceSets\Pages\EditSentenceSet;
use App\Filament\Resources\SentenceSets\Pages\ListSentenceSets;
use App\Filament\Resources\SentenceSets\RelationManagers\SentencesRelationManager;
use App\Filament\Resources\SentenceSets\Schemas\SentenceSetForm;
use App\Filament\Resources\SentenceSets\Tables\SentenceSetsTable;
use App\Models\SentenceSet;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class SentenceSetResource extends Resource
{
    protected static ?string $model = SentenceSet::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;

    protected static string|UnitEnum|null $navigationGroup = 'Vocabulary';

    protected static ?int $navigationSort = 6;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return SentenceSetForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SentenceSetsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            SentencesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSentenceSets::route('/'),
            'create' => CreateSentenceSet::route('/create'),
            'edit' => EditSentenceSet::route('/{record}/edit'),
        ];
    }
}
