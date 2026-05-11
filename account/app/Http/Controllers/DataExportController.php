<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\SavedCredential;
use App\Models\Transaction;
use App\Models\UserSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Exception;

class DataExportController extends Controller
{
    public function export(Request $request)
    {
        try {
            $request->validate([
                'export_type' => 'required|in:account,activity,drive,mail,complete',
            ]);

            $user = $request->user();
            $exportType = $request->export_type;
            $data = [];

            switch ($exportType) {
                case 'account':
                    $data = [
                        'user' => $user->only(['name', 'email', 'username', 'phone', 'birthday', 'gender', 'recovery_email', 'created_at']),
                        'profile' => $user->profile?->toArray(),
                    ];
                    break;

                case 'activity':
                    $data = [
                        'activity_logs' => ActivityLog::where('user_id', $user->id)->get()->toArray(),
                    ];
                    break;

                case 'drive':
                    $data = [
                        'note' => 'Drive files export requires integration with YG Drive service. Contact support for full export.',
                    ];
                    break;

                case 'mail':
                    $data = [
                        'note' => 'Mail export requires integration with YG Mail service. Use YG Mail settings to export emails.',
                    ];
                    break;

                case 'complete':
                    $data = [
                        'user' => $user->only(['name', 'email', 'username', 'phone', 'birthday', 'gender', 'recovery_email', 'account_type', 'created_at']),
                        'profile' => $user->profile?->toArray(),
                        'activity_logs' => ActivityLog::where('user_id', $user->id)->get()->toArray(),
                        'transactions' => Transaction::where('user_id', $user->id)->get()->toArray(),
                        'subscriptions' => UserSubscription::where('user_id', $user->id)->get()->toArray(),
                        'saved_credentials' => SavedCredential::where('user_id', $user->id)->get(['site_name', 'site_url', 'username', 'created_at'])->toArray(),
                        'kyc' => $user->kyc?->only(['document_type', 'document_number', 'status']),
                    ];
                    break;
            }

            // Generate JSON file
            $filename = 'yg_export_' . $exportType . '_' . $user->id . '_' . now()->format('Y-m-d_H-i-s') . '.json';
            $jsonContent = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

            // Store temporarily
            $path = 'exports/' . $user->id . '/' . $filename;
            Storage::put($path, $jsonContent);

            ActivityLog::record(
                $user->id,
                'Exported data: ' . $exportType,
                'Privacy',
                '📥',
                $request->ip()
            );

            return response()->json([
                'success' => true,
                'message' => 'Data export completed. Your file is ready.',
                'filename' => $filename,
            ]);
        } catch (Exception $e) {
            Log::error('Data export error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to export data.',
            ], 500);
        }
    }
}
