<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Fee>
 */
class FeeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $feeTypes = [
            ['name' => 'Delivery Fee', 'code' => 'DELIVERY', 'amount' => 500.00, 'type' => 'fixed'],
            ['name' => 'Processing Fee', 'code' => 'PROCESSING', 'amount' => 50.00, 'type' => 'fixed'],
            ['name' => 'Installation Fee', 'code' => 'INSTALLATION', 'amount' => 1000.00, 'type' => 'fixed'],
            ['name' => 'Service Fee', 'code' => 'SERVICE', 'amount' => 5.00, 'type' => 'percentage'],
            ['name' => 'Handling Fee', 'code' => 'HANDLING', 'amount' => 2.50, 'type' => 'percentage'],
        ];

        $fee = $this->faker->randomElement($feeTypes);

        return [
            'name' => $fee['name'],
            'code' => $fee['code'] . '_' . $this->faker->unique()->randomNumber(3),
            'amount' => $fee['amount'],
            'type' => $fee['type'],
            'description' => $this->faker->sentence(),
            'active' => $this->faker->boolean(80), // 80% chance of being active
        ];
    }

    /**
     * Indicate that the fee is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => true,
        ]);
    }

    /**
     * Indicate that the fee is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => false,
        ]);
    }

    /**
     * Indicate that the fee is fixed amount.
     */
    public function fixed(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'fixed',
            'amount' => $this->faker->randomFloat(2, 10, 1000),
        ]);
    }

    /**
     * Indicate that the fee is percentage.
     */
    public function percentage(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'percentage',
            'amount' => $this->faker->randomFloat(2, 1, 20),
        ]);
    }
}