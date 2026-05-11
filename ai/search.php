<?php
/**
 * YGXONE Search — Google-style Search Results
 * Fixed: API key no longer exposed to client (uses search_api.php proxy).
 */
define('YUGA_ROOT', __DIR__);
require_once YUGA_ROOT . '/core/SEO.php';
session_start();

$config = file_exists(YUGA_ROOT . '/config.php') ? require YUGA_ROOT . '/config.php' : [];
$seo    = new SEO($config, 'search');

$query  = trim($_GET['q'] ?? '');
$tab    = in_array($_GET['tab'] ?? 'all', ['all','news','images','videos','maps']) ? ($_GET['tab'] ?? 'all') : 'all';
$page   = max(1, (int) ($_GET['p'] ?? 1));

$appName    = $config['platform_name'] ?? 'YGXONE';
$accountUrl = $config['yg_account_url'] ?? 'https://account.ygxone.com';
$driveUrl   = $config['yg_drive_url']   ?? '#';
$mailUrl    = $config['yg_mail_url']    ?? '#';
$aiEnabled  = (bool) ($config['ai_enabled'] ?? true);

// Build canonical URL for proxy calls (same origin, no leaking key)
$_script  = rtrim(str_replace(['/search.php'], '', $_SERVER['SCRIPT_NAME'] ?? ''), '/');
$proxyUrl = '/search_api.php';
$suggestUrl = '/suggest.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $query ? htmlspecialchars($query) . ' - YGXONE Search' : 'YGXONE Search' ?></title>
<meta name="description" content="Search results for: <?= htmlspecialchars($query) ?>">
<?= $seo->head() ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  :root {
    --crimson:   #9B1B30;
    --blue:      #1a73e8;
    --text:      #f5f5f5;
    --muted:     #888888;
    --border:    rgba(255, 255, 255, 0.1);
    --bg:        #0c0c0c;
    --card:      rgba(255, 255, 255, 0.03);
    --font:      'Outfit', sans-serif;
  }

  html, body { background: var(--bg); color: var(--text); font-family: var(--font); -webkit-font-smoothing: antialiased; }

  /* ── Premium Top Bar ── */
  .topbar {
    position: sticky;
    top: 0;
    background: rgba(12, 12, 12, 0.8);
    backdrop-filter: blur(20px);
    border-bottom: 1px solid var(--border);
    z-index: 200;
    padding: 12px 24px;
  }
  .topbar-row1 {
    display: flex;
    align-items: center;
    gap: 32px;
    max-width: 1400px;
    margin: 0 auto;
  }
  .logo-link {
    font-size: 20px;
    font-weight: 800;
    text-decoration: none;
    letter-spacing: -1px;
    color: white;
  }
  .logo-link span { color: var(--crimson); }

  .top-search-bar {
    display: flex;
    align-items: center;
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: 20px;
    height: 48px;
    padding: 0 20px;
    flex: 1;
    max-width: 700px;
    transition: all 0.3s;
  }
  .top-search-bar:focus-within {
    border-color: var(--crimson);
    background: rgba(255, 255, 255, 0.05);
    box-shadow: 0 0 30px rgba(155, 27, 48, 0.1);
  }
  .top-search-input {
    flex: 1;
    border: none;
    outline: none;
    font-size: 15px;
    color: white;
    background: transparent;
    padding: 0 12px;
  }

  /* ── Results Layout ── */
  .content-wrap {
    display: flex;
    max-width: 1400px;
    margin: 0 auto;
    padding: 40px 24px;
    gap: 60px;
  }
  .main-col { flex: 1; min-width: 0; max-width: 800px; }
  .sidebar { flex: 0 0 350px; }

  /* ── AI Intelligence Panel ── */
  .ai-overview {
    background: linear-gradient(135deg, rgba(155, 27, 48, 0.05) 0%, rgba(255, 255, 255, 0.02) 100%);
    border: 1px solid rgba(155, 27, 48, 0.2);
    border-radius: 24px;
    padding: 32px;
    margin-bottom: 48px;
    position: relative;
    overflow: hidden;
  }
  .ai-overview::before {
    content: '';
    position: absolute;
    top: -50%; left: -50%;
    width: 200%; height: 200%;
    background: radial-gradient(circle at center, rgba(155, 27, 48, 0.1) 0%, transparent 70%);
    pointer-events: none;
  }
  .ai-overview-title {
    display: flex; align-items: center; gap: 10px;
    font-size: 16px; font-weight: 700; color: var(--crimson);
    margin-bottom: 20px;
    text-transform: uppercase;
    letter-spacing: 2px;
  }
  .ai-body {
    font-size: 18px;
    line-height: 1.8;
    color: #e0e0e0;
    font-weight: 300;
  }
  
  /* ── Result Cards ── */
  .result-item {
    padding: 24px;
    border-radius: 20px;
    background: transparent;
    border: 1px solid transparent;
    transition: all 0.3s;
    margin-bottom: 12px;
  }
  .result-item:hover {
    background: var(--card);
    border-color: var(--border);
    transform: translateX(10px);
  }
  .result-breadcrumb {
    display: flex; align-items: center; gap: 8px;
    font-size: 12px; color: var(--muted);
    margin-bottom: 8px;
    text-transform: uppercase;
    letter-spacing: 1px;
  }
  .result-title {
    font-size: 22px;
    font-weight: 600;
    color: white;
    text-decoration: none;
    margin-bottom: 8px;
    display: block;
  }
  .result-title:hover { color: var(--crimson); }
  .result-snippet {
    font-size: 15px;
    color: #aaaaaa;
    line-height: 1.6;
    font-weight: 300;
  }

  /* ── Knowledge Panel ── */
  .kp-card {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: 24px;
    padding: 28px;
    position: sticky;
    top: 100px;
  }
  .kp-title { font-size: 28px; font-weight: 800; margin-bottom: 8px; color: white; }
  .kp-sub { font-size: 14px; color: var(--crimson); font-weight: 600; margin-bottom: 20px; text-transform: uppercase; }
  .kp-row { margin-bottom: 16px; font-size: 14px; }
  .kp-label { color: var(--muted); margin-bottom: 4px; display: block; font-size: 11px; text-transform: uppercase; font-weight: 700; }
  .kp-val { color: #dddddd; line-height: 1.5; }

  /* ── Animations ── */
  @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
  .fade-in { animation: fadeIn 0.8s cubic-bezier(0.4, 0, 0.2, 1) forwards; }
</style>
</head>
<body>

<!-- ── Premium Top Bar ─────────────────────────────────────────── -->
<header class="topbar">
  <div class="topbar-row1">
    <a href="home.php" class="logo-link">
      YGXONE<span>AI</span>
    </a>

    <form class="top-search-wrap" id="search-form" action="search.php" method="GET" autocomplete="off" style="flex:1">
      <div class="top-search-bar">
        <svg class="w-5 h-5 text-gray-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        <input
          type="text" name="q" id="q"
          class="top-search-input"
          value="<?= htmlspecialchars($query) ?>"
          placeholder="Ask anything..."
          spellcheck="false"
          autofocus
        >
      </div>
    </form>
    
    <div class="flex items-center gap-4">
        <div class="w-10 h-10 rounded-full glass border border-white/10 flex items-center justify-center text-xs font-bold">JD</div>
    </div>
  </div>
</header>

<!-- ── Main Content ────────────────────────────────────────── -->
<div class="content-wrap">
  <main class="main-col">

    <!-- AI Intelligence Overview -->
    <section class="ai-overview fade-in" id="ai-overview">
      <div class="ai-overview-title">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
        Sovereign Intelligence Analysis
      </div>
      <div class="ai-body" id="ai-body">
          <div class="flex gap-2">
              <div class="w-2 h-2 bg-crimson rounded-full animate-bounce"></div>
              <div class="w-2 h-2 bg-crimson rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
              <div class="w-2 h-2 bg-crimson rounded-full animate-bounce" style="animation-delay: 0.4s"></div>
          </div>
      </div>
      <div class="ai-sources mt-6 pt-6 border-t border-white/5 flex flex-wrap gap-3" id="ai-sources"></div>
    </section>

    <!-- Results Container -->
    <div id="results-container" class="space-y-4"></div>

    <!-- Related & Pagination -->
    <section class="mt-20 border-t border-white/5 pt-10">
        <nav class="pagination flex justify-center gap-2" id="pagination"></nav>
    </section>

  </main>

  <!-- Sidebar Knowledge Panel -->
  <aside class="sidebar fade-in" style="animation-delay: 0.3s">
    <div class="kp-card" id="kp-card" style="display:none">
      <div class="kp-title" id="kp-title"></div>
      <div class="kp-sub" id="kp-sub"></div>
      <div id="kp-rows"></div>
    </div>
    
    <!-- Ecosystem Quick Access -->
    <div class="mt-8 glass rounded-3xl p-6 border border-white/5">
        <h4 class="text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-6">Ecosystem</h4>
        <div class="grid grid-cols-2 gap-4">
            <a href="#" class="flex flex-col items-center gap-2 p-4 rounded-2xl hover:bg-white/5 transition">
                <span class="text-2xl">📧</span>
                <span class="text-[10px] font-bold uppercase">Mail</span>
            </a>
            <a href="#" class="flex flex-col items-center gap-2 p-4 rounded-2xl hover:bg-white/5 transition">
                <span class="text-2xl">📁</span>
                <span class="text-[10px] font-bold uppercase">Drive</span>
            </a>
        </div>
    </div>
  </aside>
</div>

<script>
// ── Configuration (NO API KEY HERE) ───────────────────────────────────────
const PROXY_URL   = <?= json_encode($proxyUrl) ?>;
const SUGGEST_URL = <?= json_encode($suggestUrl) ?>;
const INITIAL_QUERY = <?= json_encode($query) ?>;
const INITIAL_TAB   = <?= json_encode($tab) ?>;
const INITIAL_PAGE  = <?= json_encode($page) ?>;
const AI_ENABLED    = <?= json_encode($aiEnabled) ?>; // controlled by YUGA_AI_ENABLED in .env

// ── State ─────────────────────────────────────────────────────────────────
let currentResults = [];
let aiCollapsed = false;

// ── On load ───────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  if (INITIAL_QUERY.trim().length >= 2) {
    performSearch(INITIAL_QUERY, INITIAL_TAB, INITIAL_PAGE);
  }
});

