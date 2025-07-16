<?php

namespace Database\Factories;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Notification>
 */
class NotificationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = ['course_enrollment', 'exam_reminder', 'assignment_due', 'new_content', 'grade_posted', 'system_announcement'];
        $type = $this->faker->randomElement($types);

        return [
            'type' => $type,
            'title' => $this->generateTitle($type),
            'message' => $this->generateMessage($type),
            'data' => $this->generateData($type),
            'is_read' => $this->faker->boolean(30), // 30% chance of being read
            'read_at' => $this->faker->boolean(30) ? $this->faker->dateTimeBetween('-1 week', 'now') : null,
            'user_id' => User::factory(),
            'created_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ];
    }

    /**
     * Generate title based on notification type.
     */
    private function generateTitle(string $type): string
    {
        switch ($type) {
            case 'course_enrollment':
                return 'Course Enrollment Confirmation';
            case 'exam_reminder':
                return 'Upcoming Exam Reminder';
            case 'assignment_due':
                return 'Assignment Due Soon';
            case 'new_content':
                return 'New Content Available';
            case 'grade_posted':
                return 'Grade Posted';
            case 'system_announcement':
                return 'System Announcement';
            default:
                return 'Notification';
        }
    }

    /**
     * Generate message based on notification type.
     */
    private function generateMessage(string $type): string
    {
        switch ($type) {
            case 'course_enrollment':
                return 'You have successfully enrolled in the course. Start learning now!';
            case 'exam_reminder':
                return 'You have an exam scheduled for tomorrow. Make sure to prepare and review the materials.';
            case 'assignment_due':
                return 'Your assignment is due in 2 days. Please submit it on time to avoid penalties.';
            case 'new_content':
                return 'New content has been added to your course. Check it out to continue your learning journey.';
            case 'grade_posted':
                return 'Your grade has been posted for the recent assessment. Check your progress dashboard.';
            case 'system_announcement':
                return 'Important system maintenance is scheduled for this weekend. Plan accordingly.';
            default:
                return 'You have a new notification.';
        }
    }

    /**
     * Generate data based on notification type.
     */
    private function generateData(string $type): array
    {
        switch ($type) {
            case 'course_enrollment':
                return [
                    'course_id' => $this->faker->numberBetween(1, 100),
                    'course_name' => $this->faker->sentence(3),
                    'enrollment_date' => $this->faker->dateTimeBetween('-1 week', 'now')->format('Y-m-d'),
                ];
            case 'exam_reminder':
                return [
                    'exam_id' => $this->faker->numberBetween(1, 50),
                    'exam_title' => $this->faker->sentence(4),
                    'exam_date' => $this->faker->dateTimeBetween('tomorrow', '+1 week')->format('Y-m-d H:i'),
                    'duration' => $this->faker->numberBetween(60, 180),
                ];
            case 'assignment_due':
                return [
                    'assignment_id' => $this->faker->numberBetween(1, 200),
                    'assignment_title' => $this->faker->sentence(5),
                    'due_date' => $this->faker->dateTimeBetween('+1 day', '+1 week')->format('Y-m-d H:i'),
                    'course_id' => $this->faker->numberBetween(1, 100),
                ];
            case 'new_content':
                return [
                    'content_id' => $this->faker->numberBetween(1, 500),
                    'content_title' => $this->faker->sentence(4),
                    'content_type' => $this->faker->randomElement(['video', 'text', 'quiz', 'assignment']),
                    'course_id' => $this->faker->numberBetween(1, 100),
                ];
            case 'grade_posted':
                return [
                    'grade' => $this->faker->numberBetween(60, 100),
                    'max_grade' => 100,
                    'assessment_title' => $this->faker->sentence(3),
                    'course_id' => $this->faker->numberBetween(1, 100),
                ];
            case 'system_announcement':
                return [
                    'priority' => $this->faker->randomElement(['low', 'medium', 'high']),
                    'category' => $this->faker->randomElement(['maintenance', 'feature', 'security']),
                    'effective_date' => $this->faker->dateTimeBetween('now', '+1 week')->format('Y-m-d'),
                ];
            default:
                return [];
        }
    }

    /**
     * Create an unread notification.
     */
    public function unread(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_read' => false,
            'read_at' => null,
        ]);
    }

    /**
     * Create a read notification.
     */
    public function read(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_read' => true,
            'read_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
        ]);
    }
}
