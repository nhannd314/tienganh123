<?php

namespace App\Filament\Resources\Sentences\Pages;

use App\Filament\Resources\Sentences\Schemas\SentenceForm;
use App\Filament\Resources\Sentences\SentenceResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSentence extends CreateRecord
{
    protected static string $resource = SentenceResource::class;

    protected function afterCreate(): void
    {
        SentenceForm::queueMissingAudio($this->getRecord());
    }
}
