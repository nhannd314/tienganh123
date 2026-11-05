<?php

namespace App\Models;

use App\Jobs\GenerateSentenceAudio;
use Database\Factories\SentenceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

#[Fillable(['sentence', 'translation', 'image', 'audio'])]
class Sentence extends Model
{
    /** @use HasFactory<SentenceFactory> */
    use HasFactory;

    /**
     * When the words of a sentence change (backticks aside), its audio reads the old words: it is dropped so a new voice can
     * be generated, unless a new audio is saved along with the words.
     */
    protected static function booted(): void
    {
        static::updating(function (self $sentence): void {
            $isReworded = $sentence->isDirty('sentence')
                && self::plainText($sentence->sentence) !== self::plainText($sentence->getOriginal('sentence'));

            if ($isReworded && ! $sentence->isDirty('audio') && filled($sentence->audio)) {
                Storage::disk('public')->delete($sentence->audio);
                $sentence->audio = null;
            }
        });
    }

    /**
     * The sentence already stored with the same words as the text, whatever backticks or capitals either has, or null.
     * Among stored sentences that differ only by their backticks, the one written exactly as the text comes first, as
     * rewriting another one to the text would break the unique index on the sentence.
     */
    public static function findByPlainText(string $text): ?self
    {
        return self::withSameWordsAs($text)
            ->orderByRaw('CASE WHEN sentence = ? THEN 0 ELSE 1 END', [$text])
            ->orderBy('id')
            ->first();
    }

    /**
     * Whether rewriting this sentence as the text would make it another stored sentence, backticks and capitals aside.
     * While its own words stay the same, only a sentence written exactly as the text is in the way, so a sentence that
     * already has such a twin can still be edited.
     */
    public function wouldDuplicateAnother(string $text): bool
    {
        $hasSameWords = mb_strtolower(self::plainText($text)) === mb_strtolower(self::plainText($this->sentence));

        return self::withSameWordsAs($text)
            ->whereKeyNot($this->getKey())
            ->when($hasSameWords, fn (Builder $query): Builder => $query->where('sentence', $text))
            ->exists();
    }

    /**
     * The stored sentences with the same words as the text, whatever backticks or capitals either has.
     *
     * @return Builder<self>
     */
    private static function withSameWordsAs(string $text): Builder
    {
        return self::query()->whereRaw("LOWER(REPLACE(sentence, '`', '')) = ?", [mb_strtolower(self::plainText($text))]);
    }

    /**
     * Update the sentence from the typed fields, leaving the ones left empty as they are.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateKeepingEmpty(array $data): void
    {
        $this->update(array_filter($data, fn (mixed $value): bool => filled($value)));
    }

    /**
     * The stored sentences with the same words as the given texts (backticks and capitals aside), by those words in lower case.
     *
     * @param  list<string>  $texts
     * @return Collection<string, self>
     */
    public static function findManyByPlainText(array $texts): Collection
    {
        $keys = array_values(array_unique(array_map(fn (string $text): string => mb_strtolower(self::plainText($text)), $texts)));

        if ($keys === []) {
            return collect();
        }

        return self::query()
            ->whereIn(DB::raw("LOWER(REPLACE(sentence, '`', ''))"), $keys)
            ->get()
            ->unique(fn (self $sentence): string => mb_strtolower(self::plainText($sentence->sentence)))
            ->keyBy(fn (self $sentence): string => mb_strtolower(self::plainText($sentence->sentence)));
    }

    /**
     * Make sure each of the texts is a stored sentence (written without backticks when new) and queue the Azure voice of
     * those that have no audio yet; the audio appears on the sentence once the job has run.
     *
     * @param  list<string>  $texts
     */
    public static function ensureStored(array $texts): void
    {
        $stored = self::findManyByPlainText($texts);

        foreach ($texts as $text) {
            $key = mb_strtolower(self::plainText($text));

            if (! $stored->has($key)) {
                $stored->put($key, self::create(['sentence' => self::plainText($text)]));
            }

            if (blank($stored->get($key)->audio)) {
                GenerateSentenceAudio::dispatch($stored->get($key));
            }
        }
    }

    /**
     * The text without the backticks that mark its popover words.
     */
    public static function plainText(string $text): string
    {
        return str_replace('`', '', $text);
    }

    /**
     * @return BelongsToMany<SentenceSet, $this>
     */
    public function sentenceSets(): BelongsToMany
    {
        return $this->belongsToMany(SentenceSet::class)
            ->using(SentenceSentenceSet::class)
            ->withPivot('sort_order')
            ->withTimestamps();
    }

    /**
     * Public URL of the sentence image stored on the public disk, or null when absent.
     *
     * @return Attribute<string|null, never>
     */
    protected function imageUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->image ? Storage::disk('public')->url($this->image) : null);
    }

    /**
     * Public URL of the sentence audio stored on the public disk, or null when absent.
     *
     * @return Attribute<string|null, never>
     */
    protected function audioUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->audio ? Storage::disk('public')->url($this->audio) : null);
    }
}
