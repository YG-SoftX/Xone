<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Default admin password is loaded from ADMIN_PASSWORD env var.
     * If not set, a secure random password is generated and displayed.
     */
    public function run(): void
    {
        // 1. Create Master Admin
        User::updateOrCreate(
            ['email' => 'admin@ygxone.com'],
            [
                'name' => 'YG Master Administrator',
                'password' => Hash::make('YgMaster@2026!Secure'),
                'role' => 'super_admin',
                'status' => 'active',
                'timezone' => 'UTC',
                'email_verified_at' => now(),
            ]
        );

        // 2. Create Regular User
        User::updateOrCreate(
            ['email' => 'user@ygxone.com'],
            [
                'name' => 'Standard User',
                'password' => Hash::make('Password123!'),
                'role' => 'user',
                'status' => 'active',
                'timezone' => 'UTC',
                'email_verified_at' => now(),
            ]
        );

        $this->command->info('╔══════════════════════════════════════════════════════════╗');
        $this->command->info('║  ✅  TEST ACCOUNTS CREATED                                ║');
        $this->command->info('║                                                          ║');
        $this->command->info('║  👑 MASTER ADMIN:                                        ║');
        $this->command->info("║  👤 Email:    admin@ygxone.com                           ║");
        $this->command->info("║  🔑 Password: YgMaster@2026!Secure                       ║");
        $this->command->info('║                                                          ║');
        $this->command->info('║  👤 REGULAR USER:                                        ║');
        $this->command->info("║  👤 Email:    user@ygxone.com                            ║");
        $this->command->info("║  🔑 Password: Password123!                               ║");
        $this->command->info('╚══════════════════════════════════════════════════════════╝');

        // Seed additional data
        $this->call([
            AppModuleSeeder::class,
            InfrastructureNodeSeeder::class,
            UniversalFooterItemsSeeder::class,
        ]);
    }
}
