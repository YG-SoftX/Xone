@extends('layouts.dashboard')
@section('title', 'My Activity')

@section('dashboard-content')
<div class="max-w-4xl mx-auto px-6 py-12">
    
    <!-- Header -->
    <div class="flex justify-between items-end mb-10 pb-8 border-b border-gray-100">
        <div>
            <h1 class="text-[28px] font-normal text-[#202124] mb-2">My Activity</h1>
            <p class="text-sm text-[#5f6368]">Review and manage the activity in your YG Account, including your searches and service usage.</p>
        </div>
        <div class="flex gap-2">
            <button class="px-4 py-2 border border-gray-300 rounded-full text-xs font-medium text-gray-600 hover:bg-gray-50 flex items-center gap-2">
                <i class="fas fa-filter"></i> Filter by date
            </button>
            <button class="px-4 py-2 border border-gray-300 rounded-full text-xs font-medium text-gray-600 hover:bg-gray-50">
                Delete
            </button>
        </div>
    </div>

    <!-- Timeline Body -->
    <div class="space-y-12">
        @forelse($activities as $date => $dayActivities)
            <div class="relative">
                <!-- Date Header -->
                <h2 class="text-sm font-bold text-gray-900 mb-6 bg-white sticky top-16 z-10 py-2">
                    {{ \Carbon\Carbon::parse($date)->isToday() ? 'Today' : (\Carbon\Carbon::parse($date)->isYesterday() ? 'Yesterday' : \Carbon\Carbon::parse($date)->format('F j, Y')) }}
                </h2>

                <!-- Day's Activities -->
                <div class="space-y-4 border-l-2 border-gray-100 ml-3 pl-8 pb-4">
                    @foreach($dayActivities as $activity)
                        <div class="google-card !p-4 relative hover:bg-gray-50 transition-colors">
                            <!-- Timeline Dot -->
                            <div class="absolute -left-[41px] top-1/2 -translate-y-1/2 w-4 h-4 rounded-full bg-white border-2 {{ $activity->color ?? 'border-blue-500' }}"></div>

                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 rounded-xl {{ $activity->bgColor ?? 'bg-blue-50' }} flex items-center justify-center shrink-0">
                                        <i class="fas {{ $activity->icon ?? 'fa-cube' }} {{ $activity->iconColor ?? 'text-blue-600' }}"></i>
                                    </div>
                                    <div>
                                        <div class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-0.5">
                                            {{ $activity->service_node ?? 'YG Ecosystem' }}
                                        </div>
                                        <div class="text-sm text-gray-900 font-medium">
                                            {{ $activity->description }}
                                        </div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-xs font-medium text-gray-400">
                                        {{ \Carbon\Carbon::parse($activity->created_at)->format('g:i A') }}
                                    </div>
                                    <div class="text-[10px] text-gray-300 font-bold uppercase tracking-tighter mt-1">
                                        IP: {{ $activity->ip_address }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="text-center py-24 bg-gray-50 rounded-3xl border border-dashed border-gray-300">
                <i class="fas fa-history text-4xl text-gray-200 mb-4"></i>
                <h3 class="text-lg font-medium text-gray-400">No activity recorded yet</h3>
                <p class="text-sm text-gray-400">Your life in the empire will be chronicled here.</p>
            </div>
        @endforelse
    </div>

    <footer class="mt-20 pt-10 border-t border-gray-100 text-center">
        <p class="text-xs text-gray-400">Only you can see this activity. YG protects your privacy and security. <a href="#" class="text-blue-600 hover:underline">Learn more</a></p>
    </footer>

</div>
@endsection
