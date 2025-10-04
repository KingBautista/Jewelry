<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Helpers\PasswordHelper;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Philippines-specific customers with realistic data
        $customers = [
            [
                'first_name' => 'Maria',
                'last_name' => 'Santos',
                'email' => 'maria.santos@email.com',
                'phone' => '+63 917 123 4567',
                'address' => '123 Rizal Street, Barangay San Antonio',
                'city' => 'Manila',
                'state' => 'Metro Manila',
                'postal_code' => '1000',
                'country' => 'Philippines',
                'date_of_birth' => '1985-03-15',
                'gender' => 'female',
                'notes' => 'VIP customer, mahilig sa ginto at alahas',
                'active' => true,
            ],
            [
                'first_name' => 'Juan',
                'last_name' => 'Cruz',
                'email' => 'juan.cruz@email.com',
                'phone' => '+63 918 234 5678',
                'address' => '456 Quezon Avenue, Barangay Pinyahan',
                'city' => 'Quezon City',
                'state' => 'Metro Manila',
                'postal_code' => '1100',
                'country' => 'Philippines',
                'date_of_birth' => '1990-07-22',
                'gender' => 'male',
                'notes' => 'Regular customer, naghahanap ng engagement ring',
                'active' => true,
            ],
            [
                'first_name' => 'Ana',
                'last_name' => 'Reyes',
                'email' => 'ana.reyes@email.com',
                'phone' => '+63 919 345 6789',
                'address' => '789 Ayala Avenue, Barangay San Lorenzo',
                'city' => 'Makati',
                'state' => 'Metro Manila',
                'postal_code' => '1200',
                'country' => 'Philippines',
                'date_of_birth' => '1992-11-08',
                'gender' => 'female',
                'notes' => 'Corporate client, malaking order ng alahas',
                'active' => true,
            ],
            [
                'first_name' => 'Pedro',
                'last_name' => 'Garcia',
                'email' => 'pedro.garcia@email.com',
                'phone' => '+63 920 456 7890',
                'address' => '321 BGC High Street, Barangay Fort Bonifacio',
                'city' => 'Taguig',
                'state' => 'Metro Manila',
                'postal_code' => '1630',
                'country' => 'Philippines',
                'date_of_birth' => '1988-05-12',
                'gender' => 'male',
                'notes' => 'Mahilig sa mamahaling alahas at koleksyon',
                'active' => true,
            ],
            [
                'first_name' => 'Carmen',
                'last_name' => 'Lopez',
                'email' => 'carmen.lopez@email.com',
                'phone' => '+63 921 567 8901',
                'address' => '654 Ortigas Avenue, Barangay San Antonio',
                'city' => 'Pasig',
                'state' => 'Metro Manila',
                'postal_code' => '1600',
                'country' => 'Philippines',
                'date_of_birth' => '1995-09-30',
                'gender' => 'female',
                'notes' => 'Bagong customer, gusto ng custom na alahas',
                'active' => true,
            ],
            [
                'first_name' => 'Roberto',
                'last_name' => 'Martinez',
                'email' => 'roberto.martinez@email.com',
                'phone' => '+63 922 678 9012',
                'address' => '987 Shaw Boulevard, Barangay Addition Hills',
                'city' => 'Mandaluyong',
                'state' => 'Metro Manila',
                'postal_code' => '1550',
                'country' => 'Philippines',
                'date_of_birth' => '1983-12-03',
                'gender' => 'male',
                'notes' => 'Mahilig sa lumang alahas at antiques',
                'active' => false,
            ],
            [
                'first_name' => 'Isabella',
                'last_name' => 'Rodriguez',
                'email' => 'isabella.rodriguez@email.com',
                'phone' => '+63 923 789 0123',
                'address' => '147 EDSA, Barangay Balingasa',
                'city' => 'Quezon City',
                'state' => 'Metro Manila',
                'postal_code' => '1105',
                'country' => 'Philippines',
                'date_of_birth' => '1991-01-18',
                'gender' => 'female',
                'notes' => 'Espesyalista sa wedding alahas',
                'active' => true,
            ],
            [
                'first_name' => 'Miguel',
                'last_name' => 'Hernandez',
                'email' => 'miguel.hernandez@email.com',
                'phone' => '+63 924 890 1234',
                'address' => '258 Commonwealth Avenue, Barangay Batasan Hills',
                'city' => 'Quezon City',
                'state' => 'Metro Manila',
                'postal_code' => '1121',
                'country' => 'Philippines',
                'date_of_birth' => '1987-06-25',
                'gender' => 'male',
                'notes' => 'Investment buyer ng alahas',
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
            
            // Prepare user data
            $userData = [
                'user_login' => $customerData['email'],
                'user_email' => $customerData['email'],
                'user_salt' => $salt,
                'user_pass' => $password,
                'user_status' => 1,
                'user_activation_key' => $activation_key,
                'user_role_id' => null,
            ];

            // Prepare customer meta data
            $customerMetaData = [
                'user_type' => 'customer',
                'customer_code' => User::generateCustomerCode(),
                'first_name' => $customerData['first_name'],
                'last_name' => $customerData['last_name'],
                'phone' => $customerData['phone'],
                'address' => $customerData['address'],
                'city' => $customerData['city'],
                'state' => $customerData['state'],
                'postal_code' => $customerData['postal_code'],
                'country' => $customerData['country'],
                'date_of_birth' => $customerData['date_of_birth'],
                'gender' => $customerData['gender'],
                'notes' => $customerData['notes'],
            ];
            
            // Check if user already exists
            $existingUser = User::where('user_email', $customerData['email'])->first();
            
            if (!$existingUser) {
                $user = User::create($userData);
                $user->saveUserMeta($customerMetaData);
            }
        }

        // Create additional random customers to reach 25 total
        $faker = \Faker\Factory::create();
        $cities = ['Manila', 'Quezon City', 'Makati', 'Taguig', 'Pasig', 'Mandaluyong', 'San Juan', 'Marikina', 'Pasay', 'Parañaque', 'Las Piñas', 'Muntinlupa', 'Caloocan', 'Malabon', 'Navotas', 'Valenzuela'];
        $genders = ['male', 'female', 'other'];
        
        // Calculate how many more customers we need to reach 25
        $existingCount = count($customers);
        $additionalCount = 25 - $existingCount;
        
        for ($i = 0; $i < $additionalCount; $i++) {
            $firstName = $faker->firstName();
            $lastName = $faker->lastName();
            $email = strtolower($firstName . '.' . $lastName . '@' . $faker->domainName());
            
            // Generate password fields
            $salt = PasswordHelper::generateSalt();
            $password = PasswordHelper::generatePassword($salt, 'password123');
            $activation_key = PasswordHelper::generateSalt();
            
            // Prepare user data
            $userData = [
                'user_login' => $email,
                'user_email' => $email,
                'user_salt' => $salt,
                'user_pass' => $password,
                'user_status' => $faker->boolean(80) ? 1 : 0, // 80% active
                'user_activation_key' => $activation_key,
                'user_role_id' => null,
            ];

            // Prepare customer meta data
            $customerMetaData = [
                'user_type' => 'customer',
                'customer_code' => User::generateCustomerCode(),
                'first_name' => $firstName,
                'last_name' => $lastName,
                'phone' => '+63 9' . $faker->numerify('## ### ####'),
                'address' => $faker->streetAddress(),
                'city' => $faker->randomElement($cities),
                'state' => 'Metro Manila',
                'postal_code' => $faker->numerify('####'),
                'country' => 'Philippines',
                'date_of_birth' => $faker->date('Y-m-d', '2000-01-01'),
                'gender' => $faker->randomElement($genders),
                'notes' => $faker->optional(0.3)->sentence(),
            ];
            
            // Check if user already exists
            $existingUser = User::where('user_email', $email)->first();
            
            if (!$existingUser) {
                $user = User::create($userData);
                $user->saveUserMeta($customerMetaData);
            }
        }
    }
}