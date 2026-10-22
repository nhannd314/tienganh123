<?php

namespace Database\Factories;

use App\Enums\ExamQuestionType;
use App\Models\ExamGroup;
use App\Models\ExamQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExamQuestion>
 */
class ExamQuestionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'exam_group_id' => ExamGroup::factory(),
            'type' => ExamQuestionType::Choice,
            'prompt' => fake()->sentence(),
            'options' => fake()->unique()->words(4),
            'answer' => '1',
            'sort_order' => 0,
        ];
    }

    /**
     * Indicate that the question is a word to fill in, with the given accepted answers separated by "|".
     */
    public function fill(string $answer = 'went'): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ExamQuestionType::Fill,
            'options' => null,
            'answer' => $answer,
        ]);
    }
}
