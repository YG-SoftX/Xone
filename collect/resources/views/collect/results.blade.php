@extends('layouts.app')
@section('title', "Results: {$form->title}")

@section('content')
<div class="max-w-full mx-auto px-6 py-10">
    <div class="flex items-center justify-between mb-8">
        <div class="flex items-center gap-4">
            <a href="{{ route('collect.index') }}" class="w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center hover:bg-gray-200 transition-all text-gray-500">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h1 class="text-[26px] font-normal text-gray-900 tracking-tight">{{ $form->title }}</h1>
                <p class="text-xs text-gray-500 uppercase tracking-widest font-bold">Form Responses</p>
            </div>
        </div>
        
        <div class="flex items-center gap-3">
            <a href="{{ route('collect.public', $form->slug) }}" target="_blank" class="px-5 py-2.5 bg-gray-100 text-gray-700 rounded-full text-xs font-bold uppercase tracking-widest hover:bg-gray-200 transition-all flex items-center gap-2">
                <i class="fas fa-external-link-alt text-[10px]"></i> View Public Form
            </a>
            <a href="{{ route('collect.export', $form->id) }}" class="px-5 py-2.5 bg-green-600 text-white rounded-full text-xs font-bold uppercase tracking-widest shadow-md hover:bg-green-700 transition-all flex items-center gap-2">
                <i class="fas fa-file-csv text-[10px]"></i> Export CSV
            </a>
        </div>
    </div>

    <!-- Summary Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="google-card !p-6 border-l-4 border-l-purple-500">
            <div class="text-[10px] uppercase font-black text-gray-400 tracking-widest mb-2">Total Responses</div>
            <div class="text-4xl font-normal text-gray-900">{{ $submissions->total() }}</div>
        </div>
        <div class="google-card !p-6">
            <div class="text-[10px] uppercase font-black text-gray-400 tracking-widest mb-2">Status</div>
            <div class="flex items-center gap-2 mt-2">
                <div class="w-3 h-3 bg-green-500 rounded-full animate-pulse"></div>
                <span class="text-sm font-bold text-gray-900">Accepting Responses</span>
            </div>
        </div>
    </div>

    <!-- Results Table -->
    <div class="google-card overflow-hidden !p-0">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="text-left py-4 px-6 text-[10px] text-gray-500 uppercase font-black tracking-widest whitespace-nowrap">Timestamp</th>
                        @foreach($form->fields as $field)
                            <th class="text-left py-4 px-6 text-[10px] text-gray-500 uppercase font-black tracking-widest whitespace-nowrap">{{ $field['label'] }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($submissions as $submission)
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="py-4 px-6 text-xs text-gray-500 whitespace-nowrap">
                                {{ $submission->created_at->format('M j, Y g:i A') }}
                            </td>
                            @foreach($form->fields as $index => $field)
                                @php
                                    $answer = $submission->data["field_{$index}"] ?? '';
                                    if (is_array($answer)) {
                                        $answer = implode(', ', $answer);
                                    }
                                @endphp
                                <td class="py-4 px-6 text-sm text-gray-900 max-w-xs truncate" title="{{ $answer }}">
                                    {{ $answer ?: '-' }}
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($form->fields) + 1 }}" class="py-12 text-center">
                                <div class="w-12 h-12 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-3 text-gray-300">
                                    <i class="fas fa-inbox text-lg"></i>
                                </div>
                                <p class="text-sm font-bold text-gray-900">Waiting for responses</p>
                                <p class="text-xs text-gray-500 mt-1">Responses will appear here automatically.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($submissions->hasPages())
            <div class="p-4 border-t border-gray-100 bg-gray-50/30">
                {{ $submissions->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
