@extends('layouts.dashboard')
@section('title', 'Imperial Proclamation Portal')

@section('dashboard-content')
<div class="max-w-4xl mx-auto px-6 py-12">
    
    <div class="flex justify-between items-center mb-10">
        <div class="flex items-center gap-5">
            <div class="w-14 h-14 bg-brand rounded-2xl flex items-center justify-center shadow-xl">
                <i class="fas fa-bullhorn text-white text-2xl"></i>
            </div>
            <div>
                <h1 class="text-[28px] font-normal text-gray-900 tracking-tight">Imperial Proclamation Portal</h1>
                <p class="text-[14px] text-gray-500">Issue supreme directives to all citizens of YG Xone</p>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-10 p-6 bg-brand/5 border border-brand/20 rounded-2xl flex items-center gap-4 animate-fade-in">
            <div class="w-10 h-10 bg-brand rounded-full flex items-center justify-center text-white">
                <i class="fas fa-check"></i>
            </div>
            <div>
                <p class="text-sm font-bold text-brand">The Voice of the Empire has been heard.</p>
                <p class="text-xs text-gray-500">{{ session('success') }}</p>
            </div>
        </div>
    @endif

    <!-- Broadcast Composer -->
    <div class="google-card border-brand/20">
        <form action="{{ route('admin.system.broadcast.send') }}" method="POST" class="space-y-8">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div class="space-y-2">
                    <label class="text-[10px] uppercase font-black text-gray-400 tracking-widest">Proclamation Title</label>
                    <input type="text" name="title" value="Welcome to YG Xone: The Sovereign Launch" 
                        class="w-full px-5 py-3 bg-gray-50 border border-gray-100 rounded-xl text-sm focus:ring-4 focus:ring-brand/5 outline-none font-bold text-gray-900">
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] uppercase font-black text-gray-400 tracking-widest">Broadcast Type</label>
                    <select name="type" class="w-full px-5 py-3 bg-gray-50 border border-gray-100 rounded-xl text-sm focus:ring-4 focus:ring-brand/5 outline-none font-bold text-gray-700">
                        <option value="proclamation">Imperial Proclamation</option>
                        <option value="update">Ecosystem Update</option>
                        <option value="announcement">Global Announcement</option>
                        <option value="security">Security Directive</option>
                    </select>
                </div>
            </div>

            <div class="space-y-2">
                <label class="text-[10px] uppercase font-black text-gray-400 tracking-widest">Message to the Empire</label>
                <textarea name="message" rows="6" class="w-full px-6 py-4 bg-gray-50 border border-gray-100 rounded-2xl text-sm focus:ring-4 focus:ring-brand/5 outline-none leading-relaxed text-gray-600">Citizens of the Empire,

Today marks the definitive birth of YG Xone. We have officially operationalized the world's first fully sovereign, 16-node private internet infrastructure. 

You now have access to the Sovereign Search Hub, the Imperial Browser, and our private Ads & AdSense network. Your data is your own. Your influence is your own. Your economy is your own.

The future of neural sovereignty has begun. Welcome to the new digital age.

— The Imperial Core Admin</textarea>
            </div>

            <div class="flex items-center justify-between pt-6 border-t border-gray-50">
                <div class="flex items-center gap-2 text-gray-400">
                    <i class="fas fa-users text-xs"></i>
                    <span class="text-[10px] font-bold uppercase tracking-widest">Target: All Citizens (16 Nodes)</span>
                </div>
                <button class="px-10 py-4 bg-brand text-white rounded-full text-sm font-bold uppercase tracking-widest shadow-xl hover:bg-opacity-90 transition-all flex items-center gap-3">
                    <i class="fas fa-paper-plane text-xs"></i> Issue Proclamation
                </button>
            </div>
        </form>
    </div>

</div>
@endsection
