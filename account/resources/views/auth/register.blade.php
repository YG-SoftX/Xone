<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join the Empire | YGXONE Registration</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <script src="{{ asset('js/app.js') }}" defer></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;700;900&family=Inter:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background-color: #f8fafc;
            color: #0f172a;
        }
        .snow-bg {
            background: radial-gradient(circle at 50% 50%, #ffffff 0%, #f1f5f9 100%);
        }
        .white-glass {
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.9);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.05);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .white-glass:hover {
            transform: translateY(-8px);
            border-color: rgba(59, 130, 246, 0.3);
            box-shadow: 0 40px 60px -15px rgba(59, 130, 246, 0.1);
        }
        .gradient-blue {
            background: linear-gradient(135deg, #2563eb 0%, #7c3aed 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col snow-bg">
    <!-- Header -->
    <header class="p-8 flex justify-between items-center relative z-10">
        <div class="flex items-center gap-3">
            <img src="https://pay.ygxone.com/assets/images/logo-icon.png" class="w-8 h-8" alt="YG">
            <span class="font-black uppercase tracking-tighter text-xl">YGXONE <span class="text-blue-600 italic">Identity</span></span>
        </div>
        <a href="/login" class="text-xs font-bold uppercase tracking-widest text-slate-400 hover:text-blue-600 transition-colors">Existing Citizen? Sign In</a>
    </header>

    <main class="flex-1 flex flex-col items-center justify-center p-8 relative z-10">
        <div class="text-center mb-16">
            <h1 class="text-5xl font-black tracking-tighter mb-4">Choose your <span class="gradient-blue italic">Authority</span></h1>
            <p class="text-slate-400 font-bold uppercase tracking-[0.3em] text-xs">Establish your presence in the YGXONE ecosystem</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 max-w-5xl w-full">
            <!-- Individual Card -->
            <a href="{{ route('register.individual') }}" class="white-glass p-10 rounded-[40px] group">
                <div class="w-16 h-16 bg-blue-50 rounded-2xl flex items-center justify-center mb-8 border border-blue-100 group-hover:bg-blue-600 group-hover:border-blue-600 transition-all">
                    <i class="fas fa-user text-blue-600 text-2xl group-hover:text-white transition-colors"></i>
                </div>
                <h3 class="text-2xl font-black uppercase tracking-tight mb-4 text-slate-900">Personal Citizen</h3>
                <p class="text-slate-500 mb-8 leading-relaxed">Designed for individuals. Access YG Mail, Private Drive, and Personal Finance tools with a single secure identity.</p>
                <ul class="space-y-3 mb-10">
                    <li class="flex items-center gap-3 text-sm font-bold text-slate-600">
                        <i class="fas fa-check text-blue-500"></i> 15GB Encrypted Storage
                    </li>
                    <li class="flex items-center gap-3 text-sm font-bold text-slate-600">
                        <i class="fas fa-check text-blue-500"></i> Personal YG Mailbox
                    </li>
                    <li class="flex items-center gap-3 text-sm font-bold text-slate-600">
                        <i class="fas fa-check text-blue-500"></i> Individual YG Pay Wallet
                    </li>
                </ul>
                <div class="inline-flex items-center gap-2 text-blue-600 font-black uppercase tracking-widest text-xs">
                    Initialize Identity <i class="fas fa-arrow-right"></i>
                </div>
            </a>

            <!-- Business Card -->
            @if(\App\Models\PlatformFeature::isEnabled('business_registration'))
            <a href="{{ route('register.business') }}" class="white-glass p-10 rounded-[40px] group border-purple-100/50">
                <div class="w-16 h-16 bg-purple-50 rounded-2xl flex items-center justify-center mb-8 border border-purple-100 group-hover:bg-purple-600 group-hover:border-purple-600 transition-all">
                    <i class="fas fa-building text-purple-600 text-2xl group-hover:text-white transition-colors"></i>
                </div>
                <h3 class="text-2xl font-black uppercase tracking-tight mb-4 text-slate-900">Enterprise Entity</h3>
                <p class="text-slate-500 mb-8 leading-relaxed">Designed for teams and organizations. Centralized management, shared assets, and workforce automation tools.</p>
                <ul class="space-y-3 mb-10">
                    <li class="flex items-center gap-3 text-sm font-bold text-slate-600">
                        <i class="fas fa-check text-purple-500"></i> Managed Team Accounts
                    </li>
                    <li class="flex items-center gap-3 text-sm font-bold text-slate-600">
                        <i class="fas fa-check text-purple-500"></i> Shared Team Drives
                    </li>
                    <li class="flex items-center gap-3 text-sm font-bold text-slate-600">
                        <i class="fas fa-check text-purple-500"></i> Business Admin Console
                    </li>
                </ul>
                <div class="inline-flex items-center gap-2 text-purple-600 font-black uppercase tracking-widest text-xs">
                    Establish Enterprise <i class="fas fa-arrow-right"></i>
                </div>
            </a>
            @endif
        </div>
    </main>

    <footer class="p-8 text-center text-[10px] font-bold text-slate-300 uppercase tracking-[0.3em]">
        Protected by Guardian Secure Protocol v4.0
    </footer>
</body>
</html>
