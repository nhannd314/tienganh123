<?php

namespace App\Filament\Resources\Vocabularies\Pages;

use App\Filament\Resources\Vocabularies\VocabularyResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListVocabularies extends ListRecords
{
    protected static string $resource = VocabularyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
