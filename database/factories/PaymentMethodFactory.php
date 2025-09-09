<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PaymentMethod>
 */
class PaymentMethodFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $banks = [
            'BDO', 'BPI', 'Metrobank', 'Security Bank', 'EastWest Bank',
            'RCBC', 'UnionBank', 'PNB', 'Landbank', 'Chinabank'
        ];

        $bank = $this->faker->randomElement($banks);

        return [
            'bank_name' => $bank,
            'account_name' => $this->faker->company(),
            'account_number' => $this->faker->numerify('##########'),
            'description' => $this->faker->sentence(),
            'qr_code_image' => $this->faker->optional(0.3)->imageUrl(200, 200, 'business'),
            'active' => $this->faker->boolean(80), // 80% chance of being active
        ];
    }

    /**
     * Indicate that the payment method is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => true,
        ]);
    }

    /**
     * Indicate that the payment method is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => false,
        ]);
    }

    /**
     * Create a BDO payment method.
     */
    public function bdo(): static
    {
        return $this->state(fn (array $attributes) => [
            'bank_name' => 'BDO',
            'account_name' => 'Jewelry Store Inc.',
            'account_number' => $this->faker->numerify('##########'),
        ]);
    }

    /**
     * Create a BPI payment method.
     */
    public function bpi(): static
    {
        return $this->state(fn (array $attributes) => [
            'bank_name' => 'BPI',
            'account_name' => 'Jewelry Store Inc.',
            'account_number' => $this->faker->numerify('##########'),
        ]);
    }

    /**
     * Create a Metrobank payment method.
     */
    public function metrobank(): static
    {
        return $this->state(fn (array $attributes) => [
            'bank_name' => 'Metrobank',
            'account_name' => 'Jewelry Store Inc.',
            'account_number' => $this->faker->numerify('##########'),
        ]);
    }
}