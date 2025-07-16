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

        return [
            'title' => $this->faker->randomElement($examTitles),
            'instructions' => $this->faker->paragraphs(3, true),
            'is_published' => $this->faker->boolean(80),
            'course_id' => Course::factory(),
            'content_id' => null, // Will be set if needed
            'tenant_id' => 1, // Will be overridden in seeder
        ];
    }

    /**
     * Indicate that the exam is unpublished.
     */
    public function unpublished(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => false,
        ]);
    }

    /**
     * Indicate that the exam is published.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => true,
        ]);
    }

    /**
     * Create a short quiz.
     */
    public function quiz(): static
    {
        return $this->state(fn (array $attributes) => [
            'title' => 'Quick Quiz - ' . $this->faker->word(),
            'instructions' => 'Complete this quick quiz to test your understanding.',
        ]);
    }

    /**
     * Create a comprehensive exam.
     */
    public function comprehensive(): static
    {
        return $this->state(fn (array $attributes) => [
            'title' => 'Comprehensive Examination - ' . $this->faker->word(),
            'instructions' => 'This is a comprehensive examination covering all course materials. Please read all questions carefully.',
        ]);
    }
}
