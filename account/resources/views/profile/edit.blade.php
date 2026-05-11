@extends('layouts.app')

@section('title', 'Manage Your Identity - YGXone')
@section('page-title', 'Account Settings')

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-12" x-data="{ tab: 'personal' }">
    
    <div class="flex flex-col md:flex-row gap-12">
        
        {{-- Sidebar Navigation --}}
        <div class="md:w-72 flex-shrink-0 space-y-2">
            <button @click="tab = 'personal'" :class="tab === 'personal' ? 'bg-blue-600 text-white shadow-xl shadow-blue-600/20' : 'text-gray-500 hover:bg-gray-50'" class="w-full text-left px-6 py-4 rounded-2xl font-bold transition flex items-center gap-4">
                <i class="fas fa-user-circle text-lg"></i> Personal Info
            </button>
            <button @click="tab = 'security'" :class="tab === 'security' ? 'bg-blue-600 text-white shadow-xl shadow-blue-600/20' : 'text-gray-500 hover:bg-gray-50'" class="w-full text-left px-6 py-4 rounded-2xl font-bold transition flex items-center gap-4">
                <i class="fas fa-shield-alt text-lg"></i> YG Guard Security
            </button>
            <button @click="tab = 'sync'" :class="tab === 'sync' ? 'bg-blue-600 text-white shadow-xl shadow-blue-600/20' : 'text-gray-500 hover:bg-gray-50'" class="w-full text-left px-6 py-4 rounded-2xl font-bold transition flex items-center gap-4">
                <i class="fas fa-sync text-lg"></i> Ecosystem Sync
            </button>
            
            <div class="pt-8">
                <div class="p-6 bg-gradient-to-br from-gray-900 to-blue-900 rounded-[2rem] text-white">
                    <p class="text-[10px] font-black uppercase tracking-[0.2em] opacity-50 mb-3">Identity Strength</p>
                    <div class="flex items-center gap-4 mb-4">
                        <div class="flex-1 h-1.5 bg-white/10 rounded-full overflow-hidden">
                            <div class="h-full bg-blue-400 w-[85%] shadow-glow"></div>
                        </div>
                        <span class="text-xs font-black">85%</span>
                    </div>
                    <p class="text-[10px] leading-relaxed opacity-70">Complete your bio and link your mobile for 100% protection.</p>
                </div>
            </div>
        </div>

        {{-- Main Content Hub --}}
        <div class="flex-1">
            
            {{-- Tab: Personal Info --}}
            <div x-show="tab === 'personal'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0">
                <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data" class="space-y-10">
                    @csrf
                    @method('PATCH')

                    {{-- Avatar Portal --}}
                    <div class="bg-white p-10 rounded-[3rem] border border-gray-100 shadow-2xl shadow-gray-200/10 text-center md:text-left flex flex-col md:flex-row items-center gap-10">
                        <div class="relative group" x-data="{ photoPreview: null }">
                            <input type="file" name="avatar" class="hidden" x-ref="avatar" 
                                   @change="const file = $refs.avatar.files[0]; if (file) { const reader = new FileReader(); reader.onload = (e) => { photoPreview = e.target.result; }; reader.readAsDataURL(file); }">
                            
                            <div class="w-32 h-32 rounded-full border-4 border-white shadow-2xl overflow-hidden bg-gray-100 relative">
                                <template x-if="!photoPreview">
                                    <img src="{{ $user->image ? asset('storage/' . $user->image) : 'https://ui-avatars.com/api/?name='.urlencode($user->name).'&background=random' }}" 
                                         class="w-full h-full object-cover">
                                </template>
                                <template x-if="photoPreview">
                                    <img :src="photoPreview" class="w-full h-full object-cover">
                                </template>
                                
                                <button type="button" @click="$refs.avatar.click()" class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition flex items-center justify-center text-white text-xl backdrop-blur-sm">
                                    <i class="fas fa-camera"></i>
                                </button>
                            </div>
                            <div class="absolute -bottom-2 -right-2 w-10 h-10 bg-blue-600 rounded-2xl flex items-center justify-center text-white shadow-xl border-4 border-white">
                                <i class="fas fa-pen text-xs"></i>
                            </div>
                        </div>
                        <div>
                            <h3 class="text-2xl font-black text-gray-900 mb-1">Universal Avatar</h3>
                            <p class="text-gray-500 mb-4">This photo will represent you in YG Pay, Mail, and all other services.</p>
                            <button type="button" @click="$refs.avatar.click()" class="text-xs font-black uppercase tracking-widest text-blue-600 hover:underline">Upload New Identity Photo</button>
                        </div>
                    </div>

                    {{-- Identity Form --}}
                    <div class="bg-white p-10 rounded-[3rem] border border-gray-100 shadow-2xl shadow-gray-200/10 space-y-8">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div>
                                <label class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-400 mb-2 block">Full Display Name</label>
                                <input type="text" name="name" value="{{ old('name', $user->name) }}" required class="premium-input">
                            </div>
                            <div>
                                <label class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-400 mb-2 block">YG Username</label>
                                <input type="text" name="username" value="{{ old('username', $user->username) }}" class="premium-input" placeholder="@username">
                            </div>
                            <div>
                                <label class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-400 mb-2 block">Primary Email</label>
                                <input type="email" name="email" value="{{ old('email', $user->email) }}" required class="premium-input">
                            </div>
                            <div>
                                <label class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-400 mb-2 block">Recovery Phone</label>
                                <input type="text" name="phone" value="{{ old('phone', $user->mobile) }}" class="premium-input" placeholder="+1 234 567 890">
                            </div>
                        </div>

                        <div class="pt-8 border-t border-gray-50 flex items-center justify-between">
                            <p class="text-xs text-gray-400 font-medium">Last updated {{ $user->updated_at->diffForHumans() }}</p>
                            <button type="submit" class="px-10 py-5 bg-blue-600 text-white rounded-2xl font-bold shadow-xl shadow-blue-600/20 hover:scale-105 active:scale-95 transition">
                                Save Universal Profile
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            {{-- Tab: Security --}}
            <div x-show="tab === 'security'" x-cloak>
                @include('settings.security.index')
            </div>

            {{-- Tab: Ecosystem Sync --}}
            <div x-show="tab === 'sync'" x-cloak>
                <div class="bg-white p-10 rounded-[3rem] border border-gray-100 shadow-2xl shadow-gray-200/10">
                    <h3 class="text-2xl font-black text-gray-900 mb-2 tracking-tight">Ecosystem Sync Status</h3>
                    <p class="text-gray-500 mb-10">Your profile is currently broadcasted to the following services.</p>

                    <div class="space-y-4">
                        @foreach(['YG Pay', 'YG Mail', 'YG Drive', 'YG Vault', 'YG Support'] as $service)
                            <div class="p-6 rounded-3xl bg-gray-50 border border-gray-100 flex items-center justify-between group hover:bg-blue-50 transition">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 bg-white rounded-2xl flex items-center justify-center text-blue-600 shadow-sm">
                                        <i class="fas fa-{{ $service == 'YG Pay' ? 'wallet' : ($service == 'YG Mail' ? 'envelope' : 'cube') }}"></i>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-gray-900">{{ $service }}</h4>
                                        <p class="text-[10px] font-black uppercase tracking-widest text-green-500">Live Sync Active</p>
                                    </div>
                                </div>
                                <div class="w-10 h-10 rounded-full border-2 border-white shadow-md overflow-hidden opacity-50 group-hover:opacity-100 transition">
                                    <img src="{{ $user->image ? asset('storage/' . $user->image) : 'https://ui-avatars.com/api/?name='.urlencode($user->name) }}" class="w-full h-full object-cover">
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
