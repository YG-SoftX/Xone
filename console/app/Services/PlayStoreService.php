<?php

namespace App\Services;

use App\Models\Project;
use App\Models\PlayStoreApp;
use Illuminate\Support\Facades\Storage;

class PlayStoreService
{
    /**
     * Submit new app to Play Store
     */
    public function submitApp(Project $project, array $data): PlayStoreApp
    {
        // Validate package name format
        if (!$this->isValidPackageName($data['package_name'])) {
            throw new \InvalidArgumentException('Invalid package name format. Use format: com.example.app');
        }

        // Check if package name already exists
        if (PlayStoreApp::where('package_name', $data['package_name'])->exists()) {
            throw new \Exception('Package name already registered');
        }

        $app = $project->playStoreApps()->create([
            'package_name' => $data['package_name'],
            'app_name' => $data['app_name'],
            'version' => $data['version'] ?? '1.0.0',
            'status' => 'draft',
            'price' => $data['price'] ?? 0,
            'is_paid' => ($data['price'] ?? 0) > 0,
            'metadata' => [
                'description' => $data['description'] ?? null,
                'category' => $data['category'] ?? null,
                'screenshots' => [],
                'icon' => null,
            ],
        ]);

        return $app;
    }

    /**
     * Upload APK/IPA file
     */
    public function uploadBinary(PlayStoreApp $app, $file): string
    {
        $path = $file->store('apps/' . $app->package_name, 'public');
        
        // Update metadata with file info
        $metadata = $app->metadata;
        $metadata['binary_path'] = $path;
        $metadata['file_size'] = $file->getSize();
        $metadata['uploaded_at'] = now()->toISOString();
        
        $app->update(['metadata' => $metadata]);

        return $path;
    }

    /**
     * Upload app icon
     */
    public function uploadIcon(PlayStoreApp $app, $file): string
    {
        $path = $file->store('icons/' . $app->package_name, 'public');
        
        $metadata = $app->metadata;
        $metadata['icon'] = $path;
        
        $app->update(['metadata' => $metadata]);

        return $path;
    }

    /**
     * Add screenshots
     */
    public function addScreenshots(PlayStoreApp $app, array $files): array
    {
        $paths = [];
        
        foreach ($files as $file) {
            $path = $file->store('screenshots/' . $app->package_name, 'public');
            $paths[] = $path;
        }

        $metadata = $app->metadata;
        $metadata['screenshots'] = array_merge(
            $metadata['screenshots'] ?? [],
            $paths
        );
        
        $app->update(['metadata' => $metadata]);

        return $paths;
    }

    /**
     * Submit app for review
     */
    public function submitForReview(PlayStoreApp $app): PlayStoreApp
    {
        // Validate required fields
        $this->validateAppCompleteness($app);

        $app->update([
            'status' => 'review',
            'metadata' => array_merge($app->metadata, [
                'submitted_at' => now()->toISOString(),
            ]),
        ]);

        // TODO: Trigger review process notification
        // Notification::send(...)->route('mail', 'review@ygxone.com')

        return $app;
    }

    /**
     * Approve app (admin only)
     */
    public function approveApp(PlayStoreApp $app): PlayStoreApp
    {
        $app->update([
            'status' => 'published',
            'metadata' => array_merge($app->metadata, [
                'approved_at' => now()->toISOString(),
                'published_at' => now()->toISOString(),
            ]),
        ]);

        return $app;
    }

    /**
     * Reject app (admin only)
     */
    public function rejectApp(PlayStoreApp $app, string $reason): PlayStoreApp
    {
        $app->update([
            'status' => 'rejected',
            'metadata' => array_merge($app->metadata, [
                'rejection_reason' => $reason,
                'rejected_at' => now()->toISOString(),
            ]),
        ]);

        return $app;
    }

    /**
     * Update app version
     */
    public function updateVersion(PlayStoreApp $app, string $newVersion): PlayStoreApp
    {
        $app->update([
            'version' => $newVersion,
            'status' => 'draft',
            'metadata' => array_merge($app->metadata, [
                'version_updated_at' => now()->toISOString(),
            ]),
        ]);

        return $app;
    }

    /**
     * Get app statistics
     */
    public function getAppStats(PlayStoreApp $app): array
    {
        return [
            'downloads' => $app->metadata['downloads'] ?? 0,
            'rating' => $app->metadata['rating'] ?? 0,
            'reviews_count' => $app->metadata['reviews_count'] ?? 0,
            'last_updated' => $app->updated_at,
        ];
    }

    /**
     * Validate package name format
     */
    protected function isValidPackageName(string $name): bool
    {
        return preg_match('/^[a-z][a-z0-9_]*(\.[a-z0-9_]+)+[0-9a-z_]$/i', $name) === 1;
    }

    /**
     * Validate app completeness before submission
     */
    protected function validateAppCompleteness(PlayStoreApp $app): void
    {
        $metadata = $app->metadata;

        if (empty($metadata['description'])) {
            throw new \Exception('App description is required');
        }

        if (empty($metadata['icon'])) {
            throw new \Exception('App icon is required');
        }

        if (empty($metadata['binary_path'])) {
            throw new \Exception('App binary (APK/IPA) is required');
        }

        if (empty($metadata['screenshots']) || count($metadata['screenshots']) < 2) {
            throw new \Exception('At least 2 screenshots are required');
        }
    }
}
