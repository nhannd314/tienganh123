<?php

namespace App\Filament\Resources\Flashcards\Pages;

use App\Filament\Resources\Flashcards\FlashcardResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFlashcards extends ListRecords
{
    protected static string $resource = FlashcardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
