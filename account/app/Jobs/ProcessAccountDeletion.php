<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\AccountDeletionRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessAccountDeletion implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 300;

    protected $deletionRequest;

    /**
     * Create a new job instance.
     */
    public function __construct(AccountDeletionRequest $deletionRequest)
    {
        $this->deletionRequest = $deletionRequest;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Check if deletion was cancelled
            if ($this->deletionRequest->cancelled_at) {
                Log::info('Account deletion cancelled', [
                    'user_id' => $this->deletionRequest->user_id,
                ]);
                return;
            }

            // Get user
            $user = User::find($this->deletionRequest->user_id);
            
            if (!$user) {
                Log::warning('User not found for deletion', [
                    'user_id' => $this->deletionRequest->user_id,
                ]);
                return;
            }

            DB::beginTransaction();

            // Delete related data in correct order (foreign key constraints)
            
            // 1. Delete developer projects and related data
            $user->developerProjects()->each(function (\App\Models\DeveloperProject $project) {
                $project->credentials()->delete();
                $project->subscriptions()->delete();
                $project->members()->delete();
                $project->quotas()->delete();
                $project->webhooks()->delete();
                $project->billing()->delete();
                $project->planSubscriptions()->delete();
                $project->delete();
            });

            // 2. Delete social accounts
            $user->socialAccounts()->delete();

            // 3. Delete webhooks
            $user->webhooks()->delete();

            // 4. Delete consents
            $user->consents()->delete();

            // 5. Delete data exports
            $user->dataExports()->delete();

            // 6. Delete sessions
            $user->sessions()->delete();

            // 7. Delete billing data
            $user->invoices()->delete();
            $user->transactions()->delete();
            $user->billingAccounts()->delete();

            // 8. Delete device data
            $user->devices()->each(function (\App\Models\UserDevice $device) {
                $device->activityLogs()->delete();
                $device->accountLinks()->delete();
                $device->delete();
            });

            // 9. Finally delete the user
            $user->delete();

            // Mark deletion as completed
            $this->deletionRequest->update([
                'completed_at' => now(),
            ]);

            DB::commit();

            Log::info('Account successfully deleted (GDPR Article 17)', [
                'user_id' => $this->deletionRequest->user_id,
                'email' => $user->email,
                'requested_at' => $this->deletionRequest->requested_at,
                'completed_at' => now(),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Account deletion failed', [
                'user_id' => $this->deletionRequest->user_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        // Mark the deletion request as permanently failed so the user can be informed
        $this->deletionRequest->update(['status' => 'failed']);

        Log::error('ProcessAccountDeletion permanently failed after all retries', [
            'user_id' => $this->deletionRequest->user_id,
            'error'   => $exception->getMessage(),
        ]);
    }
}
