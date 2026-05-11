<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        \App\Models\Mail::create([
            'from' => 'yg-soft@world.com',
            'to' => 'user@yginbox.com',
            'subject' => 'Welcome to YG Sovereign Mail Node',
            'body' => 'Welcome to the most secure, privacy-focused mailing ecosystem. Your identity node is now active and E2EE ready.',
            'folder' => 'inbox',
            'read' => true
        ]);

        \App\Models\Mail::create([
            'from' => 'node04@sovereign.node',
            'to' => 'user@yginbox.com',
            'subject' => 'DISPATCH: Encrypted Strategy Intel',
            'body' => 'E2EE_CIPHER::JUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUy',
            'folder' => 'inbox',
            'read' => false
        ]);

        \App\Models\Mail::create([
            'from' => 'finance@ygpay.com',
            'to' => 'user@yginbox.com',
            'subject' => 'YG Pay: Corporate Asset Settlement',
            'body' => 'Protocol Alpha: Please settle the node deployment dues. [YG_INVOICE:12500]',
            'folder' => 'inbox',
            'read' => false
        ]);

        \App\Models\Mail::create([
            'from' => 'legal@sovereign.node',
            'to' => 'user@yginbox.com',
            'subject' => 'Protocol 09-C: Non-Disclosure Sync',
            'body' => 'E2EE_CIPHER::JUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUyJUUy',
            'folder' => 'inbox',
            'read' => false
        ]);
    }
}
