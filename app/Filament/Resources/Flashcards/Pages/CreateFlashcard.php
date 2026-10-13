<?php

namespace App\Filament\Resources\Flashcards\Pages;

use App\Filament\Resources\Flashcards\FlashcardResource;
use App\Filament\Resources\Flashcards\Schemas\FlashcardForm;
use Filament\Resources\Pages\CreateRecord;

class CreateFlashcard extends CreateRecord
{
    protected static string $resource = FlashcardResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return FlashcardForm::fillMissing($data);
    }

    protected function afterCreate(): void
    {
        FlashcardForm::queueMissingMedia($this->getRecord());
    }
}
