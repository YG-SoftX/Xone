<?php
/**
 * Yuga Voice Assistant
 * Hands-free, conversation-based personal assistant.
 * Works on any device with a modern browser (Chrome, Edge, Safari).
 */
define('YUGA_ROOT', dirname(__DIR__));
$config   = file_exists(YUGA_ROOT.'/config.php') ? require YUGA_ROOT.'/config.php' : [];
$api_base = (isset($_SERVER['HTTPS'])?'https':'http').'://'.$_SERVER['HTTP_HOST']
    . str_replace('/assistant/index.php', '/api/', $_SERVER['SCRIPT_NAME']);
$model    = $_GET['model'] ?? ($config['default_model'] ?? 'default');
$api_key  = $_GET['key']   ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<link rel="manifest" href="manifest.json">
<title>Yuga Assistant</title>
<style>
*{box-sizing:border-box;margin:0;padding:0;-webkit-tap-highlight-color:transparent}
:root{
  --bg:#080d1a;--bg2:#0d1526;--bd:#1e293b;
  --tx:#e2e8f0;--mu:#94a3b8;--di:#475569;
  --pu:#6366f1;--pl:#a5b4fc;--te:#14b8a6;--am:#f59e0b;--gr:#10b981;--re:#ef4444;
}
html,body{height:100%;overflow:hidden;background:var(--bg);color:var(--tx);font-family:system-ui,-apple-system,sans-serif;user-select:none}

/* ── Animated background ────────────────────────── */
.bg-orbs{position:fixed;inset:0;pointer-events:none;overflow:hidden;z-index:0}
.orb{position:absolute;border-radius:50%;filter:blur(80px);opacity:.18;animation:drift 12s ease-in-out infinite}
.orb1{width:400px;height:400px;background:var(--pu);top:-100px;left:-100px;animation-delay:0s}
.orb2{width:300px;height:300px;background:var(--te);bottom:-80px;right:-80px;animation-delay:-4s}
.orb3{width:200px;height:200px;background:var(--am);top:40%;left:60%;animation-delay:-8s}
@keyframes drift{0%,100%{transform:translate(0,0)}50%{transform:translate(40px,30px)}}

/* ── Layout ─────────────────────────────────────── */
.shell{position:relative;z-index:1;height:100vh;display:flex;flex-direction:column;padding:0 16px 24px}

/* ── Top bar ─────────────────────────────────────── */
.topbar{display:flex;align-items:center;padding:16px 0 12px;gap:10px;flex-shrink:0}
.logo{width:32px;height:32px;background:var(--pu);border-radius:8px;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:15px;flex-shrink:0}
.title{font-size:15px;font-weight:600}
.model-badge{margin-left:auto;font-size:11px;background:rgba(99,102,241,.2);color:var(--pl);padding:3px 10px;border-radius:20px;border:1px solid rgba(99,102,241,.3)}
.btn-icon{width:34px;height:34px;border-radius:8px;border:1px solid var(--bd);background:transparent;color:var(--mu);display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:16px;transition:.15s}
.btn-icon:hover,.btn-icon.on{background:rgba(99,102,241,.15);color:var(--pl);border-color:rgba(99,102,241,.4)}

