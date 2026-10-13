<?php

namespace App\Filament\Resources\TokenPackages\Pages;

use App\Filament\Resources\TokenPackages\TokenPackageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTokenPackages extends ListRecords
{
    protected static string $resource = TokenPackageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
