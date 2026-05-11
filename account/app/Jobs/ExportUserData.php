<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\DataExport;
use Illuminate\Support\Facades\Storage;

class ExportUserData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 600;

    protected $dataExport;
    protected $user;

    /**
     * Create a new job instance.
     */
    public function __construct(DataExport $dataExport)
    {
        $this->dataExport = $dataExport;
        $this->user = $dataExport->user;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Update status to processing
            $this->dataExport->update(['status' => 'processing']);

            // Collect all user data
            $userData = $this->collectUserData();

            // Create JSON export file
            $jsonContent = json_encode($userData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            $fileName = "yg-account-data-export-{$this->user->id}-" . date('Y-m-d-H-i-s') . '.json';
            $filePath = "exports/{$fileName}";

            // Store file
            Storage::disk('local')->put($filePath, $jsonContent);

            // Update export record
            $this->dataExport->update([
                'status' => 'completed',
                'file_path' => $filePath,
                'completed_at' => now(),
            ]);

        } catch (\Exception $e) {
            $this->dataExport->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        // Safety net: ensure status is marked failed even if handle() explodes before the catch
        $this->dataExport->update([
            'status' => 'failed',
            'error_message' => $exception->getMessage(),
            'completed_at' => now(),
        ]);
    }

    /**
     * Collect all user data for export (GDPR Article 15 compliance)
     */
    protected function collectUserData(): array
    {
        return [
            'export_metadata' => [
                'exported_at' => now()->toISOString(),
                'requested_at' => $this->dataExport->requested_at->toISOString(),
                'user_id' => $this->user->id,
                'format_version' => '1.0',
            ],
            
            'profile_information' => [
                'name' => $this->user->name,
                'email' => $this->user->email,
                'phone' => $this->user->phone,
                'created_at' => $this->user->created_at->toISOString(),
                'updated_at' => $this->user->updated_at->toISOString(),
                'email_verified_at' => $this->user->email_verified_at?->toISOString(),
                'privacy_settings' => $this->user->privacy_settings,
                'notification_preferences' => $this->user->notification_preferences,
                'email_settings' => $this->user->email_settings,
            ],

            'login_history' => $this->user->sessions()
                ->orderByDesc('last_activity')
                ->limit(100)
                ->get()
                ->map(function ($session) {
                    return [
                        'ip_address' => $session->ip_address,
                        'user_agent' => $session->user_agent,
                        'last_activity' => $session->last_activity?->toISOString(),
                        'created_at' => $session->created_at->toISOString(),
                    ];
                })
                ->toArray(),

            'connected_social_accounts' => $this->user->socialAccounts()
                ->get()
                ->map(function ($account) {
                    return [
                        'provider' => $account->provider,
                        'email' => $account->email,
                        'name' => $account->name,
                        'connected_at' => $account->created_at->toISOString(),
                    ];
                })
                ->toArray(),

            'consent_records' => $this->user->consents()
                ->get()
                ->map(function ($consent) {
                    return [
                        'consent_type' => $consent->consent_type,
                        'preferences' => $consent->preferences,
                        'given_at' => $consent->created_at->toISOString(),
                        'withdrawn_at' => $consent->withdrawn_at?->toISOString(),
                        'ip_address' => $consent->ip_address,
                    ];
                })
                ->toArray(),

            'webhook_endpoints' => $this->user->webhooks()
                ->get()
                ->map(function ($webhook) {
                    return [
                        'url' => $webhook->url,
                        'events' => $webhook->events,
                        'description' => $webhook->description,
                        'is_active' => $webhook->is_active,
                        'created_at' => $webhook->created_at->toISOString(),
                    ];
                })
                ->toArray(),

            'developer_projects' => $this->user->developerProjects()
                ->with(['credentials', 'subscriptions'])
                ->get()
                ->map(function ($project) {
                    return [
                        'name' => $project->name,
                        'description' => $project->description,
                        'created_at' => $project->created_at->toISOString(),
                        'api_credentials_count' => $project->credentials->count(),
                        'active_subscriptions' => $project->subscriptions->pluck('product_name')->toArray(),
                    ];
                })
                ->toArray(),

            'billing_information' => [
                'invoices' => $this->user->invoices()
                    ->get()
                    ->map(function ($invoice) {
                        return [
                            'invoice_number' => $invoice->invoice_number,
                            'amount' => $invoice->amount,
                            'currency' => $invoice->currency,
                            'status' => $invoice->status,
                            'due_date' => $invoice->due_date?->toISOString(),
                            'paid_at' => $invoice->paid_at?->toISOString(),
                        ];
                    })
                    ->toArray(),
                
                'transactions' => $this->user->transactions()
                    ->get()
                    ->map(function ($transaction) {
                        return [
                            'transaction_id' => $transaction->transaction_id,
                            'amount' => $transaction->amount,
                            'currency' => $transaction->currency,
                            'status' => $transaction->status,
                            'provider' => $transaction->provider,
                            'processed_at' => $transaction->processed_at?->toISOString(),
                        ];
                    })
                    ->toArray(),
            ],

            'device_fingerprints' => $this->user->devices()
                ->get()
                ->map(function ($device) {
                    return [
                        'fingerprint_hash' => $device->fingerprint_hash,
                        'device_type' => $device->device_type,
                        'os_name' => $device->os_name,
                        'browser_name' => $device->browser_name,
                        'first_seen' => $device->first_seen->toISOString(),
                        'last_seen' => $device->last_seen->toISOString(),
                    ];
                })
                ->toArray(),
        ];
    }
}
