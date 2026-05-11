<?php
/**
 * YGXONE Chat — Sovereign AI Interface (Open WebUI Style)
 */
define('YUGA_ROOT', __DIR__);
session_start();
$config = file_exists(YUGA_ROOT . '/config.php') ? require YUGA_ROOT . '/config.php' : [];
$appName = $config['platform_name'] ?? 'YGXONE';
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>YGXONE Chat — Sovereign AI</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
    body { font-family: 'Outfit', sans-serif; background-color: #0d0d0d; color: #ececec; }
    .sidebar { background-color: #080808; border-right: 1px solid #1a1a1a; }
    .chat-container { background-color: #0d0d0d; }
    .message-user { background-color: #1a1a1a; border-radius: 1.5rem; }
    .message-ai { background-color: transparent; }
    .input-box { background-color: #161616; border: 1px solid #2a2a2a; box-shadow: 0 0 20px rgba(0,0,0,0.5); }
    .glass { background: rgba(255, 255, 255, 0.03); backdrop-filter: blur(10px); }
    .crimson-accent { color: #9B1B30; }
    .bg-crimson { background-color: #9B1B30; }
    
    pre { background: #1a1a1a; padding: 1rem; border-radius: 0.5rem; overflow-x: auto; font-family: monospace; font-size: 0.85rem; margin-top: 0.5rem; }
    code { font-family: monospace; color: #ff7b72; }
    
    .typing-cursor::after {
        content: '';
        display: inline-block;
        width: 8px;
        height: 18px;
        background-color: #9B1B30;
        margin-left: 4px;
        vertical-align: middle;
        animation: blink 1s infinite;
    }
    @keyframes blink { 0%, 100% { opacity: 1; } 50% { opacity: 0; } }

    /* Custom Scrollbar */
    ::-webkit-scrollbar { width: 6px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: #2a2a2a; border-radius: 10px; }
    ::-webkit-scrollbar-thumb:hover { background: #3a3a3a; }
</style>
</head>
<body class="flex h-screen overflow-hidden">
<?php include YUGA_ROOT . '/ecosystem-nav.php'; ?>

    <!-- Sidebar -->
    <aside class="sidebar w-64 flex flex-col transition-all duration-300 hidden md:flex">
        <div class="p-4 flex items-center gap-3 border-b border-white/5">
            <div class="w-8 h-8 bg-crimson rounded-lg flex items-center justify-center font-bold">Y</div>
            <span class="font-bold tracking-tight text-sm uppercase">Sovereign Chat</span>
        </div>
        
        <div class="p-3">
            <button onclick="newChat()" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-white/5 transition border border-white/10 text-xs font-semibold">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                New Chat
            </button>
        </div>

        <div class="flex-1 overflow-y-auto px-2 py-2 space-y-1" id="chat-history">
            <!-- history items -->
            <div class="text-[10px] uppercase tracking-widest text-gray-600 px-4 mt-4 mb-2">Recent Intelligence</div>
            <div class="px-4 py-2 rounded-lg hover:bg-white/5 cursor-pointer text-xs text-gray-400 truncate">Sovereign Market Analysis</div>
            <div class="px-4 py-2 rounded-lg hover:bg-white/5 cursor-pointer text-xs text-gray-400 truncate">Quantum Security Research</div>
        </div>

        <div class="p-4 border-t border-white/5 space-y-4">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-gray-800 flex items-center justify-center text-[10px] font-bold">JD</div>
                <div class="flex-1">
                    <div class="text-xs font-bold">Sovereign User</div>
                    <div class="text-[10px] text-gray-600">Local Instance</div>
                </div>
                <button class="text-gray-600 hover:text-white transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </button>
            </div>
        </div>
    </aside>

    <!-- Main Chat -->
    <main class="flex-1 flex flex-col relative chat-container">
        
        <!-- Header -->
        <header class="h-14 flex items-center justify-between px-6 border-b border-white/5 absolute top-0 left-0 right-0 glass z-50">
            <div class="flex items-center gap-4">
                <button class="md:hidden text-gray-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <div class="flex items-center gap-2">
                    <span class="text-xs font-bold uppercase tracking-widest text-gray-500">Model:</span>
                    <button class="text-xs font-bold flex items-center gap-2 px-3 py-1 bg-white/5 rounded-full hover:bg-white/10 transition">
                        <span>YugaLM (8-bit)</span>
                        <svg class="w-3 h-3 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <button class="glass px-4 py-1.5 rounded-full text-[10px] font-bold hover:bg-white/10 transition border border-white/5">Share</button>
            </div>
        </header>

        <!-- Messages Area -->
        <div class="flex-1 overflow-y-auto p-4 md:p-10 pt-20 pb-40 space-y-10" id="chat-messages">
            
            <!-- Welcome View -->
            <div class="max-w-3xl mx-auto h-full flex flex-col items-center justify-center text-center space-y-6" id="welcome-view">
                <div class="w-20 h-20 bg-crimson rounded-3xl flex items-center justify-center text-4xl shadow-2xl shadow-crimson/20">Y</div>
                <h1 class="text-3xl font-bold tracking-tight">How can YGXONE assist you today?</h1>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 w-full max-w-2xl">
                    <button onclick="quickQuery('Analyze the latest trends in sovereign tech')" class="glass p-4 rounded-2xl border border-white/5 text-left hover:bg-white/5 transition flex items-center gap-4">
                        <span class="text-2xl">📈</span>
                        <div class="flex-1">
                            <div class="text-xs font-bold">Market Analysis</div>
                            <div class="text-[10px] text-gray-500">Sovereign technology trends</div>
                        </div>
                    </button>
                    <button onclick="quickQuery('Explain quantum encryption to a 5 year old')" class="glass p-4 rounded-2xl border border-white/5 text-left hover:bg-white/5 transition flex items-center gap-4">
                        <span class="text-2xl">🔐</span>
                        <div class="flex-1">
                            <div class="text-xs font-bold">Deep Reasoning</div>
                            <div class="text-[10px] text-gray-500">Quantum security concepts</div>
                        </div>
                    </button>
                </div>
            </div>

        </div>

        <!-- Input Area -->
        <div class="absolute bottom-0 left-0 right-0 p-4 md:p-8 flex justify-center pointer-events-none">
            <div class="w-full max-w-3xl pointer-events-auto">
                <div class="input-box rounded-[2.5rem] p-3 flex flex-col gap-2">
                    <div class="flex items-end gap-3 px-3">
                        <button class="p-2 text-gray-500 hover:text-white transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                        </button>
                        <textarea 
                            id="chat-input"
                            rows="1" 
                            placeholder="Message YGXONE AI..." 
                            class="flex-1 bg-transparent border-none outline-none resize-none py-2 text-sm placeholder-gray-600"
                            oninput="this.style.height = ''; this.style.height = this.scrollHeight + 'px'"
                        ></textarea>
                        <button id="send-btn" onclick="sendMessage()" class="bg-crimson p-2 rounded-full hover:brightness-125 transition active:scale-90 flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                        </button>
                    </div>
                    
                    <div class="flex items-center gap-1 px-4 pb-1 overflow-x-auto no-scrollbar">
                        <button class="flex items-center gap-1.5 px-3 py-1 rounded-full border border-white/5 bg-white/5 text-[10px] font-bold text-gray-400 hover:bg-white/10 transition">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            Web Search
                        </button>
                        <button class="flex items-center gap-1.5 px-3 py-1 rounded-full border border-white/5 bg-white/5 text-[10px] font-bold text-gray-400 hover:bg-white/10 transition">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            Reasoning
                        </button>
                    </div>
                </div>
                <div class="text-[10px] text-center mt-3 text-gray-700 font-bold tracking-tighter uppercase">
                    YGXONE AI may hallucinate &bull; 100% Sovereign & Local &bull; No Tracking
                </div>
            </div>
        </div>
    </main>

    <script>
        const chatMessages = document.getElementById('chat-messages');
        const welcomeView = document.getElementById('welcome-view');
        const chatInput = document.getElementById('chat-input');
        
        let isTyping = false;

        async function sendMessage() {
            const query = chatInput.value.trim();
            if (!query || isTyping) return;

            // Clear input and hide welcome
            chatInput.value = '';
            chatInput.style.height = 'auto';
            welcomeView.style.display = 'none';

            // Append User Message
            appendMessage('user', query);
            
            isTyping = true;
            
            // Append AI Placeholder
            const aiMessageEl = appendMessage('ai', '');
            const bodyEl = aiMessageEl.querySelector('.message-body');
            bodyEl.classList.add('typing-cursor');

            try {
                // Reroute to search_api for RAG search
                const res = await fetch('search_api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ query: query, action: 'web_search' })
                });

                const data = await res.json();
                bodyEl.classList.remove('typing-cursor');
                
                if (data.ok && data.answer) {
                    await typeWriter(bodyEl, data.answer);
                } else {
                    bodyEl.innerHTML = "I encountered an intelligence failure. Please ensure the local models are trained.";
                }
            } catch (err) {
                bodyEl.innerHTML = "Connectivity lost. Ensure your local server is running.";
                bodyEl.classList.remove('typing-cursor');
            } finally {
                isTyping = false;
            }
        }

        function appendMessage(role, text) {
            const wrapper = document.createElement('div');
            wrapper.className = `flex ${role === 'user' ? 'justify-end' : 'justify-start'} w-full max-w-3xl mx-auto`;
            
            const html = role === 'user' 
                ? `
                    <div class="message-user px-6 py-4 max-w-[85%] text-sm leading-relaxed">
                        ${escapeHtml(text)}
                    </div>
                ` 
                : `
                    <div class="flex gap-6 w-full py-4">
                        <div class="w-8 h-8 rounded-lg bg-crimson flex items-center justify-center text-xs font-bold shrink-0">Y</div>
                        <div class="message-body text-sm leading-relaxed flex-1 space-y-4">
                            ${text}
                        </div>
                    </div>
                `;
            
            wrapper.innerHTML = html;
            chatMessages.appendChild(wrapper);
            chatMessages.scrollTo({ top: chatMessages.scrollHeight, behavior: 'smooth' });
            return wrapper;
        }

        async function typeWriter(element, text) {
            // Use textContent to prevent XSS from AI response
            element.textContent = '';
            let i = 0;
            const speed = 10;
            return new Promise(resolve => {
                function type() {
                    if (i < text.length) {
                        element.textContent += text.charAt(i);
                        i++;
                        setTimeout(type, speed);
                    } else {
                        resolve();
                    }
                }
                type();
            });
        }

        function quickQuery(q) {
            chatInput.value = q;
            sendMessage();
        }

        function escapeHtml(str) {
            return str.replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
        }

        chatInput.addEventListener('keypress', e => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });
    </script>
</body>
</html>
