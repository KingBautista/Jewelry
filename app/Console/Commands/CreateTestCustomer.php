<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Role;
use App\Helpers\PasswordHelper;

class CreateTestCustomer extends Command
{
    protected $signature = 'customer:create-test';
    protected $description = 'Create a test customer for portal testing';

    public function handle()
    {
        // Get or create customer role
        $customerRole = Role::where('name', 'customer')->first();
        if (!$customerRole) {
            $customerRole = Role::create([
                'name' => 'customer',
                'active' => 1,
                'is_super_admin' => 0
            ]);
            $this->info('Customer role created');
        }

        // Create test customer
        $salt = PasswordHelper::generateSalt();
        $password = PasswordHelper::generatePassword($salt, 'password123');
        
        $customer = User::create([
            'user_login' => 'testcustomer',
            'user_email' => 'customer@test.com',
            'user_pass' => $password,
            'user_salt' => $salt,
            'user_status' => 1,
            'user_role_id' => $customerRole->id
        ]);

        $this->info('Test customer created: customer@test.com / password123');
        return 0;
    }
}