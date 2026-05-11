@extends('layouts.platform')

@section('title', 'Sovereign Security Hub - YGXONE Guard')

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10" x-data="{ 
    show2FAModal: false, 
    qrCodeSvg: '', 
    secret: '',
    loading: false,
    async open2FA() {
        this.loading = true;
        try {
            const response = await fetch('{{ route('settings.security.enable-2fa') }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            });
            const data = await response.json();
            if (data.success) {
                this.qrCodeSvg = data.qr_code_svg;
                this.secret = data.secret;
                this.show2FAModal = true;
            }
        } catch (e) { alert('Security Gateway Error. Please try again.'); }
        this.loading = false;
    }
}">
    <!-- Header Section -->
    <div class="mb-10">
        <h1 class="text-4xl font-black text-white leading-tight mb-2 tracking-tighter">{{ __("SOVEREIGN SECURITY") }}</h1>
        <p class="text-gray-400 text-sm tracking-widest uppercase font-bold">{{ __("Initialize & Manage Your Ecosystem Protection") }}</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
        
        <!-- Sidebar: Identity Status -->
        <div class="lg:col-span-1 space-y-6">
            <div class="group relative rounded-[2.5rem] p-8 bg-white/5 border border-white/10 backdrop-blur-xl overflow-hidden shadow-2xl">
                <div class="absolute -top-20 -left-20 w-40 h-40 bg-blue-500/20 rounded-full blur-3xl group-hover:bg-blue-500/30 transition-all"></div>
                
                <div class="relative z-10">
                    <div class="w-16 h-16 bg-blue-500/20 rounded-2xl flex items-center justify-center mb-6 border border-blue-500/30">
                        <i class="fas fa-shield-alt text-blue-400 text-3xl"></i>
                    </div>
                    <h2 class="text-2xl font-black text-white mb-2 uppercase tracking-tight">YG Guard</h2>
                    <p class="text-gray-400 text-xs leading-relaxed mb-8">Your account is fortified by the YGXONE sovereign security protocol.</p>
                    
                    <div class="space-y-4">
                        <div class="flex items-center justify-between p-3 rounded-2xl bg-white/5 border border-white/5">
                            <span class="text-[10px] font-black text-gray-500 uppercase tracking-widest">Protocol</span>
                            <span class="text-[10px] font-black text-green-400 uppercase tracking-widest">AES-256 E2E</span>
                        </div>
                        <div class="flex items-center justify-between p-3 rounded-2xl bg-white/5 border border-white/5">
                            <span class="text-[10px] font-black text-gray-500 uppercase tracking-widest">Phone Link</span>
                            <span class="text-[10px] font-black text-blue-400 uppercase tracking-widest">Verified</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="rounded-3xl p-6 bg-white/5 border border-white/5 backdrop-blur-md">
                <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 mb-4">Fortress Intelligence</h3>
                <div class="flex gap-4">
                    <div class="w-10 h-10 rounded-xl bg-teal-500/10 flex-shrink-0 flex items-center justify-center border border-teal-500/20 text-teal-400">
                        <i class="fas fa-brain text-xs"></i>
                    </div>
                    <p class="text-xs text-gray-400 leading-normal">Our neural networks monitor login patterns 24/7 to neutralize brute-force attempts before they reach your vault.</p>
                </div>
            </div>
        </div>

        <!-- Main Settings: The Vault -->
        <div class="lg:col-span-2 space-y-8">
            
            <!-- 2FA: The Secondary Barrier -->
            <div class="group relative rounded-[3rem] p-10 bg-white/5 border border-white/10 backdrop-blur-xl overflow-hidden shadow-2xl">
                <div class="flex items-start justify-between mb-10">
                    <div>
                        <h3 class="text-2xl font-black text-white mb-2 uppercase tracking-tight">Two-Factor Authentication</h3>
                        <p class="text-gray-400 text-sm">Add a secondary layer of authority to your identity.</p>
                    </div>
                    <div class="px-5 py-2 rounded-full text-[10px] font-black uppercase tracking-widest {{ $twoFactorEnabled ? 'bg-green-500/20 text-green-400 border border-green-500/30' : 'bg-red-500/20 text-red-400 border border-red-500/30 animate-pulse' }}">
                        {{ $twoFactorEnabled ? 'Sovereign Protected' : 'Action Required' }}
                    </div>
                </div>

                @if($twoFactorEnabled)
                    <div class="p-8 bg-green-500/5 rounded-3xl border border-green-500/20 flex items-center justify-between mb-10">
                        <div class="flex items-center gap-6">
                            <div class="w-14 h-14 bg-green-500/20 rounded-2xl flex items-center justify-center text-green-400 border border-green-500/30">
                                <i class="fas fa-fingerprint text-2xl"></i>
                            </div>
                            <div>
                                <h4 class="font-black text-white text-lg uppercase tracking-tight">Guard is Active</h4>
                                <p class="text-[10px] text-green-400/70 uppercase tracking-widest font-black">Secure Handshake: Google Authenticator</p>
                            </div>
                        </div>
                    </div>
                    
                    <form action="{{ route('settings.security.disable-2fa') }}" method="POST" onsubmit="return confirm('DANGER: Removing this barrier lowers your sovereign security level. Continue?')">
                        @csrf
                        <div class="flex flex-col md:flex-row items-center gap-4">
                            <input type="password" name="password" required placeholder="Verify Credentials" class="w-full md:max-w-[250px] bg-black/40 border border-white/10 text-white rounded-2xl py-4 px-6 focus:border-red-500/50 outline-none transition">
                            <button type="submit" class="w-full md:w-auto px-8 py-4 bg-red-500/10 text-red-400 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-red-500 hover:text-white transition-all">
                                Disable Barrier
                            </button>
                        </div>
                    </form>
                @else
                    <div class="p-12 border-2 border-dashed border-white/10 rounded-[2.5rem] text-center bg-white/2 hover:bg-white/5 transition-all">
                        <button @click="open2FA()" :disabled="loading" class="group relative px-12 py-5 bg-blue-600 text-white rounded-2xl font-black shadow-2xl shadow-blue-600/30 hover:scale-105 active:scale-95 transition-all">
                            <span x-show="!loading" class="uppercase tracking-widest text-sm"><i class="fas fa-plus mr-3 group-hover:rotate-90 transition-transform"></i>Initialize Guard</span>
                            <span x-show="loading" class="uppercase tracking-widest text-sm"><i class="fas fa-circle-notch fa-spin mr-3"></i>Syncing...</span>
                        </button>
                        <p class="mt-6 text-[10px] text-gray-500 font-black uppercase tracking-widest">Recommended for all citizens of the empire</p>
                    </div>
                @endif
            </div>

            <!-- Password Credentials -->
            <div class="rounded-[3rem] p-10 bg-white/5 border border-white/10 backdrop-blur-xl shadow-2xl">
                <h3 class="text-2xl font-black text-white mb-10 uppercase tracking-tight">Update Access Secret</h3>
                
                <form action="{{ route('settings.security.update-password') }}" method="POST" class="space-y-8">
                    @csrf
                    <div>
                        <label class="text-[10px] font-black uppercase tracking-widest text-gray-500 mb-3 block">Current Secret</label>
                        <input type="password" name="current_password" required class="w-full bg-black/40 border border-white/10 text-white rounded-2xl py-4 px-6 focus:border-blue-500/50 outline-none transition" placeholder="••••••••">
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div>
                            <label class="text-[10px] font-black uppercase tracking-widest text-gray-500 mb-3 block">New Secret</label>
                            <input type="password" name="password" required class="w-full bg-black/40 border border-white/10 text-white rounded-2xl py-4 px-6 focus:border-blue-500/50 outline-none transition" placeholder="••••••••">
                        </div>
                        <div>
                            <label class="text-[10px] font-black uppercase tracking-widest text-gray-500 mb-3 block">Confirm Secret</label>
                            <input type="password" name="password_confirmation" required class="w-full bg-black/40 border border-white/10 text-white rounded-2xl py-4 px-6 focus:border-blue-500/50 outline-none transition" placeholder="••••••••">
                        </div>
                    </div>
                    <button type="submit" class="w-full py-5 bg-gradient-to-r from-blue-600 to-indigo-700 text-white rounded-2xl font-black shadow-2xl shadow-blue-600/30 hover:scale-[1.01] active:scale-95 transition-all uppercase tracking-widest text-sm">
                        Rotate Security Credentials
                    </button>
                </form>
            </div>

            <!-- Session Authority -->
            <div class="rounded-[3rem] p-10 bg-white/5 border border-white/10 backdrop-blur-xl shadow-2xl">
                <div class="flex items-center justify-between mb-10">
                    <h3 class="text-2xl font-black text-white uppercase tracking-tight">Active Sessions</h3>
                    <form action="{{ route('settings.security.terminate-sessions') }}" method="POST">
                        @csrf
                        <button type="submit" class="text-[10px] font-black uppercase tracking-widest text-red-400 hover:text-red-300 transition-colors">Terminate Other Authority</button>
                    </form>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    @foreach($sessions as $session)
                        <div class="p-6 rounded-3xl bg-white/2 border border-white/5 flex items-center justify-between group hover:border-blue-500/30 transition-all">
                            <div class="flex items-center gap-5">
                                <div class="w-12 h-12 bg-white/5 rounded-2xl flex items-center justify-center text-gray-500 border border-white/10 group-hover:text-blue-400 transition-colors">
                                    <i class="fas fa-{{ str_contains($session['user_agent'], 'Mobile') ? 'mobile-alt' : 'desktop' }} text-xl"></i>
                                </div>
                                <div>
                                    <h4 class="font-black text-white text-sm uppercase tracking-tight">{{ $session['is_current'] ? 'This Portal' : 'Remote Terminal' }}</h4>
                                    <p class="text-[10px] text-gray-500 font-black uppercase tracking-widest">{{ $session['ip_address'] }} • {{ __("Operational") }}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

        </div>
    </div>

    <!-- 2FA Handshake Modal -->
    <template x-if="show2FAModal">
        <div class="fixed inset-0 z-[100] flex items-center justify-center p-6 bg-black/80 backdrop-blur-2xl">
            <div class="bg-[#020617] max-w-lg w-full rounded-[3.5rem] border border-white/10 shadow-3xl p-10 md:p-14 relative overflow-hidden" @click.away="show2FAModal = false">
                <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-blue-600 to-purple-600"></div>
                
                <div class="text-center mb-10">
                    <h3 class="text-3xl font-black text-white mb-3 uppercase tracking-tighter">Initialize Guard</h3>
                    <p class="text-gray-400 text-sm">Scan this authority key with Google Authenticator to synchronize your identity.</p>
                </div>

                <div class="flex justify-center mb-10">
                    <div class="p-4 bg-white rounded-[2rem] shadow-2xl border-4 border-blue-500/50" x-html="qrCodeSvg"></div>
                </div>

                <div class="mb-10 text-center">
                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-500 mb-3">Manual Authority Secret</p>
                    <code class="px-6 py-4 bg-white/5 rounded-2xl font-mono text-blue-400 font-black text-xl tracking-[0.2em] border border-white/5 block" x-text="secret"></code>
                </div>

                <form @submit.prevent="const res = await fetch('{{ route('settings.security.verify-2fa') }}', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' },
                    body: JSON.stringify({ code: $refs.code.value })
                }); const data = await res.json(); if(data.success) { window.location.reload(); } else { alert(data.error); }">
                    <div class="mb-10 text-center">
                        <label class="text-[10px] font-black uppercase tracking-widest text-gray-500 mb-4 block">Enter 6-Digit Authority Code</label>
                        <input x-ref="code" type="text" maxlength="6" class="w-full bg-white/5 border border-white/10 rounded-2xl py-6 text-center text-5xl font-black text-white tracking-[0.4em] focus:border-blue-500/50 outline-none transition" placeholder="000000">
                    </div>

                    <div class="grid grid-cols-2 gap-6">
                        <button type="button" @click="show2FAModal = false" class="py-5 text-gray-500 font-black uppercase tracking-widest text-[10px] hover:text-white transition-colors">Abort</button>
                        <button type="submit" class="py-5 bg-blue-600 text-white rounded-2xl font-black shadow-2xl shadow-blue-600/30 hover:scale-105 active:scale-95 transition-all uppercase tracking-widest text-xs">Verify Authority</button>
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
