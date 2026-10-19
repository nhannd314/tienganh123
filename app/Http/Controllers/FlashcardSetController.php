<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\FlashcardSet;
use App\Models\Unit;
use App\Services\ExampleSentences;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class FlashcardSetController extends Controller
{
    private const SIDEBAR_LIMIT = 6;

    /**
     * Show a flashcard set with all of its cards and example sentences, the sets that share a tag with it and those used in the same courses.
     */
    public function __invoke(FlashcardSet $flashcardSet, ExampleSentences $exampleSentences): View
    {
        $flashcardSet->load([
            'flashcards.word',
            'sentenceSet.sentences',
            'tags' => fn ($query) => $query->orderBy('name'),
        ]);

        return view('flashcards.show', [
            'flashcardSet' => $flashcardSet,
            'flashcards' => $flashcardSet->flashcards,
            'exampleSentences' => $exampleSentences->for($flashcardSet->sentenceSet?->sentences ?? new Collection),
            'courses' => $this->courses($flashcardSet),
            'sameTopic' => $this->sameTopic($flashcardSet),
            'sameCourse' => $this->sameCourse($flashcardSet),
        ]);
    }

    /**
     * Published courses with a published lesson that use the flashcardSet.
     *
     * @return Collection<int, Course>
     */
    private function courses(FlashcardSet $flashcardSet): Collection
    {
        return Course::query()
            ->published()
            ->whereHas('units', fn ($unit) => $unit->whereHas('lessons', fn ($lesson) => $lesson->published()->whereRelation('flashcardSets', 'flashcard_sets.id', $flashcardSet->getKey())))
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    /**
     * Other flashcard sets that carry at least one of the tags of the set.
     *
     * @return Collection<int, FlashcardSet>
     */
    private function sameTopic(FlashcardSet $flashcardSet): Collection
    {
        if ($flashcardSet->tags->isEmpty()) {
            return new Collection;
        }

        return $this->sidebarQuery($flashcardSet)
            ->whereHas('tags', fn ($query) => $query->whereIn('tags.id', $flashcardSet->tags->modelKeys()))
            ->get();
    }

    /**
     * Other flashcard sets used in a published lesson of a published course that also uses the set.
     *
     * @return Collection<int, FlashcardSet>
     */
    private function sameCourse(FlashcardSet $flashcardSet): Collection
    {
        $courseIds = Unit::query()
            ->whereHas('course', fn ($query) => $query->published())
            ->whereHas('lessons', fn ($query) => $query->published()->whereRelation('flashcardSets', 'flashcard_sets.id', $flashcardSet->id))
            ->pluck('course_id')
            ->unique();

        if ($courseIds->isEmpty()) {
            return new Collection;
        }

        return $this->sidebarQuery($flashcardSet)
            ->whereHas('lessons', fn ($query) => $query->published()->whereHas('unit', fn ($unit) => $unit->whereIn('course_id', $courseIds)))
            ->get();
    }

    /**
     * @return Builder<FlashcardSet>
     */
    private function sidebarQuery(FlashcardSet $flashcardSet): Builder
    {
        return FlashcardSet::query()
            ->whereKeyNot($flashcardSet->getKey())
            ->withCount('flashcards')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->limit(self::SIDEBAR_LIMIT);
    }
}
