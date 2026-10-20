<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Unit;
use App\Models\Vocabulary;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * What the sidebar of a vocabulary lists (its page and its review): the courses that use it, the vocabularies that
 * share a tag with it and those used in the same courses.
 */
class VocabularySidebar
{
    private const LIMIT = 6;

    /**
     * @return array{courses: Collection<int, Course>, sameTopic: Collection<int, Vocabulary>, sameCourse: Collection<int, Vocabulary>}
     */
    public function for(Vocabulary $vocabulary): array
    {
        $vocabulary->loadMissing('tags');

        return [
            'courses' => $this->courses($vocabulary),
            'sameTopic' => $this->sameTopic($vocabulary),
            'sameCourse' => $this->sameCourse($vocabulary),
        ];
    }

    /**
     * Published courses with a published lesson that use the vocabulary.
     *
     * @return Collection<int, Course>
     */
    private function courses(Vocabulary $vocabulary): Collection
    {
        return Course::query()
            ->published()
            ->whereHas('units', fn ($unit) => $unit->whereHas('lessons', fn ($lesson) => $lesson->published()->whereRelation('vocabularies', 'vocabularies.id', $vocabulary->getKey())))
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

        return $this->query($vocabulary)
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
            ->whereHas('course', fn ($query) => $query->published())
            ->whereHas('lessons', fn ($query) => $query->published()->whereRelation('vocabularies', 'vocabularies.id', $vocabulary->id))
            ->pluck('course_id')
            ->unique();

        if ($courseIds->isEmpty()) {
            return new Collection;
        }

        return $this->query($vocabulary)
            ->whereHas('lessons', fn ($query) => $query->published()->whereHas('unit', fn ($unit) => $unit->whereIn('course_id', $courseIds)))
            ->get();
    }

    /**
     * @return Builder<Vocabulary>
     */
    private function query(Vocabulary $vocabulary): Builder
    {
        return Vocabulary::query()
            ->whereKeyNot($vocabulary->getKey())
            ->withCount('words')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->limit(self::LIMIT);
    }
}
