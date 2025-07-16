<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Course;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Course>
 */
class CourseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $titles = [
            'Introduction to React Development',
            'Advanced JavaScript Concepts',
            'Python for Data Science',
            'Full Stack Web Development',
            'Machine Learning Fundamentals',
            'Mobile App Development with Flutter',
            'Database Design and Management',
            'Cloud Computing with AWS',
            'Cybersecurity Essentials',
            'UI/UX Design Principles',
            'DevOps and CI/CD Pipelines',
            'Node.js Backend Development',
            'Vue.js Framework Mastery',
            'Data Visualization with D3.js',
            'Artificial Intelligence Basics',
            'Docker and Containerization',
            'API Development and Testing',
            'Blockchain Technology',
            'Digital Marketing Strategy',
            'Project Management Fundamentals'
        ];

        $levels = ['course', 'module', 'chapter'];

        return [
            'title' => $this->faker->randomElement($titles),
            'description' => $this->faker->paragraphs(3, true),
            'schedule_level' => $this->faker->randomElement($levels),
            'is_active' => $this->faker->boolean(80), // 80% chance of being active
            'tenant_id' => Tenant::factory(),
            'category_id' => Category::factory(),
        ];
    }

    /**
     * Indicate that the course is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the course is for course level.
     */
    public function course(): static
    {
        return $this->state(fn (array $attributes) => [
            'schedule_level' => 'course',
        ]);
    }

    /**
     * Indicate that the course is module level.
     */
    public function module(): static
    {
        return $this->state(fn (array $attributes) => [
            'schedule_level' => 'module',
        ]);
    }

    /**
     * Indicate that the course is chapter level.
     */
    public function chapter(): static
    {
        return $this->state(fn (array $attributes) => [
            'schedule_level' => 'chapter',
        ]);
    }
}
