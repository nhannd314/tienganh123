<?php

namespace App\Models;

use App\Enums\ExamGroupType;
use App\Filament\Forms\Components\RichEditor\RichContentCustomBlocks\AudioBlock;
use App\Filament\Forms\Components\RichEditor\RichContentCustomBlocks\VideoBlock;
use App\Filament\Forms\Components\RichEditor\RichContentCustomBlocks\YoutubeVideoBlock;
use Database\Factories\ExamGroupFactory;
use Filament\Forms\Components\RichEditor\RichContentRenderer;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

/**
 * A group of questions of an exam; a group of written questions has instructions (rich content) shown above them. Each of its questions is worth `weight` times
 * a question of weight 1 (see App\Services\ExamPaper). The word groups list their words in `word_ids`, the sentence
 * pronunciation group its sentences in `sentences`.
 */
#[Fillable(['exam_id', 'type', 'title', 'instructions', 'weight', 'word_ids', 'sentences', 'passage', 'audio', 'sort_order'])]
class ExamGroup extends Model
{
    /** @use HasFactory<ExamGroupFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ExamGroupType::class,
            'weight' => 'float',
            'word_ids' => 'array',
            'sentences' => 'array',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Exam, $this>
     */
    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    /**
     * @return HasMany<ExamQuestion, $this>
     */
    public function questions(): HasMany
    {
        return $this->hasMany(ExamQuestion::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * The words listed in the group, in the order they were listed; a word deleted since is left out.
     *
     * @return Collection<int, Word>
     */
    public function words(): Collection
    {
        $ids = array_map('intval', $this->word_ids ?? []);

        if ($ids === []) {
            return collect();
        }

        $words = Word::query()->whereIn('id', $ids)->get()->keyBy('id');

        return collect($ids)->unique()->map(fn (int $id): ?Word => $words->get($id))->filter()->values();
    }

    /**
     * The sentences to read aloud, by their number from 1 (the key of their question).
     *
     * @return array<int, string>
     */
    public function numberedSentences(): array
    {
        $sentences = array_values(array_filter(array_map(fn (mixed $sentence): string => trim((string) $sentence), $this->sentences ?? []), fn (string $sentence): bool => $sentence !== ''));

        return $sentences === [] ? [] : array_combine(range(1, count($sentences)), $sentences);
    }

    /**
     * The passage cut into its plain text and the words to fill in, which are wrapped in backticks (`apple`); the words
     * to fill in are numbered from 1 as `blank`.
     *
     * @return list<array{text: string, blank: int|null}>
     */
    public function passageSegments(): array
    {
        $parts = preg_split('/`([^`]*)`/u', (string) $this->passage, -1, PREG_SPLIT_DELIM_CAPTURE);
        $segments = [];
        $blank = 0;

        foreach ($parts as $index => $part) {
            $isBlank = $index % 2 === 1;

            if ($isBlank && trim($part) === '') {
                continue;
            }

            if (! $isBlank && $part === '') {
                continue;
            }

            $segments[] = ['text' => $isBlank ? trim($part) : $part, 'blank' => $isBlank ? ++$blank : null];
        }

        return $segments;
    }

    /**
     * The instructions of a group of written questions (a rich editor document, which may hold images, audio and
     * videos) as HTML; the other groups show none.
     *
     * @return Attribute<string, never>
     */
    protected function instructionsHtml(): Attribute
    {
        return Attribute::get(fn (): string => $this->type !== ExamGroupType::Questions || blank($this->instructions) ? '' : RichContentRenderer::make($this->instructions)
            ->customBlocks([YoutubeVideoBlock::class, VideoBlock::class, AudioBlock::class])
            ->fileAttachmentsDisk('public')
            ->fileAttachmentsVisibility('public')
            ->toHtml());
    }

    /**
     * Public URL of the audio of the group stored on the public disk, or null when absent.
     *
     * @return Attribute<string|null, never>
     */
    protected function audioUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->audio ? Storage::disk('public')->url($this->audio) : null);
    }
}
