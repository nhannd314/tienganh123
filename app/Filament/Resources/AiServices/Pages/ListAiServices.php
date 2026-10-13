<?php

namespace App\Filament\Resources\AiServices\Pages;

use App\Filament\Resources\AiServices\AiServiceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAiServices extends ListRecords
{
    protected static string $resource = AiServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
