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
        $scheduledAt = $this->faker->dateTimeBetween('-1 month', '+2 months');
        $duration = $this->faker->numberBetween(60, 180);
        $statuses = ['scheduled', 'completed', 'cancelled'];

        return [
            'scheduled_at' => $scheduledAt,
            'duration_mins' => $duration,
            'meeting_url' => $this->faker->url(),
            'is_recorded' => $this->faker->boolean(30),
            'recording_url' => $this->faker->boolean(20) ? $this->faker->url() : null,
            'status' => $this->faker->randomElement($statuses),
            'course_id' => Course::factory(),
            'tutor_id' => User::factory()->state(['role' => 'tutor']),
            'content_id' => null, // Will be set if needed
            'tenant_id' => 1, // Will be overridden in seeder
        ];
    }

    /**
     * Indicate that the session is scheduled.
     */
    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'scheduled',
        ]);
    }

    /**
     * Indicate that the session is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'is_recorded' => true,
            'recording_url' => $this->faker->url(),
        ]);
    }

    /**
     * Indicate that the session is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
        ]);
    }

    /**
     * Indicate that the session is recorded.
     */
    public function recorded(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_recorded' => true,
            'recording_url' => $this->faker->url(),
        ]);
    }
}
