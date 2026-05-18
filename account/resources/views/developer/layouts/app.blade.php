<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Developer Console') - YG Developer Platform</title>
    
    <!-- Tailwind CSS -->
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <script src="{{ asset('js/app.js') }}" defer></script>
    
    <!-- Chart.js for Analytics -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .sidebar-link:hover {
            background: rgba(99, 102, 241, 0.1);
            border-left: 3px solid #6366f1;
        }
        .sidebar-link.active {
            background: rgba(99, 102, 241, 0.15);
            border-left: 3px solid #6366f1;
            color: #6366f1;
        }
        .card-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        .transition-all {
            transition: all 0.3s ease;
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <aside class="w-64 bg-white shadow-lg hidden md:block">
            <div class="p-6 border-b">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 gradient-bg rounded-lg flex items-center justify-center">
                        <i class="fas fa-code text-white text-xl"></i>
                    </div>
                    <div>
                        <h1 class="font-bold text-gray-800">YG Developer</h1>
                        <p class="text-xs text-gray-500">Console</p>
                    </div>
                </div>
            </div>
            
            <nav class="mt-6">
                <a href="{{ route('developer.dashboard') }}" 
                   class="sidebar-link {{ request()->routeIs('developer.dashboard') ? 'active' : '' }} flex items-center gap-3 px-6 py-3 text-gray-700 hover:text-indigo-600 transition-all">
                    <i class="fas fa-home w-5"></i>
                    <span>Dashboard</span>
                </a>
                
                <a href="{{ route('developer.projects.index') }}" 
                   class="sidebar-link {{ request()->routeIs('developer.projects.*') ? 'active' : '' }} flex items-center gap-3 px-6 py-3 text-gray-700 hover:text-indigo-600 transition-all">
                    <i class="fas fa-folder w-5"></i>
                    <span>Projects</span>
                </a>
                
                <a href="{{ route('developer.credentials.index') }}" 
                   class="sidebar-link {{ request()->routeIs('developer.credentials.*') ? 'active' : '' }} flex items-center gap-3 px-6 py-3 text-gray-700 hover:text-indigo-600 transition-all">
                    <i class="fas fa-key w-5"></i>
                    <span>Credentials</span>
                </a>
                
                <a href="{{ route('developer.analytics.index') }}" 
                   class="sidebar-link {{ request()->routeIs('developer.analytics.*') ? 'active' : '' }} flex items-center gap-3 px-6 py-3 text-gray-700 hover:text-indigo-600 transition-all">
                    <i class="fas fa-chart-line w-5"></i>
                    <span>Analytics</span>
                </a>
                
                <a href="{{ route('developer.quotas.index') }}" 
                   class="sidebar-link {{ request()->routeIs('developer.quotas.*') ? 'active' : '' }} flex items-center gap-3 px-6 py-3 text-gray-700 hover:text-indigo-600 transition-all">
                    <i class="fas fa-tachometer-alt w-5"></i>
                    <span>Quotas</span>
                </a>
                
                <a href="{{ route('developer.webhooks.index') }}" 
                   class="sidebar-link {{ request()->routeIs('developer.webhooks.*') ? 'active' : '' }} flex items-center gap-3 px-6 py-3 text-gray-700 hover:text-indigo-600 transition-all">
                    <i class="fas fa-webhook w-5"></i>
                    <span>Webhooks</span>
                </a>
                
                <a href="{{ route('developer.team.index') }}" 
                   class="sidebar-link {{ request()->routeIs('developer.team.*') ? 'active' : '' }} flex items-center gap-3 px-6 py-3 text-gray-700 hover:text-indigo-600 transition-all">
                    <i class="fas fa-users w-5"></i>
                    <span>Team</span>
                </a>
                
                <a href="{{ route('developer.billing.index') }}" 
                   class="sidebar-link {{ request()->routeIs('developer.billing.*') ? 'active' : '' }} flex items-center gap-3 px-6 py-3 text-gray-700 hover:text-indigo-600 transition-all">
                    <i class="fas fa-credit-card w-5"></i>
                    <span>Billing</span>
                </a>
            </nav>
            
            <div class="absolute bottom-0 w-64 p-6 border-t bg-white">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center">
                        <i class="fas fa-user text-gray-600"></i>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-800">{{ Auth::user()->name }}</p>
                        <p class="text-xs text-gray-500">{{ Auth::user()->email }}</p>
                    </div>
                </div>
            </div>
        </aside>
        
        <!-- Main Content -->
        <main class="flex-1 overflow-y-auto">
            <!-- Top Bar -->
            <header class="bg-white shadow-sm px-8 py-4 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <button class="md:hidden text-gray-600">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                    <h2 class="text-xl font-semibold text-gray-800">@yield('page-title', 'Dashboard')</h2>
                </div>
                
                <div class="flex items-center gap-4">
                    <button class="relative p-2 text-gray-600 hover:text-indigo-600">
                        <i class="fas fa-bell text-xl"></i>
                        <span class="absolute top-0 right-0 w-2 h-2 bg-red-500 rounded-full"></span>
                    </button>
                    
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg text-gray-700 transition-all">
                            <i class="fas fa-sign-out-alt mr-2"></i>Logout
                        </button>
                    </form>
                </div>
            </header>
            
            <!-- Page Content -->
            <div class="p-8">
                @if(session('success'))
                    <div class="mb-6 p-4 bg-green-50 border-l-4 border-green-500 rounded-r-lg">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-check-circle text-green-500 text-xl"></i>
                            <p class="text-green-700">{{ session('success') }}</p>
                        </div>
                    </div>
                @endif
                
                @if(session('error'))
                    <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 rounded-r-lg">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-exclamation-circle text-red-500 text-xl"></i>
                            <p class="text-red-700">{{ session('error') }}</p>
                        </div>
                    </div>
                @endif
                
                @if($errors->any())
                    <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 rounded-r-lg">
                        <div class="flex items-start gap-3">
                            <i class="fas fa-exclamation-triangle text-red-500 text-xl mt-0.5"></i>
                            <div>
                                <p class="text-red-700 font-medium mb-2">Please fix the following errors:</p>
                                <ul class="list-disc list-inside text-red-600 text-sm">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                @endif
                
                @yield('content')
            </div>
        </main>
    </div>
    
    @stack('scripts')
</body>
</html>
