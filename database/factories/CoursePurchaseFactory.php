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
        $amount = $this->faker->randomFloat(2, 29.99, 299.99);
        $paymentMethods = ['credit_card', 'paypal', 'bank_transfer', 'stripe'];
        $statuses = ['pending', 'completed', 'cancelled', 'refunded'];

        return [
            'amount' => $amount,
            'currency' => $this->faker->randomElement(['USD', 'EUR', 'GBP']),
            'payment_method' => $this->faker->randomElement($paymentMethods),
            'payment_status' => $this->faker->randomElement($statuses),
            'transaction_id' => 'TXN-' . strtoupper($this->faker->bothify('???-###-???')),
            'purchase_date' => $this->faker->dateTimeBetween('-6 months', 'now'),
            'payment_data' => $this->generatePaymentData(),
            'course_id' => Course::factory(),
            'student_id' => User::factory()->state(['role' => 'student']),
        ];
    }

    /**
     * Generate payment data.
     */
    private function generatePaymentData(): array
    {
        return [
            'payment_gateway' => $this->faker->randomElement(['stripe', 'paypal', 'square']),
            'gateway_transaction_id' => $this->faker->uuid(),
            'payment_fee' => $this->faker->randomFloat(2, 0.99, 9.99),
            'discount_applied' => $this->faker->boolean(30),
            'discount_amount' => $this->faker->boolean(30) ? $this->faker->randomFloat(2, 5, 50) : 0,
            'coupon_code' => $this->faker->boolean(20) ? strtoupper($this->faker->bothify('???##')) : null,
        ];
    }

    /**
     * Indicate that the purchase is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_status' => 'completed',
        ]);
    }

    /**
     * Indicate that the purchase is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_status' => 'pending',
        ]);
    }

    /**
     * Indicate that the purchase is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_status' => 'cancelled',
        ]);
    }

    /**
     * Indicate that the purchase is refunded.
     */
    public function refunded(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_status' => 'refunded',
        ]);
    }
}
