<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Unit;
use App\Models\Vocabulary;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class VocabularyController extends Controller
{
    private const SIDEBAR_LIMIT = 6;

    /**
     * Show a vocabulary with its word table, the vocabularies that share a tag with it and those used in the same courses.
     */
    public function __invoke(Vocabulary $vocabulary): View
    {
        $vocabulary->load(['words', 'tags' => fn ($query) => $query->orderBy('name')]);

        return view('vocabularies.show', [
            'vocabulary' => $vocabulary,
            'words' => $vocabulary->words,
            'courses' => $this->courses($vocabulary),
            'sameTopic' => $this->sameTopic($vocabulary),
            'sameCourse' => $this->sameCourse($vocabulary),
        ]);
    }

    /**
     * Published courses with a published unit and lesson that use the vocabulary.
     *
     * @return Collection<int, Course>
     */
    private function courses(Vocabulary $vocabulary): Collection
    {
        return Course::query()
            ->published()
            ->whereHas('units', fn ($unit) => $unit->published()->whereHas('lessons', fn ($lesson) => $lesson->published()->whereRelation('vocabularies', 'vocabularies.id', $vocabulary->getKey())))
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    /**
     * Other vocabularies that carry at least one of the tags of the vocabulary.
     *
     * @return Collection<int, Vocabulary>
     */
    private function sameTopic(Vocabulary $vocabulary): Collection
    {
        if ($vocabulary->tags->isEmpty()) {
            return new Collection;
        }

        return $this->sidebarQuery($vocabulary)
            ->whereHas('tags', fn ($query) => $query->whereIn('tags.id', $vocabulary->tags->modelKeys()))
            ->get();
    }

    /**
     * Other vocabularies used in a published lesson of a published course that also uses the vocabulary.
     *
     * @return Collection<int, Vocabulary>
     */
    private function sameCourse(Vocabulary $vocabulary): Collection
    {
        $courseIds = Unit::query()
            ->published()
            ->whereHas('course', fn ($query) => $query->published())
            ->whereHas('lessons', fn ($query) => $query->published()->whereRelation('vocabularies', 'vocabularies.id', $vocabulary->id))
            ->pluck('course_id')
            ->unique();

        if ($courseIds->isEmpty()) {
            return new Collection;
        }

        return $this->sidebarQuery($vocabulary)
            ->whereHas('lessons', fn ($query) => $query->published()->whereHas('unit', fn ($unit) => $unit->published()->whereIn('course_id', $courseIds)))
            ->get();
    }

    /**
     * @return Builder<Vocabulary>
     */
    private function sidebarQuery(Vocabulary $vocabulary): Builder
    {
        return Vocabulary::query()
            ->whereKeyNot($vocabulary->getKey())
            ->withCount('words')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->limit(self::SIDEBAR_LIMIT);
    }
}