// ── Autocomplete ──────────────────────────────────────────────────────────
const qInput  = document.getElementById('q');
const acList  = document.getElementById('ac-list');
let acItems   = [], acIdx = -1, acTimer;

qInput.addEventListener('input', () => {
  clearTimeout(acTimer);
  const v = qInput.value.trim();
  if (v.length < 2) { hideAC(); return; }
  acTimer = setTimeout(() => loadSuggestions(v), 200);
});

qInput.addEventListener('keydown', e => {
  if (acList.style.display === 'none' || !acList.style.display) return;
  if (e.key === 'ArrowDown')  { e.preventDefault(); moveAC(1); }
  if (e.key === 'ArrowUp')    { e.preventDefault(); moveAC(-1); }
  if (e.key === 'Escape')     { hideAC(); }
  if (e.key === 'Enter' && acIdx >= 0) {
    e.preventDefault();
    qInput.value = acItems[acIdx];
    hideAC();
    doSearch();
  }
});

document.addEventListener('click', e => {
  if (!e.target.closest('#search-form')) hideAC();
});

async function loadSuggestions(q) {
  try {
    const res = await fetch(`${SUGGEST_URL}?q=${encodeURIComponent(q)}`);
    const data = await res.json();
    renderAC(data);
  } catch {}
}

