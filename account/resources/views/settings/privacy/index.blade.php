@extends('layouts.app')

@section('title', 'Privacy Settings')
@section('page-title', 'Privacy & Data Control')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="space-y-6">
        <!-- GDPR Compliance Banner -->
        <div class="bg-gradient-to-r from-blue-500 to-indigo-600 rounded-xl p-6 text-white">
            <div class="flex items-start gap-4">
                <div class="w-12 h-12 bg-white bg-opacity-20 rounded-lg flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-shield-alt text-white text-xl"></i>
                </div>
                <div>
                    <h2 class="text-xl font-semibold mb-2">Your Data, Your Control</h2>
                    <p class="text-blue-100 text-sm">We comply with GDPR and give you full control over your personal data. Export, manage, or delete your data at any time.</p>
                </div>
            </div>
        </div>

        <!-- Privacy Preferences -->
        <div class="bg-white rounded-xl shadow-sm border p-6">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-user-shield text-purple-600 text-xl"></i>
                </div>
                <div>
                    <h2 class="text-xl font-semibold text-gray-800">Privacy Preferences</h2>
                    <p class="text-sm text-gray-500">Control what information is visible and how it's used</p>
                </div>
            </div>
            
            <form action="{{ route('settings.privacy.update-preferences') }}" method="POST" class="space-y-6">
                @csrf
                
                <!-- Profile Visibility -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Profile Visibility</label>
                    <select name="profile_visibility" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                        <option value="public" {{ ($user->privacy_settings['profile_visibility'] ?? 'public') === 'public' ? 'selected' : '' }}>Public - Everyone can see your profile</option>
                        <option value="friends_only" {{ ($user->privacy_settings['profile_visibility'] ?? '') === 'friends_only' ? 'selected' : '' }}>Friends Only - Only connected users</option>
                        <option value="private" {{ ($user->privacy_settings['profile_visibility'] ?? '') === 'private' ? 'selected' : '' }}>Private - Only you can see your profile</option>
                    </select>
                </div>
                
                <!-- Contact Information -->
                <div class="space-y-3">
                    <label class="flex items-center gap-3">
                        <input type="checkbox" name="show_email" {{ ($user->privacy_settings['show_email'] ?? false) ? 'checked' : '' }} class="rounded">
                        <span class="text-sm text-gray-700">Show email address on public profile</span>
                    </label>
                    
                    <label class="flex items-center gap-3">
                        <input type="checkbox" name="show_phone" {{ ($user->privacy_settings['show_phone'] ?? false) ? 'checked' : '' }} class="rounded">
                        <span class="text-sm text-gray-700">Show phone number on public profile</span>
                    </label>
                </div>
                
                <!-- Data Usage -->
                <div class="border-t pt-6 space-y-3">
                    <h3 class="font-medium text-gray-800">Data Usage Permissions</h3>
                    
                    <label class="flex items-center gap-3">
                        <input type="checkbox" name="allow_data_analytics" {{ ($user->privacy_settings['allow_data_analytics'] ?? true) ? 'checked' : '' }} class="rounded">
                        <div>
                            <span class="text-sm text-gray-700 block">Allow data analytics</span>
                            <span class="text-xs text-gray-500">Help us improve our services by analyzing usage patterns</span>
                        </div>
                    </label>
                    
                    <label class="flex items-center gap-3">
                        <input type="checkbox" name="allow_marketing_emails" {{ ($user->privacy_settings['allow_marketing_emails'] ?? false) ? 'checked' : '' }} class="rounded">
                        <div>
                            <span class="text-sm text-gray-700 block">Receive marketing emails</span>
                            <span class="text-xs text-gray-500">Get updates about new features and promotions</span>
                        </div>
                    </label>
                    
                    <label class="flex items-center gap-3">
                        <input type="checkbox" name="allow_third_party_sharing" {{ ($user->privacy_settings['allow_third_party_sharing'] ?? false) ? 'checked' : '' }} class="rounded">
                        <div>
                            <span class="text-sm text-gray-700 block">Allow third-party data sharing</span>
                            <span class="text-xs text-gray-500">Share anonymized data with trusted partners</span>
                        </div>
                    </label>
                </div>
                
                <button type="submit" class="px-6 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-all">
                    Save Privacy Preferences
                </button>
            </form>
        </div>

        <!-- Data Export (GDPR) -->
        <div class="bg-white rounded-xl shadow-sm border p-6">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-download text-green-600 text-xl"></i>
                </div>
                <div>
                    <h2 class="text-xl font-semibold text-gray-800">Export Your Data</h2>
                    <p class="text-sm text-gray-500">Download a copy of all your personal data (GDPR Article 15)</p>
                </div>
            </div>
            
            <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-4">
                <p class="text-green-700 text-sm"><strong>What's included:</strong> Profile information, login history, transaction records, API usage logs, and all associated data.</p>
            </div>
            
            @if($dataExports->count() > 0)
                <div class="space-y-3 mb-4">
                    <h3 class="font-medium text-gray-800">Previous Exports</h3>
                    @foreach($dataExports as $export)
                        <div class="border rounded-lg p-4 flex items-center justify-between">
                            <div>
                                <p class="font-medium text-gray-800">Requested {{ \Carbon\Carbon::parse($export->requested_at)->format('M d, Y') }}</p>
                                <p class="text-sm text-gray-500">Status: 
                                    <span class="px-2 py-1 rounded text-xs {{ $export->status === 'completed' ? 'bg-green-100 text-green-700' : ($export->status === 'pending' ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700') }}">
                                        {{ ucfirst($export->status) }}
                                    </span>
                                </p>
                            </div>
                            @if($export->status === 'completed')
                                <a href="{{ route('settings.privacy.download-export', $export->id) }}" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                                    <i class="fas fa-download mr-2"></i>Download
                                </a>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
            
            <form action="{{ route('settings.privacy.request-export') }}" method="POST">
                @csrf
                <button type="submit" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-all">
                    <i class="fas fa-download mr-2"></i>Request Data Export
                </button>
            </form>
        </div>

        <!-- Account Deletion (Right to be Forgotten) -->
        <div class="bg-white rounded-xl shadow-sm border p-6">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-trash-alt text-red-600 text-xl"></i>
                </div>
                <div>
                    <h2 class="text-xl font-semibold text-gray-800">Delete Your Account</h2>
                    <p class="text-sm text-gray-500">Permanently delete your account and all associated data (GDPR Article 17)</p>
                </div>
            </div>
            
            @if($user->deletion_scheduled_at)
                <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-4">
                    <p class="text-red-700 text-sm"><strong>Account deletion scheduled for {{ \Carbon\Carbon::parse($user->deletion_scheduled_at)->format('F d, Y') }}</strong></p>
                    <p class="text-red-600 text-sm mt-2">You have until this date to cancel the deletion request.</p>
                </div>
                
                <form action="{{ route('settings.privacy.cancel-deletion') }}" method="POST">
                    @csrf
                    <button type="submit" class="px-6 py-2 border border-green-600 text-green-600 rounded-lg hover:bg-green-50 transition-all">
                        <i class="fas fa-times-circle mr-2"></i>Cancel Deletion Request
                    </button>
                </form>
            @else
                <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-4">
                    <p class="text-red-700 text-sm"><strong>Warning:</strong> This action cannot be undone. All your data will be permanently deleted after a 30-day grace period.</p>
                </div>
                
                <button onclick="openDeletionModal()" class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-all">
                    <i class="fas fa-trash-alt mr-2"></i>Delete My Account
                </button>
            @endif
        </div>

        <!-- Consent Management -->
        <div class="bg-white rounded-xl shadow-sm border p-6">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-check-circle text-blue-600 text-xl"></i>
                </div>
                <div>
                    <h2 class="text-xl font-semibold text-gray-800">Consent Records</h2>
                    <p class="text-sm text-gray-500">View and manage your consent preferences</p>
                </div>
            </div>
            
            @if($consents->count() > 0)
                <div class="space-y-3">
                    @foreach($consents as $consent)
                        <div class="border rounded-lg p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="font-medium text-gray-800 capitalize">{{ str_replace('_', ' ', $consent->consent_type) }}</p>
                                    <p class="text-sm text-gray-500">{{ \Carbon\Carbon::parse($consent->created_at)->format('M d, Y H:i') }}</p>
                                </div>
                                <span class="px-2 py-1 bg-green-100 text-green-700 rounded text-xs">Given</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-gray-500 text-center py-8">No consent records found</p>
            @endif
        </div>
    </div>
