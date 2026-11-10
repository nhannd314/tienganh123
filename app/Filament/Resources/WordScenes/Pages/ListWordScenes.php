<?php

namespace App\Filament\Resources\WordScenes\Pages;

use App\Filament\Resources\WordScenes\WordSceneResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListWordScenes extends ListRecords
{
    protected static string $resource = WordSceneResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create')
                ->label('New word scene')
                ->icon(Heroicon::Plus)
                ->url(route('admin.word-scenes.create')),
        ];
    }
}
