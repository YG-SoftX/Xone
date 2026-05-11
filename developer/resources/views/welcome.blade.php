<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Developer Hub | YGXONE Ecosystem</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;700;900&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background-color: #f8fafc;
            color: #0f172a;
        }
        .code-font { font-family: 'JetBrains Mono', monospace; }
        .white-glass {
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.9);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
        }
        .btn-yg {
            background: #0f172a;
            color: #ffffff;
            transition: all 0.3s;
        }
        .btn-yg:hover {
            background: #1e293b;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        .step-number {
            width: 32px;
            height: 32px;
            background: rgba(59, 130, 246, 0.1);
            border: 1px solid rgba(59, 130, 246, 0.2);
            color: #2563eb;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-weight: 900;
            font-size: 14px;
        }
    </style>
</head>
<body class="min-h-screen">
    <!-- Navigation -->
    <nav class="border-b border-slate-200 p-6 flex justify-between items-center bg-white/80 backdrop-blur-xl sticky top-0 z-50">
        <div class="flex items-center gap-4">
            <img src="https://pay.ygxone.com/assets/images/logo-icon.png" class="w-8 h-8" alt="YG">
            <span class="font-black uppercase tracking-tighter text-xl text-slate-900">YG <span class="text-blue-600">Developers</span></span>
        </div>
        <div class="flex gap-8 text-xs font-bold uppercase tracking-widest text-slate-400">
            <a href="#" class="hover:text-blue-600 transition-colors">Documentation</a>
            <a href="#" class="hover:text-blue-600 transition-colors">API Reference</a>
            <a href="#" class="hover:text-blue-600 transition-colors">Showcase</a>
        </div>
        <a href="/login" class="btn-yg px-6 py-2 rounded-full text-xs font-black uppercase tracking-widest">Console</a>
    </nav>

    <!-- Hero -->
    <section class="max-w-6xl mx-auto px-8 py-24 text-center">
        <h1 class="text-7xl font-black tracking-tighter mb-6 text-slate-900">Build the <span class="italic text-blue-600">Next Generation</span> of Apps.</h1>
        <p class="text-slate-500 text-xl max-w-2xl mx-auto mb-12">Integrate with the world's first sovereign ecosystem. Use YG Identity, Storage, and Payments in your own applications.</p>
        <div class="flex justify-center gap-4">
            <a href="#sso-guide" class="btn-yg px-10 py-4 rounded-full text-sm font-black uppercase tracking-widest shadow-xl shadow-slate-200">Get Started</a>
            <a href="#" class="px-10 py-4 rounded-full white-glass text-sm font-black uppercase tracking-widest hover:bg-white transition-all">Read API Docs</a>
        </div>
    </section>

    <!-- SSO Guide -->
    <section id="sso-guide" class="max-w-4xl mx-auto px-8 py-24">
        <div class="text-center mb-16">
            <h2 class="text-4xl font-black uppercase tracking-tighter italic mb-4 text-slate-900">Login with YG</h2>
            <p class="text-slate-400 font-bold uppercase tracking-widest text-xs">Seamless Authentication for your Users</p>
        </div>

        <div class="space-y-12">
            <!-- Step 1 -->
            <div class="flex gap-8">
                <div class="step-number shrink-0">1</div>
                <div>
                    <h3 class="text-xl font-bold mb-2 text-slate-900">Register your Project</h3>
                    <p class="text-slate-500 mb-4">Visit the Developer Console to create your project and obtain your <code class="bg-slate-100 px-2 py-1 rounded text-blue-600">client_id</code> and <code class="bg-slate-100 px-2 py-1 rounded text-blue-600">client_secret</code>.</p>
                </div>
            </div>

            <!-- Step 2 -->
            <div class="flex gap-8">
                <div class="step-number shrink-0">2</div>
                <div>
                    <h3 class="text-xl font-bold mb-2 text-slate-900">Redirect to Sovereign Identity</h3>
                    <p class="text-slate-500 mb-4">Direct your users to our secure handshake endpoint to begin the authorization flow.</p>
                    <div class="white-glass p-6 rounded-2xl code-font text-sm text-slate-600">
                        GET https://account.ygxone.com/sso/initiate?<br>
                        &nbsp;&nbsp;client_id=YOUR_CLIENT_ID&<br>
                        &nbsp;&nbsp;service=YOUR_APP_NAME&<br>
                        &nbsp;&nbsp;callback=https://your-app.com/callback
                    </div>
                </div>
            </div>

            <!-- Step 3 -->
            <div class="flex gap-8">
                <div class="step-number shrink-0">3</div>
                <div>
                    <h3 class="text-xl font-bold mb-2 text-slate-900">Exchange Token for Identity</h3>
                    <p class="text-slate-500 mb-4">Once the user approves, swap the temporary token for their verified identity profile. <strong>Client Secret is required for external apps.</strong></p>
                    <div class="white-glass p-6 rounded-2xl code-font text-sm text-slate-600">
                        GET https://account.ygxone.com/api/sso/validate?<br>
                        &nbsp;&nbsp;token=RECEIVED_TOKEN&<br>
                        &nbsp;&nbsp;client_id=YOUR_CLIENT_ID&<br>
                        &nbsp;&nbsp;client_secret=YOUR_CLIENT_SECRET
                    </div>
                </div>
            </div>
        </div>

        <!-- Button Preview -->
        <div class="mt-24 p-12 white-glass rounded-[40px] text-center border-slate-200">
            <h4 class="text-sm font-black uppercase tracking-widest text-slate-400 mb-8">Official Button Branding</h4>
            <div class="flex flex-col items-center gap-4">
                <button class="flex items-center gap-3 px-8 py-3 bg-slate-900 text-white rounded-full font-bold hover:bg-black transition-all shadow-xl shadow-slate-300">
                    <img src="https://pay.ygxone.com/assets/images/logo-icon.png" class="w-5 h-5" alt="">
                    Login with YG
                </button>
                <p class="text-[10px] text-slate-400 uppercase font-bold tracking-widest">Used by official empire partners</p>
            </div>
        </div>
    </section>

    <footer class="p-12 border-t border-slate-200 text-center">
        <p class="text-slate-400 text-[10px] font-bold uppercase tracking-[0.3em]">Powered by Sovereign Developer Protocol v4.0</p>
    </footer>
</body>
</html>
