<?php

namespace App\Filament\Resources\WordScenes\Pages;

use App\Filament\Resources\WordScenes\WordSceneResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWordScenes extends ListRecords
{
    protected static string $resource = WordSceneResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
