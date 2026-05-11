<?php
/**
 * YGXONE Neural Command Center — High-Fidelity Edition
 */
define('YUGA_ROOT', __DIR__);
session_start();
$config = file_exists(YUGA_ROOT . '/config.php') ? require YUGA_ROOT . '/config.php' : [];
$appName = $config['platform_name'] ?? 'YGXONE';
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Neural Intelligence — YGXONE Empire</title>
    
    <!-- Fonts: High-Fidelity Typography -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&family=Outfit:wght@400;700;900&display=swap" rel="stylesheet">
    
    <!-- Icons: FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Core Scripts -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        :root {
            --yg-bg: #020617;
            --yg-glass: rgba(255, 255, 255, 0.03);
            --yg-border: rgba(255, 255, 255, 0.08);
            --yg-accent-blue: #3b82f6;
            --yg-accent-teal: #14b8a6;
        }

        body {
            background-color: var(--yg-bg) !important;
            font-family: 'Inter', sans-serif;
            color: #f8fafc;
            overflow-x: hidden;
        }

        .premium-glass {
            background: var(--yg-glass);
            border: 1px solid var(--yg-border);
            backdrop-filter: blur(16px);
        }

        .sidebar-item-active {
            background: rgba(20, 184, 166, 0.1);
            border-left: 3px solid var(--yg-accent-teal);
            color: white !important;
        }

        .sidebar-item:hover {
            background: rgba(255, 255, 255, 0.05);
            color: white !important;
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 4px; }
        ::-webkit-scrollbar-track { background: var(--yg-bg); }
        ::-webkit-scrollbar-thumb { background: var(--yg-border); border-radius: 10px; }
        
        .chat-bubble-ai {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid var(--yg-border);
            border-radius: 2rem;
        }

        .chat-bubble-user {
            background: rgba(59, 130, 246, 0.1);
            border: 1px solid rgba(59, 130, 246, 0.2);
            border-radius: 2rem;
        }

        .input-command-bar {
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(40px);
            border: 1px solid var(--yg-border);
            box-shadow: 0 40px 100px rgba(0, 0, 0, 0.6);
        }

        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="antialiased selection:bg-teal-500/30">
    <div class="flex h-screen overflow-hidden">
        
        <!-- Universal Sidebar: The Backbone of the Empire -->
        <aside class="hidden md:flex flex-col w-72 premium-glass border-r border-white/5 z-50">
            <div class="p-8">
                <a href="/" class="flex items-center space-x-3 group">
                    <div class="w-10 h-10 bg-gradient-to-br from-teal-500 to-blue-600 rounded-xl flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform">
                        <i class="fas fa-brain text-white"></i>
                    </div>
                    <span class="text-2xl font-black text-white tracking-tighter uppercase">YGXONE</span>
                </a>
            </div>

            <nav class="flex-1 px-4 space-y-2 mt-4 overflow-y-auto">
                <p class="text-[10px] font-black text-gray-500 uppercase tracking-widest px-4 mb-4">Intelligence Nodes</p>
                
                <a href="home.php" class="flex items-center px-4 py-4 rounded-2xl text-gray-400 font-bold transition-all sidebar-item sidebar-item-active">
                    <i class="fas fa-microchip w-8 text-lg"></i>
                    <span class="text-sm tracking-tight">Neural Core</span>
                </a>

                <a href="search.php" class="flex items-center px-4 py-4 rounded-2xl text-gray-400 font-bold transition-all sidebar-item">
                    <i class="fas fa-magnifying-glass w-8 text-lg"></i>
                    <span class="text-sm tracking-tight">Sovereign Search</span>
                </a>

                <div class="pt-8">
                    <p class="text-[10px] font-black text-gray-500 uppercase tracking-widest px-4 mb-4">Ecosystem Bridge</p>
                    <a href="https://account.ygxone.com/dashboard" class="flex items-center px-4 py-4 rounded-2xl text-gray-400 font-bold transition-all sidebar-item">
                        <i class="fas fa-th-large w-8 text-lg text-blue-400"></i>
                        <span class="text-sm tracking-tight">Command Center</span>
                    </a>
                    <a href="https://pay.ygxone.com" class="flex items-center px-4 py-4 rounded-2xl text-gray-400 font-bold transition-all sidebar-item">
                        <i class="fas fa-wallet w-8 text-lg text-purple-400"></i>
                        <span class="text-sm tracking-tight">YG Pay Authority</span>
                    </a>
                </div>
            </nav>

            <!-- Sidebar Footer: Status -->
            <div class="p-6 border-t border-white/5 bg-black/20 mt-auto">
                <div class="flex items-center space-x-4">
                    <div class="relative">
                        <div class="h-10 w-10 rounded-xl bg-gradient-to-br from-teal-500 to-blue-600 flex items-center justify-center text-white font-black border border-white/10 shadow-lg">
                            Y
                        </div>
                        <div class="absolute -bottom-1 -right-1 w-3 h-3 bg-green-500 border-2 border-[#020617] rounded-full"></div>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-black text-white truncate">Admin Portal</p>
                        <p class="text-[10px] text-teal-400 font-bold uppercase tracking-widest">Sovereign Node</p>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Neural Workspace -->
        <div class="flex-1 flex flex-col overflow-hidden relative">
            
            <!-- Universal Header: The Command Bar -->
            <header class="h-20 premium-glass border-b border-white/5 flex items-center justify-between px-8 z-40">
                <div class="flex items-center flex-1 space-x-8">
                    <div class="relative w-full max-w-xl">
                        <i class="fas fa-search absolute left-5 top-1/2 -translate-y-1/2 text-gray-500"></i>
                        <input type="text" placeholder="Search neural knowledge..." class="w-full bg-white/5 border border-white/10 rounded-2xl py-3 pl-12 pr-6 text-sm text-white placeholder-gray-600 focus:border-teal-500/50 outline-none transition-all">
                    </div>
                </div>

                <div class="flex items-center space-x-6 ml-8">
                    <div class="hidden md:flex items-center gap-3 px-4 py-2 bg-teal-500/10 border border-teal-500/20 rounded-xl">
                        <div class="w-2 h-2 bg-teal-500 rounded-full animate-pulse shadow-[0_0_10px_#14b8a6]"></div>
                        <span class="text-[10px] font-black text-teal-400 uppercase tracking-widest">Neural Core Online</span>
                    </div>
                </div>
            </header>

            <!-- Chat Interface area -->
            <main class="flex-1 overflow-y-auto p-8 md:p-20 pb-48 space-y-10" id="chat-messages">
                <!-- Welcome View -->
                <div class="max-w-3xl mx-auto text-center space-y-8 py-20" id="welcome-view">
                    <div class="w-24 h-24 bg-teal-500/10 rounded-[2.5rem] flex items-center justify-center mx-auto border border-teal-500/20 shadow-2xl">
                        <i class="fas fa-brain text-4xl text-teal-400"></i>
                    </div>
                    <h1 class="text-5xl font-black text-white tracking-tighter uppercase">Neural Intelligence</h1>
                    <p class="text-gray-500 text-sm font-medium tracking-wide leading-relaxed max-w-lg mx-auto">
                        Welcome to the YGXONE Neural Command Center. Your data remains sovereign. Your intelligence is local and encrypted.
                    </p>
                    <div class="flex flex-wrap justify-center gap-4">
                        <button onclick="quickQuery('Analyze ecosystem security status')" class="premium-glass px-6 py-3 rounded-2xl text-[10px] font-black uppercase tracking-widest text-gray-400 hover:text-white transition-all">Security Audit</button>
                        <button onclick="quickQuery('Scan local market intelligence')" class="premium-glass px-6 py-3 rounded-2xl text-[10px] font-black uppercase tracking-widest text-gray-400 hover:text-white transition-all">Market Intel</button>
                    </div>
                </div>
            </main>

            <!-- Unified Command Input -->
            <div class="absolute bottom-0 left-0 right-0 p-10 flex justify-center z-50">
                <div class="w-full max-w-4xl input-command-bar rounded-[2.5rem] p-4 flex items-end gap-4">
                    <textarea 
                        id="chat-input"
                        rows="1" 
                        placeholder="Message Neural Core..." 
                        class="flex-1 bg-transparent border-none outline-none resize-none py-3 px-4 text-white text-md font-medium placeholder-gray-700"
                        oninput="this.style.height = ''; this.style.height = this.scrollHeight + 'px'"
                    ></textarea>
                    <button onclick="sendMessage()" class="w-12 h-12 bg-teal-600 rounded-2xl hover:scale-105 active:scale-95 transition-all flex items-center justify-center shrink-0 shadow-2xl shadow-teal-600/30">
                        <i class="fas fa-arrow-up text-white"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const chatMessages = document.getElementById('chat-messages');
        const welcomeView = document.getElementById('welcome-view');
        const chatInput = document.getElementById('chat-input');
        
        function escHtml(str) {
            return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
        }

        function appendMessage(role, text) {
            const div = document.createElement('div');
            div.className = `flex ${role === 'user' ? 'justify-end' : 'justify-start'} w-full max-w-4xl mx-auto`;
            const safeText = escHtml(text);
            div.innerHTML = role === 'user' ?
                `<div class="chat-bubble-user px-8 py-5 max-w-[80%] text-sm font-bold text-white shadow-2xl">${safeText}</div>` :
                `<div class="flex gap-6 w-full py-4">
                    <div class="w-10 h-10 rounded-xl bg-teal-600/20 flex items-center justify-center text-teal-400 font-black border border-teal-500/20 shrink-0 shadow-lg">Y</div>
                    <div class="chat-bubble-ai flex-1 p-8 text-sm leading-relaxed text-gray-200 font-medium shadow-3xl">${safeText}</div>
                </div>`;
            chatMessages.appendChild(div);
            chatMessages.scrollTo({ top: chatMessages.scrollHeight, behavior: 'smooth' });
        }

        async function sendMessage() {
            const q = chatInput.value.trim();
            if(!q) return;
            chatInput.value = '';
            chatInput.style.height = 'auto';
            welcomeView.style.display = 'none';
            appendMessage('user', q);
            // AI Logic placeholder
            setTimeout(() => appendMessage('ai', 'Neural response initialization successful. Protocol sync complete.'), 1000);
        }

        function quickQuery(q) { chatInput.value = q; sendMessage(); }
        chatInput.addEventListener('keydown', e => { if(e.key==='Enter'&&!e.shiftKey){e.preventDefault(); sendMessage();} });
    </script>
</body>
</html>
