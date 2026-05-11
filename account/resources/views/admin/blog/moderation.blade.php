@extends('layouts.dashboard')
@section('title', 'Imperial Moderation Sentinel')

@section('dashboard-content')
<div class="max-w-6xl mx-auto px-6 py-12">
    
    <div class="flex justify-between items-center mb-10">
        <div class="flex items-center gap-5">
            <div class="w-14 h-14 bg-red-50 rounded-2xl flex items-center justify-center shadow-sm">
                <i class="fas fa-shield-alt text-red-600 text-2xl"></i>
            </div>
            <div>
                <h1 class="text-[28px] font-normal text-gray-900 tracking-tight">Imperial Moderation Sentinel</h1>
                <p class="text-[14px] text-gray-500">Govern the imperial journal and vet pending submissions</p>
            </div>
        </div>
        <div class="px-4 py-2 bg-gray-100 rounded-xl border border-gray-200">
            <span class="text-xs font-bold text-gray-500 uppercase tracking-widest">{{ $pendingPosts->count() }} Pending Submissions</span>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-8 p-4 bg-green-50 text-green-700 rounded-2xl border border-green-100 text-sm font-medium flex items-center gap-3">
            <i class="fas fa-check-circle"></i> {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-8 p-4 bg-red-50 text-red-700 rounded-2xl border border-red-100 text-sm font-medium flex items-center gap-3">
            <i class="fas fa-times-circle"></i> {{ session('error') }}
        </div>
    @endif

    <div class="google-card overflow-hidden p-0">
        <table class="w-full text-left">
            <thead class="bg-gray-50 border-b border-gray-100">
                <tr>
                    <th class="px-6 py-4 text-[10px] font-bold text-gray-400 uppercase tracking-widest">Author</th>
                    <th class="px-6 py-4 text-[10px] font-bold text-gray-400 uppercase tracking-widest">Article Details</th>
                    <th class="px-6 py-4 text-[10px] font-bold text-gray-400 uppercase tracking-widest">Category</th>
                    <th class="px-6 py-4 text-[10px] font-bold text-gray-400 uppercase tracking-widest text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($pendingPosts as $post)
                <tr class="hover:bg-gray-50/50 transition-colors group">
                    <td class="px-6 py-6">
                        <div class="flex items-center gap-3">
                            <img src="{{ $post->author->user_image }}" class="w-10 h-10 rounded-full border border-gray-100 shadow-sm" alt="">
                            <div class="min-w-0">
                                <p class="text-sm font-bold text-gray-900 truncate">{{ $post->author->name }}</p>
                                <p class="text-[10px] text-gray-400 font-medium uppercase">{{ $post->author->account_type }} Citizen</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-6">
                        <h4 class="text-sm font-normal text-gray-900 mb-1 line-clamp-1">{{ $post->title }}</h4>
                        <p class="text-[11px] text-gray-500 line-clamp-2 leading-relaxed">{{ $post->summary }}</p>
                    </td>
                    <td class="px-6 py-6">
                        <span class="px-3 py-1 bg-blue-50 text-blue-600 text-[9px] font-black uppercase tracking-widest rounded-full border border-blue-100">
                            {{ $post->category }}
                        </span>
                    </td>
                    <td class="px-6 py-6 text-right">
                        <div class="flex justify-end gap-2">
                            <form action="{{ route('admin.blog.approve', $post) }}" method="POST">
                                @csrf
                                <button type="submit" class="p-2.5 bg-green-50 text-green-600 rounded-xl border border-green-100 hover:bg-green-600 hover:text-white transition-all shadow-sm" title="Approve">
                                    <i class="fas fa-check"></i>
                                </button>
                            </form>
                            <form action="{{ route('admin.blog.reject', $post) }}" method="POST">
                                @csrf
                                <button type="submit" class="p-2.5 bg-red-50 text-red-600 rounded-xl border border-red-100 hover:bg-red-600 hover:text-white transition-all shadow-sm" title="Reject">
                                    <i class="fas fa-times"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="px-6 py-20 text-center">
                        <div class="flex flex-col items-center">
                            <i class="fas fa-inbox text-gray-200 text-5xl mb-4"></i>
                            <p class="text-sm text-gray-400 font-medium">The moderation queue is currently clear.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>
@endsection
