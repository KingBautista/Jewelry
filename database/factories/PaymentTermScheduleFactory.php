<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\PaymentTerm;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PaymentTermSchedule>
 */
class PaymentTermScheduleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'payment_term_id' => PaymentTerm::factory(),
            'month_number' => $this->faker->numberBetween(1, 12),
            'percentage' => $this->faker->randomFloat(2, 5, 50),
            'description' => $this->faker->sentence(3),
        ];
    }

    /**
     * Create a schedule for a specific month.
     */
    public function forMonth(int $month): static
    {
        return $this->state(fn (array $attributes) => [
            'month_number' => $month,
        ]);
    }

    /**
     * Create a schedule with a specific percentage.
     */
    public function withPercentage(float $percentage): static
    {
        return $this->state(fn (array $attributes) => [
            'percentage' => $percentage,
        ]);
    }

    /**
     * Create a schedule for a specific payment term.
     */
    public function forPaymentTerm(PaymentTerm $paymentTerm): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_term_id' => $paymentTerm->id,
        ]);
    }
}
