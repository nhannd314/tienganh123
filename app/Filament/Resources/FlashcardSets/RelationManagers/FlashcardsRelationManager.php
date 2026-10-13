<?php

namespace App\Filament\Resources\FlashcardSets\RelationManagers;

use App\Filament\Resources\Flashcards\Schemas\FlashcardForm;
use App\Models\Flashcard;
use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FlashcardsRelationManager extends RelationManager
{
    protected static string $relationship = 'flashcards';

    protected static ?string $recordTitleAttribute = 'title';

    public function form(Schema $schema): Schema
    {
        return FlashcardForm::configure($schema);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitle(fn ($record): string => $record->display_title ?? '#'.$record->getKey())
            ->columns([
                ImageColumn::make('image')
                    ->disk('public'),
                TextColumn::make('title')
                    ->placeholder('—')
                    ->searchable(),
                TextColumn::make('word.word')
                    ->label('Word')
                    ->placeholder('—')
                    ->searchable(),
            ])
            ->reorderable('flashcard_flashcard_set.sort_order')
            ->headerActions([
                CreateAction::make()
                    ->mutateDataUsing(fn (array $data): array => FlashcardForm::fillMissing($data))
                    ->after(fn (Flashcard $record) => FlashcardForm::queueMissingMedia($record)),
                AttachAction::make()
                    ->preloadRecordSelect(),
            ])
            ->recordActions([
                EditAction::make()
                    ->mutateDataUsing(fn (array $data): array => FlashcardForm::fillMissing($data))
                    ->after(fn (Flashcard $record) => FlashcardForm::queueMissingMedia($record)),
                DetachAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DetachBulkAction::make(),
                ]),
            ]);
    }
}