function renderAC(items) {
  if (!items.length) { hideAC(); return; }
  acItems = items; acIdx = -1;
  const svgIcon = `<svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>`;
  acList.innerHTML = items.map((s, i) =>
    `<div class="ac-item" data-i="${i}" onmousedown="selectAC(${i})">${svgIcon} ${esc(s)}</div>`
  ).join('');
  acList.style.display = 'block';
}

function moveAC(dir) {
  const nodes = acList.querySelectorAll('.ac-item');
  if (acIdx >= 0 && nodes[acIdx]) nodes[acIdx].classList.remove('active');
  acIdx = Math.max(-1, Math.min(acIdx + dir, acItems.length - 1));
  if (acIdx >= 0) { nodes[acIdx].classList.add('active'); qInput.value = acItems[acIdx]; }
}

function selectAC(i) { qInput.value = acItems[i]; hideAC(); doSearch(); }
function hideAC()     { acList.style.display = 'none'; acIdx = -1; }

// ── Search ────────────────────────────────────────────────────────────────
function doSearch() {
  const q = qInput.value.trim();
  if (q.length < 2) return;
  const tab = document.getElementById('tab-input').value;
  const url = new URL(window.location.href);
  url.searchParams.set('q', q);
  url.searchParams.set('tab', tab);
  url.searchParams.set('p', '1');
  window.location.href = url.toString();
}

function clearSearch() {
  qInput.value = '';
  qInput.focus();
}

async function performSearch(q, tab, page) {
  showSkeletons();

  try {
    const res = await fetch(PROXY_URL, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ query: q, tab, page, action: 'web_search' })
    });

    const data = await res.json();
    if (!data.ok) {
      showError(data.error || 'Intelligence failure.', q);
      return;
    }

    renderResults(data, q, tab, page);

  } catch (err) {
    showError('Connectivity lost: ' + err.message, q);
  }
}

function showSkeletons() {
  document.getElementById('results-container').innerHTML = [1,2,3].map(() => `
    <div class="result-item">
      <div class="skeleton h-3 w-32 mb-4 bg-white/5 rounded"></div>
      <div class="skeleton h-6 w-3/4 mb-4 bg-white/5 rounded"></div>
      <div class="skeleton h-4 w-full mb-2 bg-white/5 rounded"></div>
      <div class="skeleton h-4 w-2/3 bg-white/5 rounded"></div>
    </div>
  `).join('');
}

