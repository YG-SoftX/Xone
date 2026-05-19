<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>@yield('title', 'YG DocX') — YGXONE</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700;900&display=swap" rel="stylesheet">
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Quill.js for Rich Text Editor -->
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
    
    <!-- Alpine.js -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.3/dist/cdn.min.js"></script>

    <style>
        :root {
            --yg-bg: #0a0a0f;
            --yg-surface: #12121a;
            --yg-glass: rgba(255, 255, 255, 0.03);
            --yg-border: rgba(255, 255, 255, 0.08);
            --yg-accent: #6366f1;
            --yg-accent-glow: rgba(99, 102, 241, 0.2);
        }

        body {
            background-color: var(--yg-bg) !important;
            font-family: 'Inter', sans-serif;
            color: #f1f5f9;
            overflow-x: hidden;
        }

        .glass-panel {
            background: var(--yg-glass);
            border: 1px solid var(--yg-border);
            backdrop-filter: blur(16px);
        }

        .nav-item-active {
            background: rgba(99, 102, 241, 0.1);
            border-left: 3px solid var(--yg-accent);
            color: white !important;
        }

        .nav-item:hover {
            background: rgba(255, 255, 255, 0.05);
            color: white !important;
        }

        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: var(--yg-bg); }
        ::-webkit-scrollbar-thumb { background: var(--yg-border); border-radius: 10px; }

        [x-cloak] { display: none !important; }
    </style>
    @stack('styles')
