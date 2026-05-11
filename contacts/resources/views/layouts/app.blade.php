<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Contacts') — YGXone</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>[x-cloak]{display:none!important}</style>
</head>
<body class="font-sans antialiased bg-white text-gray-900 h-screen flex flex-col">

{{-- Top bar --}}
<header class="shrink-0 flex items-center gap-4 px-4 h-14 border-b border-gray-200 bg-white z-30">
    <button @click="$store.sidebar.open = !$store.sidebar.open" class="p-2 rounded-full hover:bg-gray-100 text-gray-600">
        <i class="fas fa-bars"></i>
    </button>
    <a href="{{ route('contacts.index') }}" class="flex items-center gap-2 text-lg font-semibold text-gray-800">
        <i class="fas fa-address-book text-blue-600"></i>
        <span class="hidden sm:block">YG Contacts</span>
    </a>

    {{-- Search --}}
    <form action="{{ route('contacts.index') }}" method="GET" class="flex-1 max-w-lg">
        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <i class="fas fa-search text-gray-400 text-sm"></i>
            </div>
            <input type="search" name="search" value="{{ request('search') }}" placeholder="Search contacts..."
                   class="w-full pl-9 pr-4 py-2 border border-transparent rounded-full bg-gray-100 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white">
        </div>
    </form>

    <div class="flex items-center gap-2">
        <a href="{{ route('contacts.create') }}"
           class="flex items-center gap-2 bg-blue-600 text-white text-sm font-medium px-4 py-2 rounded-full hover:bg-blue-700 transition">
            <i class="fas fa-plus"></i> New Contact
        </a>
    </div>
</header>

@if(session('success'))
<div class="shrink-0 bg-green-50 border-b border-green-200 text-green-800 text-sm px-4 py-2">
    <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
</div>
@endif

<div class="flex flex-1 overflow-hidden" x-data x-init="$store.sidebar.open = true">

    {{-- Sidebar --}}
    <aside class="shrink-0 w-56 border-r border-gray-200 overflow-y-auto" x-show="$store.sidebar.open" x-cloak>
        <nav class="py-4 text-sm">
            <a href="{{ route('contacts.index') }}"
               class="flex items-center gap-3 px-4 py-2 rounded-full mx-2 {{ !request('filter') && !request('group') ? 'bg-blue-100 text-blue-700 font-medium' : 'text-gray-700 hover:bg-gray-100' }}">
                <i class="fas fa-users w-5"></i> All contacts
            </a>
            <a href="{{ route('contacts.index', ['filter' => 'starred']) }}"
               class="flex items-center gap-3 px-4 py-2 rounded-full mx-2 {{ request('filter') === 'starred' ? 'bg-blue-100 text-blue-700 font-medium' : 'text-gray-700 hover:bg-gray-100' }}">
                <i class="fas fa-star w-5 text-yellow-400"></i> Starred
            </a>
            <a href="{{ route('contacts.export.vcard') }}"
               class="flex items-center gap-3 px-4 py-2 rounded-full mx-2 text-gray-700 hover:bg-gray-100">
                <i class="fas fa-download w-5"></i> Export
            </a>

            {{-- Groups --}}
            <div class="mt-4 px-4">
                <div class="flex items-center justify-between text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">
                    <span>Labels</span>
                    <button onclick="document.getElementById('create-group-modal').classList.remove('hidden')"
                            class="text-gray-400 hover:text-gray-600"><i class="fas fa-plus"></i></button>
                </div>
                @foreach(isset($groups) ? $groups : [] as $group)
                <a href="{{ route('contacts.index', ['group' => $group->id]) }}"
                   class="flex items-center gap-2 py-1.5 rounded-full px-2 hover:bg-gray-100 {{ request('group') == $group->id ? 'bg-blue-100 text-blue-700' : 'text-gray-700' }}">
                    <span class="w-3 h-3 rounded-full" style="background: {{ $group->color }}"></span>
                    <span class="flex-1 truncate text-sm">{{ $group->name }}</span>
                    <span class="text-xs text-gray-400">{{ $group->contacts_count ?? '' }}</span>
                </a>
                @endforeach
            </div>
        </nav>
    </aside>

    <main class="flex-1 overflow-auto">
        @yield('contacts-content')
    </main>
</div>

{{-- Create group modal --}}
<div id="create-group-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/30 p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-6">
        <h3 class="text-lg font-semibold mb-4">New Label</h3>
        <form method="POST" action="{{ route('contacts.groups.store') }}" class="space-y-3">
            @csrf
            <input type="text" name="name" required placeholder="Label name"
                   class="w-full border border-gray-300 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            <div class="flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('create-group-modal').classList.add('hidden')"
                        class="px-4 py-2 text-sm text-gray-600 hover:bg-gray-50 rounded-lg">Cancel</button>
                <button type="submit" class="px-5 py-2 bg-blue-600 text-white text-sm rounded-xl hover:bg-blue-700">Create</button>
            </div>
        </form>
    </div>
</div>

@stack('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.store('sidebar', { open: true });
    });
</script>
</body>
</html>
