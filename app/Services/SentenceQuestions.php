<?php

namespace App\Services;

/**
 * The questions of the sentence parts of a review (those of a lesson, or of a sentence set), made from the sentences
 * AdvancedReviewContent picks: 7 (sentence with its marked words typed in), 8 (meaning → whole sentence typed) and
 * 9 (heard sentence → whole sentence typed).
 */
class SentenceQuestions
{
    /**
     * Part 7 questions: a sentence cut into its text and the marked words to type in, with its translation under it.
     *
     * @param  list<array{id: int, segments: list<array{text: string, isMarked: bool}>, translation: string|null}>  $markedSentences
     * @return list<array{id: int, prompt: string, phonetic: null, segments: list<array{text: string, isMarked: bool}>, translation: string|null}>
     */
    public function fillIn(array $markedSentences): array
    {
        return array_map(fn (array $sentence): array => [
            ...$sentence,
            'prompt' => implode('', array_column($sentence['segments'], 'text')),
            'phonetic' => null,
        ], $markedSentences);
    }

    /**
     * Part 8 questions: the meaning of a sentence, and the sentence to write. `word` is the sentence, shown once it has been answered.
     *
     * @param  list<array{id: int, sentence: string, translation: string|null, audio: string|null}>  $writingSentences
     * @return list<array{id: int, prompt: string, phonetic: null, word: string}>
     */
    public function write(array $writingSentences): array
    {
        return array_map(fn (array $sentence): array => [
            'id' => $sentence['id'],
            'prompt' => $sentence['translation'],
            'phonetic' => null,
            'word' => $sentence['sentence'],
        ], $writingSentences);
    }

    /**
     * Part 9 questions: a sentence that is only heard (`prompt` is what is spoken, or `audio` is played), and the sentence to write.
     *
     * @param  list<array{id: int, sentence: string, translation: string|null, audio: string|null}>  $writingSentences
     * @return list<array{id: int, prompt: string, phonetic: null, word: string, audio: string|null}>
     */
    public function dictation(array $writingSentences): array
    {
        return array_map(fn (array $sentence): array => [
            'id' => $sentence['id'],
            'prompt' => $sentence['sentence'],
            'phonetic' => null,
            'word' => $sentence['sentence'],
            'audio' => $sentence['audio'],
        ], $writingSentences);
    }
}
