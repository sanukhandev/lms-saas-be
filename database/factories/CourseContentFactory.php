<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\CourseContent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CourseContent>
 */
class CourseContentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = ['module', 'chapter'];
        $titles = [
            'Introduction and Overview',
            'Getting Started',
            'Core Concepts',
            'Practical Examples',
            'Advanced Techniques',
            'Best Practices',
            'Common Mistakes',
            'Project Setup',
            'Implementation Guide',
            'Testing and Debugging',
            'Deployment Strategies',
            'Performance Optimization',
            'Security Considerations',
            'Final Project',
            'Course Summary'
        ];

        return [
            'title' => $this->faker->randomElement($titles),
            'description' => $this->faker->paragraphs(2, true),
            'type' => $this->faker->randomElement($types),
            'position' => $this->faker->numberBetween(1, 20),
            'duration_mins' => $this->faker->numberBetween(30, 120),
            'course_id' => Course::factory(),
            'parent_id' => null, // Will be set when creating sub-contents
            'tenant_id' => 1, // Will be overridden in seeder
        ];
    }

    /**
     * Indicate that the content is a module.
     */
    public function module(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'module',
        ]);
    }

    /**
     * Indicate that the content is a chapter.
     */
    public function chapter(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'chapter',
        ]);
    }

    /**
     * Indicate that the content has a parent.
     */
    public function withParent(): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => CourseContent::factory()->create()->id,
        ]);
    }
}
