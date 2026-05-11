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
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $this->command->info("Super admin user created:");
        $this->command->info("Email: admin@ygxone.com");
        $this->command->info("Password: YgMaster@2026!Secure");
        $this->command->warn("⚠️  Change this password immediately after first login!");
    }
}