</head>
<body class="antialiased selection:bg-indigo-500/30">
    <div class="flex h-screen overflow-hidden">
        
        <!-- Sidebar -->
        <aside class="hidden md:flex flex-col w-72 glass-panel border-r border-white/5 z-50">
            <!-- Brand -->
            <div class="p-6">
                <a href="/" class="flex items-center space-x-3 group">
                    <div class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform">
                        <i class="fas fa-file-alt text-white"></i>
                    </div>
                    <span class="text-xl font-black text-white tracking-tighter uppercase">YG DOCX</span>
                </a>
            </div>

            <!-- New Document Button -->
            <div class="px-6 mb-4">
                <a href="{{ route('documents.create') }}" 
                   class="flex items-center justify-center w-full py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-black rounded-xl hover:scale-[1.02] active:scale-95 transition-all shadow-lg shadow-indigo-600/20 uppercase tracking-widest text-xs gap-2">
                    <i class="fas fa-plus"></i> New Document
                </a>
            </div>

            <nav class="flex-1 px-4 space-y-1 overflow-y-auto">
                <p class="text-[10px] font-black text-gray-500 uppercase tracking-widest px-4 mb-3">Documents</p>
                
                <a href="{{ route('home') }}" class="flex items-center px-4 py-3 rounded-xl text-gray-400 font-semibold transition-all nav-item {{ request()->routeIs('home') ? 'nav-item-active' : '' }}">
                    <i class="fas fa-file-lines w-8 text-lg"></i>
                    <span class="text-sm">My Documents</span>
                </a>

                <a href="{{ route('home') }}?type=draft" class="flex items-center px-4 py-3 rounded-xl text-gray-400 font-semibold transition-all nav-item">
                    <i class="fas fa-pen w-8 text-lg"></i>
                    <span class="text-sm">Drafts</span>
                </a>

                <a href="{{ route('home') }}?type=published" class="flex items-center px-4 py-3 rounded-xl text-gray-400 font-semibold transition-all nav-item">
                    <i class="fas fa-check-circle w-8 text-lg"></i>
                    <span class="text-sm">Published</span>
                </a>

                <a href="{{ route('folders.index') }}" class="flex items-center px-4 py-3 rounded-xl text-gray-400 font-semibold transition-all nav-item">
                    <i class="fas fa-folder w-8 text-lg"></i>
                    <span class="text-sm">Folders</span>
                </a>

                <a href="{{ route('templates.index') }}" class="flex items-center px-4 py-3 rounded-xl text-gray-400 font-semibold transition-all nav-item">
                    <i class="fas fa-copy w-8 text-lg"></i>
                    <span class="text-sm">Templates</span>
                </a>

                <div class="pt-6 mt-4 border-t border-white/5">
                    <p class="text-[10px] font-black text-gray-500 uppercase tracking-widest px-4 mb-3">Ecosystem Bridge</p>
                    <a href="https://account.ygxone.com/dashboard" class="flex items-center px-4 py-3 rounded-xl text-gray-400 font-semibold transition-all nav-item">
                        <i class="fas fa-th-large w-8 text-lg text-indigo-400"></i>
                        <span class="text-sm">Command Center</span>
                    </a>
                    <a href="https://pay.ygxone.com" class="flex items-center px-4 py-3 rounded-xl text-gray-400 font-semibold transition-all nav-item">
                        <i class="fas fa-wallet w-8 text-lg text-purple-400"></i>
                        <span class="text-sm">YG Pay</span>
                    </a>
                </div>
            </nav>

            <!-- User Footer -->
            <div class="p-4 border-t border-white/5 bg-black/20 mt-auto">
                <div class="flex items-center space-x-3">
                    <div class="h-9 w-9 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white font-bold border border-white/10 shadow-lg text-sm">
                        {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-bold text-white truncate">{{ auth()->user()->name ?? 'User' }}</p>
                        <p class="text-[10px] text-indigo-400 font-bold uppercase tracking-widest">Document Editor</p>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col overflow-hidden relative">
            
            <!-- Top Bar -->
            <header class="h-16 glass-panel border-b border-white/5 flex items-center justify-between px-6 z-40">
                <div class="flex items-center flex-1">
                    <div class="relative w-full max-w-md">
                        <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 text-sm"></i>
                        <input type="text" placeholder="Search documents..." 
                               class="w-full bg-white/5 border border-white/10 rounded-xl py-2.5 pl-10 pr-4 text-sm text-white placeholder-gray-600 focus:border-indigo-500/50 outline-none transition-all"
                               x-on:input.debounce.300ms="window.location.href = '{{ route('home') }}?search=' + encodeURIComponent($event.target.value)">
                    </div>
                </div>

                <div class="flex items-center space-x-4">
                    <div class="flex items-center gap-2 px-3 py-1.5 bg-white/5 border border-white/10 rounded-lg">
                        <div class="w-2 h-2 bg-indigo-500 rounded-full animate-pulse"></div>
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Synced</span>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 overflow-hidden">
                <div class="h-full overflow-y-auto p-6">
                    @if(session('success'))
                        <div class="mb-6 px-4 py-3 rounded-lg text-sm bg-green-500/10 text-green-400 border border-green-500/20">
                            {{ session('success') }}
                        </div>
                    @endif
                    @if(session('error'))
                        <div class="mb-6 px-4 py-3 rounded-lg text-sm bg-red-500/10 text-red-400 border border-red-500/20">
                            {{ session('error') }}
                        </div>
                    @endif
                    @yield('content')
                </div>
            </main>

            <!-- Footer -->
            <footer class="p-4 border-t border-white/5 bg-black/20">
                <div class="flex justify-between items-center">
                    <div class="flex items-center space-x-4">
                        <a href="https://ygxone.com" class="text-[10px] font-black text-gray-600 hover:text-white uppercase tracking-widest transition-colors">Home</a>
                        <a href="https://account.ygxone.com" class="text-[10px] font-black text-gray-600 hover:text-white uppercase tracking-widest transition-colors">Account</a>
                        <a href="https://mail.ygxone.com" class="text-[10px] font-black text-gray-600 hover:text-white uppercase tracking-widest transition-colors">Mail</a>
                    </div>
                    <p class="text-[10px] font-black text-gray-700 uppercase tracking-[0.3em]">
                        &copy; {{ date('Y') }} YGXONE EMPIRE. ALL AUTHORITY RESERVED.
                    </p>
                </div>
            </footer>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
