<!-- Google-Style User Profile Card -->
<div x-data="{ open: false }" class="relative">
    <!-- Avatar Trigger -->
    <button @click="open = !open" 
            class="w-10 h-10 rounded-full border-2 border-white hover:border-blue-100 transition shadow-sm overflow-hidden bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white font-bold text-sm">
        @if(auth()->user()->image)
            <img src="{{ Storage::url(auth()->user()->image) }}" class="w-full h-full object-cover">
        @else
            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
        @endif
    </button>

    <!-- The Profile Card Dropdown -->
    <div x-show="open" 
         x-cloak
         @click.away="open = false"
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         class="absolute right-0 mt-2 w-80 bg-white border border-gray-200 rounded-[2rem] shadow-2xl z-[250] p-6 text-center">
        
        <!-- Identity Header -->
        <div class="mb-6">
            <div class="w-20 h-20 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white text-3xl font-bold mx-auto mb-3 shadow-lg border-4 border-white overflow-hidden">
                @if(auth()->user()->image)
                    <img src="{{ Storage::url(auth()->user()->image) }}" class="w-full h-full object-cover">
                @else
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                @endif
            </div>
            <h3 class="text-lg font-bold text-gray-900">{{ auth()->user()->name }}</h3>
            <p class="text-sm text-gray-500 mb-2">{{ auth()->user()->email }}</p>
            <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-blue-50 text-blue-600 text-[10px] font-bold uppercase tracking-widest rounded-full">
                <i class="fas fa-check-circle"></i> Verified Citizen
            </span>
        </div>

        <!-- Action Links -->
        <div class="space-y-2 mb-6">
            <a href="{{ route('dashboard') }}" class="block w-full py-2.5 border border-gray-200 rounded-full text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                Manage your YG Account
            </a>
        </div>

        <!-- Footer / Sign Out -->
        <div class="pt-4 border-t border-gray-100">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="flex items-center justify-center gap-3 w-full py-3 text-gray-600 hover:bg-red-50 hover:text-red-600 rounded-xl transition text-sm font-medium">
                    <i class="fas fa-sign-out-alt"></i> Sign out of all accounts
                </button>
            </form>
        </div>

        <!-- Legal Links -->
        <div class="mt-4 flex justify-center gap-4 text-[10px] text-gray-400 font-bold uppercase tracking-tighter">
            <a href="#" class="hover:underline">Privacy Policy</a>
            <span class="text-gray-200">•</span>
            <a href="#" class="hover:underline">Terms of Service</a>
        </div>
    </div>
</div>
