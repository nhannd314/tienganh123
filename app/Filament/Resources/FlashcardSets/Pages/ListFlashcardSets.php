<?php

namespace App\Filament\Resources\FlashcardSets\Pages;

use App\Filament\Resources\FlashcardSets\FlashcardSetResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFlashcardSets extends ListRecords
{
    protected static string $resource = FlashcardSetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
