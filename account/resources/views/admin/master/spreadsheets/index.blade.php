@extends('admin.master-layout')
@section('title', 'Spreadsheets (YG Xcel)')
@section('content')
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="bg-white p-4 rounded shadow"><p class="text-sm text-gray-600">Total Spreadsheets</p><p class="text-2xl font-bold">{{ $stats['total'] ?? '—' }}</p></div>
    <div class="bg-white p-4 rounded shadow"><p class="text-sm text-gray-600">Published</p><p class="text-2xl font-bold text-green-600">{{ $stats['published'] ?? '—' }}</p></div>
    <div class="bg-white p-4 rounded shadow"><p class="text-sm text-gray-600">With Charts</p><p class="text-2xl font-bold text-blue-600">{{ $stats['with_charts'] ?? '—' }}</p></div>
</div>
<div class="bg-white rounded shadow">
    <table class="min-w-full divide-y">
        <thead class="bg-gray-50"><tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Owner</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sheets</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Updated</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
        </tr></thead>
        <tbody class="bg-white divide-y">
            @foreach($spreadsheets as $ss)
            <tr>
                <td class="px-6 py-4 text-sm font-medium">{{ $ss['title'] ?? 'Untitled' }}</td>
                <td class="px-6 py-4 text-sm">{{ $ss['owner_name'] ?? '—' }}</td>
                <td class="px-6 py-4"><span class="px-2 py-1 text-xs rounded-full {{ $ss['status'] === 'published' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">{{ $ss['status'] ?? 'draft' }}</span></td>
                <td class="px-6 py-4 text-sm">{{ $ss['sheets_count'] ?? '—' }}</td>
                <td class="px-6 py-4 text-sm text-gray-500">{{ $ss['updated_at'] ?? '—' }}</td>
                <td class="px-6 py-4 text-sm space-x-2">
                    <a href="{{ route('admin.spreadsheets.show', $ss['id'] ?? 0) }}" class="text-blue-600">View</a>
                    <form method="POST" action="{{ route('admin.spreadsheets.delete', $ss['id'] ?? 0) }}" class="inline"><button onclick="return confirm('Delete?')" class="text-red-600">Delete</button></form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
