<?php

namespace App\Services;

use App\DTOs\UserDTO;
use App\Models\ActivityLog;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Hash;

class UserService
{
    /**
     * Create a new user account, provision a default wallet, and fire the
     * Registered event so email verification mail is dispatched automatically.
     */
    public function register(UserDTO $dto, string $ip): User
    {
        $user = User::create([
            'name'         => $dto->name,
            'email'        => $dto->email,
            'password'     => Hash::make($dto->password),
            'account_type' => $dto->accountType,
        ]);

        Wallet::firstOrCreate(
            ['user_id' => $user->id],
            ['balance' => 0.00, 'currency' => 'USD']
        );

        event(new Registered($user));

        ActivityLog::record($user->id, 'Created a new YG Account', 'Register', '✨', $ip);

        return $user;
    }

    /**
     * Apply a partial update to a user's profile fields and log the change.
     */
    public function updateProfile(User $user, array $data, string $ip): User
    {
        $user->fill($data)->save();

        ActivityLog::record($user->id, 'Updated profile', 'Profile', '👤', $ip);

        return $user->fresh();
    }

    /**
     * Revoke all API tokens and hard-delete the user account.
     */
    public function delete(User $user): void
    {
        $user->tokens()->delete();
        $user->delete();
    }
}
