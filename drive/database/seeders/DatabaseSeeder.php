<?php

namespace Database\Seeders;

use App\Models\StorageQuota;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::create([
            'name'              => 'YG Drive Admin',
            'email'             => 'admin@ygxone.com',
            'password'          => Hash::make('admin123'),
            'role'              => 'super_admin',
            'status'            => 'active',
            'email_verified_at' => now(),
        ]);

        StorageQuota::create([
            'user_id'     => $admin->id,
            'quota_bytes' => PHP_INT_MAX,
            'plan'        => 'enterprise',
        ]);
    }
}
