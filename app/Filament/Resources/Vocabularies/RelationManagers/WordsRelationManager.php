<?php

namespace App\Filament\Resources\Vocabularies\RelationManagers;

use App\Filament\Resources\Vocabularies\Actions\BulkAddWordsAction;
use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class WordsRelationManager extends RelationManager
{
    protected static string $relationship = 'words';

    protected static ?string $recordTitleAttribute = 'word';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('word')
                    ->searchable(),
                TextColumn::make('ipa_us')
                    ->label('IPA (US)')
                    ->placeholder('—'),
            ])
            ->reorderable('vocabulary_word.sort_order')
            ->headerActions([
                BulkAddWordsAction::make(),
                AttachAction::make()
                    ->preloadRecordSelect(),
            ])
            ->recordActions([
                DetachAction::make(),
            ])
            ->toolbarActions([
                DetachBulkAction::make(),
            ]);
    }
}
