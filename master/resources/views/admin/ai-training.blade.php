<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YugaLLM Training Insights | YGX Master</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Outfit', sans-serif; background: #0a0b10; color: #f8fafc; }
        .glass { background: rgba(255, 255, 255, 0.03); backdrop-filter: blur(16px); border: 1px solid rgba(255, 255, 255, 0.05); }
    </style>
</head>
<body class="p-8">
    <div class="max-w-6xl mx-auto space-y-8">
        <header class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold tracking-tight">YugaLLM Training Insights</h1>
                <p class="text-white/40">Real-time intent capture from ecosystem search nodes</p>
            </div>
            <form action="{{ route('ai-training.clear') }}" method="POST">
                @csrf
                <button type="submit" class="px-6 py-2 bg-rose-500/20 text-rose-400 border border-rose-500/20 rounded-xl font-bold hover:bg-rose-500/30 transition-all">
                    Flush Queue
                </button>
            </form>
        </header>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @foreach($stats as $stat)
            <div class="glass p-6 rounded-3xl">
                <span class="text-xs text-white/40 uppercase font-bold tracking-widest">{{ $stat->context }}</span>
                <h3 class="text-3xl font-bold mt-1">{{ number_format($stat->count) }}</h3>
                <p class="text-[10px] text-white/30 mt-2">Last entry: {{ \Carbon\Carbon::parse($stat->last_seen)->diffForHumans() }}</p>
            </div>
            @endforeach
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <div class="glass p-8 rounded-3xl">
                <h2 class="text-xl font-bold mb-6">Top Training Queries</h2>
                <div class="space-y-4">
                    @foreach($topQueries as $q)
                    <div class="flex justify-between items-center p-4 bg-white/5 rounded-2xl border border-white/5">
                        <span class="text-sm font-medium">{{ $q->query }}</span>
                        <span class="px-3 py-1 bg-emerald-500/20 text-emerald-400 rounded-full text-xs font-bold">{{ $q->count }}x</span>
                    </div>
                    @endforeach
                </div>
            </div>

            <div class="glass p-8 rounded-3xl">
                <h2 class="text-xl font-bold mb-6">Recent Intent Stream</h2>
                <div class="space-y-3 max-h-[500px] overflow-y-auto pr-2 custom-scrollbar">
                    @foreach($recentQueries as $q)
                    <div class="p-4 bg-white/5 rounded-xl border border-white/5">
                        <div class="flex justify-between text-[10px] text-white/30 mb-1">
                            <span>{{ strtoupper($q->context) }}</span>
                            <span>{{ \Carbon\Carbon::parse($q->created_at)->format('H:i:s') }}</span>
                        </div>
                        <p class="text-sm">{{ $q->query }}</p>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</body>
</html>
