<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdminDeviceController extends Controller
{
    public function index($userId)
    {
        $user = User::findOrFail($userId);
        $devices = UserDevice::where('user_id', $userId)
            ->orderByDesc('last_active_at')
            ->get();

        return view('admin.devices.index', compact('user', 'devices'));
    }

    public function block(Request $request, $deviceId)
    {
        $device = UserDevice::findOrFail($deviceId);
        $device->update(['is_blocked' => true, 'is_trusted' => false]);

        Log::warning('Admin blocked user device', [
            'admin_user_id' => session('admin_user_id'),
            'device_id'     => $device->id,
            'user_id'       => $device->user_id,
            'device_name'   => $device->device_name,
            'ip'            => $request->ip(),
        ]);

        return redirect()->back()->with('success', 'Device blocked — the user will be signed out on next request.');
    }

    public function unblock(Request $request, $deviceId)
    {
        $device = UserDevice::findOrFail($deviceId);
        $device->update(['is_blocked' => false]);

        Log::info('Admin unblocked user device', [
            'admin_user_id' => session('admin_user_id'),
            'device_id'     => $device->id,
            'user_id'       => $device->user_id,
        ]);

        return redirect()->back()->with('success', 'Device unblocked.');
    }

    public function destroy(Request $request, $deviceId)
    {
        $device = UserDevice::findOrFail($deviceId);
        $userId = $device->user_id;

        Log::warning('Admin removed user device', [
            'admin_user_id' => session('admin_user_id'),
            'device_id'     => $device->id,
            'user_id'       => $userId,
            'device_name'   => $device->device_name,
            'ip'            => $request->ip(),
        ]);

        $device->delete();

        return redirect()->route('admin.devices.index', $userId)
            ->with('success', 'Device removed.');
    }

    public function destroyAll(Request $request, $userId)
    {
        // Only superadmin may wipe all devices.
        if (session('admin_user_id') !== 1) {
            return redirect()->back()->with('error', 'Only super-admin can remove all devices.');
        }

        $count = UserDevice::where('user_id', $userId)->count();
        UserDevice::where('user_id', $userId)->delete();

        Log::warning('Admin removed ALL devices for user', [
            'admin_user_id' => session('admin_user_id'),
            'user_id'       => $userId,
            'devices_count' => $count,
            'ip'            => $request->ip(),
        ]);

        return redirect()->route('admin.devices.index', $userId)
            ->with('success', "All {$count} device(s) removed. User will need to log in again.");
    }
}
