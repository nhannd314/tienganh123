<?php

namespace App\Filament\Resources\SentenceSets\RelationManagers;

use App\Filament\Resources\Sentences\Schemas\SentenceForm;
use App\Filament\Resources\Sentences\Tables\SentencesTable;
use App\Filament\Resources\SentenceSets\Schemas\SentenceSetForm;
use App\Models\Sentence;
use Filament\Actions\Action;
use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SentencesRelationManager extends RelationManager
{
    protected static string $relationship = 'sentences';

    protected static ?string $recordTitleAttribute = 'sentence';

    public function form(Schema $schema): Schema
    {
        return SentenceForm::configure($schema, withSentenceSet: false);
    }

    public function table(Table $table): Table
    {
        return SentencesTable::configure($table, withSentenceSet: false)
            ->reorderable('sentence_sentence_set.sort_order')
            ->headerActions([
                Action::make('addSentences')
                    ->label('Add sentences')
                    ->icon(Heroicon::QueueList)
                    ->modalHeading('Add sentences')
                    ->modalSubmitActionLabel('Add')
                    ->schema(SentenceSetForm::sentenceLinesFields())
                    ->action(function (array $data): void {
                        SentenceSetForm::addSentences($this->getOwnerRecord(), $data['sentence_lines'], (bool) ($data['generate_sentence_images'] ?? false));
                    }),
                AttachAction::make()
                    ->preloadRecordSelect()
                    ->recordSelectSearchColumns(['sentence', 'translation']),
            ])
            ->recordActions([
                EditAction::make()
                    ->extraModalFooterActions(fn (EditAction $action): array => [
                        SentenceForm::generateImageAction(fn (): Sentence => $action->getRecord())->cancelParentActions(),
                    ])
                    ->after(fn (Sentence $record) => SentenceForm::queueMissingAudio($record)),
                DetachAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DetachBulkAction::make(),
                ]),
            ]);
    }
}
