<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Inbox') — YGXONE Mail</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'sans-serif'],
                        title: ['Outfit', 'sans-serif'],
                    },
                    colors: {
                        yg: {
                            50: '#fff1f2',
                            100: '#ffe4e6',
                            200: '#fecdd3',
                            300: '#fda4af',
                            400: '#fb7185',
                            500: '#f43f5e',
                            600: '#e11d48',
                            700: '#be123c',
                            800: '#9f1239',
                            900: '#881337',
                            accent: '#e11d48',
                            accentHover: '#be123c',
                            canvas: '#f6f8fc',
                        }
                    }
                }
            }
        }
    </script>

    <style>
        body {
            background-color: #f6f8fc;
            color: #1f1f1f;
        }

        .sidebar-pill {
            transition: all 0.15s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .sidebar-pill.active {
            background-color: #ffd8de !important;
            color: #be123c !important;
            font-weight: 600;
        }
        .sidebar-pill:not(.active):hover {
            background-color: #e9eef6;
            color: #1f1f1f;
        }

        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 9999px; border: 2px solid #f6f8fc; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

        [x-cloak] { display: none !important; }

        .glow-hover:hover {
            box-shadow: 0 0 15px rgba(225, 29, 72, 0.2);
        }
    </style>
    @stack('head')
</head>
<body class="antialiased overflow-hidden font-sans bg-yg-canvas text-gray-900">
    <div class="flex h-screen w-screen overflow-hidden p-0 md:p-3 gap-0 md:gap-3" x-data="mailLayout()">

        <!-- Sidebar -->
        <aside class="fixed md:relative inset-y-0 left-0 z-50 md:z-10 flex flex-col transition-all duration-300 ease-in-out shrink-0 bg-yg-canvas"
               :class="{
                   'w-64': !sidebarCollapsed && !mobileOpen,
                   'w-20': sidebarCollapsed && !mobileOpen,
                   'translate-x-0 w-64 shadow-2xl bg-white border-r border-gray-100': mobileOpen,
                   '-translate-x-full md:translate-x-0': !mobileOpen
               }">
            
            <!-- Sidebar Header & Logo -->
            <div class="h-16 flex items-center justify-between px-6 border-b border-gray-100/50">
                <a href="{{ route('mail.inbox') }}" class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-yg-500 to-yg-700 rounded-2xl flex items-center justify-center text-white shadow-lg shadow-yg-500/20">
                        <i class="fas fa-envelope-open-text text-base"></i>
                    </div>
                    <div class="flex flex-col" x-show="!sidebarCollapsed || mobileOpen">
                        <span class="text-base font-bold font-title tracking-wide text-gray-800 leading-none">YGXONE</span>
                        <span class="text-xs font-semibold text-yg-600 tracking-wider">MAIL SERVICE</span>
                    </div>
                </a>
                
                <!-- Mobile Close Button -->
                <button class="md:hidden p-2 text-gray-500 hover:bg-gray-100 rounded-xl" @click="mobileOpen = false">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Compose Button -->
            <div class="p-4" :class="{ 'flex justify-center': sidebarCollapsed && !mobileOpen }">
                <button @click="openCompose()"
                        class="flex items-center justify-center gap-3 py-4 bg-white text-gray-700 font-semibold rounded-2xl border border-gray-200/80 shadow-sm hover:shadow-md transition-all duration-200 group text-sm w-full outline-none focus:ring-2 focus:ring-yg-100"
                        :class="{ 'w-12 h-12 rounded-full !p-0 border-yg-200': sidebarCollapsed && !mobileOpen }">
                    <i class="fas fa-plus text-yg-600 text-base group-hover:scale-110 transition-transform"></i>
                    <span x-show="!sidebarCollapsed || mobileOpen">Compose</span>
                </button>
            </div>

            <!-- Folder list -->
            <nav class="flex-1 px-2 space-y-1 overflow-y-auto">
                <a href="{{ route('mail.inbox', ['folder' => 'inbox']) }}"
                   class="sidebar-pill flex items-center gap-4 px-4 py-3 rounded-full text-sm text-gray-600 {{ $currentFolder === 'inbox' ? 'active' : '' }}">
                    <i class="fas fa-inbox w-5 text-center text-base"></i>
                    <span class="flex-1" x-show="!sidebarCollapsed || mobileOpen">Inbox</span>
                    @if(($unreadCounts['inbox'] ?? 0) > 0)
                        <span class="bg-yg-600 text-white text-[10px] font-bold px-2 py-0.5 rounded-full" x-show="!sidebarCollapsed || mobileOpen">
                            {{ $unreadCounts['inbox'] }}
                        </span>
                    @endif
                </a>

                <a href="{{ route('mail.inbox', ['folder' => 'sent']) }}"
                   class="sidebar-pill flex items-center gap-4 px-4 py-3 rounded-full text-sm text-gray-600 {{ $currentFolder === 'sent' ? 'active' : '' }}">
                    <i class="fas fa-paper-plane w-5 text-center text-base"></i>
                    <span class="flex-1" x-show="!sidebarCollapsed || mobileOpen">Sent</span>
                </a>

                <a href="{{ route('mail.inbox', ['folder' => 'starred']) }}"
                   class="sidebar-pill flex items-center gap-4 px-4 py-3 rounded-full text-sm text-gray-600 {{ $currentFolder === 'starred' ? 'active' : '' }}">
                    <i class="fas fa-star w-5 text-center text-base"></i>
                    <span class="flex-1" x-show="!sidebarCollapsed || mobileOpen">Starred</span>
                    @if(($unreadCounts['starred'] ?? 0) > 0)
                        <span class="bg-amber-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full" x-show="!sidebarCollapsed || mobileOpen">
                            {{ $unreadCounts['starred'] }}
                        </span>
                    @endif
                </a>

                <a href="{{ route('mail.inbox', ['folder' => 'spam']) }}"
                   class="sidebar-pill flex items-center gap-4 px-4 py-3 rounded-full text-sm text-gray-600 {{ $currentFolder === 'spam' ? 'active' : '' }}">
                    <i class="fas fa-circle-exclamation w-5 text-center text-base"></i>
                    <span class="flex-1" x-show="!sidebarCollapsed || mobileOpen">Spam</span>
                    @if(($unreadCounts['spam'] ?? 0) > 0)
                        <span class="bg-rose-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full" x-show="!sidebarCollapsed || mobileOpen">
                            {{ $unreadCounts['spam'] }}
                        </span>
                    @endif
                </a>

                <a href="{{ route('mail.inbox', ['folder' => 'trash']) }}"
                   class="sidebar-pill flex items-center gap-4 px-4 py-3 rounded-full text-sm text-gray-600 {{ $currentFolder === 'trash' ? 'active' : '' }}">
                    <i class="fas fa-trash-can w-5 text-center text-base"></i>
                    <span class="flex-1" x-show="!sidebarCollapsed || mobileOpen">Trash</span>
                </a>

                <div class="h-[1px] bg-gray-200/60 my-4 mx-3" x-show="!sidebarCollapsed || mobileOpen"></div>
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider px-4 mb-2" x-show="!sidebarCollapsed || mobileOpen">Actions</p>

                <!-- Backup Action Button -->
                <form method="POST" action="{{ route('api.backup') }}"
                      x-data="{ loading: false }"
                      @submit.prevent="if(!confirm('Export all emails & attachments as a secure backup zip? This may take a moment.')) return; loading = true; $el.submit()">
                    @csrf
                    <button type="submit"
                            class="sidebar-pill flex items-center gap-4 px-4 py-3 rounded-full text-sm text-gray-600 w-full text-left outline-none"
                            :class="{ 'opacity-50 pointer-events-none': loading }">
                        <i class="fas fa-database w-5 text-center text-base" :class="{ 'fa-spinner fa-spin text-yg-600': loading }"></i>
                        <span class="flex-1" x-show="!sidebarCollapsed || mobileOpen" x-text="loading ? 'Exporting...' : 'Backup to Drive'"></span>
                    </button>
                </form>
            </nav>

            <!-- User Panel -->
            <div class="p-4 border-t border-gray-100 bg-gray-50/50">
                <div class="flex items-center gap-3" :class="{ 'justify-center': sidebarCollapsed && !mobileOpen }">
                    <div class="w-10 h-10 bg-gradient-to-tr from-yg-500 to-yg-700 rounded-xl flex items-center justify-center text-white font-bold text-sm shadow-md shrink-0">
                        {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                    </div>
                    <div class="flex-1 min-w-0" x-show="!sidebarCollapsed || mobileOpen">
                        <p class="text-xs font-bold text-gray-800 truncate leading-tight">{{ auth()->user()->name }}</p>
                        <p class="text-[10px] text-gray-400 truncate leading-tight mt-0.5">{{ auth()->user()->email }}</p>
                    </div>
                    <form method="POST" action="{{ route('logout') }}" x-show="!sidebarCollapsed || mobileOpen" class="shrink-0">
                        @csrf
                        <button type="submit" class="p-1.5 text-gray-400 hover:text-yg-600 hover:bg-yg-50 rounded-lg transition" title="Sign out">
                            <i class="fas fa-right-from-bracket text-sm"></i>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <!-- Main Wrapper (Card Design) -->
        <div class="flex-1 flex flex-col overflow-hidden bg-white md:rounded-3xl border border-gray-200/70 shadow-sm relative z-20">
            
            <!-- Topbar Header -->
            <header class="h-20 bg-white border-b border-gray-100 flex items-center justify-between px-6 gap-4 shrink-0">
                <div class="flex items-center flex-1 gap-4">
                    <!-- Collapse Toggle Sidebar -->
                    <button class="hidden md:flex p-2.5 text-gray-500 hover:bg-gray-100 rounded-xl transition" @click="sidebarCollapsed = !sidebarCollapsed">
                        <i class="fas" :class="sidebarCollapsed ? 'fa-indent' : 'fa-outdent'"></i>
                    </button>
                    <button class="md:hidden p-2.5 text-gray-500 hover:bg-gray-100 rounded-xl transition" @click="mobileOpen = !mobileOpen">
                        <i class="fas fa-bars"></i>
                    </button>

                    <!-- Beautiful Pill Search Bar with Filters -->
                    <div class="relative flex-1 max-w-xl" x-data="{ openFilters: false, q: '{{ $searchQuery ?? '' }}' }" @click.away="openFilters = false">
                        <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                        
                        <form action="{{ route('mail.search') }}" method="GET">
                            <input type="text" name="q" x-model="q"
                                   @focus="openFilters = true"
                                   placeholder="Search secure emails, subjects, or senders..."
                                   class="w-full bg-gray-50/80 border border-gray-200/80 rounded-2xl py-3 pl-11 pr-12 text-sm text-gray-800 placeholder-gray-400 focus:bg-white focus:border-yg-300 focus:ring-4 focus:ring-yg-50 outline-none transition-all duration-200 font-medium">
                        </form>
                        
                        <!-- Filter Icon Button -->
                        <button type="button" @click="openFilters = !openFilters"
                                class="absolute right-3.5 top-1/2 -translate-y-1/2 p-1.5 text-gray-400 hover:text-yg-600 hover:bg-gray-100 rounded-lg transition">
                            <i class="fas fa-sliders text-xs"></i>
                        </button>

                        <!-- Advanced Search Popover Filter -->
                        <div x-show="openFilters" x-transition
                             class="absolute left-0 right-0 mt-3 p-5 bg-white border border-gray-200 shadow-2xl rounded-2xl z-50 space-y-4" x-cloak>
                            <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Search Filters</h4>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">From</label>
                                    <input type="text" placeholder="sender@example.com" class="w-full text-xs p-2 bg-gray-50 border border-gray-200 rounded-xl outline-none focus:border-yg-400">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Subject</label>
                                    <input type="text" placeholder="Keywords" class="w-full text-xs p-2 bg-gray-50 border border-gray-200 rounded-xl outline-none focus:border-yg-400">
                                </div>
                            </div>
                            <div class="flex items-center justify-between pt-2 border-t border-gray-100">
                                <label class="flex items-center gap-2 cursor-pointer text-xs text-gray-600 font-medium">
                                    <input type="checkbox" class="rounded border-gray-300 text-yg-600 focus:ring-yg-400">
                                    <span>Has attachments</span>
                                </label>
                                <button type="submit" class="px-4 py-2 bg-yg-600 hover:bg-yg-700 text-white text-xs font-semibold rounded-xl shadow-sm transition">
                                    Search
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Toolbar Actions -->
                <div class="flex items-center gap-3">
                    @if(isset($quota) && $quota)
                        <span class="hidden sm:flex items-center gap-2 text-xs font-semibold text-gray-600 bg-gray-50 border border-gray-200/80 px-3.5 py-2 rounded-2xl">
                            <i class="fas fa-chart-line text-yg-500"></i>
                            <span>Daily Limit: {{ $quota->daily_sent }}/{{ $quota->daily_limit }}</span>
                        </span>
                    @endif

                    <a href="https://ygxone.com" class="hidden sm:flex items-center gap-1.5 px-3 py-2 text-xs font-semibold text-gray-600 bg-gray-50 hover:bg-gray-100 border border-gray-200/80 rounded-2xl transition">
                        <i class="fas fa-home"></i> Home
                    </a>
                </div>
            </header>

            <!-- Main Page Content Slot -->
            <main class="flex-1 overflow-hidden relative flex flex-col bg-white">
                @if(session('success'))
                    <div class="mx-6 mt-4 px-4 py-3.5 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-2xl text-xs font-semibold flex items-center gap-3 shadow-sm animate-fade-in">
                        <i class="fas fa-circle-check text-emerald-500 text-sm"></i>
                        <span class="flex-1">{{ session('success') }}</span>
                    </div>
                @endif
                @if(session('error'))
                    <div class="mx-6 mt-4 px-4 py-3.5 bg-rose-50 border border-rose-200 text-rose-800 rounded-2xl text-xs font-semibold flex items-center gap-3 shadow-sm animate-fade-in">
                        <i class="fas fa-circle-exclamation text-rose-500 text-sm"></i>
                        <span class="flex-1">{{ session('error') }}</span>
                    </div>
                @endif

                <div class="flex-1 overflow-y-auto">
                    @yield('mail-content')
                </div>
            </main>
        </div>

        <!-- Floating Gmail-like Compose Window (Bottom Right) -->
        <div x-show="composeOpen"
             x-transition:enter="transition ease-out duration-250"
             x-transition:enter-start="opacity-0 translate-y-10 scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 scale-100"
             x-transition:leave-end="opacity-0 translate-y-10 scale-95"
             class="fixed bottom-0 right-0 md:right-10 z-50 w-full sm:max-w-2xl bg-white shadow-2xl rounded-t-2xl border border-gray-200 overflow-hidden flex flex-col"
             :class="{
                 'h-[450px]': !composeMinimized && !composeMaximized,
                 'h-12 !w-80': composeMinimized,
                 'h-[85vh] !max-w-4xl': composeMaximized
             }"
             x-cloak>
            
            <!-- Window Titlebar -->
            <div class="bg-gray-900 text-white px-5 py-3 flex items-center justify-between cursor-pointer shrink-0" @click="composeMinimized = !composeMinimized">
                <span class="text-xs font-bold tracking-wide">New Encrypted Message</span>
                <div class="flex items-center gap-3" @click.stop>
                    <!-- Minimize Button -->
                    <button class="hover:bg-white/10 w-6 h-6 rounded flex items-center justify-center transition" @click="composeMinimized = !composeMinimized" title="Minimize">
                        <i class="fas fa-minus text-[10px]"></i>
                    </button>
                    <!-- Maximize Button -->
                    <button class="hover:bg-white/10 w-6 h-6 rounded flex items-center justify-center transition" @click="composeMaximized = !composeMaximized; composeMinimized = false" title="Maximize">
                        <i class="fas" :class="composeMaximized ? 'fa-compress text-[10px]' : 'fa-expand text-[10px]'"></i>
                    </button>
                    <!-- Close Button -->
                    <button class="hover:bg-rose-600 hover:text-white w-6 h-6 rounded flex items-center justify-center transition" @click="closeCompose()" title="Close">
                        <i class="fas fa-times text-xs"></i>
                    </button>
                </div>
            </div>

            <!-- Form Content -->
            <form id="compose-form" action="{{ route('mail.send') }}" method="POST"
                  enctype="multipart/form-data"
                  class="flex-1 flex flex-col overflow-hidden"
                  x-show="!composeMinimized">
                @csrf
                <!-- Fields -->
                <div class="px-5 py-2 border-b border-gray-100 flex items-center gap-3 shrink-0">
                    <span class="text-xs font-semibold text-gray-400 w-12">To</span>
                    <input type="email" name="to" required placeholder="recipient@example.com"
                           class="flex-1 text-xs py-1.5 border-none outline-none font-medium text-gray-800 focus:ring-0 focus:outline-none">
                </div>
                <div class="px-5 py-2 border-b border-gray-100 flex items-center gap-3 shrink-0">
                    <span class="text-xs font-semibold text-gray-400 w-12">Subject</span>
                    <input type="text" name="subject" required placeholder="Subject title"
                           class="flex-1 text-xs py-1.5 border-none outline-none font-semibold text-gray-800 focus:ring-0 focus:outline-none">
                </div>

                <!-- Rich text content textarea -->
                <div class="flex-1 p-5 overflow-y-auto">
                    <textarea name="body" required placeholder="Write your private message here..."
                              class="w-full h-full resize-none border-none outline-none text-xs leading-relaxed text-gray-800 focus:ring-0 focus:outline-none placeholder-gray-400"></textarea>
                </div>

                <!-- Footer / Controls -->
                <div class="px-5 py-4 bg-gray-50/80 border-t border-gray-100 flex items-center justify-between shrink-0">
                    <div class="flex items-center gap-4">
                        <!-- Attachment Input Trigger -->
                        <label class="p-2 hover:bg-gray-200/80 rounded-xl cursor-pointer text-gray-500 hover:text-gray-700 transition" title="Attach Files">
                            <input type="file" name="attachments[]" multiple class="hidden">
                            <i class="fas fa-paperclip text-sm"></i>
                        </label>
                        <span class="text-[10px] font-semibold text-gray-400 flex items-center gap-1.5 bg-emerald-50 text-emerald-700 px-2.5 py-1 rounded-full border border-emerald-100">
                            <i class="fas fa-lock text-[10px]"></i> E2E Encrypted
                        </span>
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="button" @click="closeCompose()" class="px-4 py-2.5 text-xs font-bold text-gray-500 hover:bg-gray-100 rounded-xl transition">
                            Discard
                        </button>
                        <button type="submit" class="px-5 py-2.5 bg-yg-600 hover:bg-yg-700 text-white text-xs font-bold rounded-xl shadow-md shadow-yg-600/10 hover:shadow-lg transition flex items-center gap-2">
                            <i class="fas fa-paper-plane text-[10px]"></i> Send
                        </button>
                    </div>
                </div>
            </form>
        </div>

    </div>

    @stack('scripts')

    <script>
        function mailLayout() {
            return {
                mobileOpen: false,
                sidebarCollapsed: false,
                composeOpen: new URLSearchParams(window.location.search).get('compose') === '1',
                composeMinimized: false,
                composeMaximized: false,

                openCompose() {
                    this.composeOpen = true;
                    this.composeMinimized = false;
                },
                closeCompose() {
                    if (confirm('Discard this message draft?')) {
                        this.composeOpen = false;
                        document.getElementById('compose-form')?.reset();
                    }
                }
            };
        }
    </script>
</body>
</html>
