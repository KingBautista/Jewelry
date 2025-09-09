<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tax>
 */
class TaxFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $taxTypes = [
            ['name' => 'VAT', 'code' => 'VAT', 'rate' => 12.00],
            ['name' => 'Sales Tax', 'code' => 'SALES_TAX', 'rate' => 8.00],
            ['name' => 'Service Tax', 'code' => 'SERVICE_TAX', 'rate' => 10.00],
            ['name' => 'Withholding Tax', 'code' => 'WHT', 'rate' => 2.00],
        ];

        $tax = $this->faker->randomElement($taxTypes);

        return [
            'name' => $tax['name'],
            'code' => $tax['code'] . '_' . $this->faker->unique()->randomNumber(3),
            'rate' => $tax['rate'],
            'description' => $this->faker->sentence(),
            'active' => $this->faker->boolean(80), // 80% chance of being active
        ];
    }

    /**
     * Indicate that the tax is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => true,
        ]);
    }

    /**
     * Indicate that the tax is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => false,
        ]);
    }
}