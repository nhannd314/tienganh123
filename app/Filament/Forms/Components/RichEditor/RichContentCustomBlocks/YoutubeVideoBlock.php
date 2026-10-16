<?php

namespace App\Filament\Forms\Components\RichEditor\RichContentCustomBlocks;

use App\Models\Lesson;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\TextInput;

class YoutubeVideoBlock extends RichContentCustomBlock
{
    public static function getId(): string
    {
        return 'youtube_video';
    }

    public static function getLabel(): string
    {
        return 'YouTube video';
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action
            ->modalDescription('Paste the link of a YouTube video.')
            ->schema([
                TextInput::make('url')
                    ->label('YouTube URL')
                    ->url()
                    ->required()
                    ->placeholder('https://www.youtube.com/watch?v=...')
                    ->rules([
                        fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                            if (Lesson::youtubeEmbedUrl(is_string($value) ? $value : null) === null) {
                                $fail('This is not a valid YouTube link.');
                            }
                        },
                    ]),
            ]);
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public static function getPreviewLabel(array $config): string
    {
        return 'YouTube video: '.($config['url'] ?? '');
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public static function toPreviewHtml(array $config): string
    {
        return view('filament.forms.components.rich-editor.rich-content-custom-blocks.youtube-video.preview', [
            'embedUrl' => Lesson::youtubeEmbedUrl($config['url'] ?? null),
        ])->render();
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $data
     */
    public static function toHtml(array $config, array $data): string
    {
        return view('filament.forms.components.rich-editor.rich-content-custom-blocks.youtube-video.index', [
            'embedUrl' => Lesson::youtubeEmbedUrl($config['url'] ?? null),
        ])->render();
    }
}
