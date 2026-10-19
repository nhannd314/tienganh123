<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use App\Observers\WordObserver;
use Database\Factories\WordFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

#[Fillable(['word', 'slug', 'ipa_us', 'ipa_uk', 'meanings', 'examples', 'image'])]
#[ObservedBy(WordObserver::class)]
class Word extends Model
{
    /** @use HasFactory<WordFactory> */
    use HasFactory, HasSlug;

    /**
     * The abbreviation shown for each part of speech, by the name it is stored under.
     *
     * @var array<string, string>
     */
    public const PART_OF_SPEECH_ABBREVIATIONS = [
        'noun' => 'n',
        'verb' => 'v',
        'adjective' => 'a',
        'adverb' => 'adv',
        'pronoun' => 'pron',
        'preposition' => 'prep',
        'conjunction' => 'conj',
        'interjection' => 'interj',
        'determiner' => 'det',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'meanings' => 'array',
            'examples' => 'array',
        ];
    }

    /**
     * @return HasMany<Flashcard, $this>
     */
    public function flashcards(): HasMany
    {
        return $this->hasMany(Flashcard::class);
    }

    /**
     * @return BelongsToMany<Vocabulary, $this>
     */
    public function vocabularies(): BelongsToMany
    {
        return $this->belongsToMany(Vocabulary::class)
            ->withPivot('sort_order')
            ->withTimestamps();
    }

    /**
     * Another abbreviation that is accepted when meanings are typed, by the name it stands for.
     *
     * @var array<string, string>
     */
    public const PART_OF_SPEECH_ALIASES = [
        'adj' => 'adjective',
    ];

    /**
     * A part of speech as it is stored, in full ("n" becomes "noun"); one that is already in full or is unknown is only lowercased.
     */
    public static function expandPartOfSpeech(?string $partOfSpeech): string
    {
        $partOfSpeech = mb_strtolower(trim((string) $partOfSpeech));

        return array_search($partOfSpeech, self::PART_OF_SPEECH_ABBREVIATIONS, true) ?: (self::PART_OF_SPEECH_ALIASES[$partOfSpeech] ?? $partOfSpeech);
    }

    /**
     * A part of speech as it is shown, abbreviated ("noun" becomes "n"); one with no known abbreviation is shown as it is.
     */
    public static function abbreviatePartOfSpeech(?string $partOfSpeech): string
    {
        $partOfSpeech = trim((string) $partOfSpeech);

        return self::PART_OF_SPEECH_ABBREVIATIONS[mb_strtolower($partOfSpeech)] ?? $partOfSpeech;
    }

    /**
     * Every meaning as the label shown in a quiz option, e.g. "(n) quả táo".
     *
     * @return list<string>
     */
    public function meaningLabels(): array
    {
        return collect($this->meanings ?? [])
            ->filter(fn (array $meaning): bool => filled($meaning['meaning'] ?? null))
            ->map(fn (array $meaning): string => trim(filled($meaning['pos'] ?? null) ? '('.self::abbreviatePartOfSpeech($meaning['pos']).') '.$meaning['meaning'] : $meaning['meaning']))
            ->values()
            ->all();
    }

    /**
     * Every meaning, one per line, as a review shows the word's meaning, e.g. "(n) quả táo\n(v) ăn táo".
     * Shown with `white-space: pre-line`.
     */
    public function allMeaningsLabel(): string
    {
        return implode("\n", $this->meaningLabels());
    }

    /**
     * Every meaning without its part of speech, joined by "; ", e.g. "quả táo; ăn táo".
     */
    public function plainMeaningsLabel(): string
    {
        return collect($this->meanings ?? [])
            ->map(fn (array $meaning): string => trim($meaning['meaning'] ?? ''))
            ->filter()
            ->implode('; ');
    }

    /**
     * The first meaning without its part of speech, e.g. "quả táo", or null when the word has no meaning.
     */
    public function firstPlainMeaning(): ?string
    {
        return collect($this->meanings ?? [])
            ->map(fn (array $meaning): string => trim($meaning['meaning'] ?? ''))
            ->first(fn (string $meaning): bool => $meaning !== '');
    }

    /**
     * Name of the attribute the slug is generated from.
     */
    protected function slugSourceAttribute(): string
    {
        return 'word';
    }

    /**
     * Public URL of the US pronunciation file: storage/app/public/audio/us/{slug}.mp3
     *
     * @return Attribute<string, never>
     */
    protected function audioUsUrl(): Attribute
    {
        return Attribute::get(fn (): string => Storage::disk('public')->url("audio/us/{$this->slug}.mp3"));
    }

    /**
     * Public URL of the UK pronunciation file: storage/app/public/audio/uk/{slug}.mp3
     *
     * @return Attribute<string, never>
     */
    protected function audioUkUrl(): Attribute
    {
        return Attribute::get(fn (): string => Storage::disk('public')->url("audio/uk/{$this->slug}.mp3"));
    }

    /**
     * Public URL of the word image stored on the public disk, or null when absent.
     *
     * @return Attribute<string|null, never>
     */
    protected function imageUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->image ? Storage::disk('public')->url($this->image) : null);
    }
}
