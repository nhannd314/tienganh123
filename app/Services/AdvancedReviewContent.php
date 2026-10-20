<?php

namespace App\Services;

use App\Models\Lesson;
use App\Models\Sentence;

/**
 * The content of the advanced parts of the lesson review (parts 7 to 11): the sentences of the lesson and the paragraphs of
 * its passage, as the questions are made from them and as the progress of the lesson counts them.
 */
class AdvancedReviewContent
{
    /**
     * The sentences of the lesson (at most REVIEW_QUESTIONS_PER_PART) that have words marked with backticks, each cut into its text and the words to fill in.
     *
     * @return list<array{id: int, segments: list<array{text: string, isMarked: bool}>, translation: string|null}>
     */
    public function markedSentences(Lesson $lesson): array
    {
        return ($lesson->sentenceSet?->sentences ?? collect())
            ->map(fn (Sentence $sentence): array => [
                'id' => $sentence->id,
                'segments' => Lesson::sentenceSegments($sentence->sentence),
                'translation' => $sentence->translation,
            ])
            ->filter(fn (array $sentence): bool => collect($sentence['segments'])->contains('isMarked', true))
            ->take(Lesson::REVIEW_QUESTIONS_PER_PART)
            ->values()
            ->all();
    }

    /**
     * The sentences of the lesson (at most REVIEW_QUESTIONS_PER_PART) to be written whole; part 8 shows their meaning, so
     * it needs a translation, part 9 only needs them to be heard.
     *
     * @return list<array{id: int, sentence: string, translation: string|null, audio: string|null}>
     */
    public function writingSentences(Lesson $lesson, bool $needsTranslation): array
    {
        return ($lesson->sentenceSet?->sentences ?? collect())
            ->filter(fn (Sentence $sentence): bool => ! $needsTranslation || filled($sentence->translation))
            ->take(Lesson::REVIEW_QUESTIONS_PER_PART)
            ->map(fn (Sentence $sentence): array => [
                'id' => $sentence->id,
                'sentence' => Lesson::spokenSentence($sentence->sentence),
                'translation' => $sentence->translation,
                'audio' => $sentence->audio_url,
            ])
            ->values()
            ->all();
    }

    /**
     * The most words the passage asks to fill in; the marked words after them are shown as plain text.
     */
    public const MAX_PASSAGE_BLANKS = 30;

    /**
     * The whole passage as paragraphs, each cut into its text and the words to fill in; every word to fill in carries its
     * `id`, numbered from 1 across the passage.
     *
     * @return list<list<array{text: string, isMarked: bool, id?: int}>>
     */
    public function passageParagraphs(Lesson $lesson): array
    {
        $next = 1;

        return array_map(
            function (array $segments) use (&$next): array {
                return array_map(function (array $segment) use (&$next): array {
                    if (! $segment['isMarked']) {
                        return $segment;
                    }

                    return $next > self::MAX_PASSAGE_BLANKS ? [...$segment, 'isMarked' => false] : [...$segment, 'id' => $next++];
                }, $segments);
            },
            app(PassageBlanks::class)->paragraphs($lesson->passage),
        );
    }

    /**
     * The words to fill in across the passage: blank id => word.
     *
     * @return array<int, string>
     */
    public function passageBlanks(Lesson $lesson): array
    {
        $blanks = [];

        foreach ($this->passageParagraphs($lesson) as $segments) {
            foreach ($segments as $segment) {
                if (isset($segment['id'])) {
                    $blanks[$segment['id']] = $segment['text'];
                }
            }
        }

        return $blanks;
    }
}
