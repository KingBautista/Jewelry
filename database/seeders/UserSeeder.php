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

        // Get all roles except Customer
        $roles = Role::where('name', '!=', 'Customer')->get();
        
        if ($roles->isEmpty()) {
            throw new \Exception('No roles found. Please run RoleSeeder first.');
        }

        // Create one user for each role (excluding Customer)
        foreach ($roles as $role) {
            $this->createUserForRole($role);
        }
    }

    /**
     * Create a user for the given role
     */
    private function createUserForRole(Role $role): void
    {
        // Generate unique username and email based on role
        $roleSlug = strtolower(str_replace(' ', '_', $role->name));
        $username = $roleSlug;
        $email = $roleSlug . '@illussso.com';
        
        // Generate password fields
        $salt = PasswordHelper::generateSalt();
        $password = PasswordHelper::generatePassword($salt, 'password123');

        // Create user
        $user = User::create([
            'user_login' => $username,
            'user_email' => $email,
            'user_pass' => $password,
            'user_salt' => $salt,
            'user_status' => 1,
            'user_activation_key' => null,
            'remember_token' => null,
            'user_role_id' => $role->id,
        ]);

        // Create user meta data based on role
        $metaData = $this->getMetaDataForRole($role);
        $user->saveUserMeta($metaData);
    }

    /**
     * Get meta data for the given role
     */
    private function getMetaDataForRole(Role $role): array
    {
        $roleMetaData = [
            'Developer Account' => [
                'first_name' => 'Developer',
                'last_name' => 'Account',
                'nickname' => 'Dev',
                'biography' => 'Developer account with full system access.',
                'theme' => 'dark',
            ],
            'Web Administrator' => [
                'first_name' => 'Web',
                'last_name' => 'Administrator',
                'nickname' => 'Admin',
                'biography' => 'Web administrator for jewelry business management.',
                'theme' => 'dark',
            ]
        ];

        return $roleMetaData[$role->name] ?? [
            'first_name' => 'User',
            'last_name' => $role->name,
            'nickname' => $role->name,
            'biography' => "User with {$role->name} role.",
            'theme' => 'light',
        ];
    }
} 