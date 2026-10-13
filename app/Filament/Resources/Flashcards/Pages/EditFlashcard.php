<?php

namespace App\Filament\Resources\Flashcards\Pages;

use App\Filament\Resources\Flashcards\FlashcardResource;
use App\Filament\Resources\Flashcards\Schemas\FlashcardForm;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditFlashcard extends EditRecord
{
    protected static string $resource = FlashcardResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return FlashcardForm::fillMissing($data);
    }

    protected function afterSave(): void
    {
        FlashcardForm::queueMissingMedia($this->getRecord());
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
