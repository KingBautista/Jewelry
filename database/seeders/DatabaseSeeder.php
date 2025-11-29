<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
  /**
   * Seed the application's database.
   */
  public function run(): void
  {
    // Step 1: Core System Seeders (No Dependencies)
    $this->call(PermissionSeeder::class);
    $this->call(RoleSeeder::class);
    $this->call(NavigationSeeder::class);
    $this->call(EmailSettingSeeder::class);
    
    // Step 2: Role-Permission Relationships (Depends on Roles & Permissions)
    $this->call(RolePermissionSeeder::class);
    
    // Step 3: Financial Configuration Seeders (No Dependencies - Must run before CustomerSeeder)
    $this->call(TaxSeeder::class);
    $this->call(FeeSeeder::class);
    $this->call(DiscountSeeder::class);
    $this->call(PaymentTermSeeder::class);
    $this->call(PaymentMethodSeeder::class);
    $this->call(PaymentTypeSeeder::class);
    
    // Step 4: User Management (Depends on Roles)
    $this->call(UserSeeder::class);
    
    // Step 5: Business Data Seeders (Depends on Users, Customers, Financial Config)
    // $this->call(CustomerSeeder::class);
  }
}
