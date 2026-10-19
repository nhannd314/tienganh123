<?php

namespace App\Filament\Resources\Courses\Schemas;

use App\Models\Course;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class CourseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Get $get, Set $set, ?string $state, string $operation): void {
                        if ($operation === 'create' && blank($get('slug'))) {
                            $set('slug', Str::slug($state ?? ''));
                        }
                    }),
                TextInput::make('slug')
                    ->maxLength(255)
                    ->alphaDash()
                    ->unique(ignoreRecord: true)
                    ->helperText('Leave empty to generate it from the title.'),
                Textarea::make('description')
                    ->rows(4)
                    ->columnSpanFull(),
                FileUpload::make('thumbnail')
                    ->image()
                    ->imageEditor()
                    ->disk('public')
                    ->directory('courses')
                    ->visibility('public')
                    ->maxSize(2048)
                    ->helperText('Shown on the home page card and beside the course description.')
                    ->columnSpanFull(),
                TextInput::make('monthly_price')
                    ->required()
                    ->numeric()
                    ->integer()
                    ->minValue(0)
                    ->default(0)
                    ->suffix('₫')
                    ->helperText('Price of one month of this course in VND. 0 makes the course free.'),
                TextInput::make('regular_price')
                    ->numeric()
                    ->integer()
                    ->minValue(0)
                    ->suffix('₫')
                    ->gt('monthly_price')
                    ->helperText('Regular price of one month, shown struck through. The sale percentage is worked out from it and the monthly price. Leave empty for no sale.'),
                Textarea::make('unit_titles')
                    ->label('Units')
                    ->rows(8)
                    ->columnSpanFull()
                    ->helperText('One unit per line, in order. Shown only while the course has no units.')
                    ->visible(fn (?Course $record): bool => ! $record?->units()->exists())
                    ->saveRelationshipsUsing(function (Course $record, ?string $state): void {
                        $titles = collect(preg_split('/\R/', (string) $state))
                            ->map(fn (string $title): string => trim($title))
                            ->filter()
                            ->unique()
                            ->values();

                        foreach ($titles as $index => $title) {
                            $record->units()->create(['title' => $title, 'sort_order' => $index + 1]);
                        }

                    })
                    ->dehydrated(false),
                TextInput::make('unit_count')
                    ->numeric()
                    ->integer()
                    ->minValue(0)
                    ->maxValue(65535)
                    ->helperText('Shown on the course page. Leave empty to count the units.'),
                TextInput::make('lesson_count')
                    ->numeric()
                    ->integer()
                    ->minValue(0)
                    ->maxValue(65535)
                    ->helperText('Shown on the course page. Leave empty to count the published lessons.'),
                Select::make('level')
                    ->options([
                        'Cơ bản' => 'Cơ bản',
                        'Sơ cấp' => 'Sơ cấp',
                        'Trung cấp' => 'Trung cấp',
                        'Nâng cao' => 'Nâng cao',
                    ])
                    ->helperText('Shown on the course page.'),
                Select::make('age_groups')
                    ->multiple()
                    ->options([
                        'Mầm non' => 'Mầm non',
                        'Tiểu học' => 'Tiểu học',
                        'THCS' => 'THCS',
                        'THPT' => 'THPT',
                        'Người lớn' => 'Người lớn',
                    ]),
                TextInput::make('sort_order')
                    ->required()
                    ->numeric()
                    ->integer()
                    ->minValue(0)
                    ->default(0),
                Toggle::make('is_published')
                    ->default(false),
            ]);
    }
}
