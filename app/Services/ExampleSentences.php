<?php

namespace App\Services;

use App\Models\Lesson;
use App\Models\Sentence;
use App\Models\Word;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ExampleSentences
{
    /**
     * The sentences ready to show as example sentences, each cut into segments: a word marked with backticks is shown as a
     * label, and carries the stored word that matches it, if any, to open its popover; any other segment is plain text.
     * A sentence with nothing to speak is left out.
     *
     * @param  Collection<int, Sentence>  $sentences
     * @return Collection<int, array{sentence: string, translation: ?string, audioUrl: ?string, imageUrl: ?string, segments: list<array{text: string, isMarked: bool, word: ?Word}>}>
     */
    public function for(Collection $sentences): Collection
    {
        $examples = $sentences
            ->filter(fn (Sentence $sentence): bool => filled(trim(Lesson::spokenSentence($sentence->sentence))))
            ->map(fn (Sentence $sentence): array => [
                'sentence' => $sentence->sentence,
                'translation' => $sentence->translation,
                'audio' => $sentence->audio,
                'image' => $sentence->image,
                'segments' => Lesson::sentenceSegments($sentence->sentence),
            ])
            ->values();

        $markedWords = $examples->flatMap(fn (array $example): array => $example['segments'])
            ->filter(fn (array $segment): bool => $segment['isMarked'])
            ->map(fn (array $segment): string => mb_strtolower(trim($segment['text'])))
            ->unique();

        $words = $markedWords->isEmpty() ? collect() : Word::query()
            ->whereIn(DB::raw('lower(word)'), $markedWords->all())
            ->get()
            ->keyBy(fn (Word $word): string => mb_strtolower($word->word));

        return $examples->map(fn (array $example): array => [
            'sentence' => Lesson::spokenSentence($example['sentence']),
            'translation' => $example['translation'] ?? null,
            'audioUrl' => filled($example['audio'] ?? null) ? Storage::disk('public')->url($example['audio']) : null,
            'imageUrl' => filled($example['image'] ?? null) ? Storage::disk('public')->url($example['image']) : null,
            'segments' => array_map(fn (array $segment): array => [
                'text' => $segment['text'],
                'isMarked' => $segment['isMarked'],
                'word' => $segment['isMarked'] ? $words->get(mb_strtolower(trim($segment['text']))) : null,
            ], $example['segments']),
        ]);
    }
}
