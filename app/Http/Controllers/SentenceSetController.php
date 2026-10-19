<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\FlashcardSet;
use App\Models\SentenceSet;
use App\Models\Vocabulary;
use App\Services\ExampleSentences;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class SentenceSetController extends Controller
{
    private const SIDEBAR_LIMIT = 6;

    /**
     * Show a sentence set as the example sentences card of a lesson, with the courses, vocabularies, flashcard sets and other
     * sentence sets related to it.
     */
    public function __invoke(SentenceSet $sentenceSet, ExampleSentences $exampleSentences): View
    {
        $sentenceSet->load(['tags' => fn ($query) => $query->orderBy('name'), 'sentences']);

        abort_if($sentenceSet->sentences->isEmpty(), 404);

        return view('sentences.show', [
            'sentenceSet' => $sentenceSet,
            'exampleSentences' => $exampleSentences->for($sentenceSet->sentences),
            'courses' => $this->courses($sentenceSet),
            'vocabularies' => $this->vocabularies($sentenceSet),
            'flashcardSets' => $this->flashcardSets($sentenceSet),
            'sameTopic' => $this->sameTopic($sentenceSet),
        ]);
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
            ->limit(self::SIDEBAR_LIMIT)
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
            ->limit(self::SIDEBAR_LIMIT)
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
            ->limit(self::SIDEBAR_LIMIT)
            ->get();
    }
}