</div>

<!-- Account Deletion Confirmation Modal -->
<div id="deletionModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-2xl max-w-2xl w-full mx-4">
        <div class="px-6 py-4 border-b bg-red-50">
            <h3 class="text-xl font-semibold text-red-800"><i class="fas fa-exclamation-triangle mr-2"></i>Delete Account Permanently</h3>
        </div>
        
        <form id="deletionForm" action="{{ route('settings.privacy.request-deletion') }}" method="POST" class="p-6 space-y-6">
            @csrf
            
            <div class="bg-red-50 border-l-4 border-red-500 p-4">
                <p class="text-red-700 text-sm"><strong>This will permanently delete:</strong></p>
                <ul class="text-red-600 text-sm mt-2 space-y-1 list-disc list-inside">
                    <li>Your profile and personal information</li>
                    <li>All projects and API credentials</li>
                    <li>Billing history and invoices</li>
                    <li>Login history and activity logs</li>
                    <li>All associated data across YG ecosystem</li>
                </ul>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Enter your password to confirm</label>
                <input type="password" name="password" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Reason for deletion (optional)</label>
                <textarea name="reason" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500" placeholder="Help us understand why you're leaving..."></textarea>
            </div>
            
            <label class="flex items-center gap-3">
                <input type="checkbox" name="confirmation" required class="rounded">
                <span class="text-sm text-gray-700">I understand this action cannot be undone</span>
            </label>
            
            <div class="flex gap-3">
                <button type="button" onclick="closeDeletionModal()" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">Cancel</button>
                <button type="submit" class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">Delete My Account</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function openDeletionModal() {
    document.getElementById('deletionModal').classList.remove('hidden');
}

function closeDeletionModal() {
    document.getElementById('deletionModal').classList.add('hidden');
    document.getElementById('deletionForm').reset();
}
</script>
@endpush
@endsection
