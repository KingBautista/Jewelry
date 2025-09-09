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
    // Basic Settings Seeders
    $this->call(PermissionSeeder::class);
    $this->call(RoleSeeder::class);
    $this->call(UserSeeder::class);
    $this->call(NavigationSeeder::class);
    $this->call(RolePermissionSeeder::class);
    
    // Financial Configuration Seeders
    $this->call(TaxSeeder::class);
    $this->call(FeeSeeder::class);
    $this->call(DiscountSeeder::class);
    $this->call(PaymentTermSeeder::class);
    $this->call(PaymentMethodSeeder::class);
    
    // $this->call(MediaLibrarySeeder::class);
  }
}
