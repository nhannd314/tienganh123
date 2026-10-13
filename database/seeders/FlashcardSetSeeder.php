<?php

namespace Database\Seeders;

use App\Models\Flashcard;
use App\Models\FlashcardSet;
use App\Models\Unit;
use App\Models\Word;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class FlashcardSetSeeder extends Seeder
{
    /**
     * Seed flashcard sets from existing words and assign them to lessons.
     *
     * Requires CourseSeeder and WordSeeder to run first.
     */
    public function run(): void
    {
        $wordIds = Word::pluck('id', 'slug');

        foreach ($this->flashcards() as $index => $data) {
            $flashcardSet = FlashcardSet::updateOrCreate(
                ['slug' => Str::slug($data['title'])],
                [
                    'title' => $data['title'],
                    'description' => $data['description'],
                    'sort_order' => $index + 1,
                ],
            );

            $flashcardSet->flashcards()->sync(
                collect($data['words'])
                    ->filter(fn (string $slug): bool => $wordIds->has($slug))
                    ->values()
                    ->mapWithKeys(fn (string $slug, int $position): array => [
                        Flashcard::firstOrCreate(['word_id' => $wordIds[$slug]])->id => ['sort_order' => $position + 1],
                    ])
                    ->all(),
            );

            Unit::where('slug', $data['unit'])->first()
                ?->lessons()
                ->orderBy('sort_order')
                ->first()
                ?->flashcardSets()
                ->syncWithoutDetaching([$flashcardSet->id => ['sort_order' => $index + 1]]);
        }
    }

    /**
     * Get the sample flashcard sets, the word slugs they contain and the unit whose first lesson receives them.
     *
     * @return list<array{title: string, description: string, words: list<string>, unit: string}>
     */
    private function flashcards(): array
    {
        return [
            [
                'title' => 'Ôn tập: Food and Drinks',
                'description' => 'Thẻ ghi nhớ từ vựng đồ ăn và thức uống.',
                'words' => ['apple', 'water', 'ice-cream'],
                'unit' => 'food-and-drinks',
            ],
            [
                'title' => 'Ôn tập: Daily Routines',
                'description' => 'Thẻ ghi nhớ từ vựng sinh hoạt hằng ngày.',
                'words' => ['work', 'book', 'water'],
                'unit' => 'daily-routines',
            ],
            [
                'title' => 'Ôn tập: At Work',
                'description' => 'Thẻ ghi nhớ từ vựng nơi làm việc.',
                'words' => ['work', 'book'],
                'unit' => 'writing-emails',
            ],
        ];
    }
}
