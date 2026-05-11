<?php

namespace App\Repositories;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class UserRepository implements UserRepositoryInterface
{
    public function __construct(private readonly User $model) {}

    public function findById(int $id): ?User
    {
        return $this->model->find($id);
    }

    public function findByEmail(string $email): ?User
    {
        return $this->model->where('email', $email)->first();
    }

    /**
     * Paginate users with optional search/status/account_type filters.
     */
    public function paginate(int $perPage = 20, array $filters = []): LengthAwarePaginator
    {
        $query = $this->model->newQuery();

        if (!empty($filters['search'])) {
            $term = $filters['search'];
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                  ->orWhere('email', 'like', "%{$term}%");
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['account_type'])) {
            $query->where('account_type', $filters['account_type']);
        }

        return $query->latest()->paginate($perPage);
    }

    public function updateStatus(int $id, string $status): bool
    {
        return (bool) $this->model->where('id', $id)->update(['status' => $status]);
    }

    public function delete(int $id): bool
    {
        return (bool) $this->model->where('id', $id)->delete();
    }
}
