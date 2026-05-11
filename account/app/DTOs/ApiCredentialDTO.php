<?php

namespace App\DTOs;

use Illuminate\Http\Request;

final class ApiCredentialDTO
{
    public function __construct(
        public readonly string  $name,
        public readonly string  $type,
        public readonly int     $projectId,
        public readonly ?string $allowedIps = null,
        public readonly ?string $allowedOrigins = null,
        public readonly bool    $isActive = true,
    ) {}

    public static function fromRequest(Request $request, int $projectId): self
    {
        return new self(
            name:           $request->string('name')->trim()->value(),
            type:           $request->string('type', 'api_key')->value(),
            projectId:      $projectId,
            allowedIps:     $request->input('allowed_ips'),
            allowedOrigins: $request->input('allowed_origins'),
            isActive:       $request->boolean('is_active', true),
        );
    }

    /** Convert to an array suitable for Eloquent mass-assignment. */
    public function toArray(): array
    {
        return [
            'name'            => $this->name,
            'type'            => $this->type,
            'project_id'      => $this->projectId,
            'allowed_ips'     => $this->allowedIps,
            'allowed_origins' => $this->allowedOrigins,
            'is_active'       => $this->isActive,
        ];
    }
}
