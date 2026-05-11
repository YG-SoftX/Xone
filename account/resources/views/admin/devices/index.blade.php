@extends('admin.master-layout')

@section('title', 'Devices — ' . $user->name)

@section('content')
<div class="space-y-6">

    {{-- Page Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Device Management & Tracking</h2>
            <p class="text-sm text-gray-500 mt-1">
                Monitoring hardware identifiers and geolocation for
                <a href="{{ route('admin.users.show', $user->id) }}" class="text-blue-600 hover:underline font-medium">{{ $user->name }}</a>
            </p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.users.show', $user->id) }}"
               class="px-4 py-2 text-sm bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition">
                ← Back to User
            </a>
            @if($devices->count() > 0)
            <button onclick="document.getElementById('modal-remove-all').classList.remove('hidden')"
                class="px-4 py-2 text-sm bg-red-600 hover:bg-red-700 text-white rounded-lg transition font-medium">
                🗑 Remove All Devices
            </button>
            @endif
        </div>
    </div>

    {{-- Device Count Summary --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 flex items-center gap-4">
            <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center text-blue-600 text-xl">📱</div>
            <div>
                <p class="text-2xl font-bold text-gray-900">{{ $devices->count() }}</p>
                <p class="text-xs text-gray-500">Total Devices</p>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 flex items-center gap-4">
            <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center text-green-600 text-xl">✅</div>
            <div>
                <p class="text-2xl font-bold text-gray-900">{{ $devices->where('is_blocked', false)->count() }}</p>
                <p class="text-xs text-gray-500">Active</p>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 flex items-center gap-4">
            <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center text-purple-600 text-xl">📍</div>
            <div>
                <p class="text-2xl font-bold text-gray-900">{{ $devices->whereNotNull('latitude')->count() }}</p>
                <p class="text-xs text-gray-500">Geo-Tracked</p>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 flex items-center gap-4">
            <div class="w-10 h-10 bg-yellow-100 rounded-full flex items-center justify-center text-yellow-600 text-xl">🛡</div>
            <div>
                <p class="text-2xl font-bold text-gray-900">{{ round($devices->avg('device_reputation_score') ?? 0) }}%</p>
                <p class="text-xs text-gray-500">Avg Trust Score</p>
            </div>
        </div>
    </div>

    {{-- Devices Table --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
            <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Hardware & Tracking Data</h3>
            <span class="text-xs text-gray-400">Total: {{ $devices->count() }} registered</span>
        </div>

        @if($devices->isEmpty())
            <div class="py-16 text-center text-gray-400">
                <div class="text-5xl mb-3">📵</div>
                <p class="text-lg font-medium">No devices registered</p>
                <p class="text-sm mt-1">This user has no saved devices.</p>
            </div>
        @else
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Device & Identifiers</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Location & IP</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Hardware Info</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reputation</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Last Active</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($devices as $device)
                    <tr class="{{ $device->is_blocked ? 'bg-red-50' : '' }} hover:bg-gray-50 transition group">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-3">
                                <span class="text-2xl">
                                    @if($device->device_type === 'mobile' || str_contains(strtolower($device->os ?? ''), 'android') || str_contains(strtolower($device->os ?? ''), 'ios'))
                                        📱
                                    @elseif($device->device_type === 'tablet')
                                        📋
                                    @else
                                        💻
                                    @endif
                                </span>
                                <div>
                                    <p class="text-sm font-bold text-gray-900">{{ $device->device_name ?? 'Unknown Device' }}</p>
                                    <div class="flex flex-col gap-0.5 mt-1">
                                        @if($device->imei)
                                            <p class="text-[10px] text-gray-500 font-mono">IMEI: {{ $device->imei }}</p>
                                        @endif
                                        @if($device->device_id)
                                            <p class="text-[10px] text-gray-400 font-mono">UID: {{ Str::limit($device->device_id, 12) }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <div class="flex flex-col">
                                <span class="text-gray-900 font-medium">
                                    @if($device->city || $device->country_code)
                                        {{ $device->city ?? 'Unknown' }}, {{ $device->country_code ?? 'XX' }}
                                    @else
                                        <span class="text-gray-400 italic">No GPS data</span>
                                    @endif
                                </span>
                                <span class="text-xs font-mono text-gray-500">{{ $device->ip_address ?? '—' }}</span>
                                @if($device->latitude && $device->longitude)
                                    <a href="https://www.google.com/maps?q={{ $device->latitude }},{{ $device->longitude }}" target="_blank" class="text-[10px] text-blue-500 hover:underline mt-1">
                                        View on Map ↗
                                    </a>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                            <div class="flex flex-col">
                                <span>{{ $device->os ?? '—' }} {{ $device->os_version }}</span>
                                <span class="text-xs text-gray-400">{{ $device->browser ?? '—' }} {{ $device->browser_version }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex flex-col gap-1 w-24">
                                <div class="flex justify-between text-[10px] font-medium">
                                    <span class="text-gray-500">Trust</span>
                                    <span class="{{ $device->device_reputation_score > 70 ? 'text-green-600' : ($device->device_reputation_score > 30 ? 'text-yellow-600' : 'text-red-600') }}">
                                        {{ $device->device_reputation_score }}%
                                    </span>
                                </div>
                                <div class="w-full bg-gray-100 rounded-full h-1.5 overflow-hidden">
                                    <div class="h-full rounded-full {{ $device->device_reputation_score > 70 ? 'bg-green-500' : ($device->device_reputation_score > 30 ? 'bg-yellow-500' : 'bg-red-500') }}"
                                         style="width: {{ $device->device_reputation_score }}%"></div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $device->last_active_at ? $device->last_active_at->diffForHumans() : '—' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex flex-col gap-1">
                                @if($device->is_blocked)
                                    <span class="px-2 py-0.5 text-[10px] font-bold bg-red-100 text-red-800 rounded uppercase">Blocked</span>
                                @else
                                    <span class="px-2 py-0.5 text-[10px] font-bold bg-green-100 text-green-800 rounded uppercase">Active</span>
                                @endif
                                
                                @if($device->is_trusted)
                                    <span class="px-2 py-0.5 text-[10px] font-bold bg-blue-100 text-blue-800 rounded uppercase">Trusted</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <div class="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                <button onclick="showDeviceDetails({{ json_encode($device) }})"
                                    class="p-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition" title="Extended Info">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                </button>
                                
                                @if($device->is_blocked)
                                    <form method="POST" action="{{ route('admin.devices.unblock', $device->id) }}">
                                        @csrf
                                        <button type="submit" class="p-2 text-green-600 hover:bg-green-50 rounded-lg transition" title="Unblock">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        </button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('admin.devices.block', $device->id) }}">
                                        @csrf
                                        <button type="submit" class="p-2 text-yellow-600 hover:bg-yellow-50 rounded-lg transition" title="Block">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
                                        </button>
                                    </form>
                                @endif

                                <form method="POST" action="{{ route('admin.devices.destroy', $device->id) }}"
                                    onsubmit="return confirmDeviceRemove(this, '{{ addslashes($device->device_name ?? 'this device') }}')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition" title="Remove">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>

{{-- Extended Device Details Modal --}}
<div id="modal-device-details" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden animate-in fade-in zoom-in duration-200">
        <div class="bg-gray-900 px-6 py-4 flex justify-between items-center">
            <h3 class="text-lg font-bold text-white flex items-center gap-2">
                <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                Extended Device Intelligence
            </h3>
            <button onclick="document.getElementById('modal-device-details').classList.add('hidden')" class="text-gray-400 hover:text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l18 18"></path></svg>
            </button>
        </div>
        <div class="p-6 overflow-y-auto max-h-[80vh]">
            <div class="grid grid-cols-2 gap-6">
                {{-- Column 1: Identity --}}
                <div class="space-y-4">
                    <div>
                        <h4 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">Hardware Identity</h4>
                        <div class="bg-gray-50 rounded-lg p-3 space-y-2">
                            <div class="flex justify-between">
                                <span class="text-xs text-gray-500">IMEI</span>
                                <span class="text-xs font-mono font-bold" id="det-imei"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-xs text-gray-500">Android ID</span>
                                <span class="text-xs font-mono" id="det-android-id"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-xs text-gray-500">IDFA (iOS)</span>
                                <span class="text-xs font-mono" id="det-idfa"></span>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <h4 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">Fingerprinting</h4>
                        <div class="bg-gray-50 rounded-lg p-3 space-y-2">
                            <div class="flex justify-between">
                                <span class="text-xs text-gray-500">Canvas Hash</span>
                                <span class="text-[10px] font-mono truncate w-32 text-right" id="det-canvas"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-xs text-gray-500">Resolution</span>
                                <span class="text-xs font-mono" id="det-res"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-xs text-gray-500">Timezone</span>
                                <span class="text-xs" id="det-tz"></span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Column 2: Security & Tracking --}}
                <div class="space-y-4">
                    <div>
                        <h4 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">Geolocation Tracking</h4>
                        <div class="bg-blue-50 rounded-lg p-3 space-y-2 border border-blue-100">
                            <div class="flex justify-between">
                                <span class="text-xs text-gray-500">Latitude</span>
                                <span class="text-xs font-bold text-blue-700" id="det-lat"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-xs text-gray-500">Longitude</span>
                                <span class="text-xs font-bold text-blue-700" id="det-lon"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-xs text-gray-500">Accuracy</span>
                                <span class="text-xs" id="det-acc"></span>
                            </div>
                            <div class="mt-2 pt-2 border-t border-blue-100">
                                <p class="text-[10px] text-blue-600 font-medium">Last Location Update:</p>
                                <p class="text-xs text-blue-800" id="det-loc-time"></p>
                            </div>
                        </div>
                    </div>

                    <div>
                        <h4 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">Risk Assessment</h4>
                        <div id="det-risk-container" class="bg-red-50 rounded-lg p-3 border border-red-100 hidden">
                            <p class="text-[10px] text-red-600 font-bold mb-1">Detected Risk Flags:</p>
                            <ul id="det-risk-list" class="text-xs text-red-800 list-disc list-inside"></ul>
                        </div>
                        <div id="det-safe-container" class="bg-green-50 rounded-lg p-3 border border-green-100">
                            <p class="text-xs text-green-800 flex items-center gap-1 font-medium">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M2.166 4.9L10 1.55l7.834 3.35a1 1 0 01.666.945V10c0 5.825-4.139 10.518-8.5 12-4.361-1.482-8.5-6.175-8.5-12V5.845a1 1 0 01.666-.945zM10 7a1 1 0 011 1v3a1 1 0 11-2 0V8a1 1 0 011-1zm0 6a1 1 0 100 2 1 1 0 000-2z" clip-rule="evenodd"></path></svg>
                                No immediate risk flags detected
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="mt-6 pt-6 border-t border-gray-100 flex justify-between items-center text-[10px] text-gray-400">
                <p>System Fingerprint ID: <span id="det-sys-id" class="font-mono"></span></p>
                <p>Logins: <span id="det-logins" class="font-bold text-gray-600"></span></p>
            </div>
        </div>
    </div>
</div>

{{-- Confirm Remove All Modal --}}
<div id="modal-remove-all" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 overflow-hidden">
        <div class="bg-red-600 px-6 py-4">
            <h3 class="text-lg font-bold text-white">⚠ Remove All Devices</h3>
        </div>
        <div class="p-6">
            <p class="text-gray-700 text-sm leading-relaxed">
                This will permanently remove <strong>all {{ $devices->count() }} device(s)</strong> for
                <strong>{{ $user->name }}</strong>. The user will be forced to log in again on all their devices.
            </p>
            <p class="mt-3 text-xs text-red-600 font-medium">This action cannot be undone.</p>
        </div>
        <div class="px-6 py-4 bg-gray-50 flex justify-end gap-3">
            <button onclick="document.getElementById('modal-remove-all').classList.add('hidden')"
                class="px-4 py-2 text-sm bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg transition">
                Cancel
            </button>
            <form method="POST" action="{{ route('admin.devices.destroy-all', $user->id) }}">
                @csrf
                @method('DELETE')
                <button type="submit"
                    class="px-4 py-2 text-sm bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium transition">
                    Yes, Remove All
                </button>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
function confirmDeviceRemove(form, deviceName) {
    return confirm(`Remove "${deviceName}" from this account?\n\nThe user will be signed out on that device.`);
}

function showDeviceDetails(device) {
    // Fill hardware identity
    document.getElementById('det-imei').textContent = device.imei || '—';
    document.getElementById('det-android-id').textContent = device.android_id || '—';
    document.getElementById('det-idfa').textContent = device.idfa || '—';
    
    // Fingerprinting
    document.getElementById('det-canvas').textContent = device.canvas_fingerprint || '—';
    document.getElementById('det-res').textContent = device.screen_resolution || '—';
    document.getElementById('det-tz').textContent = device.timezone || '—';
    
    // Geolocation
    document.getElementById('det-lat').textContent = device.latitude || '—';
    document.getElementById('det-lon').textContent = device.longitude || '—';
    document.getElementById('det-acc').textContent = device.location_accuracy ? device.location_accuracy + 'm' : '—';
    document.getElementById('det-loc-time').textContent = device.last_location_update || 'Never tracked';
    
    // Risks
    const riskList = document.getElementById('det-risk-list');
    riskList.innerHTML = '';
    const riskFlags = typeof device.risk_flags === 'string' ? JSON.parse(device.risk_flags) : device.risk_flags;
    
    if (riskFlags && riskFlags.length > 0) {
        document.getElementById('det-risk-container').classList.remove('hidden');
        document.getElementById('det-safe-container').classList.add('hidden');
        riskFlags.forEach(risk => {
            const li = document.createElement('li');
            li.textContent = risk;
            riskList.appendChild(li);
        });
    } else {
        document.getElementById('det-risk-container').classList.add('hidden');
        document.getElementById('det-safe-container').classList.remove('hidden');
    }
    
    // Footer
    document.getElementById('det-sys-id').textContent = device.device_id || 'N/A';
    document.getElementById('det-logins').textContent = device.login_count || '0';
    
    // Show modal
    document.getElementById('modal-device-details').classList.remove('hidden');
}

// Close modal on escape key
document.addEventListener('keydown', function(event) {
    if (event.key === "Escape") {
        document.getElementById('modal-device-details').classList.add('hidden');
        document.getElementById('modal-remove-all').classList.add('hidden');
    }
});
</script>
@endpush
@endsection
