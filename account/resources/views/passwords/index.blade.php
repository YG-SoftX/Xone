@extends('layouts.platform')

@section('title', 'YG Sovereign Vault - Encrypted Sanctuary')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10" x-data="{ 
    search: '', 
    showAdd: false,
    copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(() => {
            alert('Authority Credential Copied.');
        });
    }
}">
    <!-- Header: Vault Authority -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-8 mb-12">
        <div>
            <h1 class="text-4xl font-black text-white leading-tight mb-2 tracking-tighter">{{ __("YG SOVEREIGN VAULT") }}</h1>
            <p class="text-gray-400 text-sm tracking-widest uppercase font-bold">{{ __("Zero-Knowledge Encrypted Sanctuary") }}</p>
        </div>
        <button @click="showAdd = true" class="group relative px-10 py-5 bg-blue-600 text-white rounded-2xl font-black shadow-2xl shadow-blue-600/30 hover:scale-105 active:scale-95 transition-all">
            <span class="relative z-10 flex items-center uppercase tracking-widest text-sm">
                <i class="fas fa-plus-circle mr-3 group-hover:rotate-90 transition-transform"></i>
                {{ __("New Credential") }}
            </span>
        </button>
    </div>

    <!-- Stats: Vault Integrity -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-12">
        <div class="rounded-[2rem] p-6 bg-white/5 border border-white/10 backdrop-blur-xl">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-blue-500/20 rounded-2xl flex items-center justify-center text-blue-400 border border-blue-500/30">
                    <i class="fas fa-key text-xl"></i>
                </div>
                <div>
                    <span class="block text-2xl font-black text-white">{{ count($credentials) }}</span>
                    <span class="text-[10px] font-black text-gray-500 uppercase tracking-widest">Identities</span>
                </div>
            </div>
        </div>
        <div class="rounded-[2rem] p-6 bg-white/5 border border-white/10 backdrop-blur-xl">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-green-500/20 rounded-2xl flex items-center justify-center text-green-400 border border-green-500/30">
                    <i class="fas fa-shield-alt text-xl"></i>
                </div>
                <div>
                    <span class="block text-2xl font-black text-green-400">OPTIMIZED</span>
                    <span class="text-[10px] font-black text-gray-500 uppercase tracking-widest">Vault Status</span>
                </div>
            </div>
        </div>
        <div class="md:col-span-2 rounded-[2rem] p-6 bg-white/5 border border-white/10 backdrop-blur-xl flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-purple-500/20 rounded-2xl flex items-center justify-center text-purple-400 border border-purple-500/30">
                    <i class="fas fa-sync text-xl"></i>
                </div>
                <div>
                    <span class="block text-xl font-black text-white uppercase tracking-tight">Ecosystem Neural Sync</span>
                    <span class="text-[10px] font-black text-purple-400 uppercase tracking-widest">Active Across All Nodes</span>
                </div>
            </div>
            <div class="flex space-x-1">
                <div class="w-1 h-4 bg-purple-500/40 rounded-full animate-bounce" style="animation-delay: 0.1s"></div>
                <div class="w-1 h-6 bg-purple-500/60 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
                <div class="w-1 h-4 bg-purple-500/40 rounded-full animate-bounce" style="animation-delay: 0.3s"></div>
            </div>
        </div>
    </div>

    <!-- Search: Intelligence Filter -->
    <div class="relative mb-12">
        <div class="absolute inset-0 bg-blue-500/5 blur-3xl rounded-full"></div>
        <div class="relative flex items-center">
            <i class="fas fa-search absolute left-8 text-gray-500"></i>
            <input type="text" x-model="search" placeholder="Scan identities by name, domain, or authority..." 
                   class="w-full pl-16 pr-8 py-6 bg-white/5 border border-white/10 rounded-3xl text-white placeholder-gray-600 focus:border-blue-500/50 outline-none transition-all shadow-2xl backdrop-blur-md">
        </div>
    </div>

    <!-- Credential Grid: The Sanctuary -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        @foreach($credentials as $cred)
            <div class="group relative rounded-[2.5rem] p-8 bg-white/5 border border-white/10 hover:border-blue-500/30 backdrop-blur-xl transition-all duration-300 shadow-2xl overflow-hidden"
                 x-show="search === '' || '{{ strtolower($cred->site_name) }}'.includes(search.toLowerCase()) || '{{ strtolower($cred->username) }}'.includes(search.toLowerCase())">
                
                <div class="absolute -top-10 -right-10 w-24 h-24 bg-blue-500/5 rounded-full blur-2xl group-hover:bg-blue-500/10 transition-all"></div>
                
                <div class="flex items-center gap-5 mb-8">
                    <div class="w-14 h-14 bg-white/5 rounded-2xl flex items-center justify-center text-gray-500 group-hover:bg-blue-500/20 group-hover:text-blue-400 border border-white/5 transition-all">
                        <i class="fas fa-globe-americas text-2xl"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 class="text-xl font-black text-white truncate uppercase tracking-tight">{{ $cred->site_name }}</h3>
                        <span class="text-[10px] font-black text-blue-400 uppercase tracking-widest">{{ $cred->category }}</span>
                    </div>
                </div>

                <div class="space-y-6 mb-8">
                    <div class="flex flex-col">
                        <span class="text-[10px] font-black text-gray-500 uppercase tracking-widest mb-2">Authority Identity</span>
                        <div class="flex items-center justify-between bg-black/40 px-5 py-4 rounded-2xl border border-white/5">
                            <span class="text-sm font-bold text-white truncate">{{ $cred->username }}</span>
                            <button @click="copyToClipboard('{{ $cred->username }}')" class="text-gray-500 hover:text-blue-400 transition-colors">
                                <i class="far fa-copy"></i>
                            </button>
                        </div>
                    </div>
                    <div class="flex flex-col" x-data="{ show: false }">
                        <span class="text-[10px] font-black text-gray-500 uppercase tracking-widest mb-2">Secret Credential</span>
                        <div class="flex items-center justify-between bg-black/40 px-5 py-4 rounded-2xl border border-white/5">
                            <span class="text-sm font-mono text-blue-400 truncate tracking-[0.2em]" x-text="show ? '{{ $cred->encrypted_password }}' : '••••••••••••'"></span>
                            <div class="flex items-center gap-3">
                                <button @click="show = !show" class="text-gray-500 hover:text-blue-400 transition-colors">
                                    <i class="far" :class="show ? 'fa-eye-slash' : 'fa-eye'"></i>
                                </button>
                                <button @click="copyToClipboard('{{ $cred->encrypted_password }}')" class="text-gray-500 hover:text-blue-400 transition-colors">
                                    <i class="far fa-copy"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-between pt-6 border-t border-white/5">
                    <span class="text-[10px] font-black text-gray-600 uppercase tracking-widest">Added {{ $cred->created_at->diffForHumans() }}</span>
                    <div class="flex items-center gap-3">
                        <button class="w-8 h-8 rounded-lg bg-white/5 flex items-center justify-center text-gray-500 hover:text-blue-400 hover:bg-blue-500/10 transition-all"><i class="fas fa-edit text-xs"></i></button>
                        <form method="POST" action="{{ route('passwords.destroy', $cred->id) }}" onsubmit="return confirm('Purge this credential from the sanctuary?')" class="inline">
                            @csrf @method('DELETE')
                            <button class="w-8 h-8 rounded-lg bg-white/5 flex items-center justify-center text-gray-500 hover:text-red-400 hover:bg-red-500/10 transition-all"><i class="fas fa-trash-alt text-xs"></i></button>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Add Credential: Initialization Portal -->
    <template x-if="showAdd">
        <div class="fixed inset-0 z-[100] flex items-center justify-center p-6 bg-black/80 backdrop-blur-2xl">
            <div class="bg-[#020617] w-full max-w-xl rounded-[3.5rem] border border-white/10 shadow-3xl p-10 md:p-14 relative overflow-hidden" @click.away="showAdd = false">
                <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-blue-600 to-purple-600"></div>
                
                <div class="text-center mb-10">
                    <h2 class="text-3xl font-black text-white mb-2 uppercase tracking-tighter">Initialize Credential</h2>
                    <p class="text-gray-400 text-sm">Store a new authority identity in your sovereign sanctuary.</p>
                </div>

                <form method="POST" action="{{ route('passwords.store') }}" class="space-y-8">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="text-[10px] font-black text-gray-500 uppercase tracking-widest mb-3 block">Domain Name</label>
                            <input type="text" name="site_name" required class="w-full bg-white/5 border border-white/10 text-white rounded-2xl py-4 px-6 focus:border-blue-500/50 outline-none transition" placeholder="e.g. Netflix">
                        </div>
                        <div>
                            <label class="text-[10px] font-black text-gray-500 uppercase tracking-widest mb-3 block">Category</label>
                            <select name="category" class="w-full bg-white/5 border border-white/10 text-white rounded-2xl py-4 px-6 focus:border-blue-500/50 outline-none transition">
                                <option value="social">Social Media</option>
                                <option value="finance">Banking & Finance</option>
                                <option value="work">Work & Business</option>
                                <option value="other">Other Authority</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="text-[10px] font-black text-gray-500 uppercase tracking-widest mb-3 block">Identity / Username</label>
                        <input type="text" name="username" required class="w-full bg-white/5 border border-white/10 text-white rounded-2xl py-4 px-6 focus:border-blue-500/50 outline-none transition" placeholder="wick@ygxone.com">
                    </div>
                    <div x-data="{ pass: '', generate() { let chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*()'; let p = ''; for(let i=0; i<20; i++) p += chars.charAt(Math.floor(Math.random() * chars.length)); this.pass = p; } }">
                        <div class="flex justify-between items-center mb-3">
                            <label class="text-[10px] font-black text-gray-500 uppercase tracking-widest">Secret Credential</label>
                            <button type="button" @click="generate()" class="text-[10px] font-black text-blue-400 uppercase tracking-widest hover:text-blue-300">Generate Fortress Key</button>
                        </div>
                        <input type="text" name="password" x-model="pass" required class="w-full bg-white/5 border border-white/10 text-blue-400 font-mono rounded-2xl py-4 px-6 focus:border-blue-500/50 outline-none transition text-lg tracking-[0.2em]" placeholder="••••••••">
                    </div>

                    <div class="pt-6 grid grid-cols-2 gap-6">
                        <button type="button" @click="showAdd = false" class="py-5 text-gray-500 font-black uppercase tracking-widest text-[10px] hover:text-white transition-colors">Abort</button>
                        <button type="submit" class="py-5 bg-blue-600 text-white rounded-2xl font-black shadow-2xl shadow-blue-600/30 hover:scale-105 active:scale-95 transition-all uppercase tracking-widest text-xs">
                            Store in Sanctuary
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>

</div>

<style>
    body { background-color: #020617 !important; color: #f8fafc; }
</style>
@endsection
