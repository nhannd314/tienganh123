<?php

namespace App\Filament\Resources\TokenPackages;

use App\Filament\Resources\TokenPackages\Pages\CreateTokenPackage;
use App\Filament\Resources\TokenPackages\Pages\EditTokenPackage;
use App\Filament\Resources\TokenPackages\Pages\ListTokenPackages;
use App\Filament\Resources\TokenPackages\Schemas\TokenPackageForm;
use App\Filament\Resources\TokenPackages\Tables\TokenPackagesTable;
use App\Models\TokenPackage;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class TokenPackageResource extends Resource
{
    protected static ?string $model = TokenPackage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyDollar;

    protected static string|UnitEnum|null $navigationGroup = 'Pricing';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Token packages';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return TokenPackageForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TokenPackagesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTokenPackages::route('/'),
            'create' => CreateTokenPackage::route('/create'),
            'edit' => EditTokenPackage::route('/{record}/edit'),
        ];
    }
}
