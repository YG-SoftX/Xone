<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create the Master Sovereign Identity Node
        User::factory()->create([
            'name' => 'Sovereign Master',
            'email' => 'master@yg-account.com',
            'password' => bcrypt('password123'),
        ]);

        // This node serves as the SSO identity source for the entire ecosystem.
        
        // 2. Seed API Products for Developer Portal
        $this->call([
            ApiProductsSeeder::class,
            PricingPlansSeeder::class,
        ]);
    }
}
