@extends('layouts.dashboard')
@section('title', 'Help Center')

@section('dashboard-content')
<div class="max-w-5xl mx-auto px-6 py-12">
    
    <!-- Hero Section -->
    <div class="text-center mb-16">
        <h1 class="text-[36px] font-normal text-[#202124] mb-8">How can we help you?</h1>
        <div class="max-w-3xl mx-auto relative">
            <div class="bg-white border border-gray-200 rounded-lg shadow-md flex items-center px-6 py-4 focus-within:shadow-lg transition-shadow">
                <i class="fas fa-search text-gray-400 text-xl mr-4"></i>
                <input type="text" placeholder="Describe your issue" class="w-full text-lg border-none outline-none text-gray-700">
            </div>
        </div>
    </div>

    <!-- Product Grid -->
    <h2 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-8">Support by Product</h2>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-16">
        <a href="#" class="google-card flex flex-col items-center text-center hover:bg-gray-50 transition-colors">
            <i class="fas fa-user-circle text-[#1a73e8] text-3xl mb-3"></i>
            <span class="text-sm font-medium text-gray-700">YG Account</span>
        </a>
        <a href="#" class="google-card flex flex-col items-center text-center hover:bg-gray-50 transition-colors">
            <i class="fas fa-envelope text-red-500 text-3xl mb-3"></i>
            <span class="text-sm font-medium text-gray-700">YG Mail</span>
        </a>
        <a href="#" class="google-card flex flex-col items-center text-center hover:bg-gray-50 transition-colors">
            <i class="fab fa-google-drive text-blue-500 text-3xl mb-3"></i>
            <span class="text-sm font-medium text-gray-700">YG Drive</span>
        </a>
        <a href="#" class="google-card flex flex-col items-center text-center hover:bg-gray-50 transition-colors">
            <i class="fas fa-wallet text-purple-600 text-3xl mb-3"></i>
            <span class="text-sm font-medium text-gray-700">YG Pay</span>
        </a>
    </div>

    <!-- Common Questions -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
        <div>
            <h3 class="text-lg font-normal text-[#202124] mb-6">Popular Articles</h3>
            <ul class="space-y-4">
                <li><a href="#" class="text-sm text-[#1a73e8] hover:underline flex items-center gap-3"><i class="far fa-file-alt text-gray-400"></i> How to change your imperial password</a></li>
                <li><a href="#" class="text-sm text-[#1a73e8] hover:underline flex items-center gap-3"><i class="far fa-file-alt text-gray-400"></i> Managing staff and organization members</a></li>
                <li><a href="#" class="text-sm text-[#1a73e8] hover:underline flex items-center gap-3"><i class="far fa-file-alt text-gray-400"></i> Fixing sync issues across ecosystem nodes</a></li>
                <li><a href="#" class="text-sm text-[#1a73e8] hover:underline flex items-center gap-3"><i class="far fa-file-alt text-gray-400"></i> Understanding your imperial billing statement</a></li>
            </ul>
        </div>
        
        <div class="google-card bg-blue-50/20 border-blue-100 flex flex-col justify-between">
            <div>
                <h3 class="text-lg font-normal text-[#202124] mb-4">Need more help?</h3>
                <p class="text-sm text-gray-600 mb-6">If you can't find the answer you're looking for, our Imperial Guardians are ready to assist you personally.</p>
            </div>
            <button class="w-full py-3 bg-[#1a73e8] text-white rounded-lg text-sm font-medium hover:bg-blue-700 shadow-md">Contact Support</button>
        </div>
    </div>

    <footer class="mt-20 pt-10 border-t border-gray-100 flex justify-between items-center text-[10px] text-gray-400 uppercase font-bold tracking-widest">
        <div>© 2026 YGXone Empire Support</div>
        <div class="flex gap-6">
            <a href="#" class="hover:text-gray-600">Privacy Policy</a>
            <a href="#" class="hover:text-gray-600">Terms of Service</a>
        </div>
    </footer>

</div>
@endsection
