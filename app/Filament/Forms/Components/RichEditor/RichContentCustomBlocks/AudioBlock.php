<?php

namespace App\Filament\Forms\Components\RichEditor\RichContentCustomBlocks;

use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Illuminate\Support\Facades\Storage;

class AudioBlock extends RichContentCustomBlock
{
    public static function getId(): string
    {
        return 'audio';
    }

    public static function getLabel(): string
    {
        return 'Audio';
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action
            ->modalDescription('Upload an audio file.')
            ->schema([
                FileUpload::make('file')
                    ->label('Audio file')
                    ->disk('public')
                    ->directory('lessons/content-audio')
                    ->visibility('public')
                    ->acceptedFileTypes(['audio/mpeg', 'audio/mp3', 'audio/wav', 'audio/x-wav', 'audio/ogg', 'audio/mp4', 'audio/x-m4a', 'audio/aac'])
                    ->maxSize(20480)
                    ->required(),
            ]);
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public static function getPreviewLabel(array $config): string
    {
        return 'Audio: '.basename((string) self::path($config));
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public static function toPreviewHtml(array $config): string
    {
        return view('filament.forms.components.rich-editor.rich-content-custom-blocks.audio.preview', [
            'url' => self::url($config),
        ])->render();
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $data
     */
    public static function toHtml(array $config, array $data): string
    {
        return view('filament.forms.components.rich-editor.rich-content-custom-blocks.audio.index', [
            'url' => self::url($config),
        ])->render();
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private static function path(array $config): ?string
    {
        $file = $config['file'] ?? null;
        $file = is_array($file) ? reset($file) : $file;

        return is_string($file) && filled($file) ? $file : null;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private static function url(array $config): ?string
    {
        $path = self::path($config);

        return $path === null ? null : Storage::disk('public')->url($path);
    }
}
