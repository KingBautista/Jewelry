<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Navigation;

class NavigationSeeder extends Seeder
{
    public function run(): void
    {
        // Delete existing records instead of truncate to avoid foreign key issues
        Navigation::query()->delete();

        // Create parent navigations first
        $parents = [
            'invoice_management' => Navigation::create([
                'name' => 'Invoice Management',
                'slug' => 'invoice-management',
                'icon' => 'file',
                'parent_id' => null,
                'active' => 1,
                'show_in_menu' => 1
            ]),
            'payment_management' => Navigation::create([
                'name' => 'Payment Management',
                'slug' => 'payment-management',
                'icon' => 'dollarSign',
                'parent_id' => null,
                'active' => 1,
                'show_in_menu' => 1
            ]),
            'customer_management' => Navigation::create([
                'name' => 'Customer Management',
                'slug' => 'customer-management',
                'icon' => 'user',
                'parent_id' => null,
                'active' => 1,
                'show_in_menu' => 1
            ]),
            'financial_management' => Navigation::create([
                'name' => 'Financial Management',
                'slug' => 'financial-management',
                'icon' => 'chartLine',
                'parent_id' => null,
                'active' => 1,
                'show_in_menu' => 1
            ]),
            'user_management' => Navigation::create([
                'name' => 'User Management',
                'slug' => 'user-management',
                'icon' => 'users',
                'parent_id' => null,
                'active' => 1,
                'show_in_menu' => 1
            ]),
            'system_settings' => Navigation::create([
                'name' => 'System Settings',
                'slug' => 'system-settings',
                'icon' => 'cog',
                'parent_id' => null,
                'active' => 1,
                'show_in_menu' => 1
            ])
        ];

        // Create child navigations
        $navigations = [
            // User Management Children
            [
                'name' => 'All Users',
                'slug' => 'user-management/users',
                'icon' => 'users',
                'parent_id' => $parents['user_management']->id,
                'active' => 1,
                'show_in_menu' => 1
            ],
            [
                'name' => 'Permission Settings',
                'slug' => 'user-management/roles',
                'icon' => 'lock',
                'parent_id' => $parents['user_management']->id,
                'active' => 1,
                'show_in_menu' => 1
            ],

            // Invoice Management Children
            [
                'name' => 'All Invoices',
                'slug' => 'invoice-management/invoices',
                'icon' => 'file',
                'parent_id' => $parents['invoice_management']->id,
                'active' => 1,
                'show_in_menu' => 1
            ],

            // Payment Management Children
            [
                'name' => 'All Payments',
                'slug' => 'payment-management/payments',
                'icon' => 'dollarSign',
                'parent_id' => $parents['payment_management']->id,
                'active' => 1,
                'show_in_menu' => 1
            ],

            // Customer Management Children
            [
                'name' => 'All Customers',
                'slug' => 'customer-management/customers',
                'icon' => 'user',
                'parent_id' => $parents['customer_management']->id,
                'active' => 1,
                'show_in_menu' => 1
            ],

            // Financial Management Children
            [
                'name' => 'Taxes',
                'slug' => 'financial-management/taxes',
                'icon' => 'calculator',
                'parent_id' => $parents['financial_management']->id,
                'active' => 1,
                'show_in_menu' => 1
            ],
            [
                'name' => 'Fees',
                'slug' => 'financial-management/fees',
                'icon' => 'tag',
                'parent_id' => $parents['financial_management']->id,
                'active' => 1,
                'show_in_menu' => 1
            ],
            [
                'name' => 'Discounts',
                'slug' => 'financial-management/discounts',
                'icon' => 'percent',
                'parent_id' => $parents['financial_management']->id,
                'active' => 1,
                'show_in_menu' => 1
            ],
            [
                'name' => 'Payment Terms',
                'slug' => 'financial-management/payment-terms',
                'icon' => 'calendarAlt',
                'parent_id' => $parents['financial_management']->id,
                'active' => 1,
                'show_in_menu' => 1
            ],
            [
                'name' => 'Payment Methods',
                'slug' => 'financial-management/payment-methods',
                'icon' => 'creditCard',
                'parent_id' => $parents['financial_management']->id,
                'active' => 1,
                'show_in_menu' => 1
            ],

            // System Settings Children
            [
                'name' => 'Navigation Settings',
                'slug' => 'system-settings/navigation',
                'icon' => 'list',
                'parent_id' => $parents['system_settings']->id,
                'active' => 1,
                'show_in_menu' => 1
            ],
            [
                'name' => 'Audit Trail',
                'slug' => 'system-settings/audit-trail',
                'icon' => 'history',
                'parent_id' => $parents['system_settings']->id,
                'active' => 1,
                'show_in_menu' => 1
            ]
        ];

        // Create child navigations
        foreach ($navigations as $navigation) {
            Navigation::create($navigation);
        }
    }
} 