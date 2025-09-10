<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Customer;
use App\Helpers\PasswordHelper;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create specific customers with realistic data
        $customers = [
            [
                'first_name' => 'Maria',
                'last_name' => 'Santos',
                'email' => 'maria.santos@email.com',
                'phone' => '+63 917 123 4567',
                'address' => '123 Rizal Street',
                'city' => 'Manila',
                'state' => 'Metro Manila',
                'postal_code' => '1000',
                'country' => 'Philippines',
                'date_of_birth' => '1985-03-15',
                'gender' => 'female',
                'notes' => 'VIP customer, prefers gold jewelry',
                'active' => true,
            ],
            [
                'first_name' => 'Juan',
                'last_name' => 'Cruz',
                'email' => 'juan.cruz@email.com',
                'phone' => '+63 918 234 5678',
                'address' => '456 Quezon Avenue',
                'city' => 'Quezon City',
                'state' => 'Metro Manila',
                'postal_code' => '1100',
                'country' => 'Philippines',
                'date_of_birth' => '1990-07-22',
                'gender' => 'male',
                'notes' => 'Regular customer, interested in engagement rings',
                'active' => true,
            ],
            [
                'first_name' => 'Ana',
                'last_name' => 'Reyes',
                'email' => 'ana.reyes@email.com',
                'phone' => '+63 919 345 6789',
                'address' => '789 Ayala Avenue',
                'city' => 'Makati',
                'state' => 'Metro Manila',
                'postal_code' => '1200',
                'country' => 'Philippines',
                'date_of_birth' => '1992-11-08',
                'gender' => 'female',
                'notes' => 'Corporate client, bulk orders',
                'active' => true,
            ],
            [
                'first_name' => 'Pedro',
                'last_name' => 'Garcia',
                'email' => 'pedro.garcia@email.com',
                'phone' => '+63 920 456 7890',
                'address' => '321 BGC High Street',
                'city' => 'Taguig',
                'state' => 'Metro Manila',
                'postal_code' => '1630',
                'country' => 'Philippines',
                'date_of_birth' => '1988-05-12',
                'gender' => 'male',
                'notes' => 'Luxury jewelry collector',
                'active' => true,
            ],
            [
                'first_name' => 'Carmen',
                'last_name' => 'Lopez',
                'email' => 'carmen.lopez@email.com',
                'phone' => '+63 921 567 8901',
                'address' => '654 Ortigas Avenue',
                'city' => 'Pasig',
                'state' => 'Metro Manila',
                'postal_code' => '1600',
                'country' => 'Philippines',
                'date_of_birth' => '1995-09-30',
                'gender' => 'female',
                'notes' => 'New customer, interested in custom designs',
                'active' => true,
            ],
            [
                'first_name' => 'Roberto',
                'last_name' => 'Martinez',
                'email' => 'roberto.martinez@email.com',
                'phone' => '+63 922 678 9012',
                'address' => '987 Shaw Boulevard',
                'city' => 'Mandaluyong',
                'state' => 'Metro Manila',
                'postal_code' => '1550',
                'country' => 'Philippines',
                'date_of_birth' => '1983-12-03',
                'gender' => 'male',
                'notes' => 'Antique jewelry enthusiast',
                'active' => false,
            ],
            [
                'first_name' => 'Isabella',
                'last_name' => 'Rodriguez',
                'email' => 'isabella.rodriguez@email.com',
                'phone' => '+63 923 789 0123',
                'address' => '147 EDSA',
                'city' => 'Quezon City',
                'state' => 'Metro Manila',
                'postal_code' => '1105',
                'country' => 'Philippines',
                'date_of_birth' => '1991-01-18',
                'gender' => 'female',
                'notes' => 'Wedding jewelry specialist',
                'active' => true,
            ],
            [
                'first_name' => 'Miguel',
                'last_name' => 'Hernandez',
                'email' => 'miguel.hernandez@email.com',
                'phone' => '+63 924 890 1234',
                'address' => '258 Commonwealth Avenue',
                'city' => 'Quezon City',
                'state' => 'Metro Manila',
                'postal_code' => '1121',
                'country' => 'Philippines',
                'date_of_birth' => '1987-06-25',
                'gender' => 'male',
                'notes' => 'Investment jewelry buyer',
                'active' => true,
            ],
            [
                'first_name' => 'Sofia',
                'last_name' => 'Gonzalez',
                'email' => 'sofia.gonzalez@email.com',
                'phone' => '+63 925 901 2345',
                'address' => '369 Katipunan Avenue',
                'city' => 'Quezon City',
                'state' => 'Metro Manila',
                'postal_code' => '1108',
                'country' => 'Philippines',
                'date_of_birth' => '1993-04-14',
                'gender' => 'female',
                'notes' => 'Fashion jewelry lover',
                'active' => true,
            ],
            [
                'first_name' => 'Carlos',
                'last_name' => 'Perez',
                'email' => 'carlos.perez@email.com',
                'phone' => '+63 926 012 3456',
                'address' => '741 C5 Road',
                'city' => 'Taguig',
                'state' => 'Metro Manila',
                'postal_code' => '1632',
                'country' => 'Philippines',
                'date_of_birth' => '1989-10-07',
                'gender' => 'male',
                'notes' => 'Corporate gifts buyer',
                'active' => false,
            ]
        ];

        foreach ($customers as $customerData) {
            // Generate password fields for each customer
            $salt = PasswordHelper::generateSalt();
            $password = PasswordHelper::generatePassword($salt, 'password123'); // Default password
            $activation_key = PasswordHelper::generateSalt();
            
            $customerDataWithPassword = array_merge($customerData, [
                'customer_salt' => $salt,
                'customer_pass' => $password,
                'customer_activation_key' => $activation_key,
            ]);
            
            Customer::firstOrCreate(['email' => $customerData['email']], $customerDataWithPassword);
        }

        // Create additional random customers using factory
        Customer::factory(15)->create();
    }
}