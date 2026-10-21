<?php

namespace App\Filament\Resources\AffiliateCommissions;

use App\Filament\Resources\AffiliateCommissions\Pages\ListAffiliateCommissions;
use App\Filament\Resources\AffiliateCommissions\Tables\AffiliateCommissionsTable;
use App\Models\AffiliateCommission;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class AffiliateCommissionResource extends Resource
{
    protected static ?string $model = AffiliateCommission::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;

    protected static string|UnitEnum|null $navigationGroup = 'Affiliate';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Commissions';

    protected static ?string $modelLabel = 'commission';

    protected static ?string $pluralModelLabel = 'commissions';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return AffiliateCommissionsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAffiliateCommissions::route('/'),
        ];
    }
}
