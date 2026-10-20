<?php

namespace App\Filament\Resources\FlashcardSets\Schemas;

use App\Filament\Resources\Flashcards\Schemas\FlashcardForm;
use App\Jobs\GenerateFlashcardAudio;
use App\Jobs\GenerateFlashcardImage;
use App\Models\Flashcard;
use App\Models\FlashcardSet;
use App\Services\AzureTextToSpeech;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class FlashcardSetForm
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
                    ->columnSpanFull(),
                Select::make('sentence_set_id')
                    ->label('Sentence set')
                    ->relationship('sentenceSet', 'title')
                    ->searchable()
                    ->preload()
                    ->columnSpanFull(),
                Select::make('tags')
                    ->relationship('tags', 'name')
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->columnSpanFull(),
                TextInput::make('sort_order')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }

    /**
     * Cut the lines of a textarea into flashcard titles, skipping blank lines and repeats.
     *
     * @return list<string>
     */
    public static function parseLines(?string $text): array
    {
        return collect(preg_split('/\R/u', (string) $text))
            ->map(fn (string $line): string => trim($line))
            ->filter()
            ->unique(fn (string $title): string => mb_strtolower($title))
            ->values()
            ->all();
    }

    /**
     * Add a flashcard per line to the end of the set. A flashcard already titled like the line is reused; a new one is
     * linked to the word spelled like it and gets its OpenAI illustration and Azure voice queued.
     */
    public static function addFlashcards(FlashcardSet $set, ?string $text): void
    {
        $attachedIds = $set->flashcards()->pluck('flashcards.id')->all();
        $sortOrder = (int) $set->flashcards()->max('flashcard_flashcard_set.sort_order');
        $added = collect();

        foreach (self::parseLines($text) as $title) {
            $flashcard = Flashcard::query()->whereRaw('lower(title) = ?', [mb_strtolower($title)])->first()
                ?? Flashcard::create(FlashcardForm::fillMissing(['title' => $title]));

            if (! in_array($flashcard->id, $attachedIds, true)) {
                $set->flashcards()->attach($flashcard, ['sort_order' => ++$sortOrder]);
                $attachedIds[] = $flashcard->id;
            }

            if ($flashcard->wasRecentlyCreated) {
                $added->push($flashcard);
            }
        }

        if ($added->isNotEmpty()) {
            self::queueMedia($added);
        }
    }

    /**
     * Queue the OpenAI illustration and the Azure voice of the new flashcards; both appear on the cards once their jobs have run.
     *
     * @param  Collection<int, Flashcard>  $flashcards
     */
    private static function queueMedia(Collection $flashcards): void
    {
        $queued = [];

        if (blank(config('services.openai.key'))) {
            self::warn('The flashcard images were not generated', 'OPENAI_API_KEY is not set.');
        } else {
            $flashcards->each(fn (Flashcard $flashcard) => GenerateFlashcardImage::dispatch($flashcard));
            $queued[] = 'images';
        }

        if (app(AzureTextToSpeech::class)->isConfigured()) {
            $flashcards->each(fn (Flashcard $flashcard) => GenerateFlashcardAudio::dispatch($flashcard));
            $queued[] = 'audio';
        } else {
            self::warn('The flashcard audio was not generated', 'AZURE_SPEECH_KEY and AZURE_SPEECH_REGION are not set.');
        }

        if ($queued !== []) {
            Notification::make()
                ->title('The flashcard '.implode(' and ', $queued).($queued === ['audio'] ? ' is' : ' are').' being generated')
                ->body('They will appear on the flashcards in a moment.')
                ->success()
                ->send();
        }
    }

    private static function warn(string $title, string $body): void
    {
        Notification::make()->title($title)->body($body)->warning()->send();
    }
}
