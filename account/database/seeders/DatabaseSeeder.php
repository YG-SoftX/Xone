<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create the Master Sovereign Identity Node
        User::updateOrCreate(
            ['email' => 'master@yg-account.com'],
            [
                'name' => 'Sovereign Master',
                'password' => bcrypt('password123'),
                'email_verified_at' => now(),
            ]
        );

        // This node serves as the SSO identity source for the entire ecosystem.
        
        // 2. Seed API Products for Developer Portal
        $this->call([
            ApiProductsSeeder::class,
            PricingPlansSeeder::class,
        ]);
    }
}
