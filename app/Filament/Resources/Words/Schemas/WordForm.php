<?php

namespace App\Filament\Resources\Words\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class WordForm
{
    /**
     * Parts of speech offered for a meaning.
     *
     * @var array<string, string>
     */
    private const PARTS_OF_SPEECH = [
        'noun' => 'Noun',
        'verb' => 'Verb',
        'adjective' => 'Adjective',
        'adverb' => 'Adverb',
        'pronoun' => 'Pronoun',
        'preposition' => 'Preposition',
        'conjunction' => 'Conjunction',
        'interjection' => 'Interjection',
        'determiner' => 'Determiner',
    ];

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('word')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                TextInput::make('ipa_us')
                    ->label('IPA (US)')
                    ->maxLength(255),
                TextInput::make('ipa_uk')
                    ->label('IPA (UK)')
                    ->maxLength(255),
                Repeater::make('meanings')
                    ->schema([
                        Select::make('pos')
                            ->label('Part of speech')
                            ->options(self::PARTS_OF_SPEECH)
                            ->required(),
                        TextInput::make('meaning')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->columns(2)
                    ->minItems(1)
                    ->defaultItems(1)
                    ->addActionLabel('Add meaning')
                    ->reorderable()
                    ->columnSpanFull(),
                Repeater::make('examples')
                    ->schema([
                        TextInput::make('sentence')
                            ->required()
                            ->maxLength(500),
                        TextInput::make('translation')
                            ->maxLength(500),
                        FileUpload::make('audio')
                            ->acceptedFileTypes(['audio/mpeg'])
                            ->disk('public')
                            ->directory('audio/examples')
                            ->visibility('public')
                            ->maxSize(2048)
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->defaultItems(0)
                    ->addActionLabel('Add example')
                    ->reorderable()
                    ->collapsible()
                    ->columnSpanFull(),
                FileUpload::make('image')
                    ->image()
                    ->imageEditor()
                    ->disk('public')
                    ->directory('words')
                    ->visibility('public')
                    ->maxSize(2048)
                    ->columnSpanFull(),
            ]);
    }
}
