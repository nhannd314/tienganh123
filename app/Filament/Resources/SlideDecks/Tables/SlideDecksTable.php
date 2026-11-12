<?php

namespace App\Filament\Resources\SlideDecks\Tables;

use App\Models\SlideDeck;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SlideDecksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['user', 'slides'])->withCount('slides'))
            ->columns([
                ImageColumn::make('slides.image')
                    ->label('Ảnh')
                    ->disk('public')
                    ->stacked()
                    ->limit(3),
                TextColumn::make('title')
                    ->label('Tiêu đề')
                    ->searchable(),
                TextColumn::make('user.name')
                    ->label('Người tạo')
                    ->description(fn (SlideDeck $record): ?string => $record->user?->email)
                    ->searchable(['name', 'email']),
                TextColumn::make('slides_count')
                    ->label('Số slide')
                    ->sortable(),
                TextColumn::make('code')
                    ->label('Link')
                    ->formatStateUsing(fn (SlideDeck $record): string => $record->shareUrl())
                    ->copyable()
                    ->copyableState(fn (SlideDeck $record): string => $record->shareUrl())
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('Tạo lúc')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->recordActions([
                ViewAction::make(),
                self::presentAction(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * Open the slideshow full screen by its short link, in a new tab.
     */
    public static function presentAction(): Action
    {
        return Action::make('present')
            ->label('Trình chiếu')
            ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
            ->url(fn (SlideDeck $record): string => $record->shareUrl())
            ->openUrlInNewTab();
    }
}
