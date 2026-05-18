<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sovereign Access | YG Account Admin</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <script src="{{ asset('js/app.js') }}" defer></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;700;900&family=Inter:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background-color: #f8fafc;
            color: #0f172a;
            overflow: hidden;
        }
        .snow-bg {
            background: radial-gradient(circle at 50% 50%, #ffffff 0%, #f1f5f9 100%);
        }
        .white-glass {
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(24px);
            border: 1px solid rgba(255, 255, 255, 0.8);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.05);
        }
        .glow-input:focus {
            box-shadow: 0 0 20px rgba(59, 130, 246, 0.1);
            border-color: rgba(59, 130, 246, 0.5) !important;
        }
        .btn-imperial {
            background: linear-gradient(135deg, #2563eb 0%, #7c3aed 100%);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .btn-imperial:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.3);
        }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen p-6 snow-bg">
    <div class="max-w-md w-full relative">
        <!-- Brand Header -->
        <div class="text-center mb-10">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-white border border-slate-200 mb-6 shadow-sm">
                <img src="https://pay.ygxone.com/assets/images/logo-icon.png" class="w-10 h-10" alt="YG">
            </div>
            <h1 class="text-3xl font-black uppercase tracking-tighter mb-2 text-slate-900">Sovereign <span class="text-blue-600 italic">Command</span></h1>
            <p class="text-slate-400 text-sm font-bold uppercase tracking-widest">Identify Yourself to the Empire</p>
        </div>

        <!-- Login Card -->
        <div class="white-glass p-10 rounded-[40px] relative overflow-hidden">
            <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-transparent via-blue-500 to-transparent opacity-20"></div>
            
            @if($errors->any())
                <div class="mb-6 p-4 bg-red-50 border border-red-100 text-red-600 text-xs font-bold rounded-2xl flex items-center gap-3">
                    <i class="fas fa-triangle-exclamation"></i>
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('admin.login') }}" class="space-y-6">
                @csrf
                <div class="space-y-2">
                    <label class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 ml-4">Imperial Email</label>
                    <div class="relative">
                        <i class="fas fa-envelope absolute left-5 top-1/2 -translate-y-1/2 text-slate-300 text-sm"></i>
                        <input type="email" name="email" required placeholder="name@ygxone.com"
                            class="w-full pl-12 pr-6 py-4 bg-white border border-slate-200 rounded-2xl text-slate-800 placeholder-slate-300 glow-input transition-all outline-none">
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 ml-4">Access Code</label>
                    <div class="relative">
                        <i class="fas fa-lock absolute left-5 top-1/2 -translate-y-1/2 text-slate-300 text-sm"></i>
                        <input type="password" name="password" required placeholder="••••••••"
                            class="w-full pl-12 pr-6 py-4 bg-white border border-slate-200 rounded-2xl text-slate-800 placeholder-slate-300 glow-input transition-all outline-none">
                    </div>
                </div>

                <div class="pt-4">
                    <button type="submit" class="w-full py-4 btn-imperial text-white font-black uppercase tracking-widest text-sm rounded-2xl shadow-lg">
                        Authorize Access <i class="fas fa-chevron-right ml-2 text-[10px]"></i>
                    </button>
                </div>
            </form>
        </div>

        <!-- Footer -->
        <div class="mt-10 text-center">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.3em]">
                Protected by Guardian Secure Protocol v4.0
            </p>
        </div>
    </div>
</body>
</html>
