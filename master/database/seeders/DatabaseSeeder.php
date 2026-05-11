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
        $password = env('ADMIN_PASSWORD', '');
        $generateRandom = false;

        if (empty($password)) {
            $password = 'YGXmaster@' . substr(str_replace(['+', '/', '='], '', base64_encode(random_bytes(8))), 0, 8);
            $generateRandom = true;
        }

        $user = User::firstOrCreate(
            ['email' => 'admin@ygxone.com'],
            [
                'name' => 'YGX Super Admin',
                'password' => Hash::make($password),
                'role' => 'super_admin',
                'status' => 'active',
                'timezone' => 'UTC',
            ]
        );

        if ($generateRandom && $user->wasRecentlyCreated) {
            $this->command->warn('');
            $this->command->info('╔══════════════════════════════════════════════════════════╗');
            $this->command->info('║  ⚠️  NO ADMIN_PASSWORD SET IN .env                       ║');
            $this->command->info('║  A random password was generated for first-time login:   ║');
            $this->command->info('║                                                          ║');
            $this->command->info("║  👤 Email:    admin@ygxone.com                             ║");
            $this->command->info("║  🔑 Password: {$password}                           ║");
            $this->command->info('║                                                          ║');
            $this->command->info('║  ⚡  Add ADMIN_PASSWORD=your_secure_password to .env     ║');
            $this->command->info('╚══════════════════════════════════════════════════════════╝');
            $this->command->warn('');
        }

        // Seed additional data
        $this->call([
            AppModuleSeeder::class,
            InfrastructureNodeSeeder::class,
        ]);
    }
}
