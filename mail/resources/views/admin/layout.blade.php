<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin') — YG Mail</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; background: #0f172a; color: #e2e8f0; }
        .layout { display: flex; min-height: 100vh; }
        .sidebar { width: 240px; background: #1e293b; border-right: 1px solid #334155; padding: 20px 0; }
        .sidebar h2 { padding: 0 20px; font-size: 16px; margin-bottom: 20px; color: #f8fafc; }
        .sidebar a { display: block; padding: 10px 20px; color: #94a3b8; text-decoration: none; font-size: 14px; }
        .sidebar a:hover, .sidebar a.active { background: #334155; color: #e2e8f0; }
        .main { flex: 1; padding: 32px; }
        .topbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px; }
        .topbar h1 { font-size: 24px; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 32px; }
        .stat-card { background: #1e293b; border: 1px solid #334155; border-radius: 12px; padding: 20px; }
        .stat-card .value { font-size: 28px; font-weight: 700; color: #3b82f6; }
        .stat-card .label { font-size: 12px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 4px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        th, td { padding: 12px 16px; text-align: left; border-bottom: 1px solid #334155; font-size: 14px; }
        th { color: #64748b; font-weight: 600; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px; }
        .badge { padding: 4px 10px; border-radius: 999px; font-size: 11px; font-weight: 600; }
        .badge-success { background: rgba(52,211,153,0.1); color: #34d399; }
        .badge-danger { background: rgba(248,113,113,0.1); color: #f87171; }
        .badge-warning { background: rgba(251,191,36,0.1); color: #fbbf24; }
        .btn { padding: 8px 16px; border-radius: 8px; border: 1px solid #475569; background: #1e293b; color: #e2e8f0; font-size: 13px; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn:hover { background: #334155; }
        .btn-danger { border-color: #ef4444; color: #f87171; }
        .btn-danger:hover { background: rgba(239,68,68,0.1); }
        .btn-sm { padding: 4px 10px; font-size: 12px; }
        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 14px; }
        .alert-success { background: rgba(52,211,153,0.1); border: 1px solid #10b981; color: #34d399; }
        .alert-error { background: rgba(248,113,113,0.1); border: 1px solid #ef4444; color: #f87171; }
        .alert-info { background: rgba(59,130,246,0.1); border: 1px solid #3b82f6; color: #60a5fa; }
        .logout-btn { background: transparent; border: 1px solid #475569; color: #94a3b8; padding: 6px 14px; border-radius: 6px; cursor: pointer; font-size: 13px; }
        .logout-btn:hover { border-color: #ef4444; color: #f87171; }
    </style>
</head>
<body>
    <div class="layout">
        <div class="sidebar">
            <h2>📧 YG Mail Admin</h2>
            <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">Dashboard</a>
            <a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}">Users</a>
            <a href="{{ route('admin.config') }}" class="{{ request()->routeIs('admin.config') ? 'active' : '' }}">Configuration</a>
            <a href="{{ route('admin.queue') }}" class="{{ request()->routeIs('admin.queue.*') ? 'active' : '' }}">Queue</a>
        </div>
        <div class="main">
            <div class="topbar">
                <h1>@yield('title')</h1>
                <form method="POST" action="{{ route('admin.logout') }}" style="display:inline">
                    @csrf
                    <button type="submit" class="logout-btn">Logout</button>
                </form>
            </div>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-error">{{ session('error') }}</div>
            @endif
            @if(session('info'))
                <div class="alert alert-info">{{ session('info') }}</div>
            @endif

            @yield('content')
        </div>
    </div>
</body>
</html>
