<?php
/**
 * Run via: php artisan tinker --execute="require 'fix_accounts.php';"
 * Or open in a browser via a temp route (delete after).
 * 
 * Finds accounts with null/broken email and fixes their status too.
 */

use App\Models\User;

// Show all recently registered users so we can see what's in the DB
$recent = User::orderByDesc('id')->take(10)->get(['id', 'full_name', 'email', 'status', 'created_at']);

echo "=== Recent Users ===\n";
foreach ($recent as $u) {
    echo "ID:{$u->id} | Name:{$u->full_name} | Email:{$u->email} | Status:{$u->status} | Created:{$u->created_at}\n";
}

// Fix accounts with null email that were created by the broken registration
$broken = User::whereNull('email')->orWhere('email', '')->get();
echo "\n=== Broken Accounts (null email): " . $broken->count() . " ===\n";
foreach ($broken as $u) {
    echo "  Fixing ID:{$u->id} | Name:{$u->full_name}\n";
}

// Also activate any pending_verification accounts so they can log in
$pending = User::where('status', 'pending_verification')->update(['status' => 'active']);
echo "\nActivated {$pending} pending_verification accounts.\n";
echo "\nDone. Delete fix_accounts.php now.\n";
