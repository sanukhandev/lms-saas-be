<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Feedback;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Feedback>
 */
class FeedbackFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $feedbackTypes = ['course_rating', 'instructor_feedback', 'general_feedback', 'bug_report', 'feature_request'];
        $ratings = [1, 2, 3, 4, 5];

        $positiveComments = [
            'Excellent course content and well-structured modules.',
            'Great instructor with clear explanations.',
            'Very helpful and informative content.',
            'The course exceeded my expectations.',
            'Practical examples made learning easier.',
            'Well-paced course with good exercises.',
            'The instructor was very knowledgeable and engaging.',
            'Clear and concise explanations throughout.',
            'Great value for money.',
            'I would definitely recommend this course.',
        ];

        $neutralComments = [
            'Good course overall, but could use more examples.',
            'The content was okay, but delivery could be improved.',
            'Average course with some useful information.',
            'The course was fine but nothing exceptional.',
            'Some parts were good, others could be better.',
        ];

        $negativeComments = [
            'The course was too basic for my level.',
            'Poor audio quality in some videos.',
            'Not enough practical exercises.',
            'The instructor spoke too fast.',
            'Content was outdated.',
            'Too much theory, not enough hands-on practice.',
            'Poorly organized course materials.',
        ];

        $rating = $this->faker->randomElement($ratings);
        $comment = '';

        if ($rating >= 4) {
            $comment = $this->faker->randomElement($positiveComments);
        } elseif ($rating == 3) {
            $comment = $this->faker->randomElement($neutralComments);
        } else {
            $comment = $this->faker->randomElement($negativeComments);
        }

        return [
            'type' => $this->faker->randomElement($feedbackTypes),
            'rating' => $rating,
            'comment' => $comment,
            'is_anonymous' => $this->faker->boolean(20), // 20% chance of being anonymous
            'is_approved' => $this->faker->boolean(85), // 85% chance of being approved
            'response' => $this->faker->boolean(30) ? $this->faker->paragraph() : null,
            'responded_at' => $this->faker->boolean(30) ? $this->faker->dateTimeBetween('-1 month', 'now') : null,
            'course_id' => Course::factory(),
            'student_id' => User::factory()->state(['role' => 'student']),
            'responded_by' => $this->faker->boolean(30) ? User::factory()->state(['role' => 'tutor']) : null,
            'tenant_id' => 1, // Will be overridden in seeder
        ];
    }

    /**
     * Create positive feedback.
     */
    public function positive(): static
    {
        return $this->state(fn (array $attributes) => [
            'rating' => $this->faker->randomElement([4, 5]),
            'comment' => $this->faker->randomElement([
                'Excellent course content and well-structured modules.',
                'Great instructor with clear explanations.',
                'Very helpful and informative content.',
                'The course exceeded my expectations.',
                'Practical examples made learning easier.',
            ]),
        ]);
    }

    /**
     * Create negative feedback.
     */
    public function negative(): static
    {
        return $this->state(fn (array $attributes) => [
            'rating' => $this->faker->randomElement([1, 2]),
            'comment' => $this->faker->randomElement([
                'The course was too basic for my level.',
                'Poor audio quality in some videos.',
                'Not enough practical exercises.',
                'The instructor spoke too fast.',
                'Content was outdated.',
            ]),
        ]);
    }

    /**
     * Create anonymous feedback.
     */
    public function anonymous(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_anonymous' => true,
        ]);
    }

    /**
     * Create feedback with response.
     */
    public function withResponse(): static
    {
        return $this->state(fn (array $attributes) => [
            'response' => $this->faker->paragraph(),
            'responded_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'responded_by' => User::factory()->state(['role' => 'tutor']),
        ]);
    }
}
