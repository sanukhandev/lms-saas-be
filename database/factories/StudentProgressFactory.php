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
        $timeSpent = $this->faker->numberBetween(5, 300); // 5 minutes to 5 hours
        $startedAt = $this->faker->dateTimeBetween('-1 month', 'now');
        $completedAt = $progressPercentage >= 100 ? $this->faker->dateTimeBetween($startedAt, 'now') : null;
        
        return [
            'completion_percentage' => $progressPercentage,
            'time_spent_mins' => $timeSpent,
            'last_accessed' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'started_at' => $startedAt,
            'completed_at' => $completedAt,
            'status' => $progressPercentage >= 100 ? 'completed' : ($progressPercentage > 0 ? 'in_progress' : 'not_started'),
            'user_id' => User::factory()->state(['role' => 'student']),
            'course_id' => Course::factory(),
            'content_id' => CourseContent::factory(),
            'tenant_id' => 1, // Will be overridden in seeder
        ];
    }

    /**
     * Create a completed progress record.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'completion_percentage' => 100,
            'status' => 'completed',
            'completed_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
        ]);
    }

    /**
     * Create an in-progress record.
     */
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'completion_percentage' => $this->faker->numberBetween(1, 99),
            'status' => 'in_progress',
            'completed_at' => null,
        ]);
    }

    /**
     * Create a not started record.
     */
    public function notStarted(): static
    {
        return $this->state(fn (array $attributes) => [
            'completion_percentage' => 0,
            'time_spent_mins' => 0,
            'status' => 'not_started',
            'completed_at' => null,
            'last_accessed' => null,
            'started_at' => null,
        ]);
    }
}
