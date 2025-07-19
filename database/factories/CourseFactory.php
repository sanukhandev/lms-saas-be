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

        $levels = ['beginner', 'intermediate', 'advanced'];
        $statuses = ['draft', 'published', 'archived'];
        $accessModels = ['one_time', 'monthly_subscription', 'full_curriculum'];

        return [
            'title' => $this->faker->randomElement($titles),
            'description' => $this->faker->paragraphs(3, true),
            'short_description' => $this->faker->sentence(12),
            'schedule_level' => $this->faker->randomElement(['course', 'module', 'chapter']),
            'status' => $this->faker->randomElement($statuses),
            'is_active' => $this->faker->boolean(80), // 80% chance of being active
            'access_model' => $this->faker->randomElement($accessModels),
            'price' => $this->faker->randomFloat(2, 29.99, 299.99),
            'discount_percentage' => $this->faker->numberBetween(0, 50),
            'subscription_price' => $this->faker->randomFloat(2, 9.99, 49.99),
            'trial_period_days' => $this->faker->randomElement([7, 14, 30, null]),
            'is_pricing_active' => $this->faker->boolean(70),
            'slug' => $this->faker->slug(),
            'currency' => 'USD',
            'level' => $this->faker->randomElement($levels),
            'duration_hours' => $this->faker->numberBetween(2, 40),
            'instructor_id' => null, // Will be set through relationships
            'thumbnail_url' => $this->faker->imageUrl(640, 480, 'education'),
            'preview_video_url' => $this->faker->url(),
            'requirements' => $this->faker->paragraph(),
            'what_you_will_learn' => $this->faker->paragraph(),
            'meta_description' => $this->faker->sentence(),
            'tags' => implode(',', $this->faker->words(5)),
            'average_rating' => $this->faker->randomFloat(1, 3.0, 5.0),
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
