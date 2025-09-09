<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PaymentTerm>
 */
class PaymentTermFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $paymentTerms = [
            ['name' => 'Installment Plan A', 'code' => 'INSTALLMENT_A', 'down_payment' => 30.00, 'remaining' => 70.00, 'months' => 5],
            ['name' => 'Installment Plan B', 'code' => 'INSTALLMENT_B', 'down_payment' => 20.00, 'remaining' => 80.00, 'months' => 6],
            ['name' => 'Installment Plan C', 'code' => 'INSTALLMENT_C', 'down_payment' => 50.00, 'remaining' => 50.00, 'months' => 3],
            ['name' => 'Cash Payment', 'code' => 'CASH', 'down_payment' => 100.00, 'remaining' => 0.00, 'months' => 1],
            ['name' => 'Flexible Payment', 'code' => 'FLEXIBLE', 'down_payment' => 25.00, 'remaining' => 75.00, 'months' => 4],
        ];

        $term = $this->faker->randomElement($paymentTerms);

        return [
            'name' => $term['name'],
            'code' => $term['code'] . '_' . $this->faker->unique()->randomNumber(3),
            'down_payment_percentage' => $term['down_payment'],
            'remaining_percentage' => $term['remaining'],
            'term_months' => $term['months'],
            'description' => $this->faker->sentence(),
            'active' => $this->faker->boolean(80), // 80% chance of being active
        ];
    }

    /**
     * Indicate that the payment term is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => true,
        ]);
    }

    /**
     * Indicate that the payment term is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => false,
        ]);
    }

    /**
     * Create a cash payment term.
     */
    public function cash(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Cash Payment',
            'code' => 'CASH',
            'down_payment_percentage' => 100.00,
            'remaining_percentage' => 0.00,
            'term_months' => 1,
        ]);
    }

    /**
     * Create an installment payment term.
     */
    public function installment(): static
    {
        $downPayment = $this->faker->randomFloat(2, 20, 50);
        $remaining = 100 - $downPayment;
        $months = $this->faker->numberBetween(3, 12);

        return $this->state(fn (array $attributes) => [
            'name' => 'Installment Plan ' . $this->faker->randomLetter(),
            'code' => 'INSTALLMENT_' . strtoupper($this->faker->randomLetter()),
            'down_payment_percentage' => $downPayment,
            'remaining_percentage' => $remaining,
            'term_months' => $months,
        ]);
    }
}