@extends('layouts.dashboard')
@section('title', 'Domains')

@section('dashboard-content')
<div class="max-w-5xl mx-auto px-6 py-10">
    
    <!-- Header -->
    <div class="flex justify-between items-end mb-10">
        <div>
            <h1 class="text-[28px] font-normal text-[#202124] mb-2">Domains</h1>
            <p class="text-sm text-[#5f6368]">Manage the custom domains used by your organization for Mail and Drive white-labeling.</p>
        </div>
        <button onclick="document.getElementById('add-domain-modal').classList.remove('hidden')" class="bg-[#1a73e8] text-white px-6 py-2 rounded-full text-sm font-medium hover:bg-blue-700 shadow-md">
            Add a domain
        </button>
    </div>

    <!-- Domain List -->
    <div class="google-card overflow-hidden">
        <table class="w-full text-left">
            <thead class="bg-gray-50 border-b border-gray-100">
                <tr>
                    <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-widest">Domain</th>
                    <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-widest">Status</th>
                    <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-widest">Type</th>
                    <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-widest text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($domains as $domain)
                <tr class="hover:bg-gray-50/50 transition-colors">
                    <td class="px-6 py-4">
                        <div class="text-sm font-medium text-gray-900">{{ $domain->domain }}</div>
                    </td>
                    <td class="px-6 py-4">
                        @if($domain->is_verified)
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-green-50 text-green-700 text-[10px] font-bold uppercase tracking-widest rounded-full">
                                <i class="fas fa-check-circle"></i> Verified
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-yellow-50 text-yellow-700 text-[10px] font-bold uppercase tracking-widest rounded-full">
                                <i class="fas fa-clock"></i> Verification Pending
                            </span>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        <span class="text-xs text-gray-500">Primary Domain</span>
                    </td>
                    <td class="px-6 py-4 text-right">
                        @if(!$domain->is_verified)
                            <form action="{{ route('organization.domains.verify', $domain) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="text-[#1a73e8] text-xs font-bold uppercase tracking-widest hover:underline">Verify</button>
                            </form>
                        @else
                            <button class="text-gray-400 text-xs font-bold uppercase tracking-widest hover:text-gray-600">Settings</button>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="px-6 py-12 text-center">
                        <div class="mb-4">
                            <i class="fas fa-globe text-gray-200 text-5xl"></i>
                        </div>
                        <p class="text-sm text-gray-400 font-medium">No custom domains added yet.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Setup Instructions (Only show if pending) -->
    @if($domains->where('is_verified', false)->count() > 0)
    <div class="google-card mt-10 bg-blue-50/20 border-blue-100">
        <h3 class="text-lg font-normal text-[#202124] mb-6">Verify ownership of your domain</h3>
        <div class="space-y-6">
            <p class="text-sm text-gray-600">To use your domain with the YG empire, you must prove you own it. Please add the following TXT record to your domain's DNS settings:</p>
            
            <div class="bg-white border border-blue-100 p-6 rounded-xl flex justify-between items-center">
                <div>
                    <div class="text-[10px] font-bold text-blue-600 uppercase tracking-widest mb-1">TXT Record</div>
                    <code class="text-sm font-mono text-gray-800">{{ $domains->where('is_verified', false)->first()->verification_token }}</code>
                </div>
                <button class="text-blue-600 hover:bg-blue-50 p-2 rounded-lg transition">
                    <i class="far fa-copy"></i>
                </button>
            </div>

            <div class="flex items-start gap-4 text-xs text-gray-500 italic">
                <i class="fas fa-info-circle mt-0.5"></i>
                <p>Note: It can take up to 48 hours for DNS changes to propagate globally. Click "Verify" once you've added the record.</p>
            </div>
        </div>
    </div>
    @endif

    <!-- Add Domain Modal -->
    <div id="add-domain-modal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-[300] flex items-center justify-center hidden">
        <div class="bg-white w-full max-w-md rounded-[2rem] p-10 shadow-2xl animate-fade-in-up">
            <h2 class="text-2xl font-normal text-[#202124] mb-2">Add a domain</h2>
            <p class="text-sm text-gray-500 mb-8">Enter the domain name you want to use for your organization.</p>
            
            <form action="{{ route('organization.domains.add') }}" method="POST">
                @csrf
                <div class="mb-8">
                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Domain Name</label>
                    <input type="text" name="domain" placeholder="example.com" class="w-full bg-[#f1f3f4] border-none rounded-xl px-4 py-3 text-sm focus:bg-white focus:ring-2 focus:ring-blue-100 transition-all outline-none" required>
                </div>
                
                <div class="flex justify-end gap-4">
                    <button type="button" onclick="document.getElementById('add-domain-modal').classList.add('hidden')" class="px-6 py-2 text-sm font-medium text-gray-500 hover:bg-gray-100 rounded-full transition">Cancel</button>
                    <button type="submit" class="bg-[#1a73e8] text-white px-8 py-2 rounded-full text-sm font-bold shadow-md hover:bg-blue-700 transition">Add & Continue</button>
                </div>
            </form>
        </div>
    </div>

</div>

<style>
    @keyframes fade-in-up {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .animate-fade-in-up { animation: fade-in-up 0.4s ease-out; }
</style>
@endsection
