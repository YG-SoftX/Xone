@extends('layouts.app')
@section('title', 'Scheduled Maintenance')

@section('content')
<div class="min-h-[70vh] flex items-center justify-center px-4">
    <div class="max-w-2xl w-full text-center">
        
        {{-- Animated Icon --}}
        <div class="relative inline-block mb-12">
            <div class="absolute inset-0 bg-blue-100 rounded-full blur-2xl animate-pulse"></div>
            <div class="relative w-24 h-24 bg-white border border-gray-100 rounded-3xl shadow-xl flex items-center justify-center transform hover:rotate-12 transition duration-500">
                <i class="fas fa-tools text-blue-600 text-4xl"></i>
            </div>
        </div>

        <h1 class="text-4xl font-extrabold text-gray-900 mb-4 font-google tracking-tight">Improving your experience</h1>
        <p class="text-xl text-gray-600 mb-8 max-w-lg mx-auto leading-relaxed">
            {{ $message ?? 'We are currently performing scheduled maintenance to ensure the highest security and performance for your account.' }}
        </p>

        <div class="bg-blue-50/50 border border-blue-100 p-6 rounded-3xl inline-flex items-center gap-4 mb-12">
            <div class="w-2 h-2 bg-blue-600 rounded-full animate-ping"></div>
            <span class="text-sm font-bold text-blue-800 uppercase tracking-widest">Our engineers are on it</span>
        </div>

        <div class="pt-8 border-t border-gray-100">
            <p class="text-sm text-gray-400 font-medium">Expected back soon. Thank you for your patience.</p>
        </div>
    </div>
</div>
@endsection
