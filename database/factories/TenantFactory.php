<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company,
            'slug' => $this->faker->slug,
            'domain' => $this->faker->domainName,
            'status' => 'active',
            'settings' => json_encode([
                'theme' => 'default',
                'features' => ['courses', 'quizzes', 'assignments']
            ]),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'inactive',
        ]);
    }
}
