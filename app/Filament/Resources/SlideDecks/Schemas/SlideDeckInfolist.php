<?php

namespace App\Filament\Resources\SlideDecks\Schemas;

use App\Models\SlideDeck;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SlideDeckInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Bộ slide')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('title')->label('Tiêu đề'),
                        TextEntry::make('user.name')->label('Người tạo'),
                        TextEntry::make('user.email')->label('Email'),
                        TextEntry::make('code')
                            ->label('Link')
                            ->state(fn (SlideDeck $record): string => $record->shareUrl())
                            ->url(fn (SlideDeck $record): string => $record->shareUrl(), shouldOpenInNewTab: true)
                            ->copyable(),
                        TextEntry::make('created_at')->label('Tạo lúc')->dateTime('d/m/Y H:i'),
                        TextEntry::make('updated_at')->label('Sửa lúc')->dateTime('d/m/Y H:i'),
                    ]),
                Section::make('Các slide')
                    ->schema([
                        RepeatableEntry::make('slides')
                            ->hiddenLabel()
                            ->columns(2)
                            ->schema([
                                ImageEntry::make('image')
                                    ->hiddenLabel()
                                    ->disk('public')
                                    ->imageHeight(160),
                                TextEntry::make('text')
                                    ->label('Nội dung')
                                    ->placeholder('Không có nội dung')
                                    ->prose(),
                            ]),
                    ]),
            ]);
    }
}
