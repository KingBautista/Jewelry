<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Delete existing records instead of truncate to avoid foreign key issues
        Role::query()->delete();

        // Data for Invoice and Payment Management System
        // (id, name, active, is_super_admin)
        $roles = [
            [1, 'Developer Account', 1, 1],
            [2, 'System Administrator', 1, 0],
            [3, 'Finance Manager', 1, 0],
            [4, 'Invoice Manager', 1, 0],
            [5, 'Customer Service', 1, 0],
            [6, 'Accountant', 1, 0],
            [7, 'Customer', 1, 0],
        ];

        foreach ($roles as $role) {
            [$id, $name, $active, $isSuperAdmin] = $role;
            Role::create([
                'id' => $id,
                'name' => $name,
                'active' => $active,
                'is_super_admin' => $isSuperAdmin,
            ]);
        }
    }
} 