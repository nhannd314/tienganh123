<?php

namespace App\Filament\Resources\SentenceSets\Pages;

use App\Filament\Resources\SentenceSets\SentenceSetResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSentenceSet extends EditRecord
{
    protected static string $resource = SentenceSetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
