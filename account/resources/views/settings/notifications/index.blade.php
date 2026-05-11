@extends('layouts.app')

@section('title', 'Notification Settings')
@section('page-title', 'Notification Preferences')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="space-y-6">
        <!-- Notification Channels -->
        <div class="bg-white rounded-xl shadow-sm border p-6">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-bell text-blue-600 text-xl"></i>
                </div>
                <div>
                    <h2 class="text-xl font-semibold text-gray-800">Notification Channels</h2>
                    <p class="text-sm text-gray-500">Choose how you want to receive notifications</p>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Email Channel -->
                <div class="border rounded-lg p-4 {{ $channels['email']['enabled'] ? 'bg-blue-50 border-blue-300' : 'bg-gray-50' }}">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-envelope text-blue-600"></i>
                            <span class="font-medium text-gray-800">Email</span>
                        </div>
                        @if($channels['email']['verified'])
                            <span class="px-2 py-1 bg-green-100 text-green-700 rounded text-xs">Verified</span>
                        @else
                            <span class="px-2 py-1 bg-yellow-100 text-yellow-700 rounded text-xs">Unverified</span>
                        @endif
                    </div>
                    <p class="text-sm text-gray-600 mb-2">{{ $user->email }}</p>
                    @if(!$channels['email']['verified'])
                        <button class="text-blue-600 text-sm hover:underline">Verify Email</button>
                    @endif
                </div>
                
                <!-- SMS Channel -->
                <div class="border rounded-lg p-4 {{ $channels['sms']['enabled'] ? 'bg-green-50 border-green-300' : 'bg-gray-50' }}">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-sms text-green-600"></i>
                            <span class="font-medium text-gray-800">SMS</span>
                        </div>
                        @if($channels['sms']['verified'])
                            <span class="px-2 py-1 bg-green-100 text-green-700 rounded text-xs">Verified</span>
                        @else
                            <span class="px-2 py-1 bg-yellow-100 text-yellow-700 rounded text-xs">Unverified</span>
                        @endif
                    </div>
                    <p class="text-sm text-gray-600 mb-2">{{ $user->phone ?? 'Not configured' }}</p>
                    @if(!$channels['sms']['enabled'])
                        <button class="text-green-600 text-sm hover:underline">Add Phone Number</button>
                    @endif
                </div>
                
                <!-- Push Channel -->
                <div class="border rounded-lg p-4 {{ $channels['push']['enabled'] ? 'bg-purple-50 border-purple-300' : 'bg-gray-50' }}">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-mobile-alt text-purple-600"></i>
                            <span class="font-medium text-gray-800">Push</span>
                        </div>
                        <span class="px-2 py-1 bg-gray-100 text-gray-600 rounded text-xs">Mobile App</span>
                    </div>
                    <p class="text-sm text-gray-600 mb-2">Requires mobile app installation</p>
                    <button onclick="testNotification('push')" class="text-purple-600 text-sm hover:underline">Test Push</button>
                </div>
            </div>
        </div>

        <!-- Notification Categories -->
        <form action="{{ route('settings.notifications.update') }}" method="POST" class="space-y-6">
            @csrf
            
            <!-- Security Notifications -->
            <div class="bg-white rounded-xl shadow-sm border p-6">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-shield-alt text-red-600 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-800">Security Notifications</h3>
                        <p class="text-sm text-gray-500">Important security alerts for your account</p>
                    </div>
                </div>
                
                <div class="space-y-4">
                    <label class="flex items-center justify-between p-3 border rounded-lg hover:bg-gray-50 cursor-pointer">
                        <div>
                            <p class="font-medium text-gray-800">Login Alerts</p>
                            <p class="text-sm text-gray-500">Get notified when someone logs into your account</p>
                        </div>
                        <input type="checkbox" name="security.login_alert" {{ ($preferences['security']['login_alert'] ?? true) ? 'checked' : '' }} class="rounded">
                    </label>
                    
                    <label class="flex items-center justify-between p-3 border rounded-lg hover:bg-gray-50 cursor-pointer">
                        <div>
                            <p class="font-medium text-gray-800">Password Changes</p>
                            <p class="text-sm text-gray-500">Alert when your password is changed</p>
                        </div>
                        <input type="checkbox" name="security.password_change" {{ ($preferences['security']['password_change'] ?? true) ? 'checked' : '' }} class="rounded">
                    </label>
                    
                    <label class="flex items-center justify-between p-3 border rounded-lg hover:bg-gray-50 cursor-pointer">
                        <div>
                            <p class="font-medium text-gray-800">New Device Login</p>
                            <p class="text-sm text-gray-500">Notify when login from unrecognized device</p>
                        </div>
                        <input type="checkbox" name="security.new_device" {{ ($preferences['security']['new_device'] ?? true) ? 'checked' : '' }} class="rounded">
                    </label>
                    
                    <label class="flex items-center justify-between p-3 border rounded-lg hover:bg-gray-50 cursor-pointer">
                        <div>
                            <p class="font-medium text-gray-800">Suspicious Activity</p>
                            <p class="text-sm text-gray-500">Immediate alert for potential security threats</p>
                        </div>
                        <input type="checkbox" name="security.suspicious_activity" {{ ($preferences['security']['suspicious_activity'] ?? true) ? 'checked' : '' }} class="rounded">
                    </label>
                </div>
            </div>

            <!-- Billing Notifications -->
            <div class="bg-white rounded-xl shadow-sm border p-6">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-credit-card text-green-600 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-800">Billing Notifications</h3>
                        <p class="text-sm text-gray-500">Payment and invoice related updates</p>
                    </div>
                </div>
                
                <div class="space-y-4">
                    <label class="flex items-center justify-between p-3 border rounded-lg hover:bg-gray-50 cursor-pointer">
                        <div>
                            <p class="font-medium text-gray-800">Invoice Ready</p>
                            <p class="text-sm text-gray-500">When new invoices are generated</p>
                        </div>
                        <input type="checkbox" name="billing.invoice_ready" {{ ($preferences['billing']['invoice_ready'] ?? true) ? 'checked' : '' }} class="rounded">
                    </label>
                    
                    <label class="flex items-center justify-between p-3 border rounded-lg hover:bg-gray-50 cursor-pointer">
                        <div>
                            <p class="font-medium text-gray-800">Payment Success</p>
                            <p class="text-sm text-gray-500">Confirmation of successful payments</p>
                        </div>
                        <input type="checkbox" name="billing.payment_success" {{ ($preferences['billing']['payment_success'] ?? true) ? 'checked' : '' }} class="rounded">
                    </label>
                    
                    <label class="flex items-center justify-between p-3 border rounded-lg hover:bg-gray-50 cursor-pointer">
                        <div>
                            <p class="font-medium text-gray-800">Payment Failed</p>
                            <p class="text-sm text-gray-500">Immediate alert for failed transactions</p>
                        </div>
                        <input type="checkbox" name="billing.payment_failed" {{ ($preferences['billing']['payment_failed'] ?? true) ? 'checked' : '' }} class="rounded">
                    </label>
                    
                    <label class="flex items-center justify-between p-3 border rounded-lg hover:bg-gray-50 cursor-pointer">
                        <div>
                            <p class="font-medium text-gray-800">Subscription Renewal</p>
                            <p class="text-sm text-gray-500">Reminders before subscription renews</p>
                        </div>
                        <input type="checkbox" name="billing.subscription_renewal" {{ ($preferences['billing']['subscription_renewal'] ?? true) ? 'checked' : '' }} class="rounded">
                    </label>
                </div>
            </div>

            <!-- Product Notifications -->
            <div class="bg-white rounded-xl shadow-sm border p-6">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-cube text-indigo-600 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-800">Product Updates</h3>
                        <p class="text-sm text-gray-500">Stay informed about YG ecosystem changes</p>
                    </div>
                </div>
                
                <div class="space-y-4">
                    <label class="flex items-center justify-between p-3 border rounded-lg hover:bg-gray-50 cursor-pointer">
                        <div>
                            <p class="font-medium text-gray-800">Service Updates</p>
                            <p class="text-sm text-gray-500">Important updates to services you use</p>
                        </div>
                        <input type="checkbox" name="product.updates" {{ ($preferences['product']['updates'] ?? true) ? 'checked' : '' }} class="rounded">
                    </label>
                    
                    <label class="flex items-center justify-between p-3 border rounded-lg hover:bg-gray-50 cursor-pointer">
                        <div>
                            <p class="font-medium text-gray-800">Maintenance Notices</p>
                            <p class="text-sm text-gray-500">Scheduled maintenance announcements</p>
                        </div>
                        <input type="checkbox" name="product.maintenance" {{ ($preferences['product']['maintenance'] ?? true) ? 'checked' : '' }} class="rounded">
                    </label>
                    
                    <label class="flex items-center justify-between p-3 border rounded-lg hover:bg-gray-50 cursor-pointer">
                        <div>
                            <p class="font-medium text-gray-800">New Features</p>
                            <p class="text-sm text-gray-500">Discover new features and capabilities</p>
                        </div>
                        <input type="checkbox" name="product.new_features" {{ ($preferences['product']['new_features'] ?? false) ? 'checked' : '' }} class="rounded">
                    </label>
                </div>
            </div>

            <!-- Team Notifications -->
            <div class="bg-white rounded-xl shadow-sm border p-6">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-users text-orange-600 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-800">Team Collaboration</h3>
                        <p class="text-sm text-gray-500">Updates about team activities</p>
                    </div>
                </div>
                
                <div class="space-y-4">
                    <label class="flex items-center justify-between p-3 border rounded-lg hover:bg-gray-50 cursor-pointer">
                        <div>
                            <p class="font-medium text-gray-800">Team Invitations</p>
                            <p class="text-sm text-gray-500">When invited to join projects</p>
                        </div>
                        <input type="checkbox" name="team.invitation" {{ ($preferences['team']['invitation'] ?? true) ? 'checked' : '' }} class="rounded">
                    </label>
                    
                    <label class="flex items-center justify-between p-3 border rounded-lg hover:bg-gray-50 cursor-pointer">
                        <div>
                            <p class="font-medium text-gray-800">Role Changes</p>
                            <p class="text-sm text-gray-500">When your permissions are updated</p>
                        </div>
                        <input type="checkbox" name="team.role_change" {{ ($preferences['team']['role_change'] ?? true) ? 'checked' : '' }} class="rounded">
                    </label>
                    
                    <label class="flex items-center justify-between p-3 border rounded-lg hover:bg-gray-50 cursor-pointer">
                        <div>
                            <p class="font-medium text-gray-800">Project Updates</p>
                            <p class="text-sm text-gray-500">Changes to projects you're part of</p>
                        </div>
                        <input type="checkbox" name="team.project_updates" {{ ($preferences['team']['project_updates'] ?? true) ? 'checked' : '' }} class="rounded">
                    </label>
                </div>
            </div>

            <!-- Marketing Notifications -->
            <div class="bg-white rounded-xl shadow-sm border p-6">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-12 h-12 bg-pink-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-bullhorn text-pink-600 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-800">Marketing & Promotions</h3>
                        <p class="text-sm text-gray-500">Optional promotional communications</p>
                    </div>
                </div>
                
                <div class="space-y-4">
                    <label class="flex items-center justify-between p-3 border rounded-lg hover:bg-gray-50 cursor-pointer">
                        <div>
                            <p class="font-medium text-gray-800">Promotional Offers</p>
                            <p class="text-sm text-gray-500">Special deals and discounts</p>
                        </div>
                        <input type="checkbox" name="marketing.promotions" {{ ($preferences['marketing']['promotions'] ?? false) ? 'checked' : '' }} class="rounded">
                    </label>
                    
                    <label class="flex items-center justify-between p-3 border rounded-lg hover:bg-gray-50 cursor-pointer">
                        <div>
                            <p class="font-medium text-gray-800">Newsletter</p>
                            <p class="text-sm text-gray-500">Monthly digest of news and updates</p>
                        </div>
                        <input type="checkbox" name="marketing.newsletter" {{ ($preferences['marketing']['newsletter'] ?? false) ? 'checked' : '' }} class="rounded">
                    </label>
                    
                    <label class="flex items-center justify-between p-3 border rounded-lg hover:bg-gray-50 cursor-pointer">
                        <div>
                            <p class="font-medium text-gray-800">Partner Offers</p>
                            <p class="text-sm text-gray-500">Exclusive offers from partners</p>
                        </div>
                        <input type="checkbox" name="marketing.partner_offers" {{ ($preferences['marketing']['partner_offers'] ?? false) ? 'checked' : '' }} class="rounded">
                    </label>
                </div>
            </div>

            <!-- Email Frequency Settings -->
            <div class="bg-white rounded-xl shadow-sm border p-6">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-12 h-12 bg-teal-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-clock text-teal-600 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-800">Email Delivery Settings</h3>
                        <p class="text-sm text-gray-500">Control when and how often you receive emails</p>
                    </div>
                </div>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Email Frequency</label>
                        <select name="email_frequency" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            <option value="immediate" {{ ($user->email_settings['frequency'] ?? 'immediate') === 'immediate' ? 'selected' : '' }}>Immediate - Send as events occur</option>
                            <option value="daily" {{ ($user->email_settings['frequency'] ?? '') === 'daily' ? 'selected' : '' }}>Daily Digest - One email per day</option>
                            <option value="weekly" {{ ($user->email_settings['frequency'] ?? '') === 'weekly' ? 'selected' : '' }}>Weekly Summary - One email per week</option>
                        </select>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Digest Time</label>
                            <input type="time" name="digest_time" value="{{ $user->email_settings['digest_time'] ?? '09:00' }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Timezone</label>
                            <select class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                <option>{{ config('app.timezone', 'UTC') }}</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Quiet Hours Start</label>
                            <input type="time" name="quiet_hours_start" value="{{ $user->email_settings['quiet_hours']['start'] ?? '22:00' }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Quiet Hours End</label>
                            <input type="time" name="quiet_hours_end" value="{{ $user->email_settings['quiet_hours']['end'] ?? '08:00' }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                    <p class="text-xs text-gray-500">No non-critical emails will be sent during quiet hours</p>
                </div>
            </div>

            <div class="flex gap-3">
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-all">
                    Save Notification Preferences
                </button>
                <button type="button" onclick="testNotification('email')" class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
                    Test Email
                </button>
                <button type="button" onclick="testNotification('sms')" class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
                    Test SMS
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
async function testNotification(channel) {
    try {
        const response = await fetch('{{ route("settings.notifications.test") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ channel: channel })
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert(result.message);
        } else {
            alert(result.error || 'Test failed');
        }
    } catch (error) {
        alert('Test request failed');
    }
}
</script>
@endpush
@endsection
