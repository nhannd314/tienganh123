<?php

namespace App\Filament\Resources\SentenceSets\Pages;

use App\Filament\Resources\SentenceSets\SentenceSetResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSentenceSets extends ListRecords
{
    protected static string $resource = SentenceSetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
