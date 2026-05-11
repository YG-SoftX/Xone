/**
 * Yuga Chat Widget  v1.1
 * Embed on any website with a single <script> tag:
 *
 *   <script
 *     src="https://yoursite.com/yuga/widget/yuga-widget.js"
 *     data-api="https://yoursite.com/yuga/api/"
 *     data-model="default"
 *     data-key="YOUR_API_KEY"
 *     data-theme="dark"
 *     data-title="AI Assistant"
 *     data-placeholder="Ask me anything..."
 *     data-position="bottom-right"
 *   ></script>
 *
 * Optional JS init:
 *   YugaWidget.init({ api: '...', model: 'default', key: '...', theme: 'light' });
 */

(function () {
    'use strict';

    // ── Config from script tag ──────────────────────────────────────────
    const scriptTag = document.currentScript || (function () {
        const tags = document.querySelectorAll('script[data-api]');
        return tags[tags.length - 1];
    })();

    const cfg = {
        api:         scriptTag?.dataset?.api         || '/yuga/api/',
        model:       scriptTag?.dataset?.model       || 'default',
        theme:       scriptTag?.dataset?.theme       || 'dark',
        title:       scriptTag?.dataset?.title       || 'Yuga Assistant',
        placeholder: scriptTag?.dataset?.placeholder || 'Ask me anything...',
        key:         scriptTag?.dataset?.key         || scriptTag?.dataset?.apiKey || '',
        position:    scriptTag?.dataset?.position    || 'bottom-right',
    };

    // Conversation session — persists across messages in this page load
    let sessionId = '';

    // ── Theme tokens ────────────────────────────────────────────────────
    const dark = cfg.theme !== 'light';
    const T = {
        bg:        dark ? '#100c0d' : '#ffffff',
        bg2:       dark ? '#1a1010' : '#f5f0f1',
        border:    dark ? 'rgba(155,27,48,.3)' : 'rgba(155,27,48,.25)',
        tx:        dark ? '#f5f0f1' : '#1a0a0c',
        mu:        dark ? '#9b8e90' : '#6b4a4d',
        userBg:    '#9B1B30',
        userTx:    '#ffffff',
        botBg:     dark ? '#1e1213' : '#fdf0f1',
        botTx:     dark ? '#f5f0f1' : '#1a0a0c',
        sysBg:     dark ? 'rgba(155,27,48,.1)' : 'rgba(155,27,48,.07)',
        sysTx:     dark ? '#e87080' : '#9B1B30',
        inputBg:   dark ? '#1a1010' : '#fdf0f1',
        inputBd:   dark ? 'rgba(255,255,255,.1)' : 'rgba(155,27,48,.2)',
        scrollTh:  dark ? '#3d2020' : '#f0c0c5',
        accent:    '#9B1B30',
        accentHov: '#c8334a',
        btnShadow: 'rgba(155,27,48,.45)',
    };

    // ── Styles ──────────────────────────────────────────────────────────
    const STYLES = `
    #yuga-widget-btn {
        position: fixed;
        ${cfg.position.includes('right') ? 'right: 22px;' : 'left: 22px;'}
        bottom: 22px;
        width: 54px; height: 54px;
        border-radius: 50%;
        background: ${T.accent};
        color: #fff; border: 2px solid rgba(255,255,255,.15); cursor: pointer;
        box-shadow: 0 4px 18px ${T.btnShadow};
        font-size: 22px; display: flex; align-items: center; justify-content: center;
        z-index: 99998; transition: background .2s, box-shadow .2s;
        font-family: system-ui, sans-serif;
    }
    #yuga-widget-btn:hover {
        background: ${T.accentHov};
        box-shadow: 0 6px 24px ${T.btnShadow};
    }

    #yuga-widget-panel {
        position: fixed;
        ${cfg.position.includes('right') ? 'right: 16px;' : 'left: 16px;'}
        bottom: 90px;
        width: 356px; max-width: calc(100vw - 32px);
        height: 500px; max-height: calc(100vh - 110px);
        background: ${T.bg};
        border: 1px solid ${T.border};
        border-radius: 14px;
        box-shadow: 0 8px 36px rgba(0,0,0,.35);
        display: flex; flex-direction: column;
        z-index: 99999; overflow: hidden;
        font-family: system-ui, -apple-system, sans-serif;
        transition: opacity .18s, transform .18s;
        opacity: 0; transform: translateY(10px) scale(.98); pointer-events: none;
    }
    #yuga-widget-panel.open {
        opacity: 1; transform: translateY(0) scale(1); pointer-events: all;
    }

    .yw-header {
        background: ${T.accent};
        padding: 12px 14px; display: flex; align-items: center; gap: 10px;
        color: #fff; flex-shrink: 0;
        border-bottom: 1px solid rgba(255,255,255,.12);
    }
    .yw-header-dot {
        width: 8px; height: 8px; border-radius: 50%;
        background: #fff; opacity: .5; flex-shrink: 0;
        transition: opacity .3s;
    }
    .yw-header-dot.online { opacity: 1; background: #4ade80; }
    .yw-header-info { flex: 1; min-width: 0; }
    .yw-header-title { font-weight: 700; font-size: 14px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .yw-header-sub { font-size: 11px; opacity: .75; margin-top: 1px; }
    .yw-header-close {
        background: rgba(255,255,255,.15); border: none; color: #fff; cursor: pointer;
        width: 26px; height: 26px; border-radius: 50%; font-size: 14px;
        display: flex; align-items: center; justify-content: center;
        transition: background .15s; flex-shrink: 0;
    }
    .yw-header-close:hover { background: rgba(255,255,255,.3); }

    .yw-messages {
        flex: 1; overflow-y: auto; padding: 12px;
        display: flex; flex-direction: column; gap: 8px;
        scrollbar-width: thin;
        scrollbar-color: ${T.scrollTh} transparent;
    }
    .yw-messages::-webkit-scrollbar { width: 4px; }
    .yw-messages::-webkit-scrollbar-thumb { background: ${T.scrollTh}; border-radius: 4px; }

    .yw-msg {
        max-width: 84%; padding: 9px 13px; border-radius: 12px;
        font-size: 13.5px; line-height: 1.55; word-break: break-word;
        animation: yw-in .15s ease;
    }
    @keyframes yw-in { from { opacity:0; transform:translateY(4px) } to { opacity:1; transform:none } }
    .yw-msg.user {
        align-self: flex-end;
        background: ${T.userBg}; color: ${T.userTx};
        border-bottom-right-radius: 3px;
    }
    .yw-msg.bot {
        align-self: flex-start;
        background: ${T.botBg}; color: ${T.botTx};
        border: 1px solid ${T.border};
        border-bottom-left-radius: 3px;
    }
    .yw-msg.system {
        align-self: center;
        background: ${T.sysBg}; color: ${T.sysTx};
        font-size: 11.5px; padding: 5px 12px; border-radius: 20px;
        text-align: center; max-width: 90%;
    }

    .yw-typing {
        align-self: flex-start;
        background: ${T.botBg}; border: 1px solid ${T.border};
        border-bottom-left-radius: 3px;
        padding: 10px 14px; border-radius: 12px;
        display: flex; gap: 4px; align-items: center;
    }
    .yw-typing span {
        width: 6px; height: 6px; background: ${T.accent};
        border-radius: 50%; animation: yw-bounce .9s infinite;
    }
    .yw-typing span:nth-child(2) { animation-delay: .18s; }
    .yw-typing span:nth-child(3) { animation-delay: .36s; }
    @keyframes yw-bounce { 0%,80%,100%{transform:scale(0.55);opacity:.5} 40%{transform:scale(1);opacity:1} }

    .yw-input-row {
        display: flex; gap: 8px; padding: 10px 12px; flex-shrink: 0;
        border-top: 1px solid ${T.border};
        background: ${T.bg};
    }
    .yw-input {
        flex: 1; border: 1px solid ${T.inputBd};
        border-radius: 9px; padding: 9px 12px; font-size: 13.5px;
        background: ${T.inputBg}; color: ${T.tx};
        resize: none; max-height: 90px; outline: none;
        transition: border-color .18s; font-family: inherit; line-height: 1.4;
    }
    .yw-input:focus { border-color: ${T.accent}; }
    .yw-input::placeholder { color: ${T.mu}; }
    .yw-send {
        background: ${T.accent}; border: none; border-radius: 9px;
        color: #fff; cursor: pointer; padding: 9px 13px; font-size: 16px;
        transition: background .18s; flex-shrink: 0; align-self: flex-end;
    }
    .yw-send:hover { background: ${T.accentHov}; }
    .yw-send:disabled { background: ${T.mu}; cursor: not-allowed; }

    .yw-footer {
        text-align: center; font-size: 10px; padding: 5px 8px; flex-shrink: 0;
        color: ${T.mu}; background: ${T.bg};
        border-top: 1px solid ${T.border};
    }
    .yw-footer a { color: ${T.accent}; text-decoration: none; }
    `;

    // ── HTML ────────────────────────────────────────────────────────────
    const HTML = `
    <div id="yuga-widget-panel">
        <div class="yw-header">
            <span class="yw-header-dot" id="yw-dot"></span>
            <div class="yw-header-info">
                <div class="yw-header-title">${cfg.title}</div>
                <div class="yw-header-sub" id="yw-status">Connecting…</div>
            </div>
            <button class="yw-header-close" id="yw-close" title="Close">✕</button>
        </div>
        <div class="yw-messages" id="yw-messages"></div>
        <div class="yw-input-row">
            <textarea class="yw-input" id="yw-input" rows="1"
                placeholder="${cfg.placeholder}"></textarea>
            <button class="yw-send" id="yw-send" title="Send">&#10148;</button>
        </div>
        <div class="yw-footer">Powered by <a href="#" target="_blank">Yuga AI</a></div>
    </div>
    <button id="yuga-widget-btn" title="Chat with AI">&#128172;</button>
    `;

    // ── Init ────────────────────────────────────────────────────────────
    function init(overrides = {}) {
        Object.assign(cfg, overrides);

        const style = document.createElement('style');
        style.textContent = STYLES;
        document.head.appendChild(style);

        const wrap = document.createElement('div');
        wrap.innerHTML = HTML;
        document.body.appendChild(wrap);

        const btn   = document.getElementById('yuga-widget-btn');
        const panel = document.getElementById('yuga-widget-panel');
        const close = document.getElementById('yw-close');
        const send  = document.getElementById('yw-send');
        const input = document.getElementById('yw-input');

        btn.addEventListener('click', () => {
            const opening = !panel.classList.contains('open');
            panel.classList.toggle('open');
            if (opening) input.focus();
        });
        close.addEventListener('click', () => panel.classList.remove('open'));

        send.addEventListener('click', sendMessage);
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
        });
        input.addEventListener('input', () => {
            input.style.height = 'auto';
            input.style.height = Math.min(input.scrollHeight, 90) + 'px';
        });

        checkStatus();
    }

    // ── Status check ───────────────────────────────────────────────────
    async function checkStatus() {
        const statusEl = document.getElementById('yw-status');
        const dot      = document.getElementById('yw-dot');
        try {
            const r = await apiCall('status');
            if (r.ok && r.vocab_built) {
                dot.classList.add('online');
                statusEl.textContent = 'Online · ready to chat';
                addMsg('bot', 'Hi! How can I help you today?');
            } else {
                statusEl.textContent = 'Model not trained yet';
                addMsg('system', 'This AI hasn\'t been trained yet. Contact the site admin.');
            }
        } catch {
            statusEl.textContent = 'Unavailable';
            addMsg('system', 'Could not reach the AI. Please try again later.');
        }
    }

    // ── Send message ───────────────────────────────────────────────────
    async function sendMessage() {
        const input  = document.getElementById('yw-input');
        const send   = document.getElementById('yw-send');
        const text   = input.value.trim();
        if (!text) return;

        addMsg('user', text);
        input.value = '';
        input.style.height = 'auto';
        send.disabled = true;

        const typing = addTyping();
        try {
            const payload = { message: text, model: cfg.model };
            if (sessionId) payload.session_id = sessionId;

            const r = await apiCall('chat', payload);
            typing.remove();

            if (r.ok) {
                if (r.session_id) sessionId = r.session_id; // persist session
                addMsg('bot', r.reply || '(no response)');
            } else {
                addMsg('system', '⚠ ' + (r.error || 'Unexpected error'));
            }
        } catch (e) {
            typing.remove();
            addMsg('system', '⚠ ' + e.message);
        }
        send.disabled = false;
        input.focus();
    }

    // ── UI helpers ─────────────────────────────────────────────────────
    function addMsg(type, text) {
        const msgs = document.getElementById('yw-messages');
        if (!msgs) return null;
        const d = document.createElement('div');
        d.className = 'yw-msg ' + type;
        d.textContent = text;
        msgs.appendChild(d);
        msgs.scrollTop = msgs.scrollHeight;
        return d;
    }

    function addTyping() {
        const msgs = document.getElementById('yw-messages');
        const d    = document.createElement('div');
        d.className = 'yw-typing';
        d.innerHTML = '<span></span><span></span><span></span>';
        msgs.appendChild(d);
        msgs.scrollTop = msgs.scrollHeight;
        return d;
    }

    // ── API call ───────────────────────────────────────────────────────
    async function apiCall(action, body = {}) {
        const url     = cfg.api.replace(/\/?$/, '/') + '?action=' + action;
        const headers = { 'Content-Type': 'application/json' };
        if (cfg.key) headers['X-API-Key'] = cfg.key;
        if (!body.model) body.model = cfg.model;

        const r = await fetch(url, {
            method: 'POST',
            headers,
            body: JSON.stringify(body),
        });
        if (!r.ok) throw new Error('HTTP ' + r.status);
        return r.json();
    }

    // ── Public API ─────────────────────────────────────────────────────
    window.YugaWidget = {
        init,
        resetSession: () => { sessionId = ''; },
        send: (text) => {
            const input = document.getElementById('yw-input');
            if (input) { input.value = text; sendMessage(); }
        },
    };

    // Auto-init
    if (scriptTag?.dataset?.api) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => init());
        } else {
            init();
        }
    }
})();
