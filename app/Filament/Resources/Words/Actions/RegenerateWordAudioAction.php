<?php

namespace App\Filament\Resources\Words\Actions;

use App\Jobs\FetchWordPronunciation;
use App\Models\Word;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class RegenerateWordAudioAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'regenerateAudio';
    }

    /**
     * Queue a job that reads the word aloud again in both accents, replacing the audio files (the IPA stays as it is).
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Regenerate audio')
            ->icon('heroicon-o-speaker-wave')
            ->color('gray')
            ->requiresConfirmation()
            ->modalDescription('The US and UK audio of this word will be generated again and replace the current files.')
            ->action(function (Word $record): void {
                FetchWordPronunciation::dispatch($record, overwrite: true, audioOnly: true);

                Notification::make()
                    ->title('Audio regeneration queued')
                    ->success()
                    ->send();
            });
    }
}
