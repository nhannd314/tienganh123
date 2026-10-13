<?php

namespace App\Filament\Resources\Vocabularies\Pages;

use App\Filament\Resources\Vocabularies\VocabularyResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditVocabulary extends EditRecord
{
    protected static string $resource = VocabularyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