function renderResults(data, q, tab, page) {
  const sources = data.sources || [];
  const answer  = data.answer  || '';

  // ── AI Overview with Typing Effect ──
  const aiBody = document.getElementById('ai-body');
  if (answer) {
      typeWriter(aiBody, answer);
      
      const chipContainer = document.getElementById('ai-sources');
      chipContainer.innerHTML = sources.slice(0, 3).map(s => `
          <a href="${s.url}" target="_blank" class="glass px-4 py-2 rounded-xl text-[10px] uppercase font-bold text-gray-400 hover:bg-white/10 transition flex items-center gap-2">
            <img src="https://www.google.com/s2/favicons?domain=${domain(s.url)}&sz=16" class="w-3 h-3">
            ${domain(s.url)}
          </a>
      `).join('');
  }

  // ── Result Cards ──
  const container = document.getElementById('results-container');
  container.innerHTML = sources.map((s, i) => `
    <div class="result-item fade-in" style="animation-delay: ${i * 0.1}s">
      <div class="result-breadcrumb">
        <img class="w-3 h-3 rounded-sm" src="https://www.google.com/s2/favicons?domain=${domain(s.url)}&sz=16" onerror="this.style.display='none'">
        <span>${domain(s.url)}</span>
      </div>
      <a href="${s.url}" class="result-title" target="_blank">${s.title || domain(s.url)}</a>
      <div class="result-snippet">${s.snippet || ''}</div>
    </div>
  `).join('');

  renderPagination(page, sources.length >= 8, q, tab);
}

function typeWriter(element, text) {
    element.innerHTML = '';
    let i = 0;
    const speed = 15;
    
    function type() {
        if (i < text.length) {
            element.innerHTML += text.charAt(i);
            i++;
            setTimeout(type, speed);
        }
    }
    type();
}

function domain(url) {
    try { return new URL(url).hostname.replace('www.', ''); } catch { return url; }
}

function renderPagination(currentPage, hasMore, q, tab) {
  const container = document.getElementById('pagination');
  const pages = [];
  if (currentPage > 1) pages.push(`<a class="glass px-4 py-2 rounded-xl text-xs" href="search.php?q=${encodeURIComponent(q)}&p=${currentPage - 1}">Prev</a>`);
  if (hasMore) pages.push(`<a class="bg-crimson px-4 py-2 rounded-xl text-xs font-bold" href="search.php?q=${encodeURIComponent(q)}&p=${currentPage + 1}">Next Intelligence</a>`);
  container.innerHTML = pages.join('');
}

function showError(msg, q) {
  document.getElementById('results-container').innerHTML = `<div class="p-8 glass rounded-3xl border-crimson text-center">${msg}</div>`;
}

// ── Helpers ──
function esc(t) { return t.replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m])); }

// ── AI Toggle ─────────────────────────────────────────────────────────────
function toggleAI() {
  const body = document.getElementById('ai-body');
  const btn  = document.getElementById('ai-toggle');
  aiCollapsed = !aiCollapsed;
  body.style.maxHeight = aiCollapsed ? '0' : '';
  body.style.overflow  = aiCollapsed ? 'hidden' : '';
  btn.textContent = aiCollapsed ? 'Expand ▼' : 'Collapse ▲';
}

// ── Helpers ───────────────────────────────────────────────────────────────
function showError(msg, q) {
  const statsBar = document.getElementById('stats-bar');
  statsBar.textContent = '';
  statsBar.classList.add('error');
  document.getElementById('results-container').innerHTML =
    `<div class="no-results"><strong>Error:</strong> ${esc(msg)}<br><br>
     Your search index may be empty. <a href="admin/" style="color:var(--crimson)">Go to Admin → Deep Crawl</a> to seed results.</div>`;
}

function formatAnswer(text) {
  if (!text) return '';
  // Convert [1], [2] citation markers
  text = text.replace(/\[(\d+)\]/g, '<span class="ai-cite">[$1]</span>');
  // Bold **text**
  text = text.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
  return text.split('\n')
    .filter(p => p.trim())
    .map(p => `<p>${p}</p>`)
    .join('');
}

function domain(url) {
  try { return new URL(url).hostname.replace(/^www\./, ''); } catch { return url; }
}

function breadcrumb(url) {
  try {
    const u = new URL(url);
    const parts = u.pathname.replace(/^\/|\/$/g, '').split('/').filter(Boolean);
    return parts.slice(0, 3).join(' › ');
  } catch { return ''; }
}

function esc(str) {
  if (!str) return '';
  return String(str)
    .replace(/&/g,'&amp;').replace(/</g,'&lt;')
    .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── Voice Search ──────────────────────────────────────────────────────────
function startVoice() {
  const SR = window.SpeechRecognition || window.webkitSpeechRecognition;
  if (!SR) { alert('Voice search is not supported in your browser.'); return; }
  const r = new SR();
  r.lang = 'en-US';
  r.onresult = e => {
    qInput.value = e.results[0][0].transcript;
    doSearch();
  };
  r.onerror = () => {};
  r.start();
}
</script>
</body>
</html>