/* ── Conversation ────────────────────────────────── */
.conv{flex:1;overflow-y:auto;display:flex;flex-direction:column;gap:14px;padding:8px 0;scroll-behavior:smooth}
.conv::-webkit-scrollbar{width:4px}.conv::-webkit-scrollbar-thumb{background:var(--bd);border-radius:2px}
.msg{display:flex;gap:10px;align-items:flex-start;animation:fadeUp .3s ease}
@keyframes fadeUp{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}
.msg.user{flex-direction:row-reverse}
.avatar{width:30px;height:30px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:13px;flex-shrink:0}
.avatar.ai{background:linear-gradient(135deg,var(--pu),var(--te));color:#fff;font-weight:700;font-size:11px}
.avatar.user{background:var(--bd);color:var(--mu)}
.bubble{max-width:78%;padding:11px 14px;border-radius:14px;font-size:14px;line-height:1.55}
.bubble.ai{background:var(--bg2);border:1px solid var(--bd);border-bottom-left-radius:4px}
.bubble.user{background:var(--pu);color:#fff;border-bottom-right-radius:4px}
.action-chip{display:inline-flex;align-items:center;gap:5px;margin-top:7px;background:rgba(20,184,166,.12);border:1px solid rgba(20,184,166,.3);border-radius:6px;padding:4px 10px;font-size:11px;color:var(--te)}

/* ── Status bar ──────────────────────────────────── */
.status-bar{text-align:center;font-size:12px;color:var(--di);height:20px;flex-shrink:0;transition:.3s}
.status-bar.listening{color:var(--re);animation:pulse-text 1s ease-in-out infinite}
.status-bar.thinking{color:var(--am)}
.status-bar.speaking{color:var(--te)}
@keyframes pulse-text{0%,100%{opacity:1}50%{opacity:.4}}

/* ── Waveform ────────────────────────────────────── */
.waveform{display:flex;align-items:center;justify-content:center;gap:4px;height:36px;flex-shrink:0;margin:4px 0}
.wave-bar{width:4px;background:var(--pu);border-radius:2px;height:6px;transition:height .1s ease;opacity:0}
.waveform.active .wave-bar{opacity:1;animation:wave var(--d,.4s) ease-in-out infinite alternate}
@keyframes wave{from{height:4px}to{height:30px}}

/* ── Mic button ──────────────────────────────────── */
.mic-area{display:flex;flex-direction:column;align-items:center;gap:10px;flex-shrink:0;padding-top:4px}
.mic-ring{width:84px;height:84px;border-radius:50%;position:relative;display:flex;align-items:center;justify-content:center}
.mic-ring::before{content:'';position:absolute;inset:-8px;border-radius:50%;border:2px solid var(--pu);opacity:0;transition:.3s}
.mic-ring.listening::before{opacity:.5;animation:ring-pulse 1.2s ease-in-out infinite}
@keyframes ring-pulse{0%,100%{transform:scale(1);opacity:.5}50%{transform:scale(1.12);opacity:.15}}
.mic-btn{width:84px;height:84px;border-radius:50%;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:32px;transition:.2s;position:relative;z-index:1;background:linear-gradient(135deg,var(--pu),#818cf8);box-shadow:0 4px 24px rgba(99,102,241,.4)}
.mic-btn:active{transform:scale(.93)}
.mic-btn.listening{background:linear-gradient(135deg,var(--re),#f87171);box-shadow:0 4px 24px rgba(239,68,68,.5)}
.mic-btn.speaking{background:linear-gradient(135deg,var(--te),#5eead4);box-shadow:0 4px 24px rgba(20,184,166,.4)}
.mic-hint{font-size:11px;color:var(--di)}

/* ── Text input fallback ─────────────────────────── */
.text-row{display:flex;gap:8px;margin-top:8px;flex-shrink:0}
.text-in{flex:1;background:var(--bg2);border:1px solid var(--bd);border-radius:10px;padding:10px 14px;color:var(--tx);font-size:14px;outline:none;font-family:inherit;transition:.15s}
.text-in:focus{border-color:var(--pu)}
.send-btn{background:var(--pu);color:#fff;border:none;border-radius:10px;padding:10px 16px;font-size:14px;cursor:pointer;font-weight:600;transition:.15s}
.send-btn:hover{background:var(--pu2,#4f46e5)}

/* ── Continuous mode toggle ──────────────────────── */
.continuous-row{display:flex;align-items:center;justify-content:center;gap:8px;font-size:12px;color:var(--di);margin-top:6px;flex-shrink:0}
.toggle{width:34px;height:18px;background:var(--bd);border-radius:9px;position:relative;cursor:pointer;transition:.2s}
.toggle::after{content:'';position:absolute;width:14px;height:14px;background:#fff;border-radius:50%;top:2px;left:2px;transition:.2s}
.toggle.on{background:var(--pu)}.toggle.on::after{left:18px}

/* ── Permission notice ───────────────────────────── */
.perm-notice{background:rgba(245,158,11,.1);border:1px solid rgba(245,158,11,.3);border-radius:10px;padding:12px 16px;font-size:13px;color:var(--am);text-align:center;margin:8px 0;display:none}
.perm-notice.show{display:block}

@media(max-width:400px){.bubble{max-width:88%;font-size:13px}.mic-btn{width:74px;height:74px;font-size:28px}}
</style>
</head>
<body>
<div class="bg-orbs">
  <div class="orb orb1"></div>
  <div class="orb orb2"></div>
  <div class="orb orb3"></div>
</div>

<div class="shell">
  <!-- Install banner (shown when PWA is installable) -->
  <div id="install-banner" style="display:none;background:linear-gradient(135deg,rgba(99,102,241,.15),rgba(20,184,166,.1));border:1px solid rgba(99,102,241,.3);border-radius:10px;padding:10px 14px;display:none;align-items:center;gap:10px;margin-bottom:8px;flex-shrink:0">
    <span style="font-size:20px">📱</span>
    <div style="flex:1;font-size:13px"><strong style="color:var(--pl)">Install Yuga</strong><br><span style="color:var(--mu);font-size:11px">Add to your home screen for quick voice access</span></div>
    <button id="install-btn" onclick="installPWA()" style="background:var(--pu);color:#fff;border:none;border-radius:8px;padding:7px 14px;font-size:12px;font-weight:600;cursor:pointer">Install</button>
    <button onclick="document.getElementById('install-banner').style.display='none'" style="background:none;border:none;color:var(--di);cursor:pointer;font-size:18px">×</button>
  </div>

  <!-- Offline indicator -->
  <div id="offline-bar" style="display:none;background:rgba(239,68,68,.15);border:1px solid rgba(239,68,68,.3);border-radius:8px;padding:7px 14px;font-size:12px;color:#fca5a5;text-align:center;flex-shrink:0;margin-bottom:6px">
    You're offline — messages will be sent when you reconnect
  </div>

  <!-- Top bar -->
  <div class="topbar">
    <div class="logo">Y</div>
    <div class="title">Yuga Assistant</div>
    <div class="model-badge" id="model-badge"><?= htmlspecialchars($model) ?></div>
    <button class="btn-icon" id="btn-tts" title="Toggle voice replies" onclick="toggleTTS()">🔊</button>
    <button class="btn-icon" id="btn-hist" title="Clear chat" onclick="clearChat()">🗑</button>
  </div>

  <!-- Permission notice -->
  <div class="perm-notice" id="perm-notice">
    Microphone access required for voice mode. Please allow when prompted.
  </div>

  <!-- Conversation -->
  <div class="conv" id="conv">
    <div class="msg">
      <div class="avatar ai">Y</div>
      <div class="bubble ai">
        Hi! I'm Yuga, your personal assistant. You can talk to me or type below.
        <br><br>Try: <em>"Open YouTube"</em>, <em>"Set a timer for 5 minutes"</em>, <em>"Search for PHP tutorials"</em>, or ask me anything.
      </div>
    </div>
  </div>

  <!-- Waveform -->
  <div class="waveform" id="waveform">
    <?php for ($i=0;$i<18;$i++): $d = round(0.3 + (abs($i-9)/9)*0.4, 2); ?>
    <div class="wave-bar" style="--d:<?= $d ?>s;animation-delay:<?= round($i*0.04,2) ?>s"></div>
    <?php endfor; ?>
  </div>

  <!-- Status -->
  <div class="status-bar" id="status-bar">Tap the mic to speak</div>

  <!-- Mic button -->
  <div class="mic-area">
    <div class="mic-ring" id="mic-ring">
      <button class="mic-btn" id="mic-btn" onclick="toggleListen()">🎤</button>
    </div>
    <div class="mic-hint" id="mic-hint">Tap to speak</div>
  </div>

  <!-- Continuous mode -->
  <div class="continuous-row">
    <span>Hands-free (continuous)</span>
    <div class="toggle" id="continuous-toggle" onclick="toggleContinuous()"></div>
    <span id="continuous-label">Off</span>
  </div>

  <!-- Text input fallback -->
  <div class="text-row">
    <input class="text-in" id="text-in" placeholder="Or type here..." onkeydown="if(event.key==='Enter')sendText()">
    <button class="send-btn" onclick="sendText()">Send</button>
  </div>
</div>

<script>
// ── Config ──────────────────────────────────────────────────────────────
const API   = <?= json_encode($api_base) ?>;
const MODEL = <?= json_encode($model) ?>;
const AKEY  = <?= json_encode($api_key) ?>;
let SESSION = 'voice_' + Date.now();

// ── State ───────────────────────────────────────────────────────────────
let recognition  = null;
let synth        = window.speechSynthesis;
let ttsEnabled   = true;
let continuous   = false;
let isListening  = false;
let isSpeaking   = false;
let isThinking   = false;
let lastNote     = '';
let notes        = JSON.parse(localStorage.getItem('yuga_notes') || '[]');

// ── UI helpers ───────────────────────────────────────────────────────────
const $ = id => document.getElementById(id);
function setStatus(text, cls='') {
  const el = $('status-bar');
  el.textContent = text;
  el.className   = 'status-bar ' + cls;
}
function setMicState(state) {
  const btn  = $('mic-btn');
  const ring = $('mic-ring');
  const wave = $('waveform');
  const hint = $('mic-hint');
  btn.className  = 'mic-btn ' + state;
  ring.className = 'mic-ring ' + (state === 'listening' ? 'listening' : '');
  wave.className = 'waveform ' + (state === 'listening' ? 'active' : '');
  btn.textContent = state === 'listening' ? '⏹' : state === 'speaking' ? '🔊' : '🎤';
  hint.textContent = state === 'listening' ? 'Tap to stop' : state === 'speaking' ? 'Speaking...' : 'Tap to speak';
}

// ── Add message to conversation ──────────────────────────────────────────
function addMsg(role, text, action=null) {
  const conv = $('conv');
  const div  = document.createElement('div');
  div.className = 'msg ' + role;
  let chip = '';
  if (action && action.type !== 'time' && action.type !== 'date') {
    chip = `<div class="action-chip">⚡ ${action.type}${action.url ? ': '+new URL(action.url).hostname : ''}</div>`;
  }
  const avatar = role === 'user' ? '👤' : 'Y';
  div.innerHTML = `
    <div class="avatar ${role==='user'?'user':'ai'}">${role==='user'?'👤':'Y'}</div>
    <div class="bubble ${role==='user'?'user':'ai'}">${escHtml(text)}${chip}</div>`;
  conv.appendChild(div);
  conv.scrollTop = conv.scrollHeight;
}
function escHtml(t) { return t.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

// ── Execute device action ────────────────────────────────────────────────
function executeAction(action) {
  if (!action) return;
  // ── Native Android bridge (APK mode — full OS control) ────────────────
  if (window.YugaBridge) {
    window.YugaBridge.execute(JSON.stringify(action));
    return;
  }
  switch (action.type) {
    case 'time':
      const now = new Date();
      addMsg('ai', 'The current time is ' + now.toLocaleTimeString());
      speak('The current time is ' + now.toLocaleTimeString());
      return true;
    case 'date':
      const today = new Date().toLocaleDateString(undefined,{weekday:'long',year:'numeric',month:'long',day:'numeric'});
      addMsg('ai', 'Today is ' + today);
      speak('Today is ' + today);
      return true;
    case 'open': case 'search': case 'weather': case 'maps':
    case 'youtube': case 'translate':
      if (action.url) window.open(action.url, '_blank');
      break;
    case 'email':
      window.location.href = action.url;
      break;
    case 'call':
      window.location.href = action.url;
      break;
    case 'timer':
      if (action.ms > 0) {
        const label = action.label || 'Timer';
        setTimeout(() => {
          if (Notification.permission === 'granted') {
            new Notification('⏰ ' + label, {body: 'Your timer is done!'});
          }
          speak(label + ' is done!');
          addMsg('ai', '⏰ ' + label + ' — time\'s up!');
        }, action.ms);
        requestNotificationPerm();
      }
      break;
    case 'reminder':
      if (action.ms > 0) {
        setTimeout(() => {
          speak('Reminder: ' + action.text);
          addMsg('ai', '🔔 Reminder: ' + action.text);
          if (Notification.permission === 'granted')
            new Notification('🔔 Reminder', {body: action.text});
        }, action.ms);
        requestNotificationPerm();
      }
      break;
    case 'note':
      notes.push({text: action.text, time: Date.now()});
      localStorage.setItem('yuga_notes', JSON.stringify(notes));
      lastNote = action.text;
      break;
    case 'clipboard':
      navigator.clipboard?.writeText(action.text).catch(()=>{});
      break;
    case 'stop':
      stopListening();
      synth.cancel();
      break;
    case 'volume_hint':
      // Just show the reply, nothing to execute
      break;
    case 'screenshot':
      // Hint only — can't do real screenshots from web
      break;
  }
  return false;
}

function requestNotificationPerm() {
  if (Notification.permission === 'default') Notification.requestPermission();
}

// ── TTS (speak) ──────────────────────────────────────────────────────────
function speak(text) {
  if (!ttsEnabled || !synth || !text) return;
  synth.cancel();
  const utt = new SpeechSynthesisUtterance(text);
  utt.rate  = 1.0;
  utt.pitch = 1.0;
  utt.onstart = () => { isSpeaking = true; setMicState('speaking'); setStatus('Speaking...', 'speaking'); };
  utt.onend   = () => {
    isSpeaking = false;
    setMicState('');
    setStatus('Tap the mic to speak');
    // Auto-restart listening in continuous mode
    if (continuous && !isListening && !isThinking) setTimeout(startListening, 600);
  };
  synth.speak(utt);
}

// ── Speech recognition ───────────────────────────────────────────────────
function setupRecognition() {
  const SpeechRec = window.SpeechRecognition || window.webkitSpeechRecognition;
  if (!SpeechRec) return null;
  const r = new SpeechRec();
  r.continuous     = false;
  r.interimResults = true;
  r.lang           = 'en-US';

  r.onstart = () => {
    isListening = true;
    setMicState('listening');
    setStatus('Listening...', 'listening');
  };
  r.onresult = (e) => {
    let interim = '', final = '';
    for (let i = e.resultIndex; i < e.results.length; i++) {
      if (e.results[i].isFinal) final += e.results[i][0].transcript;
      else interim += e.results[i][0].transcript;
    }
    if (interim) setStatus('Heard: ' + interim.slice(0,60), 'listening');
    if (final)   handleInput(final.trim());
  };
  r.onerror = (e) => {
    isListening = false;
    setMicState('');
    if (e.error === 'not-allowed') {
      $('perm-notice').classList.add('show');
      setStatus('Microphone blocked — please allow access', '');
    } else {
      setStatus('Tap the mic to speak');
    }
    if (continuous && !isSpeaking) setTimeout(startListening, 1200);
  };
  r.onend = () => {
    isListening = false;
    if (!isThinking && !isSpeaking) {
      setMicState('');
      setStatus('Tap the mic to speak');
      if (continuous) setTimeout(startListening, 600);
    }
  };
  return r;
}

function startListening() {
  if (isListening || isSpeaking || isThinking) return;
  if (!recognition) recognition = setupRecognition();
  if (!recognition) { setStatus('Speech recognition not supported. Use text input.'); return; }
  try { recognition.start(); } catch(e) {}
}
function stopListening() {
  if (recognition && isListening) { try { recognition.stop(); } catch(e) {} }
  isListening = false;
  setMicState('');
  setStatus('Tap the mic to speak');
}
function toggleListen() {
  if (isSpeaking) { synth.cancel(); isSpeaking = false; }
  isListening ? stopListening() : startListening();
}

// ── Send to API ──────────────────────────────────────────────────────────
async function handleInput(text) {
  if (!text) return;
  isThinking = true;
  setMicState('');
  addMsg('user', text);
  setStatus('Thinking...', 'thinking');

  try {
    const headers = {'Content-Type':'application/json'};
    if (AKEY) headers['X-API-Key'] = AKEY;

    const res  = await fetch(API + '?action=command', {
      method:  'POST',
      headers: headers,
      body:    JSON.stringify({model: MODEL, message: text, session_id: SESSION}),
    });
    const data = await res.json();

    if (data.ok) {
      const reply  = data.reply || 'Done.';
      const action = data.action || null;

      // Handle time/date actions (reply comes from JS, not API)
      if (action && (action.type === 'time' || action.type === 'date')) {
        executeAction(action);
      } else {
        // Add AI reply bubble
        addMsg('ai', reply, action);
        // Execute device action
        if (action) executeAction(action);
        // Speak the reply
        speak(reply);
      }
    } else {
      const err = data.error || 'Something went wrong.';
      addMsg('ai', err);
      speak(err);
    }
  } catch(e) {
    const err = 'Network error — check your connection.';
    addMsg('ai', err);
    speak(err);
  }

  isThinking = false;
  setStatus('Tap the mic to speak');
  if (!isSpeaking && continuous) setTimeout(startListening, 800);
}

// ── Text input ───────────────────────────────────────────────────────────
function sendText() {
  const inp = $('text-in');
  const txt = inp.value.trim();
  if (!txt) return;
  inp.value = '';
  handleInput(txt);
}

// ── Controls ─────────────────────────────────────────────────────────────
function toggleTTS() {
  ttsEnabled = !ttsEnabled;
  const btn  = $('btn-tts');
  btn.textContent = ttsEnabled ? '🔊' : '🔇';
  btn.classList.toggle('on', ttsEnabled);
  if (!ttsEnabled) synth.cancel();
}
function toggleContinuous() {
  continuous = !continuous;
  const tog  = $('continuous-toggle');
  tog.classList.toggle('on', continuous);
  $('continuous-label').textContent = continuous ? 'On' : 'Off';
  if (continuous) startListening();
  else { stopListening(); }
}
function clearChat() {
  const conv = $('conv');
  conv.innerHTML = '<div class="msg"><div class="avatar ai">Y</div><div class="bubble ai">Chat cleared. How can I help?</div></div>';
  SESSION = 'voice_' + Date.now(); // new session
}

// ── PWA service worker ────────────────────────────────────────────────────
if ('serviceWorker' in navigator) {
  navigator.serviceWorker.register('./sw.js').then(reg => {
    // Listen for background sync reply
    navigator.serviceWorker.addEventListener('message', e => {
      if (e.data?.type === 'sync' && offlineQueue.length) flushQueue();
    });
  });
}

// ── PWA install prompt ────────────────────────────────────────────────────
let deferredInstall = null;
window.addEventListener('beforeinstallprompt', e => {
  e.preventDefault();
  deferredInstall = e;
  const banner = document.getElementById('install-banner');
  if (banner) banner.style.display = 'flex';
});
window.addEventListener('appinstalled', () => {
  const banner = document.getElementById('install-banner');
  if (banner) banner.style.display = 'none';
  addMsg('ai', 'Yuga is now installed on your device. You can open it from your home screen.');
});
function installPWA() {
  if (!deferredInstall) return;
  deferredInstall.prompt();
  deferredInstall.userChoice.then(r => {
    if (r.outcome === 'accepted') document.getElementById('install-banner').style.display = 'none';
    deferredInstall = null;
  });
}

// ── Offline detection + message queue ─────────────────────────────────────
let offlineQueue = [];
const offlineBar = document.getElementById('offline-bar');
function updateOnlineStatus() {
  if (offlineBar) offlineBar.style.display = navigator.onLine ? 'none' : 'block';
}
window.addEventListener('online',  () => { updateOnlineStatus(); flushQueue(); });
window.addEventListener('offline', updateOnlineStatus);
updateOnlineStatus();

async function flushQueue() {
  while (offlineQueue.length) {
    const item = offlineQueue.shift();
    try {
      const r = await fetch(item.url, item.options);
      const d = await r.json();
      if (d.reply || d.answer) {
        addMsg('ai', d.reply || d.answer);
        if (ttsEnabled) speak(d.reply || d.answer);
      }
    } catch(e) { offlineQueue.unshift(item); break; }
  }
}

// ── Init ─────────────────────────────────────────────────────────────────
recognition = setupRecognition();
if (!recognition) {
  $('mic-btn').style.opacity = '.4';
  setStatus('Type below — voice not supported in this browser');
}
// Set TTS button state
$('btn-tts').classList.toggle('on', ttsEnabled);
// Request notifications early (for timers)
if ('Notification' in window && Notification.permission === 'default') {
  Notification.requestPermission();
}
</script>
</body>
</html>
