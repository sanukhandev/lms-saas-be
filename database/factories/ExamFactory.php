<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Exam;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Exam>
 */
class ExamFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $examTitles = [
            'Midterm Examination',
            'Final Assessment',
            'Quiz 1',
            'Quiz 2',
            'Practice Test',
            'Module Assessment',
            'Practical Exam',
            'Comprehensive Test',
            'Unit Test',
            'Progress Check',
            'Skills Assessment',
            'Knowledge Check',
            'Chapter Review',
            'Certification Test',
            'Performance Evaluation'
        ];

        $startTime = $this->faker->dateTimeBetween('-1 month', '+2 months');
        $endTime = (clone $startTime)->modify('+' . $this->faker->numberBetween(60, 180) . ' minutes');

        return [
            'title' => $this->faker->randomElement($examTitles),
            'description' => $this->faker->paragraphs(2, true),
            'start_time' => $startTime,
            'end_time' => $endTime,
            'duration' => $this->faker->numberBetween(30, 180), // 30 minutes to 3 hours
            'max_score' => $this->faker->numberBetween(50, 100),
            'passing_score' => $this->faker->numberBetween(60, 85),
            'is_active' => $this->faker->boolean(85),
            'course_id' => Course::factory(),
            'created_by' => User::factory()->state(['role' => 'tutor']),
        ];
    }

    /**
     * Indicate that the exam is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create a short quiz.
     */
    public function quiz(): static
    {
        return $this->state(fn (array $attributes) => [
            'title' => 'Quick Quiz - ' . $this->faker->word(),
            'duration' => $this->faker->numberBetween(15, 45),
            'max_score' => $this->faker->numberBetween(10, 25),
            'passing_score' => $this->faker->numberBetween(60, 75),
        ]);
    }

    /**
     * Create a comprehensive exam.
     */
    public function comprehensive(): static
    {
        return $this->state(fn (array $attributes) => [
            'title' => 'Comprehensive Examination - ' . $this->faker->word(),
            'duration' => $this->faker->numberBetween(120, 180),
            'max_score' => $this->faker->numberBetween(80, 100),
            'passing_score' => $this->faker->numberBetween(70, 85),
        ]);
    }
}
