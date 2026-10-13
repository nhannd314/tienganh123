<?php

namespace Database\Seeders;

use App\Models\Unit;
use App\Models\Vocabulary;
use App\Models\Word;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class LessonVocabularySeeder extends Seeder
{
    /**
     * Seed sample vocabularies from existing words and attach them to lessons.
     *
     * Requires CourseSeeder and WordSeeder to run first.
     */
    public function run(): void
    {
        $wordIds = Word::pluck('id', 'slug');

        foreach ($this->lessonVocabularies() as $index => $data) {
            $vocabulary = Vocabulary::updateOrCreate(
                ['slug' => Str::slug($data['title'])],
                ['title' => $data['title'], 'sort_order' => $index + 1],
            );

            $vocabulary->words()->sync(
                collect($data['words'])
                    ->filter(fn (string $slug): bool => $wordIds->has($slug))
                    ->values()
                    ->mapWithKeys(fn (string $slug, int $position): array => [
                        $wordIds[$slug] => ['sort_order' => $position + 1],
                    ])
                    ->all(),
            );

            Unit::where('slug', $data['unit'])->first()
                ?->lessons()
                ->orderBy('sort_order')
                ->skip($data['lesson_offset'])
                ->first()
                ?->vocabularies()
                ->syncWithoutDetaching([$vocabulary->id => ['sort_order' => 1]]);
        }
    }

    /**
     * Get the vocabularies, the word slugs they contain and the lesson (by position in a unit) that introduces them.
     *
     * @return list<array{title: string, unit: string, lesson_offset: int, words: list<string>}>
     */
    private function lessonVocabularies(): array
    {
        return [
            ['title' => 'Food and Drinks 1', 'unit' => 'food-and-drinks', 'lesson_offset' => 0, 'words' => ['apple', 'water', 'ice-cream']],
            ['title' => 'Food and Drinks 2', 'unit' => 'food-and-drinks', 'lesson_offset' => 1, 'words' => ['book', 'work', 'water', 'apple']],
            ['title' => 'At Work 1', 'unit' => 'writing-emails', 'lesson_offset' => 0, 'words' => ['work', 'book']],
        ];
    }
}
