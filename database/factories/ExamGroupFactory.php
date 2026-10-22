<?php

namespace Database\Factories;

use App\Enums\ExamGroupType;
use App\Models\Exam;
use App\Models\ExamGroup;
use App\Models\Word;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExamGroup>
 */
class ExamGroupFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'exam_id' => Exam::factory(),
            'type' => ExamGroupType::Questions,
            'title' => fake()->sentence(3),
            'instructions' => '<p>'.fake()->sentence().'</p>',
            'weight' => 1,
            'word_ids' => null,
            'passage' => null,
            'audio' => null,
            'sort_order' => 0,
        ];
    }

    /**
     * Indicate that the group asks the given kind of question about the given words.
     *
     * @param  iterable<int, Word>  $words
     */
    public function forWords(ExamGroupType $type, iterable $words): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => $type,
            'word_ids' => collect($words)->pluck('id')->all(),
        ]);
    }

    /**
     * Indicate that the group asks to read the given sentences aloud.
     *
     * @param  list<string>  $sentences
     */
    public function forSentences(array $sentences = ['I like playing football.', 'My mother is a teacher.']): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ExamGroupType::SentencePronunciation,
            'sentences' => $sentences,
        ]);
    }

    /**
     * Indicate that the group is a passage to listen to, with the words in backticks to fill in.
     */
    public function listenAndFill(string $passage = 'I have an `apple` and a `banana`.'): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ExamGroupType::ListenAndFill,
            'passage' => $passage,
            'audio' => 'exams/audio/passage.mp3',
        ]);
    }

    /**
     * Indicate that each question of the group is worth the given weight.
     */
    public function weighted(float $weight): static
    {
        return $this->state(fn (array $attributes) => [
            'weight' => $weight,
        ]);
    }
}
