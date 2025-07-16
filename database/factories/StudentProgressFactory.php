<?php

namespace Database\Factories;

use App\Models\StudentProgress;
use App\Models\User;
use App\Models\Course;
use App\Models\CourseContent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StudentProgress>
 */
class StudentProgressFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $progressPercentage = $this->faker->numberBetween(0, 100);
        $timeSpent = $this->faker->numberBetween(300, 18000); // 5 minutes to 5 hours in seconds
        
        return [
            'progress_percentage' => $progressPercentage,
            'time_spent' => $timeSpent,
            'last_accessed' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'is_completed' => $progressPercentage >= 100,
            'completion_date' => $progressPercentage >= 100 ? $this->faker->dateTimeBetween('-1 week', 'now') : null,
            'student_id' => User::factory()->state(['role' => 'student']),
            'course_id' => Course::factory(),
            'content_id' => CourseContent::factory(),
        ];
    }

    /**
     * Create a completed progress record.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'progress_percentage' => 100,
            'is_completed' => true,
            'completion_date' => $this->faker->dateTimeBetween('-1 week', 'now'),
        ]);
    }

    /**
     * Create an in-progress record.
     */
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'progress_percentage' => $this->faker->numberBetween(1, 99),
            'is_completed' => false,
            'completion_date' => null,
        ]);
    }

    /**
     * Create a not started record.
     */
    public function notStarted(): static
    {
        return $this->state(fn (array $attributes) => [
            'progress_percentage' => 0,
            'time_spent' => 0,
            'is_completed' => false,
            'completion_date' => null,
            'last_accessed' => null,
        ]);
    }
}
