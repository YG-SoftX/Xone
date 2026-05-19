<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * AdminUserSeeder
 * 
 * Creates the initial super admin user for YG Master.
 */
class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@ygxone.com'],
            [
                'name' => 'YG Master Administrator',
                'email' => 'admin@ygxone.com',
                'password' => Hash::make('YgMaster@2026!Secure'),
                'role' => 'super_admin',
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        $user = User::updateOrCreate(
            ['email' => 'user@ygxone.com'],
            [
                'name' => 'Test User',
                'email' => 'user@ygxone.com',
                'password' => Hash::make('Password123!'),
                'role' => 'user',
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        $this->command->info("✅ Users created successfully!");
        $this->command->info("--- ADMIN ---");
        $this->command->info("Email: admin@ygxone.com");
        $this->command->info("Password: YgMaster@2026!Secure");
        $this->command->info("--- USER ---");
        $this->command->info("Email: user@ygxone.com");
        $this->command->info("Password: Password123!");
        $this->command->warn("⚠️  Change these passwords immediately in production!");
    }
}
