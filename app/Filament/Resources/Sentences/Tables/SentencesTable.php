<?php

namespace App\Filament\Resources\Sentences\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SentencesTable
{
    public static function configure(Table $table, bool $withSentenceSet = true): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image')
                    ->disk('public'),
                TextColumn::make('sentence')
                    ->searchable()
                    ->limit(60),
                TextColumn::make('translation')
                    ->label('Meaning')
                    ->placeholder('—')
                    ->searchable()
                    ->limit(60),
                TextColumn::make('sentenceSets.title')
                    ->label('Sentence sets')
                    ->badge()
                    ->searchable()
                    ->hidden(! $withSentenceSet),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('sentenceSets')
                    ->label('Sentence set')
                    ->relationship('sentenceSets', 'title')
                    ->searchable()
                    ->preload()
                    ->hidden(! $withSentenceSet),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
