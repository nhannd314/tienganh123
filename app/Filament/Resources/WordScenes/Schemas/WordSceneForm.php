<?php

namespace App\Filament\Resources\WordScenes\Schemas;

use App\Jobs\GenerateWordSceneImage;
use App\Models\Word;
use App\Models\WordScene;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;

class WordSceneForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                FileUpload::make('image')
                    ->image()
                    ->disk('public')
                    ->directory('word-scenes')
                    ->visibility('public')
                    ->helperText('Leave empty to have a cartoon picture of all the words generated with OpenAI after saving.'),
                View::make('filament.forms.components.word-scene-editor')
                    // The repeater below lives at `data.items` on the resource pages.
                    ->viewData(fn (?Model $record): array => [
                        'statePath' => 'data.items',
                        'imageUrl' => $record instanceof WordScene ? $record->image_url : null,
                    ])
                    ->columnSpanFull(),
                Repeater::make('items')
                    ->label('Words')
                    ->relationship('items')
                    ->orderColumn('sort_order')
                    ->addActionLabel('Add word')
                    ->itemLabel(fn (array $state): ?string => $state['label'] ?? null)
                    ->collapsible()
                    ->minItems(1)
                    ->columnSpanFull()
                    ->schema([
                        Select::make('word_id')
                            ->label('Word')
                            ->required()
                            ->distinct()
                            ->searchable()
                            ->live()
                            ->getSearchResultsUsing(fn (string $search): array => Word::query()
                                ->where('word', 'like', "%{$search}%")
                                ->orderBy('word')
                                ->limit(50)
                                ->pluck('word', 'id')
                                ->all())
                            ->getOptionLabelUsing(fn (mixed $value): ?string => Word::find($value)?->word)
                            ->afterStateUpdated(fn (Set $set, mixed $state): mixed => $set('label', Word::find($state)?->word)),
                        Hidden::make('label')
                            ->dehydrated(false)
                            ->afterStateHydrated(fn (Set $set, Get $get): mixed => $set('label', Word::find($get('word_id'))?->word)),
                        Grid::make(4)
                            ->schema(array_map(
                                fn (string $name): TextInput => TextInput::make($name)
                                    ->label(ucfirst($name))
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(1)
                                    ->step(0.0001)
                                    ->helperText('0 to 1, of the picture size.'),
                                ['x', 'y', 'width', 'height'],
                            )),
                    ]),
            ]);
    }

    /**
     * The button that draws the picture of the saved scene with OpenAI, to sit among the buttons of an edit form.
     */
    public static function generateImageAction(Closure $wordScene): Action
    {
        return Action::make('generateImage')
            ->label('Tạo ảnh AI')
            ->icon(Heroicon::Sparkles)
            ->color('gray')
            ->requiresConfirmation()
            ->modalDescription('The picture is replaced and the rectangles drawn on the old one are cleared.')
            ->action(fn () => self::queueImage($wordScene()));
    }

    /**
     * Queue the OpenAI picture of all the words of a saved scene, which replaces the one it has; it appears on the scene once the job has run.
     */
    public static function queueImage(WordScene $wordScene): void
    {
        if (blank(config('services.openai.key'))) {
            Notification::make()
                ->title('The picture was not generated')
                ->body('OPENAI_API_KEY is not set. Upload an image instead.')
                ->warning()
                ->send();

            return;
        }

        GenerateWordSceneImage::dispatch($wordScene);

        Notification::make()
            ->title('The picture is being generated')
            ->body('Reload the page in a moment, then draw the rectangle of each word.')
            ->success()
            ->send();
    }
}
