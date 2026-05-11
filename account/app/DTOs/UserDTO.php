<?php

namespace App\DTOs;

use Illuminate\Http\Request;

final class UserDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $password,
        public readonly string $accountType = 'personal',
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            name:        $request->string('name')->trim()->value(),
            email:       $request->string('email')->lower()->value(),
            password:    $request->string('password')->value(),
            accountType: $request->input('account_type', 'personal'),
        );
    }

    public static function fromArray(array $data): self
    {
        return new self(
            name:        trim($data['name']),
            email:       strtolower($data['email']),
            password:    $data['password'],
            accountType: $data['account_type'] ?? 'personal',
        );
    }
}
