<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Discount>
 */
class DiscountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $discountTypes = [
            ['name' => 'First-time Buyer', 'code' => 'FIRST_TIME', 'amount' => 1000.00, 'type' => 'fixed'],
            ['name' => 'Bulk Purchase', 'code' => 'BULK', 'amount' => 10.00, 'type' => 'percentage'],
            ['name' => 'Loyalty Discount', 'code' => 'LOYALTY', 'amount' => 5.00, 'type' => 'percentage'],
            ['name' => 'Seasonal Sale', 'code' => 'SEASONAL', 'amount' => 500.00, 'type' => 'fixed'],
            ['name' => 'VIP Customer', 'code' => 'VIP', 'amount' => 15.00, 'type' => 'percentage'],
        ];

        $discount = $this->faker->randomElement($discountTypes);

        return [
            'name' => $discount['name'],
            'code' => $discount['code'] . '_' . $this->faker->unique()->randomNumber(3),
            'amount' => $discount['amount'],
            'type' => $discount['type'],
            'description' => $this->faker->sentence(),
            'valid_from' => $this->faker->optional(0.7)->dateTimeBetween('-1 month', '+1 month'),
            'valid_until' => $this->faker->optional(0.7)->dateTimeBetween('+1 month', '+6 months'),
            'usage_limit' => $this->faker->optional(0.5)->numberBetween(10, 1000),
            'used_count' => $this->faker->numberBetween(0, 50),
            'active' => $this->faker->boolean(80), // 80% chance of being active
        ];
    }

    /**
     * Indicate that the discount is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => true,
        ]);
    }

    /**
     * Indicate that the discount is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => false,
        ]);
    }

    /**
     * Indicate that the discount is fixed amount.
     */
    public function fixed(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'fixed',
            'amount' => $this->faker->randomFloat(2, 100, 2000),
        ]);
    }

    /**
     * Indicate that the discount is percentage.
     */
    public function percentage(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'percentage',
            'amount' => $this->faker->randomFloat(2, 5, 25),
        ]);
    }

    /**
     * Indicate that the discount is valid.
     */
    public function valid(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => true,
            'valid_from' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'valid_until' => $this->faker->dateTimeBetween('+1 month', '+6 months'),
            'used_count' => $this->faker->numberBetween(0, 10),
        ]);
    }
}