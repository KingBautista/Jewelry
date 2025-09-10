<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Customer>
 */
class CustomerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $genders = ['male', 'female', 'other'];
        $cities = ['Manila', 'Quezon City', 'Makati', 'Taguig', 'Pasig', 'Mandaluyong', 'Marikina', 'Parañaque', 'Las Piñas', 'Muntinlupa'];
        $countries = ['Philippines', 'United States', 'Canada', 'Australia', 'United Kingdom', 'Japan', 'South Korea', 'Singapore', 'Malaysia', 'Thailand'];

        return [
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email' => $this->faker->unique()->safeEmail(),
            'customer_salt' => $this->faker->sha256(),
            'customer_pass' => $this->faker->sha256(),
            'customer_activation_key' => $this->faker->optional(0.2)->sha256(),
            'phone' => $this->faker->phoneNumber(),
            'address' => $this->faker->streetAddress(),
            'city' => $this->faker->randomElement($cities),
            'state' => $this->faker->state(),
            'postal_code' => $this->faker->postcode(),
            'country' => $this->faker->randomElement($countries),
            'date_of_birth' => $this->faker->date('Y-m-d', '2000-01-01'),
            'gender' => $this->faker->randomElement($genders),
            'notes' => $this->faker->optional(0.3)->sentence(),
            'active' => $this->faker->boolean(85), // 85% chance of being active
        ];
    }

    /**
     * Indicate that the customer is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => true,
        ]);
    }

    /**
     * Indicate that the customer is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => false,
        ]);
    }

    /**
     * Create a customer with specific gender.
     */
    public function gender(string $gender): static
    {
        return $this->state(fn (array $attributes) => [
            'gender' => $gender,
        ]);
    }

    /**
     * Create a customer from a specific city.
     */
    public function fromCity(string $city): static
    {
        return $this->state(fn (array $attributes) => [
            'city' => $city,
        ]);
    }

    /**
     * Create a customer with specific age range.
     */
    public function ageBetween(int $minAge, int $maxAge): static
    {
        $minDate = now()->subYears($maxAge)->format('Y-m-d');
        $maxDate = now()->subYears($minAge)->format('Y-m-d');
        
        return $this->state(fn (array $attributes) => [
            'date_of_birth' => $this->faker->dateTimeBetween($minDate, $maxDate)->format('Y-m-d'),
        ]);
    }

    /**
     * Create a customer with complete address.
     */
    public function withCompleteAddress(): static
    {
        return $this->state(fn (array $attributes) => [
            'address' => $this->faker->streetAddress(),
            'city' => $this->faker->city(),
            'state' => $this->faker->state(),
            'postal_code' => $this->faker->postcode(),
            'country' => $this->faker->country(),
        ]);
    }

    /**
     * Create a customer with minimal information.
     */
    public function minimal(): static
    {
        return $this->state(fn (array $attributes) => [
            'phone' => null,
            'address' => null,
            'city' => null,
            'state' => null,
            'postal_code' => null,
            'country' => null,
            'date_of_birth' => null,
            'gender' => null,
            'notes' => null,
        ]);
    }
}