<?php

namespace App\Filament\Resources\SentenceSets\Schemas;

use App\Jobs\GenerateSentenceAudio;
use App\Jobs\GenerateSentenceImage;
use App\Models\Sentence;
use App\Models\SentenceSet;
use App\Services\AzureTextToSpeech;
use Closure;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class SentenceSetForm
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
                TextInput::make('sort_order')
                    ->required()
                    ->numeric()
                    ->integer()
                    ->minValue(0)
                    ->default(0),
            ]);
    }

    /**
     * The fields of the modal that adds many sentences at once: one sentence per line and whether to draw their images.
     *
     * @return list<Component>
     */
    public static function sentenceLinesFields(): array
    {
        return [
            Textarea::make('sentence_lines')
                ->label('Sentences')
                ->required()
                ->rows(10)
                ->placeholder('I have an `apple` | Tôi có 1 quả táo.')
                ->helperText('One sentence per line, as "sentence | meaning" (the meaning is optional). Wrap a word in backticks to show its IPA, audio and meaning in a popover. A sentence the set already has is skipped.')
                ->rules([
                    fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                        foreach (self::parseLines(is_string($value) ? $value : null) as $index => $line) {
                            if ($line['sentence'] === '' || mb_strlen($line['sentence']) > 500 || mb_strlen((string) $line['translation']) > 500) {
                                $fail('Line '.($index + 1).' needs a sentence and, at most 500 characters for the sentence and the meaning each.');

                                return;
                            }
                        }
                    },
                ]),
            Checkbox::make('generate_sentence_images')
                ->label('Tạo ảnh minh họa bằng AI')
                ->helperText('Generates an illustration with OpenAI for each sentence added.'),
        ];
    }

    /**
     * Cut the lines of the textarea into sentences and meanings, skipping blank lines.
     *
     * @return list<array{sentence: string, translation: ?string}>
     */
    public static function parseLines(?string $text): array
    {
        return collect(preg_split('/\R/', (string) $text))
            ->map(fn (string $line): string => trim($line))
            ->filter()
            ->map(function (string $line): array {
                [$sentence, $translation] = array_pad(explode('|', $line, 2), 2, null);

                return ['sentence' => trim($sentence), 'translation' => filled(trim((string) $translation)) ? trim($translation) : null];
            })
            ->values()
            ->all();
    }

    /**
     * Add the sentences of the lines to the end of the set, then queue their Azure voice and, when asked, their OpenAI image.
     */
    public static function addSentences(SentenceSet $set, ?string $text, bool $withImages): void
    {
        $existing = $set->sentences()->pluck('sentences.sentence')->all();
        $sortOrder = (int) $set->sentences()->max('sentence_sentence_set.sort_order');

        $lines = collect(self::parseLines($text))
            ->unique('sentence')
            ->reject(fn (array $line): bool => in_array($line['sentence'], $existing, true));

        $added = collect();

        foreach ($lines as $line) {
            $sentence = Sentence::create($line);
            $set->sentences()->attach($sentence, ['sort_order' => ++$sortOrder]);
            $added->push($sentence);
        }

        if ($added->isNotEmpty()) {
            self::queueMedia($added, $withImages);
        }
    }

    /**
     * Queue the Azure voice and, when asked, the OpenAI image of the sentences; both appear on the sentences once their jobs have run.
     *
     * @param  Collection<int, Sentence>  $sentences
     */
    private static function queueMedia(Collection $sentences, bool $withImages): void
    {
        $queued = [];

        if (app(AzureTextToSpeech::class)->isConfigured()) {
            $sentences->each(fn (Sentence $sentence) => GenerateSentenceAudio::dispatch($sentence));
            $queued[] = 'audio';
        } else {
            self::warn('The sentence audio was not generated', 'AZURE_SPEECH_KEY and AZURE_SPEECH_REGION are not set.');
        }

        if ($withImages) {
            if (blank(config('services.openai.key'))) {
                self::warn('The sentence images were not generated', 'OPENAI_API_KEY is not set.');
            } else {
                $sentences->each(fn (Sentence $sentence) => GenerateSentenceImage::dispatch($sentence));
                $queued[] = 'images';
            }
        }

        if ($queued !== []) {
            Notification::make()
                ->title('The sentence '.implode(' and ', $queued).($queued === ['audio'] ? ' is' : ' are').' being generated')
                ->body('They will appear on the sentences in a moment.')
                ->success()
                ->send();
        }
    }

    private static function warn(string $title, string $body): void
    {
        Notification::make()->title($title)->body($body)->warning()->send();
    }
}
