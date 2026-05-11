<?php

namespace App\Services;

class AppRegistryService
{
    /** @return array<string, array> All apps sorted by order */
    public function all(): array
    {
        $apps = config('ecosystem.apps', []);
        uasort($apps, fn ($a, $b) => ($a['order'] ?? 99) <=> ($b['order'] ?? 99));
        return $apps;
    }

    /** @return array|null App config or null if not found */
    public function get(string $id): ?array
    {
        return config("ecosystem.apps.{$id}");
    }

    public function path(string $id): string
    {
        return $this->get($id)['path'] ?? '';
    }

    public function isLaravel(string $id): bool
    {
        return ($this->get($id)['type'] ?? 'laravel') === 'laravel';
    }

    /** Check whether the app directory actually exists on disk */
    public function exists(string $id): bool
    {
        $path = $this->path($id);
        return $path !== '' && is_dir($path);
    }
}
