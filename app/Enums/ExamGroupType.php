<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * What a group of an exam asks. `Questions` holds questions written by hand (see ExamQuestionType); the word groups ask
 * about the words listed in the group, taken from the words table; `ListenAndFill` is a passage heard from an audio file
 * with the words wrapped in backticks to fill in; `SentencePronunciation` lists English sentences to read aloud.
 */
enum ExamGroupType: string implements HasLabel
{
    case Questions = 'questions';
    case MeaningToWord = 'meaning_to_word';
    case WordToMeaning = 'word_to_meaning';
    case ListenToMeaning = 'listen_to_meaning';
    case Pronunciation = 'pronunciation';
    case ListenAndFill = 'listen_and_fill';
    case SentencePronunciation = 'sentence_pronunciation';

    public function getLabel(): string
    {
        return match ($this) {
            self::Questions => 'Câu hỏi (chọn đáp án / điền từ)',
            self::MeaningToWord => 'Xem nghĩa tiếng Việt, gõ từ tiếng Anh',
            self::WordToMeaning => 'Xem từ tiếng Anh, chọn nghĩa tiếng Việt',
            self::ListenToMeaning => 'Nghe audio, chọn nghĩa của từ',
            self::Pronunciation => 'Xem nghĩa tiếng Việt, đọc từ (chấm phát âm)',
            self::ListenAndFill => 'Nghe audio và điền từ',
            self::SentencePronunciation => 'Đọc câu tiếng Anh (chấm phát âm)',
        };
    }

    /**
     * Whether the questions are read into the microphone and scored by Azure.
     */
    public function isSpoken(): bool
    {
        return in_array($this, [self::Pronunciation, self::SentencePronunciation], true);
    }

    /**
     * Whether the questions are the words listed in the group.
     */
    public function usesWords(): bool
    {
        return in_array($this, [self::MeaningToWord, self::WordToMeaning, self::ListenToMeaning, self::Pronunciation], true);
    }
}
