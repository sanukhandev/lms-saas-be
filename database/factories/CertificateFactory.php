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
            'certificate_no' => 'CERT-' . strtoupper($this->faker->bothify('???-###-???')),
            'template_slug' => $this->faker->randomElement(['default', 'premium', 'modern', 'classic']),
            'pdf_path' => 'certificates/' . $this->faker->uuid() . '.pdf',
            'is_verified' => $this->faker->boolean(85),
            'course_id' => Course::factory(),
            'student_id' => User::factory(),
            'exam_result_id' => null, // Will be set if needed
            'tenant_id' => 1, // Will be overridden in seeder
            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ];
    }

    /**
     * Indicate that the certificate is verified.
     */
    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_verified' => true,
        ]);
    }

    /**
     * Indicate that the certificate is not verified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_verified' => false,
        ]);
    }
}
