<?php

namespace App\Filament\Resources\AiServices\Pages;

use App\Filament\Resources\AiServices\AiServiceResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAiService extends EditRecord
{
    protected static string $resource = AiServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
