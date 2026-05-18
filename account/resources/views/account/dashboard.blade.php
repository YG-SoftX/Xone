@extends('layouts.dashboard')
@section('title', 'YG Account')

@section('dashboard-content')
<div class="max-w-4xl mx-auto px-6 py-12">
    
    <!-- Imperial Celebration Sentinel (Personal & Professional Milestones) -->
    @php
        $today = now()->format('m-d');
        $isBirthday = auth()->user()->birthday && auth()->user()->birthday->format('m-d') === $today;
        $isAnniversary = auth()->user()->joined_at && auth()->user()->joined_at->format('m-d') === $today;
    @endphp

    @if($isBirthday || $isAnniversary)
    <div class="google-card mb-12 border-yellow-400 bg-gradient-to-r from-yellow-50/50 to-orange-50/50 border-l-4 overflow-hidden relative group">
        <div class="absolute -right-4 -top-4 text-yellow-200/50 transform rotate-12 transition-transform group-hover:scale-110">
            <i class="fas fa-birthday-cake text-8xl"></i>
        </div>
        <div class="flex items-start gap-6 relative z-10">
            <div class="w-16 h-16 rounded-2xl bg-yellow-400 flex items-center justify-center text-white shadow-lg animate-bounce">
                <i class="fas {{ $isBirthday ? 'fa-birthday-cake' : 'fa-award' }} text-2xl"></i>
            </div>
            <div>
                <h3 class="text-xs font-bold text-yellow-700 uppercase tracking-widest mb-1">Imperial Celebration</h3>
                <h2 class="text-2xl font-normal text-gray-900 mb-1">
                    {{ $isBirthday ? 'Happy Birthday, ' . (auth()->user()->firstname ?? auth()->user()->name) . '!' : 'Happy Anniversary!' }}
                </h2>
                <p class="text-sm text-gray-600 leading-relaxed">
                    {{ $isBirthday ? 'The Empire celebrates another year of your presence. May your path be legendary.' : 'Congratulations on another year of service in the YGXONE Empire!' }}
                </p>
            </div>
        </div>
    </div>
    @endif

    <!-- Organization Welcome Message (Sovereign Onboarding) -->
    @if(auth()->user()->organization && auth()->user()->organization->welcome_message)
    <div class="google-card mb-12 border-brand border-l-4 bg-gray-50/30">
        <div class="flex items-start gap-4">
            <div class="p-2.5 bg-brand rounded-xl text-white shrink-0 shadow-sm">
                <i class="fas fa-quote-left text-sm"></i>
            </div>
            <div>
                <h3 class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">{{ auth()->user()->organization->name }} Message</h3>
                <p class="text-lg font-normal text-gray-800 italic leading-relaxed">"{{ auth()->user()->organization->welcome_message }}"</p>
            </div>
        </div>
    </div>
    @endif

    {{-- Header Section with Organization Theme --}}
    @php
        $org = auth()->user()->organization;
        $bgStyle = '';
        if ($org) {
            if ($org->profile_background_image) {
                $bgUrl = Storage::url($org->profile_background_image);
                $bgStyle = "background: linear-gradient(rgba(255,255,255,0.8), rgba(255,255,255,0.8)), url('{$bgUrl}'); background-size: cover; background-position: center;";
            } elseif ($org->profile_background_color) {
                $bgStyle = "background-color: {$org->profile_background_color}10;"; // 10% opacity
            }
        }
    @endphp

    <div class="text-center mb-12 animate-fade-in p-10 rounded-[3rem]" style="{{ $bgStyle }}">
        <div class="relative inline-block mb-4">
            <img src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->name) }}&background={{ str_replace('#', '', ($org->primary_color ?? '1a73e8')) }}&color=fff" class="w-24 h-24 rounded-full border-4 border-white shadow-lg" alt="">
            <x-status-indicator status="online" size="lg" />
        </div>
        <h1 class="text-[32px] font-normal text-[#202124] mb-2 tracking-tight">Welcome, {{ auth()->user()->firstname ?? auth()->user()->name }}</h1>
        <p class="text-sm text-[#5f6368]">Manage your information, privacy, and security to make YG work better for you. <a href="#" class="text-brand font-bold hover:underline">Learn more</a></p>
    </div>

    <!-- Dashboard Content Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">

        <!-- Imperial Search Bridge -->
        <div class="col-span-1 md:col-span-2 mb-4">
            <div class="relative group max-w-2xl mx-auto">
                <div class="absolute inset-0 bg-brand/5 blur-xl rounded-[3rem] opacity-0 group-focus-within:opacity-100 transition-opacity"></div>
                <div class="relative flex items-center">
                    <div class="absolute left-6 text-gray-400 group-focus-within:text-brand transition-colors">
                        <i class="fas fa-search text-lg"></i>
                    </div>
                    <form action="/search" method="GET" class="w-full">
                        <input type="text" name="q" placeholder="Search the global web or your imperial workspace..." 
                            class="w-full pl-16 pr-14 py-5 bg-white border border-gray-100 rounded-[2rem] text-lg focus:ring-4 focus:ring-brand/5 focus:border-brand/30 outline-none shadow-sm group-hover:shadow-md transition-all duration-300">
                    </form>
                    <div class="absolute right-6 flex items-center gap-4 text-gray-400">
                        <kbd class="hidden md:block px-2 py-1 bg-gray-50 border border-gray-200 rounded text-[10px] font-bold uppercase tracking-tighter">Ctrl + K</kbd>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Privacy Card -->
        <div class="google-card flex flex-col h-full">
            <div class="flex-1">
                <div class="flex items-center gap-4 mb-4">
                    <i class="fas fa-shield-alt text-[#1a73e8] text-xl"></i>
                    <h3 class="text-lg font-normal text-[#202124]">Privacy & personalization</h3>
                </div>
                <p class="text-sm text-[#5f6368] leading-relaxed mb-6">
                    See the data in your YG Account and choose what activity is saved to personalize your experience.
                </p>
            </div>
            <a href="#" class="text-[#1a73e8] text-sm font-medium hover:underline">Manage your data & privacy</a>
        </div>

        <!-- Security Card -->
        <div class="google-card flex flex-col h-full">
            <div class="flex-1">
                <div class="flex items-center gap-4 mb-4">
                    <i class="fas fa-user-shield text-[#1a73e8] text-xl"></i>
                    <h3 class="text-lg font-normal text-[#202124]">Security recommendations</h3>
                </div>
                <p class="text-sm text-[#5f6368] leading-relaxed mb-4">
                    Recommended actions found in the Security Checkup.
                </p>
                <div class="flex items-center gap-2 text-green-700 text-xs font-bold mb-6">
                    <i class="fas fa-check-circle"></i> Keep your account protected
                </div>
            </div>
            <a href="#" class="text-[#1a73e8] text-sm font-medium hover:underline">Protect your account</a>
        </div>

        <!-- Storage Card -->
        <div class="google-card col-span-1 md:col-span-2">
            <div class="flex flex-col md:flex-row items-center gap-8">
                <div class="flex-1">
                    <div class="flex items-center gap-4 mb-4">
                        <i class="fas fa-cloud text-[#1a73e8] text-xl"></i>
                        <h3 class="text-lg font-normal text-[#202124]">Account storage</h3>
                    </div>
                    <p class="text-sm text-[#5f6368] leading-relaxed mb-6">
                        Your account storage is shared across YG services, like Drive, Mail, and Photos.
                    </p>
                    <div class="w-full bg-gray-100 rounded-full h-2 mb-2">
                        <div class="bg-[#1a73e8] h-2 rounded-full" style="width: 12%"></div>
                    </div>
                    <p class="text-xs text-[#5f6368]">1.8 GB of 15 GB used</p>
                </div>
                <div class="shrink-0">
                    <a href="#" class="inline-block px-6 py-2 border border-gray-300 rounded-full text-sm font-medium text-[#1a73e8] hover:bg-blue-50">Manage storage</a>
                </div>
            </div>
        </div>

        <!-- Organization Card (Business Only) -->
        @if(auth()->user()->account_type === 'business')
        <div class="google-card col-span-1 md:col-span-2 border-brand/30 bg-brand/5">
            <div class="flex flex-col md:flex-row items-center gap-8">
                <div class="flex-1">
                    <div class="flex items-center gap-4 mb-4">
                        <i class="fas fa-building text-brand text-xl"></i>
                        <h3 class="text-lg font-normal text-[#202124]">My Organization</h3>
                    </div>
                    <p class="text-sm text-[#5f6368] leading-relaxed mb-4">
                        You are the administrator of <strong>{{ auth()->user()->organization->name ?? 'Your Business' }}</strong>. Manage your staff, provision new accounts, and control empire access.
                    </p>
                    <div class="flex gap-4">
                        <div class="text-center px-4 py-2 bg-white rounded-xl border border-gray-200 shadow-sm">
                            <div class="text-xl font-bold text-[#202124]">12</div>
                            <div class="text-[10px] uppercase font-bold text-gray-400 tracking-tighter">Active Staff</div>
                        </div>
                        <div class="text-center px-4 py-2 bg-white rounded-xl border border-gray-200 shadow-sm">
                            <div class="text-xl font-bold text-[#202124]">85%</div>
                            <div class="text-[10px] uppercase font-bold text-gray-400 tracking-tighter">Node Usage</div>
                        </div>
                    </div>
                </div>
                <div class="shrink-0">
                    <a href="{{ route('organization.index') }}" class="inline-block px-8 py-3 bg-brand text-white rounded-full text-sm font-bold shadow-md hover:bg-opacity-90 transition-all">Manage Hub</a>
                </div>
            </div>
        </div>
        @endif

        <!-- Staff Wallet (Imperial Creator Economy) -->
        <div class="google-card col-span-1 md:col-span-2 border-brand/20 bg-brand/5">
            <div class="flex flex-col md:flex-row items-center gap-8">
                <div class="flex-1">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-10 h-10 bg-brand/10 rounded-xl flex items-center justify-center">
                            <i class="fas fa-wallet text-brand"></i>
                        </div>
                        <h3 class="text-lg font-normal text-[#202124]">Imperial Wallet</h3>
                    </div>
                    <p class="text-sm text-[#5f6368] leading-relaxed mb-4">
                        You are an active contributor to the **Imperial Journal**. You receive <strong>{{ auth()->user()->organization->revenue_share_percentage ?? '10' }}%</strong> of the ad revenue generated by your articles.
                    </p>
                    <div class="flex gap-4">
                        <div class="text-center px-4 py-2 bg-white rounded-xl border border-gray-200 shadow-sm">
                            <div class="text-xl font-bold text-[#202124]">$125.50</div>
                            <div class="text-[10px] uppercase font-bold text-gray-400 tracking-tighter">Current Balance</div>
                        </div>
                        <div class="text-center px-4 py-2 bg-white rounded-xl border border-gray-200 shadow-sm">
                            <div class="text-xl font-bold text-green-600">$450.00</div>
                            <div class="text-[10px] uppercase font-bold text-gray-400 tracking-tighter">Lifetime Earnings</div>
                        </div>
                    </div>
                </div>
                <div class="shrink-0">
                    @if(auth()->user()->kyc_status === 'verified')
                        <a href="https://pay.ygxone.com/withdraw" class="bg-brand text-white px-8 py-3 rounded-full text-sm font-bold shadow-md hover:bg-opacity-90 transition-all flex items-center gap-2">
                            <img src="https://pay.ygxone.com/assets/images/logo-icon.png" class="w-4 h-4 brightness-0 invert" alt="">
                            Withdraw via YG Pay
                        </a>
                    @else
                        <div class="flex flex-col items-end gap-2">
                            <button disabled class="bg-gray-100 text-gray-400 px-8 py-3 rounded-full text-sm font-bold cursor-not-allowed flex items-center gap-2">
                                <i class="fas fa-lock text-xs"></i> Withdraw Locked
                            </button>
                            <a href="/settings/security?tab=kyc" class="text-[10px] font-bold text-red-500 uppercase tracking-widest hover:underline">Complete KYC to Unlock</a>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Imperial News Feed (Member Only / General) -->
        <div class="google-card col-span-1 md:col-span-2">
            <div class="flex justify-between items-center mb-8">
                <div class="flex items-center gap-4">
                    <i class="fas fa-newspaper text-gray-400 text-xl"></i>
                    <h3 class="text-lg font-normal text-[#202124]">Imperial News Feed</h3>
                </div>
                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Latest Updates</span>
            </div>

            <div class="space-y-6">
                <!-- Sample Announcement (Static for demo, would be a foreach loop) -->
                <div class="flex gap-6 group cursor-pointer hover:bg-gray-50 p-4 rounded-2xl transition-colors border border-transparent hover:border-gray-100">
                    <div class="w-12 h-12 rounded-xl bg-blue-50 flex items-center justify-center shrink-0">
                        <i class="fas fa-bullhorn text-blue-600"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex justify-between items-start mb-1">
                            <h4 class="text-sm font-bold text-gray-900 group-hover:text-brand transition-colors">Phase 4 Deployment: Neural Node Expansion</h4>
                            <span class="text-[10px] text-gray-400 font-medium">2 hours ago</span>
                        </div>
                        <p class="text-xs text-gray-500 line-clamp-2">The imperial core has successfully initialized the neural node expansion. All staff members are requested to review their storage quotas in the Storage Vault.</p>
                        <div class="mt-2 flex items-center gap-2">
                            <span class="px-2 py-0.5 bg-red-50 text-red-600 text-[9px] font-bold uppercase rounded">High Priority</span>
                            <span class="text-[10px] text-brand font-bold hover:underline uppercase tracking-tighter">Read Announcement</span>
                        </div>
                    </div>
                </div>

                <div class="flex gap-6 group cursor-pointer hover:bg-gray-50 p-4 rounded-2xl transition-colors border border-transparent hover:border-gray-100 opacity-75">
                    <div class="w-12 h-12 rounded-xl bg-gray-50 flex items-center justify-center shrink-0">
                        <i class="fas fa-calendar-alt text-gray-400"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex justify-between items-start mb-1">
                            <h4 class="text-sm font-bold text-gray-900">Quarterly Imperial Review</h4>
                            <span class="text-[10px] text-gray-400 font-medium">1 day ago</span>
                        </div>
                        <p class="text-xs text-gray-500 line-clamp-2">Join us for the quarterly review of our sovereign productivity suite. We will be discussing the new "Google-Sync" features.</p>
                    </div>
                </div>
            </div>
            
            <button class="w-full mt-6 py-3 text-xs font-bold text-gray-400 hover:text-brand uppercase tracking-widest border-t border-gray-50 transition-colors">
                View All Announcements
            </button>
        </div>

        <!-- Imperial Blog Feed (Google-like Sovereign Journal) -->
        <div class="col-span-1 md:col-span-2 mt-16 mb-12">
            <div class="flex justify-between items-center mb-10">
                <div class="flex items-center gap-5">
                    <div class="w-12 h-12 bg-brand/5 rounded-2xl flex items-center justify-center shadow-sm">
                        <i class="fas fa-feather-alt text-brand text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-[22px] font-normal text-gray-900 tracking-tight">The Imperial Journal</h3>
                        <p class="text-[13px] text-gray-500 font-medium">Thought leadership and ecosystem insights</p>
                    </div>
                </div>
                <button class="px-6 py-2.5 border border-gray-200 rounded-full text-[11px] font-bold text-gray-600 hover:bg-gray-50 transition-all uppercase tracking-widest hover:border-brand/30 hover:text-brand">
                    View All Articles
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                <!-- Google-style Article Card -->
                <div class="group cursor-pointer">
                    <div class="aspect-[16/9] rounded-[2.5rem] overflow-hidden mb-6 shadow-sm border border-gray-100 relative bg-gray-50">
                        <img src="https://images.unsplash.com/photo-1451187580459-43490279c0fa?auto=format&fit=crop&q=80&w=800" class="w-full h-full object-cover transition-transform duration-1000 group-hover:scale-105" alt="">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                    </div>
                    <div class="px-2">
                        <div class="flex items-center gap-3 mb-4">
                            <span class="px-3 py-1 bg-brand/10 text-brand text-[9px] font-black uppercase tracking-widest rounded-full">Intelligence</span>
                            <span class="text-[11px] font-bold text-gray-400 uppercase tracking-tighter">5 min read</span>
                        </div>
                        <h4 class="text-[20px] font-normal text-gray-900 leading-tight mb-3 group-hover:text-brand transition-colors">Operationalizing Neural Sovereignty across the Node Expansion</h4>
                        <p class="text-[14px] text-gray-500 leading-relaxed line-clamp-2 mb-5">Exploring the architectural shift towards decentralized corporate intelligence and private node security.</p>
                        <div class="flex items-center gap-3 py-2 border-t border-gray-50 mt-auto">
                            <div class="relative">
                                <img src="{{ auth()->user()->user_image }}" class="w-7 h-7 rounded-full border-2 border-white shadow-sm" alt="">
                                <div class="absolute -right-1 -bottom-1 w-3 h-3 bg-brand rounded-full border-2 border-white"></div>
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs font-bold text-gray-900">{{ auth()->user()->name }}</p>
                                <p class="text-[10px] text-gray-400 font-medium">Imperial Architect</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Google-style Article Card 2 -->
                <div class="group cursor-pointer">
                    <div class="aspect-[16/9] rounded-[2.5rem] overflow-hidden mb-6 shadow-sm border border-gray-100 relative bg-gray-50">
                        <img src="https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?auto=format&fit=crop&q=80&w=800" class="w-full h-full object-cover transition-transform duration-1000 group-hover:scale-105" alt="">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                    </div>
                    <div class="px-2">
                        <div class="flex items-center gap-3 mb-4">
                            <span class="px-3 py-1 bg-purple-50 text-purple-600 text-[9px] font-black uppercase tracking-widest rounded-full">Governance</span>
                            <span class="text-[11px] font-bold text-gray-400 uppercase tracking-tighter">3 min read</span>
                        </div>
                        <h4 class="text-[20px] font-normal text-gray-900 leading-tight mb-3 group-hover:text-brand transition-colors">The New Standard of White-Label Enterprise Sovereignty</h4>
                        <p class="text-[14px] text-gray-500 leading-relaxed line-clamp-2 mb-5">How organizations are leveraging the Sovereign Gateway to define their corporate identity.</p>
                        <div class="flex items-center gap-3 py-2 border-t border-gray-50 mt-auto">
                            <div class="relative">
                                <img src="https://ui-avatars.com/api/?name=Admin&background=random&color=fff" class="w-7 h-7 rounded-full border-2 border-white shadow-sm" alt="">
                                <div class="absolute -right-1 -bottom-1 w-3 h-3 bg-purple-500 rounded-full border-2 border-white"></div>
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs font-bold text-gray-900">Empire Administrator</p>
                                <p class="text-[10px] text-gray-400 font-medium">Governance Bureau</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <footer class="mt-12 pt-8 border-t border-gray-200 flex justify-between text-xs text-[#5f6368]">
        <div class="flex gap-6">
            @if(auth()->user()->organization && auth()->user()->organization->privacy_content)
                <a href="#" class="hover:underline font-bold text-brand">{{ auth()->user()->organization->name }} Privacy</a>
            @else
                <a href="#" class="hover:underline">Privacy Policy</a>
            @endif

            @if(auth()->user()->organization && auth()->user()->organization->tos_content)
                <a href="#" class="hover:underline font-bold text-brand">{{ auth()->user()->organization->name }} Terms</a>
            @else
                <a href="#" class="hover:underline">Terms of Service</a>
            @endif
        </div>
        <div>Only you can see your settings. YG protects your privacy and security.</div>
    </footer>
</div>

<style>
    @keyframes fade-in {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .animate-fade-in { animation: fade-in 0.6s ease-out; }
</style>
@endsection
