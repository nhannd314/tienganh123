<?php

namespace App\Filament\Resources\Sentences\Pages;

use App\Filament\Resources\Sentences\Schemas\SentenceForm;
use App\Filament\Resources\Sentences\SentenceResource;
use App\Models\Sentence;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSentence extends EditRecord
{
    protected static string $resource = SentenceResource::class;

    protected function afterSave(): void
    {
        SentenceForm::queueMissingAudio($this->getRecord());
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction(),
            $this->getCancelFormAction(),
            SentenceForm::generateImageAction(fn (): Sentence => $this->getRecord()),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
