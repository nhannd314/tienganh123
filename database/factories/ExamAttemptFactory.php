<?php

namespace Database\Factories;

use App\Models\Exam;
use App\Models\ExamAttempt;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExamAttempt>
 */
class ExamAttemptFactory extends Factory
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
            'user_id' => null,
            'student_name' => fake()->name(),
            'class_name' => '3A',
            'score' => null,
            'answers' => null,
            'pronunciation_scores' => null,
            'started_at' => now(),
            'submitted_at' => null,
        ];
    }

    /**
     * Indicate that the attempt was submitted with the given score.
     */
    public function submitted(float $score = 80): static
    {
        return $this->state(fn (array $attributes) => [
            'score' => $score,
            'answers' => [],
            'submitted_at' => now(),
        ]);
    }
}
