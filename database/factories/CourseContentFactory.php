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
        $contentTypes = ['video', 'text', 'quiz', 'assignment', 'resource'];
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
            'content_type' => $this->faker->randomElement($contentTypes),
            'content_data' => $this->generateContentData(),
            'order' => $this->faker->numberBetween(1, 20),
            'is_published' => $this->faker->boolean(85), // 85% chance of being published
            'course_id' => Course::factory(),
        ];
    }

    /**
     * Generate content data based on type.
     */
    private function generateContentData(): array
    {
        $contentTypes = ['video', 'text', 'quiz', 'assignment', 'resource'];
        $type = $this->faker->randomElement($contentTypes);

        switch ($type) {
            case 'video':
                return [
                    'video_url' => $this->faker->url(),
                    'duration' => $this->faker->numberBetween(300, 3600), // 5 minutes to 1 hour
                    'thumbnail' => $this->faker->imageUrl(640, 360, 'technology'),
                ];
            case 'text':
                return [
                    'content' => $this->faker->paragraphs(5, true),
                    'reading_time' => $this->faker->numberBetween(5, 30), // 5-30 minutes
                ];
            case 'quiz':
                return [
                    'questions' => $this->generateQuizQuestions(),
                    'passing_score' => $this->faker->numberBetween(60, 90),
                ];
            case 'assignment':
                return [
                    'instructions' => $this->faker->paragraphs(3, true),
                    'due_date' => $this->faker->dateTimeBetween('+1 week', '+1 month'),
                    'max_points' => $this->faker->numberBetween(50, 100),
                ];
            case 'resource':
                return [
                    'file_url' => $this->faker->url(),
                    'file_type' => $this->faker->randomElement(['pdf', 'doc', 'zip', 'ppt']),
                    'file_size' => $this->faker->numberBetween(1024, 10485760), // 1KB to 10MB
                ];
            default:
                return [];
        }
    }

    /**
     * Generate quiz questions.
     */
    private function generateQuizQuestions(): array
    {
        $questions = [];
        $questionCount = $this->faker->numberBetween(3, 10);

        for ($i = 0; $i < $questionCount; $i++) {
            $questions[] = [
                'question' => $this->faker->sentence() . '?',
                'options' => [
                    $this->faker->sentence(),
                    $this->faker->sentence(),
                    $this->faker->sentence(),
                    $this->faker->sentence(),
                ],
                'correct_answer' => $this->faker->numberBetween(0, 3),
                'points' => $this->faker->numberBetween(1, 5),
            ];
        }

        return $questions;
    }

    /**
     * Indicate that the content is unpublished.
     */
    public function unpublished(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => false,
        ]);
    }
}
