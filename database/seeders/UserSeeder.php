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
        User::truncate();
        UserMeta::truncate();

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
    }
} 