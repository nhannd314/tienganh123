<?php

namespace App\Filament\Resources\WordScenes\Pages;

use App\Filament\Resources\WordScenes\Schemas\WordSceneForm;
use App\Filament\Resources\WordScenes\WordSceneResource;
use Filament\Resources\Pages\CreateRecord;

class CreateWordScene extends CreateRecord
{
    protected static string $resource = WordSceneResource::class;

    protected function afterCreate(): void
    {
        // A scene saved without a picture gets one drawn by OpenAI.
        if (blank($this->getRecord()->image)) {
            WordSceneForm::queueImage($this->getRecord());
        }
    }
}
