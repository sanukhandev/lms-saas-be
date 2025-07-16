<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\ClassSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ClassSession>
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
        $startTime = $this->faker->dateTimeBetween('-1 month', '+2 months');
        $endTime = (clone $startTime)->modify('+' . $this->faker->numberBetween(60, 180) . ' minutes');

        $sessionTitles = [
            'Introduction Session',
            'Core Concepts Workshop',
            'Hands-on Practice',
            'Q&A Session',
            'Project Review',
            'Advanced Topics',
            'Final Presentation',
            'Guest Speaker Session',
            'Lab Session',
            'Group Discussion',
            'Case Study Analysis',
            'Practical Implementation',
            'Code Review Session',
            'Testing Workshop',
            'Deployment Tutorial'
        ];

        return [
            'title' => $this->faker->randomElement($sessionTitles),
            'description' => $this->faker->paragraphs(2, true),
            'start_time' => $startTime,
            'end_time' => $endTime,
            'session_type' => $this->faker->randomElement(['live', 'recorded']),
            'location' => $this->faker->randomElement(['Online', 'Room A', 'Room B', 'Lab 1', 'Conference Room']),
            'max_participants' => $this->faker->numberBetween(10, 50),
            'is_active' => $this->faker->boolean(85),
            'course_id' => Course::factory(),
            'instructor_id' => User::factory()->state(['role' => 'tutor']),
        ];
    }

    /**
     * Indicate that the session is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the session is a live session.
     */
    public function live(): static
    {
        return $this->state(fn (array $attributes) => [
            'session_type' => 'live',
        ]);
    }

    /**
     * Indicate that the session is recorded.
     */
    public function recorded(): static
    {
        return $this->state(fn (array $attributes) => [
            'session_type' => 'recorded',
        ]);
    }
}
