<?php

namespace App\Filament\Resources\FlashcardSets\Pages;

use App\Filament\Resources\FlashcardSets\FlashcardSetResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditFlashcardSet extends EditRecord
{
    protected static string $resource = FlashcardSetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
