<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\UserMeta;
use App\Models\Role;
use App\Helpers\PasswordHelper;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Delete existing records instead of truncate to avoid foreign key issues
        UserMeta::query()->delete();
        User::query()->delete();

        // Get Developer Account role
        $developerRole = Role::where('name', 'Developer Account')->first();
        
        if (!$developerRole) {
            throw new \Exception('Developer Account role not found. Please run RoleSeeder first.');
        }

        // Generate salt and password using PasswordHelper
        $salt = PasswordHelper::generateSalt();
        $password = PasswordHelper::generatePassword($salt, 'password123');

        // Create developer user
        $developerUser = User::create([
            'user_login' => 'developer',
            'user_email' => 'developer@example.com',
            'user_pass' => $password,
            'user_salt' => $salt,
            'user_status' => 1,
            'user_activation_key' => null,
            'remember_token' => null,
            'user_role_id' => $developerRole->id,
        ]);

        // Create user meta data
        $userMetaData = [
            'first_name' => 'Developer',
            'last_name' => 'Account',
            'nickname' => 'Dev',
            'biography' => 'Developer account with full system access.',
            'theme' => 'dark',
        ];

        // Save user meta data
        $developerUser->saveUserMeta($userMetaData);

        // Add admin user for system management
        $adminSalt = PasswordHelper::generateSalt();
        $adminPassword = PasswordHelper::generatePassword($adminSalt, 'password123');
        $adminUser = User::create([
            'user_login' => 'admin',
            'user_email' => 'admin@invoice-system.com',
            'user_pass' => $adminPassword,
            'user_salt' => $adminSalt,
            'user_status' => 1,
            'user_activation_key' => null,
            'remember_token' => null,
            'user_role_id' => 2, // Assuming 2 is Admin role
        ]);
        $adminMetaData = [
            'first_name' => 'System',
            'last_name' => 'Administrator',
            'nickname' => 'Admin',
            'biography' => 'System administrator for invoice and payment management.',
            'theme' => 'light',
        ];
        $adminUser->saveUserMeta($adminMetaData);

        // Add finance manager user
        $financeSalt = PasswordHelper::generateSalt();
        $financePassword = PasswordHelper::generatePassword($financeSalt, 'password123');
        $financeUser = User::create([
            'user_login' => 'finance',
            'user_email' => 'finance@invoice-system.com',
            'user_pass' => $financePassword,
            'user_salt' => $financeSalt,
            'user_status' => 1,
            'user_activation_key' => null,
            'remember_token' => null,
            'user_role_id' => 3, // Assuming 3 is Finance Manager role
        ]);
        $financeMetaData = [
            'first_name' => 'Sarah',
            'last_name' => 'Chen',
            'nickname' => 'Finance',
            'biography' => 'Finance manager responsible for financial configurations and reporting.',
            'theme' => 'light',
        ];
        $financeUser->saveUserMeta($financeMetaData);

        // Add invoice manager user
        $invoiceSalt = PasswordHelper::generateSalt();
        $invoicePassword = PasswordHelper::generatePassword($invoiceSalt, 'password123');
        $invoiceUser = User::create([
            'user_login' => 'invoice',
            'user_email' => 'invoice@invoice-system.com',
            'user_pass' => $invoicePassword,
            'user_salt' => $invoiceSalt,
            'user_status' => 1,
            'user_activation_key' => null,
            'remember_token' => null,
            'user_role_id' => 4, // Assuming 4 is Invoice Manager role
        ]);
        $invoiceMetaData = [
            'first_name' => 'Michael',
            'last_name' => 'Rodriguez',
            'nickname' => 'Invoice',
            'biography' => 'Invoice manager handling invoice creation and management.',
            'theme' => 'light',
        ];
        $invoiceUser->saveUserMeta($invoiceMetaData);

        // Add customer service user
        $customerSalt = PasswordHelper::generateSalt();
        $customerPassword = PasswordHelper::generatePassword($customerSalt, 'password123');
        $customerUser = User::create([
            'user_login' => 'customer',
            'user_email' => 'customer@invoice-system.com',
            'user_pass' => $customerPassword,
            'user_salt' => $customerSalt,
            'user_status' => 1,
            'user_activation_key' => null,
            'remember_token' => null,
            'user_role_id' => 5, // Assuming 5 is Customer Service role
        ]);
        $customerMetaData = [
            'first_name' => 'Emily',
            'last_name' => 'Thompson',
            'nickname' => 'Customer',
            'biography' => 'Customer service representative managing customer relationships.',
            'theme' => 'light',
        ];
        $customerUser->saveUserMeta($customerMetaData);

        // Add accountant user
        $accountantSalt = PasswordHelper::generateSalt();
        $accountantPassword = PasswordHelper::generatePassword($accountantSalt, 'password123');
        $accountantUser = User::create([
            'user_login' => 'accountant',
            'user_email' => 'accountant@invoice-system.com',
            'user_pass' => $accountantPassword,
            'user_salt' => $accountantSalt,
            'user_status' => 1,
            'user_activation_key' => null,
            'remember_token' => null,
            'user_role_id' => 6, // Assuming 6 is Accountant role
        ]);
        $accountantMetaData = [
            'first_name' => 'David',
            'last_name' => 'Kim',
            'nickname' => 'Accountant',
            'biography' => 'Accountant handling payment processing and financial records.',
            'theme' => 'light',
        ];
        $accountantUser->saveUserMeta($accountantMetaData);

        // Add customer portal user
        $customerPortalSalt = PasswordHelper::generateSalt();
        $customerPortalPassword = PasswordHelper::generatePassword($customerPortalSalt, 'password123');
        $customerPortalUser = User::create([
            'user_login' => 'customer_portal',
            'user_email' => 'customer@invoice-system.com',
            'user_pass' => $customerPortalPassword,
            'user_salt' => $customerPortalSalt,
            'user_status' => 1,
            'user_activation_key' => null,
            'remember_token' => null,
            'user_role_id' => 7, // Customer role
        ]);
        $customerPortalMetaData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'nickname' => 'Customer',
            'biography' => 'Customer portal user for testing invoice and payment functionality.',
            'theme' => 'light',
            'phone' => '+1-555-0123',
            'address' => '123 Main Street, Anytown, ST 12345',
            'user_type' => 'customer',
            'customer_code' => 'CUST000001',
        ];
        $customerPortalUser->saveUserMeta($customerPortalMetaData);

        // Create additional staff users to reach 25 total
        $faker = \Faker\Factory::create();
        $roles = [2, 3, 4, 5, 6]; // Admin, Finance Manager, Customer Service, Sales Rep, Accountant
        $themes = ['light', 'dark'];
        
        // Calculate how many more users we need to reach 25 total
        $existingCount = 5; // developer, admin, finance, customer service, accountant
        $additionalCount = 25 - $existingCount;
        
        for ($i = 0; $i < $additionalCount; $i++) {
            $firstName = $faker->firstName();
            $lastName = $faker->lastName();
            $email = strtolower($firstName . '.' . $lastName . '@invoice-system.com');
            $username = strtolower($firstName . '.' . $lastName);
            
            // Generate password fields
            $salt = PasswordHelper::generateSalt();
            $password = PasswordHelper::generatePassword($salt, 'password123');
            
            $user = User::create([
                'user_login' => $username,
                'user_email' => $email,
                'user_pass' => $password,
                'user_salt' => $salt,
                'user_status' => $faker->boolean(90) ? 1 : 0, // 90% active
                'user_activation_key' => $faker->boolean(10) ? PasswordHelper::generateSalt() : null, // 10% pending
                'remember_token' => null,
                'user_role_id' => $faker->randomElement($roles),
            ]);
            
            $userMetaData = [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'nickname' => $faker->optional(0.7)->firstName(),
                'biography' => $faker->optional(0.6)->sentence(),
                'theme' => $faker->randomElement($themes),
            ];
            
            $user->saveUserMeta($userMetaData);
        }
    }
} 