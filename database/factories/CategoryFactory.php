<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $categoryNames = [
            'Programming',
            'Web Development',
            'Data Science',
            'Mobile Development',
            'DevOps',
            'AI & Machine Learning',
            'Cybersecurity',
            'UI/UX Design',
            'Database Management',
            'Cloud Computing',
            'Software Testing',
            'Business Skills',
            'Digital Marketing',
            'Project Management',
            'Leadership',
            'Finance',
            'Healthcare',
            'Education',
            'Art & Design',
            'Photography',
            'Video Production',
            'Game Development',
            'Quality Assurance',
            'Network Security',
            'Blockchain Technology'
        ];

        $name = $this->faker->randomElement($categoryNames);
        $slug = Str::slug($name) . '-' . $this->faker->unique()->numberBetween(1, 1000);

        return [
            'name' => $name . ' ' . $this->faker->numberBetween(1, 100),
            'slug' => $slug,
            'parent_id' => null, // Will be set when creating subcategories
            'tenant_id' => Tenant::factory(),
        ];
    }

    /**
     * Indicate that the category is a subcategory.
     */
    public function subcategory(): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => Category::factory()->create()->id,
        ]);
    }
}
