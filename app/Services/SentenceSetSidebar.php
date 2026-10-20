<?php

namespace App\Services;

use App\Models\Course;
use App\Models\FlashcardSet;
use App\Models\SentenceSet;
use App\Models\Vocabulary;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * What the sidebar of a sentence set lists (its page and its review): the courses, vocabularies, flashcard sets and
 * other sentence sets related to it.
 */
class SentenceSetSidebar
{
    private const LIMIT = 6;

    /**
     * @return array{courses: Collection<int, Course>, vocabularies: Collection<int, Vocabulary>, flashcardSets: Collection<int, FlashcardSet>, sameTopic: Collection<int, SentenceSet>}
     */
    public function for(SentenceSet $sentenceSet): array
    {
        $sentenceSet->loadMissing('tags');

        return [
            'courses' => $this->courses($sentenceSet),
            'vocabularies' => $this->vocabularies($sentenceSet),
            'flashcardSets' => $this->flashcardSets($sentenceSet),
            'sameTopic' => $this->sameTopic($sentenceSet),
        ];
    }

    /**
     * Published courses with a published lesson that shows the sentence set.
     *
     * @return Collection<int, Course>
     */
    private function courses(SentenceSet $sentenceSet): Collection
    {
        return Course::query()
            ->published()
            ->whereHas('units', fn ($unit) => $unit->whereHas('lessons', fn ($lesson) => $lesson->published()->where('sentence_set_id', $sentenceSet->getKey())))
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    /**
     * Vocabularies that use the sentence set, or that sit in a published lesson which shows it.
     *
     * @return Collection<int, Vocabulary>
     */
    private function vocabularies(SentenceSet $sentenceSet): Collection
    {
        return Vocabulary::query()
            ->where(fn (Builder $query) => $query
                ->where('sentence_set_id', $sentenceSet->getKey())
                ->orWhereHas('lessons', fn ($lesson) => $lesson->published()->where('sentence_set_id', $sentenceSet->getKey())))
            ->withCount('words')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->limit(self::LIMIT)
            ->get();
    }

    /**
     * Flashcard sets that use the sentence set, or that sit in a published lesson which shows it.
     *
     * @return Collection<int, FlashcardSet>
     */
    private function flashcardSets(SentenceSet $sentenceSet): Collection
    {
        return FlashcardSet::query()
            ->where(fn (Builder $query) => $query
                ->where('sentence_set_id', $sentenceSet->getKey())
                ->orWhereHas('lessons', fn ($lesson) => $lesson->published()->where('sentence_set_id', $sentenceSet->getKey())))
            ->withCount('flashcards')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->limit(self::LIMIT)
            ->get();
    }

    /**
     * Other sentence sets that have sentences and carry at least one of the tags of the sentence set.
     *
     * @return Collection<int, SentenceSet>
     */
    private function sameTopic(SentenceSet $sentenceSet): Collection
    {
        if ($sentenceSet->tags->isEmpty()) {
            return new Collection;
        }

        return SentenceSet::query()
            ->whereKeyNot($sentenceSet->getKey())
            ->has('sentences')
            ->whereHas('tags', fn ($query) => $query->whereIn('tags.id', $sentenceSet->tags->modelKeys()))
            ->withCount('sentences')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->limit(self::LIMIT)
            ->get();
    }
}
