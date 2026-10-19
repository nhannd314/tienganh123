<?php

namespace App\Filament\Resources\Sentences\Schemas;

use App\Jobs\GenerateSentenceAudio;
use App\Jobs\GenerateSentenceImage;
use App\Models\Sentence;
use App\Services\AzureTextToSpeech;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class SentenceForm
{
    public static function configure(Schema $schema, bool $withSentenceSet = true): Schema
    {
        return $schema
            ->components([
                ...($withSentenceSet ? [
                    Select::make('sentenceSets')
                        ->label('Sentence sets')
                        ->relationship('sentenceSets', 'title')
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->columnSpanFull(),
                ] : []),
                TextInput::make('sentence')
                    ->required()
                    ->maxLength(500)
                    ->unique(ignoreRecord: true)
                    ->placeholder('I have an `apple`.')
                    ->helperText('Wrap a word in backticks to show its IPA, audio and meaning in a popover when the word exists.'),
                TextInput::make('translation')
                    ->label('Meaning')
                    ->maxLength(500)
                    ->placeholder('Tôi có 1 quả táo.'),
                FileUpload::make('image')
                    ->image()
                    ->disk('public')
                    ->directory('lessons/sentence-images')
                    ->visibility('public')
                    ->maxSize(2048),
                FileUpload::make('audio')
                    ->acceptedFileTypes(['audio/mpeg'])
                    ->disk('public')
                    ->directory('lessons/sentence-audio')
                    ->visibility('public')
                    ->maxSize(10240)
                    ->downloadable()
                    ->helperText('Leave empty to have it read aloud by Azure after saving.'),
            ]);
    }

    /**
     * Queue the Azure voice of a saved sentence that has no audio; it appears on the sentence once the job has run.
     */
    public static function queueMissingAudio(Sentence $sentence): void
    {
        if (filled($sentence->audio)) {
            return;
        }

        if (! app(AzureTextToSpeech::class)->isConfigured()) {
            Notification::make()
                ->title('The sentence audio was not generated')
                ->body('AZURE_SPEECH_KEY and AZURE_SPEECH_REGION are not set. Upload an audio file instead.')
                ->warning()
                ->send();

            return;
        }

        GenerateSentenceAudio::dispatch($sentence);

        Notification::make()
            ->title('The sentence audio is being generated')
            ->body('It will appear on the sentence in a moment.')
            ->success()
            ->send();
    }

    /**
     * The button that draws the image of the saved sentence with OpenAI, to sit among the buttons of an edit form.
     */
    public static function generateImageAction(Closure $sentence): Action
    {
        return Action::make('generateImage')
            ->label('Tạo ảnh AI')
            ->icon(Heroicon::Sparkles)
            ->color('gray')
            ->action(fn () => self::queueImage($sentence()));
    }

    /**
     * Queue the OpenAI image of a saved sentence, which replaces the one it has; it appears on the sentence once the job has run.
     */
    public static function queueImage(Sentence $sentence): void
    {
        if (blank(config('services.openai.key'))) {
            Notification::make()
                ->title('The sentence image was not generated')
                ->body('OPENAI_API_KEY is not set. Upload an image instead.')
                ->warning()
                ->send();

            return;
        }

        GenerateSentenceImage::dispatch($sentence, replace: true);

        Notification::make()
            ->title('The sentence image is being generated')
            ->body('It will appear on the sentence in a moment.')
            ->success()
            ->send();
    }
}
