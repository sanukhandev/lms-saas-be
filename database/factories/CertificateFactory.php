<?php

namespace Database\Factories;

use App\Models\Certificate;
use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Certificate>
 */
class CertificateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'certificate_number' => 'CERT-' . strtoupper($this->faker->bothify('???-###-???')),
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->paragraph(),
            'issued_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'expires_at' => $this->faker->dateTimeBetween('now', '+2 years'),
            'certificate_data' => $this->generateCertificateData(),
            'is_active' => $this->faker->boolean(90),
            'course_id' => Course::factory(),
            'student_id' => User::factory()->state(['role' => 'student']),
            'issued_by' => User::factory()->state(['role' => 'tutor']),
            'tenant_id' => 1, // Will be overridden in seeder
        ];
    }

    /**
     * Generate certificate data.
     */
    private function generateCertificateData(): array
    {
        return [
            'template' => $this->faker->randomElement(['modern', 'classic', 'elegant', 'minimal']),
            'grade' => $this->faker->randomElement(['A+', 'A', 'A-', 'B+', 'B', 'B-']),
            'completion_percentage' => $this->faker->numberBetween(85, 100),
            'skills_acquired' => $this->faker->randomElements([
                'Problem Solving', 'Critical Thinking', 'Communication', 'Leadership',
                'Technical Skills', 'Project Management', 'Data Analysis', 'Programming'
            ], rand(3, 6)),
            'instructor_signature' => $this->faker->name(),
            'verification_url' => $this->faker->url(),
        ];
    }

    /**
     * Indicate that the certificate is expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => $this->faker->dateTimeBetween('-1 year', '-1 day'),
        ]);
    }

    /**
     * Indicate that the certificate is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
