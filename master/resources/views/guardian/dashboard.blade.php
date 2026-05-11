<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guardian Command Center | YGX God Mode</title>
    <script src="https://cdn.tailwindcss.com"></script>
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
            <div class="w-10 h-10 bg-gradient-to-tr from-rose-500 to-purple-600 rounded-lg flex items-center justify-center font-bold text-xl shadow-lg shadow-rose-500/20 text-white">M</div>
            <div>
                <h1 class="text-xl font-bold tracking-tight">YGX Master High Command</h1>
                <p class="text-xs text-white/40 font-medium uppercase tracking-widest">Sovereign Ecosystem Control</p>
            </div>
        </div>
        <div class="flex items-center gap-6">
            <div class="flex flex-col items-end">
                <span class="text-xs text-white/40 uppercase font-bold">Neural Engine</span>
                <span class="text-emerald-400 text-sm font-mono tracking-tighter">● YUGA 1.0 ONLINE</span>
            </div>
            <a href="/{{ config('app.admin_slug', 'admin') }}" class="px-4 py-2 bg-white/5 hover:bg-white/10 rounded-full text-sm font-semibold transition-all border border-white/10">Back to God Mode</a>
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
                        <h2 class="text-2xl font-bold gradient-text">Global Financial Pulse</h2>
                        <p class="text-white/40">Aggregated ecosystem liquidity & MRR forecasting</p>
                    </div>
                    <div class="text-right">
                        <span class="text-xs text-white/40 uppercase font-bold">Master Volume</span>
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
                    <span class="text-xs text-emerald-400 uppercase font-bold tracking-widest">Global MRR</span>
                    <h3 class="text-4xl font-bold mt-2 tracking-tighter">${{ number_format($financialStats['mrr'], 2) }}</h3>
                    <p class="text-xs text-white/40 mt-4">Predicted ecosystem growth: <span class="text-emerald-400">+12.4%</span></p>
                </div>

                <!-- Churn Card -->
                <div class="glass rounded-3xl p-6 glow-purple border-purple-500/10">
                    <span class="text-xs text-purple-400 uppercase font-bold tracking-widest">Ecosystem Retention</span>
                    <h3 class="text-4xl font-bold mt-2 tracking-tighter">{{ $financialStats['churn_rate'] }}%</h3>
                    <div class="w-full bg-white/5 h-1.5 rounded-full mt-4 overflow-hidden">
                        <div class="bg-purple-500 h-full transition-all duration-1000" style="width: {{ 100 - $financialStats['churn_rate'] }}%"></div>
                    </div>
                    <p class="text-xs text-white/40 mt-3">Governance stability is <span class="text-purple-400">OPTIMAL</span></p>
                </div>
            </div>
        </section>

        <!-- Neural Sovereignty, Training & Guardian Activity -->
        <section class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Phase 5: Neural Data Sovereignty -->
            <div class="glass rounded-3xl p-8 relative overflow-hidden group col-span-1">
                <div class="neural-line absolute top-0 left-0"></div>
                <h2 class="text-xl font-bold mb-6 flex items-center gap-3">
                    <span class="p-2 bg-rose-500/20 rounded-lg text-rose-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    </span>
                    Neural Master Sovereignty
                </h2>
                
                <div class="grid grid-cols-1 gap-4">
                    <div class="p-4 rounded-2xl bg-white/5 border border-white/5 hover:border-rose-500/30 transition-all">
                        <span class="text-xs text-white/40 uppercase font-bold">Total Ingested Docs</span>
                        <p class="text-2xl font-bold mt-1 tracking-tight text-rose-400">{{ $sovereigntyStats['total_private_docs'] }}</p>
                    </div>
                    <div class="p-4 rounded-2xl bg-white/5 border border-white/5 hover:border-rose-500/30 transition-all">
                        <span class="text-xs text-white/40 uppercase font-bold">Sovereign Neural Vaults</span>
                        <p class="text-2xl font-bold mt-1 tracking-tight text-purple-400">{{ $sovereigntyStats['sovereign_users'] }}</p>
                    </div>
                </div>

                <div class="mt-6 space-y-4">
                    <div class="flex justify-between items-end">
                        <span class="text-sm font-medium">Integration</span>
                        <span class="text-xs text-white/40 uppercase font-bold">{{ number_format($sovereigntyStats['total_storage_bytes'] / 1024 / 1024, 1) }} MB</span>
                    </div>
                    <div class="w-full bg-white/5 h-2 rounded-full overflow-hidden">
                        <div class="bg-gradient-to-r from-rose-500 to-purple-500 h-full rounded-full" style="width: 65%"></div>
                    </div>
                </div>
            </div>

            <!-- YugaLLM Training Intelligence -->
            <div class="glass rounded-3xl p-8 relative overflow-hidden group col-span-1">
                <div class="neural-line absolute top-0 left-0 bg-emerald-500/30"></div>
                <h2 class="text-xl font-bold mb-6 flex items-center gap-3">
                    <span class="p-2 bg-emerald-500/20 rounded-lg text-emerald-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    </span>
                    YugaLLM Training Stats
                </h2>
                
                <div class="space-y-6">
                    <div class="text-center p-6 rounded-2xl bg-emerald-500/5 border border-emerald-500/10">
                        <span class="text-xs text-emerald-400 uppercase font-bold tracking-widest">Training Queue</span>
                        <p class="text-5xl font-bold mt-2 tracking-tighter text-emerald-400">{{ $trainingStats->total ?? 0 }}</p>
                        <p class="text-xs text-white/40 mt-2 italic">New intents queued for reinforcement</p>
                    </div>

                    <div class="p-4 rounded-xl bg-white/5 border border-white/5">
                        <div class="flex justify-between text-xs mb-2">
                            <span class="text-white/40 uppercase font-bold">Last Activity</span>
                            <span class="text-emerald-400">{{ $trainingStats->last_activity ? \Carbon\Carbon::parse($trainingStats->last_activity)->diffForHumans() : 'Never' }}</span>
                        </div>
                        <div class="flex justify-between text-xs">
                            <span class="text-white/40 uppercase font-bold">Engine Status</span>
                            <span class="text-emerald-400 font-bold">READY</span>
                        </div>
                    </div>

                    <a href="/admin/ai-training" class="block w-full text-center py-3 bg-emerald-500/20 hover:bg-emerald-500/30 text-emerald-400 rounded-xl text-sm font-bold transition-all border border-emerald-500/20">
                        View Training Insights
                    </a>
                </div>
            </div>

            <!-- Sentinel Activity Logs -->
            <div class="glass rounded-3xl p-8 border-l-4 border-rose-500 col-span-1">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-bold flex items-center gap-3">
                        <span class="p-2 bg-emerald-500/20 rounded-lg text-emerald-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        </span>
                        Master Sentinel
                    </h2>
                </div>

                <div class="space-y-4 max-h-[200px] overflow-y-auto pr-2 custom-scrollbar">
                    @foreach($safetyActivity as $log)
                    <div class="p-3 rounded-xl bg-white/5 border border-white/5 flex items-start gap-3 hover:bg-white/10 transition-all">
                        <div class="text-lg">{{ $log->icon ?? '🤖' }}</div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-medium truncate">{{ $log->activity }}</p>
                            <span class="text-[10px] text-white/30">{{ $log->created_at->diffForHumans() }}</span>
                        </div>
                    </div>
                    @endforeach
                </div>
                
                <div class="mt-6 pt-4 border-t border-white/5 grid grid-cols-2 gap-2">
                    <div class="text-center">
                        <p class="text-[10px] text-white/40 uppercase font-bold">Intercepts</p>
                        <p class="text-lg font-bold text-rose-400">{{ $sentinelStats['total_blocks'] }}</p>
                    </div>
                    <div class="text-center">
                        <p class="text-[10px] text-white/40 uppercase font-bold">Social Merit</p>
                        <p class="text-lg font-bold text-emerald-400">+{{ $sentinelStats['positive_rl_signals'] }}</p>
                    </div>
                </div>
            </div>

        </section>


    </main>

    <script>
        const ctx = document.getElementById('financialChart').getContext('2d');
        const gradient = ctx.createLinearGradient(0, 0, 0, 400);
        gradient.addColorStop(0, 'rgba(244, 63, 94, 0.4)');
        gradient.addColorStop(1, 'rgba(244, 63, 94, 0)');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4', 'Week 5', 'Today'],
                datasets: [{
                    label: 'Global Volume',
                    data: [12000, 19000, 15000, 25000, 22000, {{ $financialStats['total_volume'] }}],
                    borderColor: '#f43f5e',
                    backgroundColor: gradient,
                    fill: true,
                    tension: 0.4,
                    borderWidth: 3,
                    pointRadius: 0,
                    pointHoverRadius: 6,
                    pointHoverBackgroundColor: '#f43f5e',
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
