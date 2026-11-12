<?php

namespace App\Filament\Resources\SlideDecks\Pages;

use App\Filament\Resources\SlideDecks\SlideDeckResource;
use App\Filament\Resources\SlideDecks\Tables\SlideDecksTable;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\ViewRecord;

class ViewSlideDeck extends ViewRecord
{
    protected static string $resource = SlideDeckResource::class;

    protected function getHeaderActions(): array
    {
        return [
            SlideDecksTable::presentAction(),
            DeleteAction::make(),
        ];
    }
}
