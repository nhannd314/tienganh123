<?php

namespace Database\Factories;

use App\Enums\ClassShift;
use App\Models\Classroom;
use App\Models\ClassSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClassSession>
 */
class ClassSessionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'classroom_id' => Classroom::factory(),
            'date' => fake()->date(),
            'shift' => fake()->randomElement(ClassShift::cases()),
        ];
    }
}
