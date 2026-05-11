@extends('admin.master-layout')

@section('title', 'KYC Review Queue')

@section('content')
<div class="bg-white rounded-lg shadow">
    <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-900">KYC Submissions</h3>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Document Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Submitted</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($kycSubmissions as $kyc)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">{{ $kyc->user->name }}</div>
                            <div class="text-xs text-gray-500">{{ $kyc->user->email }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ ucfirst(str_replace('_', ' ', $kyc->document_type)) }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                {{ $kyc->status === 'approved' ? 'bg-green-100 text-green-800' : ($kyc->status === 'rejected' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') }}">
                                {{ $kyc->status }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $kyc->created_at->format('M d, Y') }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm space-x-2">
                            @if($kyc->status === 'pending')
                                <form method="POST" action="{{ route('admin.kyc.approve', $kyc->id) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="text-green-600 hover:text-green-900">Approve</button>
                                </form>
                                <form method="POST" action="{{ route('admin.kyc.reject', $kyc->id) }}" class="inline">
                                    @csrf
                                    <button type="submit" onclick="return confirm('Reject this KYC submission?')" class="text-red-600 hover:text-red-900">Reject</button>
                                </form>
                            @else
                                <span class="text-xs text-gray-400">{{ $kyc->admin_notes }}</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="px-6 py-4 border-t border-gray-200">
        {{ $kycSubmissions->links() }}
    </div>
</div>
@endsection
