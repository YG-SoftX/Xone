<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sovereign Guardian | YGXONE Nerve Center</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;700;900&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background-color: #f8fafc;
            color: #0f172a;
            overflow: hidden;
        }
        .snow-bg {
            position: fixed;
            inset: 0;
            background: radial-gradient(circle at 50% 50%, #ffffff 0%, #f1f5f9 100%);
            z-index: -1;
        }
        .white-glass {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.9);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
        }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen">
    <div class="snow-bg"></div>

    <div class="max-w-6xl w-full p-8 relative">
        <!-- Top Navigation -->
        <div class="flex justify-between items-center mb-16">
            <div class="flex items-center gap-4">
                <img src="https://pay.ygxone.com/assets/images/logo-icon.png" class="w-10 h-10" alt="YG">
                <h1 class="text-2xl font-black uppercase tracking-tighter text-slate-900">Guardian <span class="text-blue-600">Master</span></h1>
            </div>
            <div class="flex gap-4">
                <div class="px-4 py-2 white-glass rounded-full flex items-center gap-2 text-xs font-bold text-green-600">
                    <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                    ALL NODES OPERATIONAL
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <!-- AI Control Center -->
            <div class="white-glass p-8 rounded-[40px] group transition-all hover:border-blue-500/50 hover:shadow-xl">
                <div class="w-16 h-16 bg-blue-50 rounded-2xl flex items-center justify-center mb-6 border border-blue-100">
                    <i class="fas fa-brain text-blue-500 text-2xl"></i>
                </div>
                <h3 class="text-2xl font-black mb-2 uppercase italic tracking-tighter text-slate-900">Neural Engine</h3>
                <p class="text-slate-500 text-sm mb-8">Manage AI model training, distillation, and real-time response weights across the ecosystem.</p>
                <a href="/admin/ai-models" class="inline-flex items-center gap-2 text-blue-600 font-bold uppercase tracking-widest text-xs hover:gap-4 transition-all">
                    Initialize Brain <i class="fas fa-arrow-right"></i>
                </a>
            </div>

            <!-- Infrastructure Monitor -->
            <div class="white-glass p-8 rounded-[40px] group transition-all hover:border-purple-500/50 hover:shadow-xl">
                <div class="w-16 h-16 bg-purple-50 rounded-2xl flex items-center justify-center mb-6 border border-purple-100">
                    <i class="fas fa-server text-purple-500 text-2xl"></i>
                </div>
                <h3 class="text-2xl font-black mb-2 uppercase italic tracking-tighter text-slate-900">Infrastructure</h3>
                <p class="text-slate-500 text-sm mb-8">Direct node telemetry, hardware resource allocation, and zero-day patch management for 16 subdomains.</p>
                <a href="/admin" class="inline-flex items-center gap-2 text-purple-600 font-bold uppercase tracking-widest text-xs hover:gap-4 transition-all">
                    Open Console <i class="fas fa-arrow-right"></i>
                </a>
            </div>

            <!-- Global Events -->
            <div class="white-glass p-8 rounded-[40px] group transition-all hover:border-amber-500/50 hover:shadow-xl">
                <div class="w-16 h-16 bg-amber-50 rounded-2xl flex items-center justify-center mb-6 border border-amber-100">
                    <i class="fas fa-bolt text-amber-500 text-2xl"></i>
                </div>
                <h3 class="text-2xl font-black mb-2 uppercase italic tracking-tighter text-slate-900">Event Streams</h3>
                <p class="text-slate-500 text-sm mb-8">Centralized logging and event-driven publishing. Watch every packet move through the empire.</p>
                <a href="/admin/service-events" class="inline-flex items-center gap-2 text-amber-600 font-bold uppercase tracking-widest text-xs hover:gap-4 transition-all">
                    View Streams <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>

        <!-- Footer Stats -->
        <div class="mt-16 pt-8 border-t border-slate-200 flex flex-wrap gap-12 text-xs font-bold text-slate-400">
            <div>NETWORK STATUS: <span class="text-slate-600">SECURE (SHA-512)</span></div>
            <div>ACTIVE NODES: <span class="text-slate-600">16 / 16</span></div>
            <div>AI SYNC: <span class="text-slate-600">100% OPERATIONAL</span></div>
            <div class="ml-auto text-slate-300">Sovereign OS v4.2.0</div>
        </div>
    </div>
</body>
</html>
