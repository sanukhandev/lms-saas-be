<?php

namespace Database\Factories;

use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ExamResult>
 */
class ExamResultFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $score = $this->faker->numberBetween(0, 100);
        $isPassed = $score >= 60; // Assume 60% is passing

        return [
            'score' => $score,
            'is_passed' => $isPassed,
            'answers' => $this->generateAnswers(),
            'exam_id' => Exam::factory(),
            'student_id' => User::factory()->state(['role' => 'student']),
        ];
    }

    /**
     * Generate sample answers.
     */
    private function generateAnswers(): array
    {
        $answers = [];
        $questionCount = $this->faker->numberBetween(5, 15);

        for ($i = 1; $i <= $questionCount; $i++) {
            $answers["question_{$i}"] = $this->faker->randomElement(['A', 'B', 'C', 'D']);
        }

        return $answers;
    }

    /**
     * Create a passing result.
     */
    public function passing(): static
    {
        return $this->state(fn (array $attributes) => [
            'score' => $this->faker->numberBetween(60, 100),
            'is_passed' => true,
        ]);
    }

    /**
     * Create a failing result.
     */
    public function failing(): static
    {
        return $this->state(fn (array $attributes) => [
            'score' => $this->faker->numberBetween(0, 59),
            'is_passed' => false,
        ]);
    }

    /**
     * Create a high-scoring result.
     */
    public function highScore(): static
    {
        return $this->state(fn (array $attributes) => [
            'score' => $this->faker->numberBetween(85, 100),
            'is_passed' => true,
        ]);
    }
}
