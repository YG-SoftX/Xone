<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\YgMasterService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class YgMasterController extends Controller
{
    protected YgMasterService $ygMasterService;

    public function __construct(YgMasterService $ygMasterService)
    {
        $this->ygMasterService = $ygMasterService;
    }

    /**
     * Receive commands from YG Master
     */
    public function handleCommand(Request $request): JsonResponse
    {
        // Verify request is from YG Master
        $signature = $request->header('X-Master-Signature');
        $secret = config('services.yg_master.webhook_secret');

        if (!$signature || !$this->verifySignature($request->getContent(), $signature, $secret)) {
            Log::warning('Invalid signature from YG Master', [
                'ip' => $request->ip(),
            ]);

            return response()->json(['error' => 'Invalid signature'], 401);
        }

        $command = $request->json()->all();

        try {
            $result = $this->ygMasterService->handleDeploymentCommand($command);

            if ($result['success']) {
                return response()->json($result, 200);
            }

            return response()->json($result, 500);

        } catch (\Exception $e) {
            Log::error('Failed to handle YG Master command', [
                'error' => $e->getMessage(),
                'command' => $command,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Internal error',
            ], 500);
        }
    }

    /**
     * Health check endpoint for YG Master monitoring
     */
    public function healthCheck(): JsonResponse
    {
        $stats = [
            'status' => 'healthy',
            'timestamp' => now()->toISOString(),
            'version' => config('app.version', '1.0.0'),
            'uptime_seconds' => cache()->get('service_start_time', time()),
            'metrics' => [
                'active_projects' => \App\Models\Project::where('status', 'active')->count(),
                'api_calls_today' => \App\Models\AiUsageLog::whereDate('created_at', today())->count(),
                'queue_size' => \Illuminate\Support\Facades\Queue::size(),
            ],
        ];

        return response()->json($stats, 200);
    }

    /**
     * Verify HMAC signature
     */
    protected function verifySignature(string $payload, string $signature, string $secret): bool
    {
        $expected = hash_hmac('sha256', $payload, $secret);
        return hash_equals($expected, $signature);
    }
}
