@extends('layouts.app')
@section('title', 'Service Unavailable')

@section('content')
<div class="min-h-[70vh] flex items-center justify-center px-4">
    <div class="max-w-2xl w-full text-center">
        
        {{-- Animated Icon --}}
        <div class="relative inline-block mb-12">
            <div class="absolute inset-0 bg-red-100 rounded-full blur-2xl animate-pulse"></div>
            <div class="relative w-24 h-24 bg-white border border-red-50 rounded-3xl shadow-xl flex items-center justify-center">
                <i class="fas fa-exclamation-triangle text-red-500 text-4xl"></i>
            </div>
        </div>

        <h1 class="text-4xl font-extrabold text-gray-900 mb-4 font-google tracking-tight">{{ $service_name ?? 'Service' }} Unavailable</h1>
        <p class="text-xl text-gray-600 mb-12 max-w-lg mx-auto leading-relaxed">
            {{ $message ?? 'This service is currently disabled. Please contact system support for more information.' }}
        </p>

        <a href="/" class="inline-flex items-center gap-3 px-8 py-4 bg-gray-900 text-white rounded-2xl font-bold hover:bg-black transition shadow-lg">
            <i class="fas fa-home"></i> Back to Dashboard
        </a>

        <div class="mt-12 pt-8 border-t border-gray-100">
            <p class="text-xs text-gray-400 font-bold uppercase tracking-widest">System Status Code: 503 SERVICE_DISABLED</p>
        </div>
    </div>
</div>
@endsection
