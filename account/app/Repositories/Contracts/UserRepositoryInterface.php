<?php

namespace App\Repositories\Contracts;

use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

interface UserRepositoryInterface
{
    public function findById(int $id): ?User;

    public function findByEmail(string $email): ?User;

    /**
     * Paginate users with optional filters.
     *
     * Supported keys in $filters:
     *   - search       (string)  — partial match on name or email
     *   - status       (string)  — exact match on users.status
     *   - account_type (string)  — exact match on users.account_type
     */
    public function paginate(int $perPage = 20, array $filters = []): LengthAwarePaginator;

    public function updateStatus(int $id, string $status): bool;

    public function delete(int $id): bool;
}
