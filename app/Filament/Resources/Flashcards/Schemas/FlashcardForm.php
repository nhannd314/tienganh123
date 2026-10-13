<?php

namespace App\Filament\Resources\Flashcards\Schemas;

use App\Jobs\GenerateFlashcardAudio;
use App\Jobs\GenerateFlashcardImage;
use App\Models\Flashcard;
use App\Models\Word;
use App\Services\AzureTextToSpeech;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class FlashcardForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->maxLength(255)
                    ->helperText('Linked to the word with the same spelling when no word is chosen.')
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Get $get, Set $set, ?string $state): void {
                        if (blank($get('word_id')) && ($wordId = self::findWordId($state))) {
                            $set('word_id', $wordId);
                        }
                    }),
                Select::make('word_id')
                    ->label('Word')
                    ->relationship('word', 'word')
                    ->searchable()
                    ->preload(),
                FileUpload::make('image')
                    ->image()
                    ->disk('public')
                    ->directory('flashcards/images')
                    ->visibility('public')
                    ->helperText('Leave empty to have a cartoon illustration generated with OpenAI after saving.'),
                FileUpload::make('audio')
                    ->acceptedFileTypes(['audio/mpeg'])
                    ->disk('public')
                    ->directory('flashcards/audio')
                    ->visibility('public')
                    ->maxSize(10240)
                    ->downloadable()
                    ->helperText('Leave empty to have it read aloud by Azure (a child voice) after saving.'),
            ]);
    }

    /**
     * Id of the word spelled like the text (ignoring case and surrounding spaces), or null when there is none.
     */
    public static function findWordId(?string $text): ?int
    {
        if (blank($text)) {
            return null;
        }

        return Word::query()->whereRaw('lower(word) = ?', [mb_strtolower(trim($text))])->value('id');
    }

    /**
     * Fill the word the editor left empty before the flashcard is saved: the one spelled like the title.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function fillMissing(array $data): array
    {
        if (blank($data['word_id'] ?? null)) {
            $data['word_id'] = self::findWordId($data['title'] ?? null);
        }

        return $data;
    }

    /**
     * Queue what a saved flashcard still lacks: the OpenAI illustration when it has no image and the Azure voice when
     * it has no audio. Both appear on the card once their job has run.
     */
    public static function queueMissingMedia(Flashcard $flashcard): void
    {
        if (blank($flashcard->display_title)) {
            return;
        }

        $queued = [];

        if (blank($flashcard->image)) {
            if (blank(config('services.openai.key'))) {
                self::warn('The image was not generated', 'OPENAI_API_KEY is not set. Upload an image instead.');
            } else {
                GenerateFlashcardImage::dispatch($flashcard);
                $queued[] = 'image';
            }
        }

        if (blank($flashcard->audio)) {
            if (! app(AzureTextToSpeech::class)->isConfigured()) {
                self::warn('The audio was not generated', 'AZURE_SPEECH_KEY and AZURE_SPEECH_REGION are not set. Upload an audio file instead.');
            } else {
                GenerateFlashcardAudio::dispatch($flashcard);
                $queued[] = 'audio';
            }
        }

        if ($queued !== []) {
            Notification::make()
                ->title('The '.implode(' and ', $queued).(count($queued) > 1 ? ' are' : ' is').' being generated')
                ->body('They will appear on the flashcard in a moment.')
                ->success()
                ->send();
        }
    }

    private static function warn(string $title, string $body): void
    {
        Notification::make()->title($title)->body($body)->warning()->send();
    }
}
