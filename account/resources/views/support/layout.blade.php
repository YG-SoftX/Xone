@extends('layouts.app')
@section('title', 'YG Help Center')

@push('head')
<style>
    .support-hero {
        background: linear-gradient(to bottom, #f8fafc 0%, #ffffff 100%);
        padding: 80px 0;
    }
    .search-pill {
        box-shadow: 0 4px 12px rgba(0,0,0,0.05), 0 1px 2px rgba(0,0,0,0.05);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .search-pill:focus-within {
        box-shadow: 0 12px 24px rgba(0,0,0,0.1), 0 2px 4px rgba(0,0,0,0.05);
        transform: translateY(-2px);
    }
    .help-card {
        border: 1px solid #e2e8f0;
        transition: all 0.3s ease;
    }
    .help-card:hover {
        border-color: #3b82f6;
        box-shadow: 0 10px 20px -5px rgba(59, 130, 246, 0.1);
        transform: translateY(-4px);
    }
</style>
@endpush

@section('content')
<div class="support-hero">
    <div class="max-w-4xl mx-auto px-4 text-center">
        <h1 class="text-4xl md:text-5xl font-extrabold text-gray-900 mb-8 font-google tracking-tight">How can we help you?</h1>
        
        {{-- Google-Style Search Bar --}}
        <div class="relative max-w-2xl mx-auto mb-12">
            <div class="search-pill bg-white rounded-full flex items-center px-6 py-4 border border-gray-100">
                <i class="fas fa-search text-gray-400 mr-4 text-lg"></i>
                <input type="text" placeholder="Describe your issue or ask a question" 
                       class="flex-1 bg-transparent border-none focus:ring-0 text-lg text-gray-700 placeholder-gray-400">
            </div>
        </div>

        {{-- Quick Links --}}
        <div class="flex flex-wrap justify-center gap-3">
            <span class="text-sm font-bold text-gray-400 uppercase tracking-widest mr-2">Try:</span>
            <a href="#" class="px-4 py-2 bg-blue-50 text-blue-600 rounded-full text-sm font-bold hover:bg-blue-100 transition border border-blue-100">Reset Password</a>
            <a href="#" class="px-4 py-2 bg-purple-50 text-purple-600 rounded-full text-sm font-bold hover:bg-purple-100 transition border border-purple-100">Enable 2FA</a>
            <a href="#" class="px-4 py-2 bg-green-50 text-green-600 rounded-full text-sm font-bold hover:bg-green-100 transition border border-green-100">Storage Upgrade</a>
        </div>
    </div>
</div>

<div class="py-20 px-4">
    <div class="max-w-7xl mx-auto">
        <div class="grid md:grid-cols-3 lg:grid-cols-4 gap-8">
            @yield('support_content')
        </div>

        {{-- Contact Support Section --}}
        <div class="mt-32 text-center py-20 bg-gray-50 rounded-[3rem] border border-gray-100">
            <h2 class="text-3xl font-black text-gray-900 mb-4 font-google">Can't find what you're looking for?</h2>
            <p class="text-gray-600 mb-10 max-w-md mx-auto leading-relaxed text-lg">Our support team is available 24/7 to help you with any issue you might have.</p>
            <div class="flex flex-col sm:flex-row justify-center gap-6">
                <a href="#" class="px-10 py-5 bg-gray-900 text-white rounded-[2rem] font-bold text-lg hover:bg-black transition shadow-xl shadow-gray-900/20">
                    <i class="fas fa-paper-plane mr-2"></i> Submit a Ticket
                </a>
                <a href="#" class="px-10 py-5 bg-white text-gray-700 border-2 border-gray-200 rounded-[2rem] font-bold text-lg hover:border-blue-600 hover:text-blue-600 transition">
                    <i class="fas fa-comment-dots mr-2"></i> Live Chat
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
