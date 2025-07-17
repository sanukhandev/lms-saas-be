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
        $channels = ['email', 'sms', 'in_app'];
        $channel = $this->faker->randomElement($channels);

        return [
            'title' => $this->generateTitle(),
            'message' => $this->generateMessage(),
            'channel' => $channel,
            'is_sent' => $this->faker->boolean(70), // 70% chance of being sent
            'scheduled_at' => $this->faker->optional(0.3)->dateTimeBetween('-1 week', '+1 week'),
            'sent_at' => $this->faker->optional(0.7)->dateTimeBetween('-1 week', 'now'),
            'user_id' => User::factory(),
            'course_id' => null, // Will be set in seeder if needed
            'tenant_id' => 1, // Will be overridden in seeder
            'created_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ];
    }

    /**
     * Generate title based on notification type.
     */
    private function generateTitle(): string
    {
        $titles = [
            'Course Enrollment Confirmation',
            'Upcoming Exam Reminder',
            'Assignment Due Soon',
            'New Content Available',
            'Grade Posted',
            'System Announcement',
            'Class Session Reminder',
            'Course Completion Certificate',
            'Payment Confirmation',
            'Profile Update Required'
        ];
        
        return $this->faker->randomElement($titles);
    }

    /**
     * Generate message based on notification type.
     */
    private function generateMessage(): string
    {
        $messages = [
            'You have been successfully enrolled in a new course. Start your learning journey today!',
            'Don\'t forget about your upcoming exam. Make sure to review all course materials.',
            'Your assignment is due soon. Please submit it before the deadline.',
            'New content has been added to your course. Check it out to continue your learning journey.',
            'Your grade has been posted. Log in to view your results.',
            'Important system announcement: Please check your course schedule for updates.',
            'Reminder: You have a class session scheduled. Don\'t miss it!',
            'Congratulations! Your course completion certificate is ready for download.',
            'Payment confirmed. Thank you for your enrollment.',
            'Please update your profile information to keep your account secure.'
        ];
        
        return $this->faker->randomElement($messages);
    }

    /**
     * Create an email notification.
     */
    public function email(): static
    {
        return $this->state(fn (array $attributes) => [
            'channel' => 'email',
        ]);
    }

    /**
     * Create an SMS notification.
     */
    public function sms(): static
    {
        return $this->state(fn (array $attributes) => [
            'channel' => 'sms',
        ]);
    }

    /**
     * Create an in-app notification.
     */
    public function inApp(): static
    {
        return $this->state(fn (array $attributes) => [
            'channel' => 'in_app',
        ]);
    }

    /**
     * Create a sent notification.
     */
    public function sent(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_sent' => true,
            'sent_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
        ]);
    }

    /**
     * Create a scheduled notification.
     */
    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_sent' => false,
            'scheduled_at' => $this->faker->dateTimeBetween('now', '+1 week'),
            'sent_at' => null,
        ]);
    }
}
