<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\CoursePurchase;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CoursePurchase>
 */
class CoursePurchaseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $currencies = ['USD', 'EUR', 'GBP', 'CAD', 'AUD'];
        $amounts = [49.99, 99.99, 149.99, 199.99, 299.99, 399.99];
        
        return [
            'student_id' => User::factory(),
            'course_id' => Course::factory(),
            'amount_paid' => $this->faker->randomElement($amounts),
            'currency' => $this->faker->randomElement($currencies),
            'invoice_id' => null, // Will be set if needed
            'access_start_date' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'access_expires_at' => $this->faker->dateTimeBetween('+6 months', '+2 years'),
            'is_active' => $this->faker->boolean(90),
            'tenant_id' => 1, // Will be overridden in seeder
            'created_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ];
    }

    /**
     * Indicate that the purchase is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the purchase is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the purchase access has expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'access_expires_at' => $this->faker->dateTimeBetween('-1 month', '-1 day'),
        ]);
    }
}
