<?php

namespace App\Filament\Resources\WordScenes\Pages;

use App\Filament\Resources\WordScenes\Schemas\WordSceneForm;
use App\Filament\Resources\WordScenes\WordSceneResource;
use App\Models\WordScene;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditWordScene extends EditRecord
{
    protected static string $resource = WordSceneResource::class;

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction(),
            $this->getCancelFormAction(),
            WordSceneForm::generateImageAction(fn (): WordScene => $this->getRecord()),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
