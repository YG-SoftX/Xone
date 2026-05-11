<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserDevice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DeviceFingerprintController extends Controller
{
    /**
     * Receive device fingerprint data from client-side JavaScript
     */
    public function submit(Request $request)
    {
        // Must be authenticated
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'screen_resolution' => 'nullable|string',
            'color_depth' => 'nullable|integer',
            'pixel_ratio' => 'nullable|numeric',
            'timezone' => 'nullable|string',
            'language' => 'nullable|string|max:10',
            'hardware_concurrency' => 'nullable|integer',
            'device_memory' => 'nullable|integer',
            'touch_support' => 'nullable|boolean',
            'canvas' => 'nullable|string',
            'webgl' => 'nullable|string',
            'fonts' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'accuracy' => 'nullable|integer',
        ]);

        try {
            $user = Auth::user();
            
            // Merge with server-side detected data
            $deviceData = array_merge($validated, [
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            // Register or update device
            $device = UserDevice::registerOrUpdate($user->id, $deviceData);

            Log::info('Device fingerprint updated', [
                'user_id' => $user->id,
                'device_id' => $device->id,
                'has_location' => isset($validated['latitude']),
            ]);

            return response()->json([
                'success' => true,
                'device_id' => $device->id,
                'reputation_score' => $device->device_reputation_score,
            ]);

        } catch (\Exception $e) {
            Log::error('Device fingerprint submission failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return response()->json(['error' => 'Failed to process device data'], 500);
        }
    }
}
