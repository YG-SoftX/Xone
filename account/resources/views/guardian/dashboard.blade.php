<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guardian Command Center | Yuga 1.0 Intelligence</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <script src="{{ asset('js/app.js') }}" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Outfit', sans-serif; background: #0a0b10; color: #f8fafc; }
        .glass { background: rgba(255, 255, 255, 0.03); backdrop-filter: blur(16px); border: 1px solid rgba(255, 255, 255, 0.05); }
        .glow-cyan { box-shadow: 0 0 20px rgba(6, 182, 212, 0.15); }
        .glow-purple { box-shadow: 0 0 20px rgba(168, 85, 247, 0.15); }
        .glow-emerald { box-shadow: 0 0 20px rgba(16, 185, 129, 0.15); }
        .gradient-text { background: linear-gradient(135deg, #22d3ee, #a855f7); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .neural-line { background: linear-gradient(90deg, transparent, rgba(34, 211, 238, 0.5), transparent); height: 1px; width: 100%; position: relative; overflow: hidden; }
        .neural-line::after { content: ''; position: absolute; top: 0; left: -100%; width: 50%; height: 100%; background: linear-gradient(90deg, transparent, #22d3ee, transparent); animation: sweep 3s infinite linear; }
        @keyframes sweep { 0% { left: -100%; } 100% { left: 150%; } }
    </style>
</head>
<body class="antialiased overflow-x-hidden">

    <!-- Header / Navigation -->
    <header class="p-6 flex justify-between items-center glass border-b border-white/5 sticky top-0 z-50">
        <div class="flex items-center gap-4">
            <div class="w-10 h-10 bg-gradient-to-tr from-cyan-500 to-purple-600 rounded-lg flex items-center justify-center font-bold text-xl shadow-lg shadow-cyan-500/20">Y</div>
            <div>
                <h1 class="text-xl font-bold tracking-tight">Sovereign Command Center</h1>
                <p class="text-xs text-white/40 font-medium uppercase tracking-widest">Triple Intelligence Hub v1.0</p>
            </div>
        </div>
        <div class="flex items-center gap-6">
            <div class="flex flex-col items-end">
                <span class="text-xs text-white/40 uppercase font-bold">Midnight Evolution</span>
                <span class="text-emerald-400 text-sm font-mono tracking-tighter">● ACTIVE</span>
            </div>
            <a href="{{ route('society.index') }}" class="px-4 py-2 bg-white/5 hover:bg-white/10 rounded-full text-sm font-semibold transition-all border border-white/10">Return to Society</a>
        </div>
    </header>

    <main class="max-w-7xl mx-auto p-8 space-y-8">

        <!-- Phase 4: Financial Pulse Hub -->
        <section class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 glass rounded-3xl p-8 glow-cyan relative overflow-hidden">
                <div class="absolute top-0 right-0 p-8 opacity-10">
                    <svg class="w-32 h-32" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
                </div>
                <div class="flex justify-between items-start mb-8">
                    <div>
                        <h2 class="text-2xl font-bold gradient-text">Financial Pulse</h2>
                        <p class="text-white/40">Real-time ecosystem liquidity & MRR forecasting</p>
                    </div>
                    <div class="text-right">
                        <span class="text-xs text-white/40 uppercase font-bold">Total Volume</span>
                        <p class="text-3xl font-bold tracking-tighter">${{ number_format($financialStats['total_volume'], 2) }}</p>
                    </div>
                </div>
                
                <div class="h-64 mt-4">
                    <canvas id="financialChart"></canvas>
                </div>
            </div>

            <div class="space-y-6">
                <!-- MRR Card -->
                <div class="glass rounded-3xl p-6 glow-emerald border-emerald-500/10">
                    <span class="text-xs text-emerald-400 uppercase font-bold tracking-widest">Monthly Recurring Revenue</span>
                    <h3 class="text-4xl font-bold mt-2 tracking-tighter">${{ number_format($financialStats['mrr'], 2) }}</h3>
                    <p class="text-xs text-white/40 mt-4">Predicted MRR growth: <span class="text-emerald-400">+12.4%</span></p>
                </div>

                <!-- Churn Card -->
                <div class="glass rounded-3xl p-6 glow-purple border-purple-500/10">
                    <span class="text-xs text-purple-400 uppercase font-bold tracking-widest">Churn Resistance</span>
                    <h3 class="text-4xl font-bold mt-2 tracking-tighter">{{ $financialStats['churn_rate'] }}%</h3>
                    <div class="w-full bg-white/5 h-1.5 rounded-full mt-4 overflow-hidden">
                        <div class="bg-purple-500 h-full transition-all duration-1000" style="width: {{ 100 - $financialStats['churn_rate'] }}%"></div>
                    </div>
                    <p class="text-xs text-white/40 mt-3">Sovereign retention is <span class="text-purple-400">OPTIMAL</span></p>
                </div>
            </div>
        </section>

        <!-- Neural Sovereignty & Guardian Activity -->
        <section class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            
            <!-- Phase 5: Neural Data Sovereignty -->
            <div class="glass rounded-3xl p-8 relative overflow-hidden group">
                <div class="neural-line absolute top-0 left-0"></div>
                <h2 class="text-2xl font-bold mb-6 flex items-center gap-3">
                    <span class="p-2 bg-cyan-500/20 rounded-lg text-cyan-400">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    </span>
                    Neural Data Sovereignty
                </h2>
                
                <div class="grid grid-cols-2 gap-4">
                    <div class="p-4 rounded-2xl bg-white/5 border border-white/5 hover:border-cyan-500/30 transition-all">
                        <span class="text-xs text-white/40 uppercase font-bold">Private Docs</span>
                        <p class="text-3xl font-bold mt-1 tracking-tight text-cyan-400">{{ $sovereigntyStats['total_private_docs'] }}</p>
                    </div>
                    <div class="p-4 rounded-2xl bg-white/5 border border-white/5 hover:border-cyan-500/30 transition-all">
                        <span class="text-xs text-white/40 uppercase font-bold">Sovereign Vaults</span>
                        <p class="text-3xl font-bold mt-1 tracking-tight text-purple-400">{{ $sovereigntyStats['sovereign_users'] }}</p>
                    </div>
                </div>

                <div class="mt-8 space-y-4">
                    <div class="flex justify-between items-end">
                        <span class="text-sm font-medium">Neural Knowledge Integration</span>
                        <span class="text-xs text-white/40 uppercase font-bold">{{ number_format($sovereigntyStats['total_storage_bytes'] / 1024 / 1024, 1) }} MB</span>
                    </div>
                    <div class="w-full bg-white/5 h-2 rounded-full overflow-hidden">
                        <div class="bg-gradient-to-r from-cyan-500 to-purple-500 h-full rounded-full" style="width: 65%"></div>
                    </div>
                    <p class="text-xs text-white/30 italic">User-partitioned private models are isolated and sovereign.</p>
                </div>
            </div>

            <!-- Sentinel Activity Logs -->
            <div class="glass rounded-3xl p-8 border-l-4 border-cyan-500">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold flex items-center gap-3">
                        <span class="p-2 bg-red-500/20 rounded-lg text-red-400">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        </span>
                        Sentinel Intelligence Feed
                    </h2>
                    <span class="px-3 py-1 bg-white/5 rounded-full text-xs font-mono text-cyan-400 uppercase tracking-widest glow-cyan">Zero-Tolerance: ON</span>
                </div>

                <div class="space-y-4 max-h-[300px] overflow-y-auto pr-2 custom-scrollbar">
                    @foreach($safetyActivity as $log)
                    <div class="p-4 rounded-xl bg-white/5 border border-white/5 flex items-start gap-4 hover:bg-white/10 transition-all">
                        <div class="p-2 bg-white/5 rounded-lg text-lg">{{ $log->icon ?? '🤖' }}</div>
                        <div class="flex-1">
                            <div class="flex justify-between">
                                <span class="text-xs text-white/40 uppercase font-bold">{{ $log->module ?? 'Neural Shield' }}</span>
                                <span class="text-[10px] text-white/30">{{ $log->created_at->diffForHumans() }}</span>
                            </div>
                            <p class="text-sm font-medium mt-1">{{ $log->activity }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
                
                <div class="mt-6 pt-6 border-t border-white/5 grid grid-cols-3 gap-4">
                    <div class="text-center">
                        <p class="text-xs text-white/40 uppercase font-bold">Total Intercepts</p>
                        <p class="text-xl font-bold text-red-400">{{ $sentinelStats['total_blocks'] }}</p>
                    </div>
                    <div class="text-center">
                        <p class="text-xs text-white/40 uppercase font-bold">Community Reward</p>
                        <p class="text-xl font-bold text-emerald-400">+{{ $sentinelStats['positive_rl_signals'] }}</p>
                    </div>
                    <div class="text-center">
                        <p class="text-xs text-white/40 uppercase font-bold">Model Penalty</p>
                        <p class="text-xl font-bold text-purple-400">-{{ $sentinelStats['negative_rl_signals'] }}</p>
                    </div>
                </div>
            </div>

        </section>

    </main>

    <script>
        const ctx = document.getElementById('financialChart').getContext('2d');
        const gradient = ctx.createLinearGradient(0, 0, 0, 400);
        gradient.addColorStop(0, 'rgba(34, 211, 238, 0.4)');
        gradient.addColorStop(1, 'rgba(34, 211, 238, 0)');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4', 'Week 5', 'Today'],
                datasets: [{
                    label: 'Transaction Velocity',
                    data: [12000, 19000, 15000, 25000, 22000, {{ $financialStats['total_volume'] }}],
                    borderColor: '#22d3ee',
                    backgroundColor: gradient,
                    fill: true,
                    tension: 0.4,
                    borderWidth: 3,
                    pointRadius: 0,
                    pointHoverRadius: 6,
                    pointHoverBackgroundColor: '#22d3ee',
                    pointHoverBorderColor: '#fff',
                    pointHoverBorderWidth: 2,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false }, ticks: { color: 'rgba(255,255,255,0.2)' } },
                    y: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: 'rgba(255,255,255,0.2)', callback: (v) => '$' + v/1000 + 'k' } }
                }
            }
        });
    </script>
</body>
</html>
