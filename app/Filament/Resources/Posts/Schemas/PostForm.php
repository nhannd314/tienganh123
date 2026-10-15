<?php

namespace App\Filament\Resources\Posts\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PostForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                TextInput::make('slug')
                    ->helperText('Leave empty to generate it from the title.')
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Select::make('category_id')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),
                Toggle::make('is_published')
                    ->default(false),
                DateTimePicker::make('published_at')
                    ->helperText('The post is shown from this date on; leave empty to show it as soon as it is published.'),
                Textarea::make('excerpt')
                    ->rows(3)
                    ->columnSpanFull(),
                FileUpload::make('thumbnail')
                    ->image()
                    ->imageEditor()
                    ->disk('public')
                    ->directory('posts')
                    ->visibility('public')
                    ->maxSize(2048)
                    ->columnSpanFull(),
                RichEditor::make('content')
                    ->fileAttachmentsDisk('public')
                    ->fileAttachmentsDirectory('posts/content')
                    ->fileAttachmentsVisibility('public')
                    ->fileAttachmentsAcceptedFileTypes(['image/png', 'image/jpeg', 'image/webp', 'image/gif'])
                    ->fileAttachmentsMaxSize(5120)
                    ->columnSpanFull(),
            ]);
    }
}
