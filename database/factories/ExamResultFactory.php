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
        $maxScore = $this->faker->numberBetween(50, 100);
        $score = $this->faker->numberBetween(0, $maxScore);
        $passingScore = $this->faker->numberBetween(60, 85);

        $startTime = $this->faker->dateTimeBetween('-1 month', 'now');
        $endTime = (clone $startTime)->modify('+' . $this->faker->numberBetween(30, 180) . ' minutes');

        return [
            'score' => $score,
            'max_score' => $maxScore,
            'passing_score' => $passingScore,
            'passed' => $score >= $passingScore,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'time_taken' => $this->faker->numberBetween(1800, 10800), // 30 minutes to 3 hours in seconds
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
        $questionCount = $this->faker->numberBetween(5, 20);

        for ($i = 1; $i <= $questionCount; $i++) {
            $answers["question_{$i}"] = [
                'answer' => $this->faker->randomElement(['A', 'B', 'C', 'D']),
                'is_correct' => $this->faker->boolean(70), // 70% chance of being correct
                'points_earned' => $this->faker->numberBetween(0, 5),
                'time_spent' => $this->faker->numberBetween(30, 300), // 30 seconds to 5 minutes
            ];
        }

        return $answers;
    }

    /**
     * Create a passing result.
     */
    public function passing(): static
    {
        return $this->state(function (array $attributes) {
            $maxScore = $this->faker->numberBetween(50, 100);
            $passingScore = $this->faker->numberBetween(60, 85);
            $score = $this->faker->numberBetween($passingScore, $maxScore);

            return [
                'score' => $score,
                'max_score' => $maxScore,
                'passing_score' => $passingScore,
                'passed' => true,
            ];
        });
    }

    /**
     * Create a failing result.
     */
    public function failing(): static
    {
        return $this->state(function (array $attributes) {
            $maxScore = $this->faker->numberBetween(50, 100);
            $passingScore = $this->faker->numberBetween(60, 85);
            $score = $this->faker->numberBetween(0, $passingScore - 1);

            return [
                'score' => $score,
                'max_score' => $maxScore,
                'passing_score' => $passingScore,
                'passed' => false,
            ];
        });
    }

    /**
     * Create a high-scoring result.
     */
    public function highScore(): static
    {
        return $this->state(function (array $attributes) {
            $maxScore = $this->faker->numberBetween(80, 100);
            $score = $this->faker->numberBetween(85, $maxScore);

            return [
                'score' => $score,
                'max_score' => $maxScore,
                'passed' => true,
            ];
        });
    }
}
