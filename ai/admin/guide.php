<?php
define('YUGA_ROOT', dirname(__DIR__));
$config = file_exists(YUGA_ROOT.'/config.php') ? require YUGA_ROOT.'/config.php' : [];
session_start();

// Require admin session
$admin_pw = $config['admin_password'] ?? '';
if ($admin_pw && !($_SESSION['yuga_admin'] ?? false)) {
    header('Location: index.php');
    exit;
}
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Yuga Admin — Knowledge Base</title>
<style>
:root{
  --bg:#030712;--bg2:#080e1c;--bg3:#0a1020;--bg4:#0f1929;
  --bd:rgba(255,255,255,.07);--bd2:rgba(255,255,255,.13);
  --tx:#f1f5f9;--mu:#8b9ab5;--di:#445168;
  --pu:#6366f1;--vi:#8b5cf6;--cy:#06b6d4;--te:#14b8a6;
  --am:#f59e0b;--gr:#10b981;--re:#f43f5e;
  --pl:#a5b4fc;--cv:#c4b5fd;--cg:#67e8f9;
}
*{box-sizing:border-box;margin:0;padding:0}
html{scroll-behavior:smooth}
body{font-family:-apple-system,BlinkMacSystemFont,'Inter',system-ui,sans-serif;background:var(--bg);color:var(--tx);display:flex;min-height:100vh;
  background-image:radial-gradient(ellipse 60% 50% at 0% 0%,rgba(99,102,241,.09) 0%,transparent 55%)}
a{color:var(--pl);text-decoration:none;transition:.2s}
a:hover{color:var(--cv)}
h1,h2,h3,h4{letter-spacing:-.3px}
code{font-family:'SF Mono','Cascadia Code',monospace;background:rgba(99,102,241,.12);border:1px solid rgba(99,102,241,.2);color:var(--pl);padding:1px 7px;border-radius:5px;font-size:.9em}
pre{background:rgba(3,7,18,.9);border:1px solid rgba(255,255,255,.09);border-radius:14px;padding:20px 24px;font-family:monospace;font-size:13px;color:#e2e8f0;line-height:1.85;overflow-x:auto;margin:16px 0}
pre code{background:none;border:none;padding:0;color:inherit;font-size:inherit}
strong{color:var(--tx);font-weight:700}
em{color:var(--mu)}

/* Sidebar */
.sidebar{width:270px;flex-shrink:0;background:rgba(8,14,28,.96);border-right:1px solid var(--bd);
  height:100vh;position:sticky;top:0;overflow-y:auto;backdrop-filter:blur(20px)}
.sidebar::-webkit-scrollbar{width:3px}
.sidebar::-webkit-scrollbar-thumb{background:rgba(255,255,255,.1);border-radius:3px}
.sb-head{padding:22px 22px 16px;border-bottom:1px solid var(--bd)}
.sb-logo{display:flex;align-items:center;gap:10px;margin-bottom:14px}
.lm{width:32px;height:32px;background:linear-gradient(135deg,var(--pu),var(--vi));border-radius:9px;
  display:flex;align-items:center;justify-content:center;color:#fff;font-weight:900;font-size:15px;
  box-shadow:0 0 16px rgba(99,102,241,.4);flex-shrink:0}
.sb-head h2{font-size:15px;font-weight:800;margin-bottom:2px}
.sb-head p{font-size:11px;color:var(--di)}
.sb-search{width:100%;background:rgba(255,255,255,.05);border:1px solid var(--bd2);border-radius:8px;
  padding:8px 12px;color:var(--tx);font-size:13px;outline:none;font-family:inherit;margin-top:12px}
.sb-search:focus{border-color:var(--pu)}
.sb-section{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.14em;
  color:var(--di);padding:14px 22px 5px;margin-top:4px}
.sb-link{display:flex;align-items:center;gap:8px;padding:8px 22px;font-size:13px;color:var(--mu);
  transition:.2s;font-weight:500;border-left:2px solid transparent}
.sb-link:hover{color:var(--tx);background:rgba(255,255,255,.05);border-left-color:var(--bd2)}
.sb-link.active{color:var(--pl);background:rgba(99,102,241,.1);border-left-color:var(--pu)}
.sb-link .num{font-size:10px;font-weight:700;color:var(--di);margin-left:auto;
  background:rgba(255,255,255,.06);padding:1px 7px;border-radius:100px}
.sb-footer{padding:16px 22px;border-top:1px solid var(--bd);margin-top:auto}
.sb-footer a{font-size:12px;color:var(--di);display:flex;align-items:center;gap:6px}
.sb-footer a:hover{color:var(--pl)}

/* Main content */
.main{flex:1;min-width:0;overflow-y:auto}
.topbar{background:rgba(8,14,28,.9);backdrop-filter:blur(16px);border-bottom:1px solid var(--bd);
  padding:14px 48px;display:flex;align-items:center;gap:12px;position:sticky;top:0;z-index:10}
.tb-title{font-size:15px;font-weight:700;color:var(--tx)}
.tb-right{margin-left:auto;display:flex;gap:8px}
.badge{font-size:11px;padding:4px 11px;border-radius:100px;font-weight:600}
.bg-pu{background:rgba(99,102,241,.18);color:var(--pl)}
.content{max-width:860px;margin:0 auto;padding:48px 48px 80px}

/* Chapter / section styles */
.chapter{margin-bottom:72px;scroll-margin-top:80px}
.chapter-header{display:flex;align-items:center;gap:14px;margin-bottom:32px;padding-bottom:18px;
  border-bottom:1px solid rgba(255,255,255,.07);position:relative}
.chapter-header::after{content:'';position:absolute;bottom:0;left:0;width:60px;height:2px;
  background:linear-gradient(90deg,var(--pu),var(--vi))}
.ch-num{width:40px;height:40px;border-radius:12px;background:linear-gradient(135deg,var(--pu),var(--vi));
  display:flex;align-items:center;justify-content:center;color:#fff;font-size:16px;font-weight:900;flex-shrink:0;
  box-shadow:0 4px 14px rgba(99,102,241,.35)}
.ch-meta{flex:1}
.ch-meta h2{font-size:26px;font-weight:800;line-height:1.2}
.ch-meta p{font-size:13px;color:var(--mu);margin-top:4px}

.section{margin-bottom:40px;scroll-margin-top:80px}
.section h3{font-size:18px;font-weight:700;margin-bottom:14px;color:var(--tx)}
.section h4{font-size:14px;font-weight:700;margin-bottom:10px;color:var(--pl);text-transform:uppercase;letter-spacing:.05em}
.section p{font-size:14px;color:var(--mu);line-height:1.78;margin-bottom:12px}
.section ul,.section ol{padding-left:20px;display:flex;flex-direction:column;gap:8px;margin-bottom:14px}
.section li{font-size:14px;color:var(--mu);line-height:1.6}
.section li::marker{color:var(--pu)}

/* Callout boxes */
.callout{border-radius:14px;padding:18px 22px;margin:20px 0;display:flex;gap:14px}
.callout .ico{font-size:18px;flex-shrink:0;margin-top:1px}
.callout .body{flex:1}
.callout .body h4{font-size:13px;font-weight:700;margin-bottom:6px;text-transform:uppercase;letter-spacing:.04em}
.callout .body p{font-size:13px;line-height:1.7;margin:0}
.callout.tip{background:rgba(16,185,129,.09);border:1px solid rgba(16,185,129,.22)}
.callout.tip .ico{color:var(--gr)}.callout.tip .body h4{color:#6ee7b7}.callout.tip .body p{color:var(--mu)}
.callout.warn{background:rgba(245,158,11,.08);border:1px solid rgba(245,158,11,.22)}
.callout.warn .ico{color:var(--am)}.callout.warn .body h4{color:#fcd34d}.callout.warn .body p{color:var(--mu)}
.callout.info{background:rgba(99,102,241,.09);border:1px solid rgba(99,102,241,.22)}
.callout.info .ico{color:var(--pl)}.callout.info .body h4{color:var(--pl)}.callout.info .body p{color:var(--mu)}
.callout.danger{background:rgba(244,63,94,.08);border:1px solid rgba(244,63,94,.22)}
.callout.danger .ico{color:var(--re)}.callout.danger .body h4{color:#fda4af}.callout.danger .body p{color:var(--mu)}

/* Step list */
.steps{display:flex;flex-direction:column;gap:16px;margin:20px 0}
.step{display:flex;gap:16px}
.step-num{width:28px;height:28px;border-radius:50%;background:linear-gradient(135deg,var(--pu),var(--vi));
  display:flex;align-items:center;justify-content:center;color:#fff;font-size:13px;font-weight:700;
  flex-shrink:0;margin-top:2px;box-shadow:0 2px 10px rgba(99,102,241,.3)}
.step-body{flex:1}
.step-body h4{font-size:14px;font-weight:700;margin-bottom:6px;color:var(--tx)}
.step-body p{font-size:14px;color:var(--mu);line-height:1.65;margin:0}

/* Table */
.guide-table{width:100%;border-collapse:collapse;font-size:13px;margin:16px 0;border-radius:12px;overflow:hidden}
.guide-table th{background:rgba(99,102,241,.14);color:var(--pl);font-size:11px;font-weight:700;
  text-transform:uppercase;letter-spacing:.07em;padding:10px 14px;text-align:left}
.guide-table td{padding:11px 14px;border-bottom:1px solid rgba(255,255,255,.05);color:var(--mu);vertical-align:top}
.guide-table tr:last-child td{border-bottom:none}
.guide-table tr:hover td{background:rgba(255,255,255,.025)}
.guide-table td:first-child{color:var(--tx);font-weight:500}

/* Responsive */
@media(max-width:900px){.sidebar{display:none}.content{padding:28px 20px 60px}}
</style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
  <div class="sb-head">
    <div class="sb-logo">
      <div class="lm">Y</div>
      <div>
        <div class="sb-head" style="padding:0;border:none"><h2 style="font-size:15px;font-weight:800;margin:0">Yuga Admin</h2><p style="font-size:11px;color:var(--di);margin:0">Knowledge Base</p></div>
      </div>
    </div>
    <input class="sb-search" type="text" placeholder="Search guide…" oninput="filterGuide(this.value)">
  </div>

  <div class="sb-section">Getting Started</div>
  <a class="sb-link" href="#ch1">&#9654; Installation & first boot</a>
  <a class="sb-link" href="#ch2">&#9654; Admin panel overview</a>

  <div class="sb-section">Models</div>
  <a class="sb-link" href="#ch3">&#9654; Creating a model</a>
  <a class="sb-link" href="#ch4">&#9654; Training your model</a>
  <a class="sb-link" href="#ch5">&#9654; Understanding training metrics</a>
  <a class="sb-link" href="#ch6">&#9654; Deployment & testing</a>

  <div class="sb-section">Subscriptions & Revenue</div>
  <a class="sb-link" href="#ch7">&#9654; API key management</a>
  <a class="sb-link" href="#ch8">&#9654; Plans & billing</a>
  <a class="sb-link" href="#ch9">&#9654; Payment gateways</a>

  <div class="sb-section">Configuration</div>
  <a class="sb-link" href="#ch10">&#9654; Platform settings</a>
  <a class="sb-link" href="#ch11">&#9654; SMTP & email</a>
  <a class="sb-link" href="#ch12">&#9654; AI backends</a>
  <a class="sb-link" href="#ch13">&#9654; Web search</a>

  <div class="sb-section">Advanced</div>
  <a class="sb-link" href="#ch14">&#9654; White-labeling</a>
  <a class="sb-link" href="#ch15">&#9654; Version history & A/B testing</a>
  <a class="sb-link" href="#ch16">&#9654; Model export & import</a>
  <a class="sb-link" href="#ch17">&#9654; Webhooks & automations</a>
  <a class="sb-link" href="#ch18">&#9654; Scheduler & cron</a>
  <a class="sb-link" href="#ch19">&#9654; Integrations</a>

  <div class="sb-section">Messaging & Content</div>
  <a class="sb-link" href="#ch23">&#9654; Email templates</a>
  <a class="sb-link" href="#ch24">&#9654; Push notifications</a>
  <a class="sb-link" href="#ch25">&#9654; Blog manager</a>

  <div class="sb-section">Support</div>
  <a class="sb-link" href="#ch26">&#9654; Support tickets</a>
  <a class="sb-link" href="#ch27">&#9654; Contact inbox</a>

  <div class="sb-section">Automation</div>
  <a class="sb-link" href="#ch28">&#9654; Nepali pipeline</a>
  <a class="sb-link" href="#ch29">&#9654; Workflows</a>

  <div class="sb-section">Operations</div>
  <a class="sb-link" href="#ch20">&#9654; Security hardening</a>
  <a class="sb-link" href="#ch21">&#9654; Troubleshooting</a>
  <a class="sb-link" href="#ch22">&#9654; Upgrading Yuga</a>

  <div class="sb-footer">
    <a href="index.php">&#8592; Back to admin panel</a>
  </div>
</div>

<!-- Main -->
<div class="main">
  <div class="topbar">
    <div class="tb-title">Knowledge Base</div>
    <div class="tb-right">
      <span class="badge bg-pu">29 chapters</span>
      <a href="index.php" style="font-size:13px;color:var(--mu);padding:6px 14px;background:rgba(255,255,255,.06);border-radius:8px;border:1px solid var(--bd2)">&#8592; Admin</a>
    </div>
  </div>

  <div class="content" id="guide-content">

    <!-- INTRO -->
    <div style="margin-bottom:52px;padding:32px;background:linear-gradient(135deg,rgba(99,102,241,.1),rgba(139,92,246,.07));border:1px solid rgba(99,102,241,.25);border-radius:22px">
      <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.12em;color:var(--pu);margin-bottom:10px">Complete operational guide</div>
      <h1 style="font-size:32px;font-weight:900;letter-spacing:-.8px;margin-bottom:12px;background:linear-gradient(135deg,var(--tx),var(--pl));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text">Yuga v1.0 — Admin Knowledge Base</h1>
      <p style="font-size:15px;color:var(--mu);line-height:1.75;max-width:680px">Everything you need to operate Yuga — from first install through advanced AI model management, white-labeling, billing, and production hardening.</p>
      <div style="display:flex;flex-wrap:wrap;gap:10px;margin-top:20px">
        <span style="font-size:12px;background:rgba(16,185,129,.12);border:1px solid rgba(16,185,129,.25);color:#6ee7b7;padding:4px 12px;border-radius:100px">&#10003; 29 chapters</span>
        <span style="font-size:12px;background:rgba(99,102,241,.12);border:1px solid rgba(99,102,241,.25);color:var(--pl);padding:4px 12px;border-radius:100px">&#10003; Step-by-step</span>
        <span style="font-size:12px;background:rgba(245,158,11,.1);border:1px solid rgba(245,158,11,.25);color:#fcd34d;padding:4px 12px;border-radius:100px">&#10003; Troubleshooting included</span>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════
         CH 1 — INSTALLATION
    ════════════════════════════════════════════════════════════ -->
    <div class="chapter" id="ch1">
      <div class="chapter-header">
        <div class="ch-num">1</div>
        <div class="ch-meta">
          <h2>Installation &amp; First Boot</h2>
          <p>Get Yuga running on your cPanel server in under 5 minutes</p>
        </div>
      </div>

      <div class="section">
        <h3>Requirements</h3>
        <table class="guide-table">
          <tr><th>Requirement</th><th>Minimum</th><th>Recommended</th></tr>
          <tr><td>PHP</td><td>8.1</td><td>8.2+</td></tr>
          <tr><td>Extensions</td><td>pdo_sqlite, json, curl, mbstring</td><td>+ opcache, fileinfo</td></tr>
          <tr><td>Disk space</td><td>50 MB</td><td>500 MB+ (for model weights)</td></tr>
          <tr><td>Memory limit</td><td>128 MB</td><td>256 MB+</td></tr>
          <tr><td>Max execution time</td><td>300 s</td><td>600 s (for training)</td></tr>
          <tr><td>Hosting</td><td>Any cPanel shared host</td><td>VPS with SSH</td></tr>
        </table>
      </div>

      <div class="section">
        <h3>Upload &amp; install steps</h3>
        <div class="steps">
          <div class="step">
            <div class="step-num">1</div>
            <div class="step-body">
              <h4>Upload files</h4>
              <p>Extract the Yuga zip and upload the entire <code>yuga/</code> folder to your public_html (or a subfolder like <code>public_html/yuga/</code>). Use cPanel File Manager or FTP.</p>
            </div>
          </div>
          <div class="step">
            <div class="step-num">2</div>
            <div class="step-body">
              <h4>Set permissions</h4>
              <p>Make the <code>data/</code> directory writable: chmod 755 data/ and chmod 755 data/models/. The installer will check this automatically.</p>
              <pre>chmod 755 yuga/data
chmod 755 yuga/data/models</pre>
            </div>
          </div>
          <div class="step">
            <div class="step-num">3</div>
            <div class="step-body">
              <h4>Run the installer</h4>
              <p>Navigate to <code>https://yourdomain.com/yuga/install.php</code> in your browser. Fill in admin password, site name, and optionally your API key. Click <strong>Install</strong>.</p>
            </div>
          </div>
          <div class="step">
            <div class="step-num">4</div>
            <div class="step-body">
              <h4>Delete install.php</h4>
              <p>After successful install, <strong>delete install.php immediately</strong> for security. The admin panel will remind you if it still exists.</p>
            </div>
          </div>
          <div class="step">
            <div class="step-num">5</div>
            <div class="step-body">
              <h4>Log in to admin</h4>
              <p>Go to <code>https://yourdomain.com/yuga/admin/</code> and enter your admin password. You should see the dashboard.</p>
            </div>
          </div>
        </div>
        <div class="callout tip">
          <div class="ico">&#9998;</div>
          <div class="body"><h4>First-time setup checklist</h4><p>After install: (1) configure SMTP in Settings, (2) create your first model, (3) train it on your site content, (4) visit the dev portal and test the API, (5) create a subscriber and embed the widget.</p></div>
        </div>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════
         CH 2 — ADMIN PANEL OVERVIEW
    ════════════════════════════════════════════════════════════ -->
    <div class="chapter" id="ch2">
      <div class="chapter-header">
        <div class="ch-num">2</div>
        <div class="ch-meta">
          <h2>Admin Panel Overview</h2>
          <p>A map of every section and what it does</p>
        </div>
      </div>
      <div class="section">
        <h3>Navigation structure</h3>
        <table class="guide-table">
          <tr><th>Section</th><th>What you do here</th></tr>
          <tr><td>Dashboard</td><td>Real-time KPIs: subscribers, API calls, MRR, model status at a glance</td></tr>
          <tr><td>Analytics</td><td>14-day call volume chart, revenue by plan, top usage subscribers</td></tr>
          <tr><td>Model manager</td><td>Create, inspect, delete model instances; view training metadata</td></tr>
          <tr><td>Train / crawl</td><td>Train a model on text, URL, uploaded file, or crawl an entire website</td></tr>
          <tr><td>API playground</td><td>Test any API endpoint interactively before sharing with customers</td></tr>
          <tr><td>Subscribers</td><td>View, edit, suspend, or delete subscriber accounts</td></tr>
          <tr><td>Plans &amp; pricing</td><td>View current plan tiers and their limits</td></tr>
          <tr><td>Webhooks</td><td>Set up event-driven HTTP callbacks to your other systems</td></tr>
          <tr><td>Scheduler</td><td>Auto-crawl on a schedule so your model stays current</td></tr>
          <tr><td>Integrations</td><td>Connect Telegram, Slack, WhatsApp, Discord bots</td></tr>
          <tr><td>White-label</td><td>Customise the widget and portal brand per subscriber</td></tr>
          <tr><td>Version history</td><td>Save model snapshots, restore, run A/B tests</td></tr>
          <tr><td>Export / Import</td><td>Download model as .yuga bundle; import on another server</td></tr>
          <tr><td>Email templates</td><td>Customise the HTML content and subject of every transactional email</td></tr>
          <tr><td>Push notifications</td><td>Connect OneSignal; send broadcasts; configure event-triggered pushes</td></tr>
          <tr><td>Blog</td><td>Write and publish blog posts served from the public portal</td></tr>
          <tr><td>Support tickets</td><td>Read, reply, and change status on tickets submitted by subscribers</td></tr>
          <tr><td>Contact inbox</td><td>Read contact-form submissions from visitors; reply by email</td></tr>
          <tr><td>Nepali pipeline</td><td>One-click training from a curated catalog of Nepali &amp; English sources</td></tr>
          <tr><td>Workflows</td><td>Build multi-step automation chains (crawl → train → notify, etc.)</td></tr>
          <tr><td>Settings</td><td>Platform, security, SMTP, payments, AI backends, web search config</td></tr>
          <tr><td>Update</td><td>Check for and apply Yuga software updates</td></tr>
        </table>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════
         CH 3 — CREATING A MODEL
    ════════════════════════════════════════════════════════════ -->
    <div class="chapter" id="ch3">
      <div class="chapter-header">
        <div class="ch-num">3</div>
        <div class="ch-meta">
          <h2>Creating a Model</h2>
          <p>From scratch to a named model ready for training</p>
        </div>
      </div>
      <div class="section">
        <h3>What is a model?</h3>
        <p>A Yuga model is a named, independent instance of the YugaLM transformer. It has its own weights, vocabulary, training data index, and configuration. You can have multiple models — one per use-case, product, or client.</p>
      </div>
      <div class="section">
        <h3>Model sizes</h3>
        <table class="guide-table">
          <tr><th>Size</th><th>Parameters</th><th>Best for</th><th>Memory</th></tr>
          <tr><td>Nano</td><td>~100K</td><td>Simple FAQ bots on shared hosting</td><td>~10 MB</td></tr>
          <tr><td>Small</td><td>~500K</td><td>Product support, knowledge bases</td><td>~40 MB</td></tr>
          <tr><td>Medium</td><td>~2M</td><td>General-purpose assistants</td><td>~120 MB</td></tr>
          <tr><td>Large</td><td>~8M</td><td>Complex reasoning, enterprise</td><td>~400 MB</td></tr>
        </table>
        <div class="callout warn">
          <div class="ico">&#9888;</div>
          <div class="body"><h4>Shared hosting limits</h4><p>On most cPanel shared hosts, PHP memory_limit is 128–256 MB. Use Nano or Small sizes unless your host allows higher limits. A Medium model at 120 MB may fail on a 128 MB limit.</p></div>
        </div>
      </div>
      <div class="section">
        <h3>Creating a model via admin UI</h3>
        <div class="steps">
          <div class="step">
            <div class="step-num">1</div>
            <div class="step-body"><h4>Go to Model manager</h4><p>Click <strong>Model manager</strong> in the sidebar.</p></div>
          </div>
          <div class="step">
            <div class="step-num">2</div>
            <div class="step-body"><h4>Enter a name</h4><p>Type a lowercase name using letters, numbers, dashes and underscores only. Example: <code>support</code>, <code>sales-bot</code>, <code>client-acme</code>.</p></div>
          </div>
          <div class="step">
            <div class="step-num">3</div>
            <div class="step-body"><h4>Select size</h4><p>Choose a preset. If unsure, start with <strong>Nano</strong>. You can always create a new larger model and re-train it later.</p></div>
          </div>
          <div class="step">
            <div class="step-num">4</div>
            <div class="step-body"><h4>Click Create</h4><p>The model is created instantly. It will appear in the list with status <em>Untrained</em>. No training has happened yet — the model has random weights.</p></div>
          </div>
        </div>
      </div>
      <div class="section">
        <h3>Creating via API</h3>
        <pre><code>POST /api/?action=create
Content-Type: application/json
X-API-Key: YOUR_ADMIN_KEY

{ "model": "my-new-model" }</code></pre>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════
         CH 4 — TRAINING
    ════════════════════════════════════════════════════════════ -->
    <div class="chapter" id="ch4">
      <div class="chapter-header">
        <div class="ch-num">4</div>
        <div class="ch-meta">
          <h2>Training Your Model</h2>
          <p>Five ways to feed knowledge into Yuga</p>
        </div>
      </div>

      <div class="section">
        <h3>Training methods</h3>
        <table class="guide-table">
          <tr><th>Method</th><th>Admin page</th><th>API action</th><th>Best for</th></tr>
          <tr><td>Raw text</td><td>Train / crawl → Text tab</td><td>learn_text</td><td>Paste any content directly</td></tr>
          <tr><td>Single URL</td><td>Train / crawl → URL tab</td><td>learn_url</td><td>One specific page (e.g. /faq)</td></tr>
          <tr><td>Site crawl</td><td>Train / crawl → Crawl tab</td><td>learn_site</td><td>Entire website from homepage</td></tr>
          <tr><td>Deep crawl</td><td>Train / crawl → Deep crawl</td><td>deep_crawl</td><td>Full site including subpages</td></tr>
          <tr><td>File upload</td><td>Train / crawl → Upload</td><td>ingest</td><td>.txt, .pdf, .docx, .csv, .json</td></tr>
        </table>
      </div>

      <div class="section">
        <h3>Method 1: Raw text training</h3>
        <p>Go to <strong>Train / crawl</strong> → select your model → paste text into the textarea → set training steps → click <strong>Train</strong>.</p>
        <div class="callout info">
          <div class="ico">&#128161;</div>
          <div class="body"><h4>How much text?</h4><p>More content = better answers. Aim for at least 5,000 characters (about 700 words). A full knowledge base of 50,000+ characters will produce dramatically better results.</p></div>
        </div>
      </div>

      <div class="section">
        <h3>Method 2: Single URL</h3>
        <p>Paste a URL (e.g. <code>https://yoursite.com/pricing</code>) and Yuga will fetch the page, strip navigation/ads, extract clean text, and train on it. Works on any publicly accessible URL.</p>
      </div>

      <div class="section">
        <h3>Method 3 &amp; 4: Site crawl (recommended)</h3>
        <p>Enter your homepage URL (e.g. <code>https://yoursite.com</code>) and Yuga will:</p>
        <ol>
          <li>Fetch the homepage</li>
          <li>Find all internal links (same domain)</li>
          <li>Fetch each page up to the max_pages limit</li>
          <li>Extract text from all pages combined</li>
          <li>Train the model on the full dataset</li>
        </ol>
        <p><strong>Deep crawl</strong> follows links recursively and can discover hundreds of pages. Set <code>max_pages</code> to control how much it crawls.</p>
        <div class="callout warn">
          <div class="ico">&#9888;</div>
          <div class="body"><h4>Crawl takes time</h4><p>A 30-page crawl with 10,000 training steps can take 2–5 minutes on shared hosting. PHP's max_execution_time may need to be set to 300+ in your cPanel PHP settings. The admin page sets <code>set_time_limit(600)</code> automatically.</p></div>
        </div>
      </div>

      <div class="section">
        <h3>Method 5: File upload</h3>
        <p>Upload documents directly. Supported formats:</p>
        <ul>
          <li><strong>.txt</strong> — plain text, fastest</li>
          <li><strong>.pdf</strong> — extracts text (requires well-formed PDFs, not scanned images)</li>
          <li><strong>.docx</strong> — Microsoft Word documents</li>
          <li><strong>.csv</strong> — treated as structured text</li>
          <li><strong>.json</strong> — extracts all string values</li>
        </ul>
        <p>Bulk upload allows multiple files at once. Each file is processed sequentially.</p>
      </div>

      <div class="section">
        <h3>Training steps — how many?</h3>
        <table class="guide-table">
          <tr><th>Steps</th><th>Duration (nano)</th><th>Use case</th></tr>
          <tr><td>1,000</td><td>~5 seconds</td><td>Quick test</td></tr>
          <tr><td>10,000</td><td>~30 seconds</td><td>Small knowledge base</td></tr>
          <tr><td>50,000</td><td>~3 minutes</td><td>Full website content</td></tr>
          <tr><td>200,000</td><td>~10 minutes</td><td>Large documentation set</td></tr>
        </table>
        <div class="callout tip">
          <div class="ico">&#9998;</div>
          <div class="body"><h4>Training is additive</h4><p>Each training run adds to existing knowledge — it does not erase prior training. You can train incrementally: train on your FAQ first, then add your pricing page, then add new blog posts over time.</p></div>
        </div>
      </div>

      <div class="section">
        <h3>Scheduled training (auto-crawl)</h3>
        <p>Go to <strong>Scheduler</strong> → add a crawl job for your homepage → set frequency (daily, weekly). Yuga will automatically keep the model current with your latest website content. Requires a cron job on your server (see Chapter 18).</p>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════
         CH 5 — TRAINING METRICS
    ════════════════════════════════════════════════════════════ -->
    <div class="chapter" id="ch5">
      <div class="chapter-header">
        <div class="ch-num">5</div>
        <div class="ch-meta">
          <h2>Understanding Training Metrics</h2>
          <p>What loss, steps, vocab and params actually mean</p>
        </div>
      </div>
      <div class="section">
        <h3>Loss</h3>
        <p><strong>Loss</strong> measures how wrong the model's predictions are. <em>Lower is better</em>. A model with loss 2.0 makes poor predictions; at 0.5 it reliably continues text from your training data.</p>
        <table class="guide-table">
          <tr><th>Loss range</th><th>Interpretation</th><th>Action</th></tr>
          <tr><td>&gt; 3.0</td><td>Barely trained, random-ish output</td><td>Train more, add more data</td></tr>
          <tr><td>1.5 – 3.0</td><td>Early training, improving</td><td>Continue training</td></tr>
          <tr><td>0.8 – 1.5</td><td>Good — model has learned content</td><td>Test and deploy</td></tr>
          <tr><td>0.4 – 0.8</td><td>Excellent — well-fitted to content</td><td>Ready for production</td></tr>
          <tr><td>&lt; 0.3</td><td>Possible overfitting</td><td>Test generalization</td></tr>
        </table>
      </div>
      <div class="section">
        <h3>Training steps</h3>
        <p>Each step is one gradient update — the model sees a batch of text and adjusts its weights slightly toward better predictions. More steps with more data = better model, up to a point.</p>
      </div>
      <div class="section">
        <h3>Vocabulary size</h3>
        <p>The number of unique tokens the model has learned from training data. A larger vocabulary generally means the model has seen more diverse content. Yuga uses a word-level tokenizer, so vocab = unique words encountered during training.</p>
      </div>
      <div class="section">
        <h3>Parameters</h3>
        <p>The number of learnable weights in the model. More parameters = more capacity to store knowledge, but also more memory and compute time. This is fixed at model creation time based on the preset you chose.</p>
      </div>
      <div class="section">
        <h3>BM25 retrieval</h3>
        <p>Separate from the neural model, Yuga maintains a BM25 index of all sentences it has ever seen. When answering a question, it first retrieves the most relevant sentences, then uses them to ground the neural model's output. This means even a partially trained model can give accurate answers — the BM25 layer acts as a factual anchor.</p>
        <div class="callout tip">
          <div class="ico">&#9998;</div>
          <div class="body"><h4>BM25 is always on</h4><p>Even if your model's loss is high, the BM25 retrieval layer will still return relevant passages from your content. This is why Yuga can be useful even before extended training.</p></div>
        </div>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════
         CH 6 — DEPLOYMENT & TESTING
    ════════════════════════════════════════════════════════════ -->
    <div class="chapter" id="ch6">
      <div class="chapter-header">
        <div class="ch-num">6</div>
        <div class="ch-meta">
          <h2>Deployment &amp; Testing</h2>
          <p>From trained model to live production in 3 steps</p>
        </div>
      </div>
      <div class="section">
        <h3>Testing in the API playground</h3>
        <p>Go to <strong>API playground</strong> in the admin. Select your model, type a question, click <strong>Chat</strong>. This calls the real <code>brain_chat</code> endpoint with your admin key — no subscriber needed for testing.</p>
      </div>
      <div class="section">
        <h3>Embed the chat widget</h3>
        <p>Add this one-line script tag to any HTML page on your site:</p>
        <pre><code>&lt;script
  src="https://yourdomain.com/yuga/widget/yuga-widget.js"
  data-api="https://yourdomain.com/yuga/api/"
  data-model="default"
  data-key="yuga_live_YOURKEY"
  data-auto-learn="true"
&gt;&lt;/script&gt;</code></pre>
        <p>The widget appears as a floating chat button. <code>data-auto-learn="true"</code> makes it automatically learn from the current page when it loads.</p>
      </div>
      <div class="section">
        <h3>API integration</h3>
        <p>Use the <code>brain_chat</code> endpoint from your own backend or frontend:</p>
        <pre><code>curl -X POST 'https://yourdomain.com/yuga/api/?action=brain_chat' \
  -H 'X-API-Key: yuga_live_YOURKEY' \
  -H 'Content-Type: application/json' \
  -d '{"model":"default","message":"What is your pricing?"}'</code></pre>
      </div>
      <div class="section">
        <h3>Before selling to customers</h3>
        <div class="callout info">
          <div class="ico">&#128161;</div>
          <div class="body"><h4>Do I need to pre-train?</h4><p><strong>Yes, recommended.</strong> Train the model on your platform's content before giving customers API keys. A model with 0 training will answer with generic nonsense. Train on your docs, FAQ, product pages, and pricing first. Customers can then optionally add their own content via the API.</p></div>
        </div>
        <p>Minimum preparation before launch:</p>
        <ol>
          <li>Create at least one model (e.g. <code>default</code>)</li>
          <li>Crawl your website or upload your documentation</li>
          <li>Verify loss is below 2.0</li>
          <li>Test in the playground: ask 10 real customer questions</li>
          <li>Configure SMTP so welcome emails send</li>
          <li>Set up at least one payment gateway if charging</li>
        </ol>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════
         CH 7 — API KEY MANAGEMENT
    ════════════════════════════════════════════════════════════ -->
    <div class="chapter" id="ch7">
      <div class="chapter-header">
        <div class="ch-num">7</div>
        <div class="ch-meta">
          <h2>API Key Management</h2>
          <p>Creating, viewing, suspending, and revoking subscriber keys</p>
        </div>
      </div>
      <div class="section">
        <h3>How keys work</h3>
        <p>Every subscriber gets one or more API keys (depending on their plan). Keys are prefixed <code>yuga_live_</code> followed by a random hex string. They are hashed before storage — you cannot see a key after creation.</p>
        <p>Each key carries rate limits based on the subscriber's plan: calls per minute, calls per day, and concurrent request limit.</p>
      </div>
      <div class="section">
        <h3>Creating a subscriber manually</h3>
        <p>Go to <strong>Subscribers</strong> → click <strong>Add subscriber</strong> → fill in name, email, and plan → click <strong>Create</strong>. The new subscriber's key is shown once — copy it or use the email dispatch button to send it to them.</p>
      </div>
      <div class="section">
        <h3>Suspending a subscriber</h3>
        <p>In the Subscribers table, click the red <strong>Suspend</strong> button next to a subscriber. All their keys immediately stop working. A suspension email can be sent with a custom reason.</p>
      </div>
      <div class="section">
        <h3>Key rotation</h3>
        <p>If a subscriber's key is compromised, go to their subscriber record → <strong>Revoke keys</strong> → <strong>Create new key</strong>. Old key stops working immediately.</p>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════
         CH 8 — PLANS & BILLING
    ════════════════════════════════════════════════════════════ -->
    <div class="chapter" id="ch8">
      <div class="chapter-header">
        <div class="ch-num">8</div>
        <div class="ch-meta">
          <h2>Plans &amp; Billing</h2>
          <p>Understanding the plan tiers and revenue tracking</p>
        </div>
      </div>
      <div class="section">
        <h3>Default plan tiers</h3>
        <table class="guide-table">
          <tr><th>Plan</th><th>Price</th><th>Daily calls</th><th>Models</th><th>Keys</th></tr>
          <tr><td>Free</td><td>$0</td><td>100</td><td>1</td><td>1</td></tr>
          <tr><td>Starter</td><td>$9/mo</td><td>500</td><td>2</td><td>3</td></tr>
          <tr><td>Pro</td><td>$29/mo</td><td>5,000</td><td>5</td><td>10</td></tr>
          <tr><td>Enterprise</td><td>$99/mo</td><td>Unlimited</td><td>Unlimited</td><td>Unlimited</td></tr>
        </table>
        <p>To change plan pricing, edit <code>subscriptions/Plans.php</code>. Plans are defined as a PHP array — change the <code>price_month</code>, <code>price_year</code>, and feature values.</p>
      </div>
      <div class="section">
        <h3>MRR calculation</h3>
        <p>Monthly Recurring Revenue shown in the admin is calculated from subscriber counts per plan multiplied by monthly price. It does not account for discounts, refunds, or payment gateway fees.</p>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════
         CH 9 — PAYMENT GATEWAYS
    ════════════════════════════════════════════════════════════ -->
    <div class="chapter" id="ch9">
      <div class="chapter-header">
        <div class="ch-num">9</div>
        <div class="ch-meta">
          <h2>Payment Gateways</h2>
          <p>Stripe, PayPal, eSewa, FonePay, IMEPay — setup guide</p>
        </div>
      </div>
      <div class="section">
        <h3>Configuring gateways</h3>
        <p>Go to <strong>Settings</strong> → <strong>Payments</strong> tab. Enable any gateway and fill in the required credentials. Click <strong>Save payment settings</strong>.</p>
      </div>
      <div class="section">
        <h3>Stripe</h3>
        <ol>
          <li>Sign in to <strong>dashboard.stripe.com</strong></li>
          <li>Go to Developers → API Keys</li>
          <li>Copy <strong>Publishable key</strong> (starts with <code>pk_</code>) and <strong>Secret key</strong> (starts with <code>sk_</code>)</li>
          <li>Paste into Yuga Settings → Payments → Stripe section</li>
          <li>Set Webhook secret from Stripe Webhooks page</li>
        </ol>
        <div class="callout tip"><div class="ico">&#9998;</div><div class="body"><h4>Test mode</h4><p>Use <code>pk_test_</code> / <code>sk_test_</code> keys while testing. Switch to live keys when ready to charge real money. Use Stripe's test card <code>4242 4242 4242 4242</code>.</p></div></div>
      </div>
      <div class="section">
        <h3>PayPal</h3>
        <ol>
          <li>Log in to <strong>developer.paypal.com</strong></li>
          <li>Create a new App under REST API Apps</li>
          <li>Copy Client ID and Client Secret</li>
          <li>Paste into Settings → Payments → PayPal section</li>
          <li>For sandbox testing, set mode to <code>sandbox</code></li>
        </ol>
      </div>
      <div class="section">
        <h3>eSewa, FonePay, IMEPay (Nepal)</h3>
        <p>Contact each provider's merchant team to obtain merchant credentials. The settings page has dedicated fields for each gateway's merchant ID and secret key. eSewa uses SHA256 HMAC signature verification.</p>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════
         CH 10 — PLATFORM SETTINGS
    ════════════════════════════════════════════════════════════ -->
    <div class="chapter" id="ch10">
      <div class="chapter-header">
        <div class="ch-num">10</div>
        <div class="ch-meta">
          <h2>Platform Settings</h2>
          <p>Site name, URL, API key, branding basics</p>
        </div>
      </div>
      <div class="section">
        <h3>Settings → Platform tab</h3>
        <table class="guide-table">
          <tr><th>Field</th><th>What it controls</th></tr>
          <tr><td>Site name</td><td>Shown in the portal header and emails</td></tr>
          <tr><td>Site URL</td><td>Used by the mailer for links in emails, and by the widget for CORS</td></tr>
          <tr><td>API key</td><td>The global admin API key — full access to all endpoints</td></tr>
          <tr><td>Admin password</td><td>The password to log in to this admin panel</td></tr>
          <tr><td>Admin slug</td><td>Renames the /admin/ path to something secret, e.g. /xp9admin/</td></tr>
          <tr><td>Max crawl pages</td><td>Global limit for site crawl operations</td></tr>
          <tr><td>Default model</td><td>Fallback model name when no model is specified in API calls</td></tr>
        </table>
        <div class="callout danger"><div class="ico">&#9888;</div><div class="body"><h4>Keep admin password strong</h4><p>Your admin password protects full system access including all subscriber data and model weights. Use a minimum 20-character random password and store it in a password manager.</p></div></div>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════
         CH 11 — SMTP & EMAIL
    ════════════════════════════════════════════════════════════ -->
    <div class="chapter" id="ch11">
      <div class="chapter-header">
        <div class="ch-num">11</div>
        <div class="ch-meta">
          <h2>SMTP &amp; Email</h2>
          <p>Transactional emails for welcome, receipts, and alerts</p>
        </div>
      </div>
      <div class="section">
        <h3>Settings → Email/SMTP tab</h3>
        <p>Fill in SMTP host, port (usually 587 for STARTTLS or 465 for SSL), username, and password. Click <strong>Test SMTP</strong> to send a test email before saving.</p>
        <table class="guide-table">
          <tr><th>Provider</th><th>SMTP host</th><th>Port</th></tr>
          <tr><td>Gmail / G Suite</td><td>smtp.gmail.com</td><td>587</td></tr>
          <tr><td>Mailgun</td><td>smtp.mailgun.org</td><td>587</td></tr>
          <tr><td>SendGrid</td><td>smtp.sendgrid.net</td><td>587</td></tr>
          <tr><td>Amazon SES</td><td>email-smtp.us-east-1.amazonaws.com</td><td>587</td></tr>
          <tr><td>cPanel (same server)</td><td>localhost or mail.yourdomain.com</td><td>465</td></tr>
        </table>
        <div class="callout info"><div class="ico">&#128161;</div><div class="body"><h4>Gmail App Password</h4><p>If using Gmail with 2FA enabled, generate an App Password at myaccount.google.com/apppasswords. Use this instead of your regular Gmail password in the SMTP settings.</p></div></div>
      </div>
      <div class="section">
        <h3>Emails sent automatically</h3>
        <ul>
          <li><strong>Welcome email</strong> — sent on new subscriber signup with their API key</li>
          <li><strong>Usage warning</strong> — sent when a subscriber hits 80% of daily limit</li>
          <li><strong>Payment receipt</strong> — sent after successful payment</li>
          <li><strong>Suspension notice</strong> — sent when you suspend an account</li>
          <li><strong>Training complete</strong> — sent when a scheduled training job finishes</li>
        </ul>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════
         CH 12 — AI BACKENDS
    ════════════════════════════════════════════════════════════ -->
    <div class="chapter" id="ch12">
      <div class="chapter-header">
        <div class="ch-num">12</div>
        <div class="ch-meta">
          <h2>AI Backends</h2>
          <p>Augment Yuga with OpenAI, Anthropic, Groq or Ollama</p>
        </div>
      </div>
      <div class="section">
        <h3>Why configure an AI backend?</h3>
        <p>Yuga's built-in YugaLM transformer is a small model — great for retrieval-grounded Q&A but not for complex reasoning or creative writing. If you configure an external LLM backend, endpoints like <code>smart_chat</code> and <code>reason</code> can use it to generate higher-quality responses, while still grounding answers in your training data via BM25.</p>
        <div class="callout info"><div class="ico">&#128161;</div><div class="body"><h4>Fully optional</h4><p>Yuga works completely without any external AI backend. The YugaLM model and BM25 retrieval work independently. External backends are an enhancement, not a requirement.</p></div></div>
      </div>
      <div class="section">
        <h3>Supported backends</h3>
        <table class="guide-table">
          <tr><th>Backend</th><th>Best for</th><th>Cost</th></tr>
          <tr><td>OpenAI (GPT-4o)</td><td>Best overall quality</td><td>~$0.01/1K tokens</td></tr>
          <tr><td>Anthropic (Claude)</td><td>Long context, safety</td><td>~$0.015/1K tokens</td></tr>
          <tr><td>Groq (Llama 3)</td><td>Speed, low cost</td><td>~$0.0008/1K tokens</td></tr>
          <tr><td>Together AI</td><td>Open-source models</td><td>~$0.001/1K tokens</td></tr>
          <tr><td>Ollama</td><td>Local / private server</td><td>Free (self-hosted)</td></tr>
        </table>
      </div>
      <div class="section">
        <h3>Customer-provided keys</h3>
        <p>Subscribers can provide their <em>own</em> AI backend keys in their customer dashboard. When they do, their API calls use their own key instead of the platform default. This means you incur no AI costs for those subscribers.</p>
        <p>This is available to all paid plan subscribers by default.</p>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════
         CH 13 — WEB SEARCH
    ════════════════════════════════════════════════════════════ -->
    <div class="chapter" id="ch13">
      <div class="chapter-header">
        <div class="ch-num">13</div>
        <div class="ch-meta">
          <h2>Web Search (Perplexity-style)</h2>
          <p>Give Yuga real-time internet search capabilities</p>
        </div>
      </div>
      <div class="section">
        <h3>How it works</h3>
        <p>When a user sends a query to the <code>web_search</code> endpoint, Yuga:</p>
        <ol>
          <li>Sends the query to your configured search provider (DuckDuckGo, Brave, or SerpAPI)</li>
          <li>Fetches the top result pages</li>
          <li>Applies BM25 to extract the most relevant passages</li>
          <li>Optionally uses your LLM backend to synthesise a grounded answer with citations</li>
        </ol>
      </div>
      <div class="section">
        <h3>Search providers</h3>
        <table class="guide-table">
          <tr><th>Provider</th><th>Free tier</th><th>Setup</th></tr>
          <tr><td>DuckDuckGo Instant</td><td>Unlimited (no auth)</td><td>No API key needed</td></tr>
          <tr><td>Brave Search API</td><td>2,000 queries/month</td><td>Get key at brave.com/search/api</td></tr>
          <tr><td>SerpAPI</td><td>100 searches/month</td><td>Get key at serpapi.com</td></tr>
        </table>
        <div class="callout tip"><div class="ico">&#9998;</div><div class="body"><h4>Start with DuckDuckGo</h4><p>DuckDuckGo requires no API key and has no rate limits for reasonable use. It's the best starting point. Switch to Brave or SerpAPI if you need higher quality results or higher volume.</p></div></div>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════
         CH 14 — WHITE-LABELING
    ════════════════════════════════════════════════════════════ -->
    <div class="chapter" id="ch14">
      <div class="chapter-header">
        <div class="ch-num">14</div>
        <div class="ch-meta">
          <h2>White-labeling</h2>
          <p>Custom brand identity per subscriber or globally</p>
        </div>
      </div>
      <div class="section">
        <h3>What can be white-labeled</h3>
        <ul>
          <li>Widget brand name and colors</li>
          <li>Widget welcome message</li>
          <li>Powered-by text</li>
          <li>Custom CSS injection</li>
        </ul>
      </div>
      <div class="section">
        <h3>Setting up white-label per subscriber</h3>
        <p>Go to <strong>White-label</strong> in the sidebar → select a subscriber from the dropdown → customize the brand name, primary color, accent color, and welcome message → click <strong>Save</strong>.</p>
        <p>The widget served to that subscriber's key will automatically use their custom branding.</p>
      </div>
      <div class="section">
        <h3>Use case: reselling</h3>
        <p>If you sell Yuga as a white-labeled product to other businesses, each enterprise subscriber can have a fully branded widget that shows their company name and colors — with no "Powered by Yuga" visible if configured.</p>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════
         CH 15 — VERSION HISTORY & A/B TESTING
    ════════════════════════════════════════════════════════════ -->
    <div class="chapter" id="ch15">
      <div class="chapter-header">
        <div class="ch-num">15</div>
        <div class="ch-meta">
          <h2>Version History &amp; A/B Testing</h2>
          <p>Save snapshots, roll back, run experiments</p>
        </div>
      </div>
      <div class="section">
        <h3>Creating a version snapshot</h3>
        <p>Go to <strong>Version history</strong> → select model → click <strong>Save snapshot</strong> → enter a label (e.g. "after-faq-training-v2"). The current weights and BM25 index are saved with that label and a timestamp.</p>
      </div>
      <div class="section">
        <h3>Restoring a version</h3>
        <p>In the versions list, click <strong>Activate</strong> next to any snapshot. The model immediately reverts to that snapshot's weights. Use this to roll back after a bad training run.</p>
      </div>
      <div class="section">
        <h3>A/B testing</h3>
        <p>Click <strong>Start A/B test</strong> → select two versions (A and B) → set split percentage (default 50/50). API calls to this model will be randomly routed to version A or B. View results in the versions page to see which version has better engagement metrics.</p>
        <div class="callout info"><div class="ico">&#128161;</div><div class="body"><h4>A/B test use case</h4><p>Train version A with just text training, version B with site crawl. Run both for a week. Compare which produces better answers based on user feedback or follow-up query rate.</p></div></div>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════
         CH 16 — EXPORT & IMPORT
    ════════════════════════════════════════════════════════════ -->
    <div class="chapter" id="ch16">
      <div class="chapter-header">
        <div class="ch-num">16</div>
        <div class="ch-meta">
          <h2>Model Export &amp; Import</h2>
          <p>Move trained models between servers as .yuga bundles</p>
        </div>
      </div>
      <div class="section">
        <h3>Exporting a model</h3>
        <p>Go to <strong>Export / Import</strong> → select a model → click <strong>Download .yuga</strong>. A compressed bundle containing the model weights, BM25 index, vocabulary, and metadata is downloaded to your browser.</p>
        <p>The bundle filename format is <code>modelname_YYYYMMDD.yuga</code>.</p>
      </div>
      <div class="section">
        <h3>Importing a model</h3>
        <p>On the target server, go to <strong>Export / Import</strong> → <strong>Import .yuga file</strong> → upload the bundle → enter a name for the imported model → click <strong>Import</strong>. The model is fully restored including all training data.</p>
        <div class="callout warn"><div class="ico">&#9888;</div><div class="body"><h4>File size</h4><p>Large model bundles can be tens or hundreds of MB. Check your PHP <code>upload_max_filesize</code> and <code>post_max_size</code> settings in cPanel PHP configuration before importing.</p></div></div>
      </div>
      <div class="section">
        <h3>Use cases</h3>
        <ul>
          <li>Migrate model from development server to production</li>
          <li>Create a backup before major training runs</li>
          <li>Deploy the same model across multiple Yuga installations</li>
          <li>Sell pre-trained models to clients</li>
        </ul>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════
         CH 17 — WEBHOOKS & AUTOMATIONS
    ════════════════════════════════════════════════════════════ -->
    <div class="chapter" id="ch17">
      <div class="chapter-header">
        <div class="ch-num">17</div>
        <div class="ch-meta">
          <h2>Webhooks &amp; Automations</h2>
          <p>Event-driven HTTP callbacks for your other systems</p>
        </div>
      </div>
      <div class="section">
        <h3>Available events</h3>
        <table class="guide-table">
          <tr><th>Event</th><th>Triggered when</th></tr>
          <tr><td>subscriber.created</td><td>New subscriber signs up</td></tr>
          <tr><td>subscriber.suspended</td><td>Account is suspended</td></tr>
          <tr><td>payment.success</td><td>Payment confirmed</td></tr>
          <tr><td>payment.failed</td><td>Payment declined</td></tr>
          <tr><td>training.complete</td><td>A training job finishes</td></tr>
          <tr><td>api.limit_reached</td><td>Subscriber hits daily call limit</td></tr>
        </table>
      </div>
      <div class="section">
        <h3>Setting up a webhook</h3>
        <div class="steps">
          <div class="step"><div class="step-num">1</div><div class="step-body"><h4>Go to Webhooks</h4><p>Click Webhooks in the sidebar.</p></div></div>
          <div class="step"><div class="step-num">2</div><div class="step-body"><h4>Add endpoint</h4><p>Enter the URL of your receiving endpoint (must be HTTPS in production). Select which events to send to this URL.</p></div></div>
          <div class="step"><div class="step-num">3</div><div class="step-body"><h4>Set a secret</h4><p>Add a shared secret. Yuga will include an HMAC-SHA256 signature in the <code>X-Yuga-Signature</code> header so you can verify the payload is genuine.</p></div></div>
          <div class="step"><div class="step-num">4</div><div class="step-body"><h4>Test it</h4><p>Click the Test button to send a sample payload to your endpoint. Check your server logs to confirm receipt.</p></div></div>
        </div>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════
         CH 18 — SCHEDULER & CRON
    ════════════════════════════════════════════════════════════ -->
    <div class="chapter" id="ch18">
      <div class="chapter-header">
        <div class="ch-num">18</div>
        <div class="ch-meta">
          <h2>Scheduler &amp; Cron</h2>
          <p>Automatic re-training to keep your model current</p>
        </div>
      </div>
      <div class="section">
        <h3>Setting up a cron job (cPanel)</h3>
        <ol>
          <li>Log into cPanel → <strong>Cron Jobs</strong></li>
          <li>Add a new cron job with this command:</li>
        </ol>
        <pre><code>curl -s "https://yourdomain.com/yuga/api/?action=cron_tick&key=YOUR_ADMIN_KEY" > /dev/null 2>&1</code></pre>
        <p>Set it to run <strong>every hour</strong> (or every 5 minutes for time-sensitive crawls).</p>
        <div class="callout info"><div class="ico">&#128161;</div><div class="body"><h4>Alternative: PHP CLI cron</h4><p>If you have shell access: <code>*/30 * * * * php /home/user/public_html/yuga/cron.php</code></p></div></div>
      </div>
      <div class="section">
        <h3>Adding scheduled jobs</h3>
        <p>Go to <strong>Scheduler</strong> → click <strong>Add job</strong> → choose job type (crawl URL / learn URL / custom API call) → set frequency (hourly / daily / weekly) → save. Jobs run the next time the cron tick fires.</p>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════
         CH 19 — INTEGRATIONS
    ════════════════════════════════════════════════════════════ -->
    <div class="chapter" id="ch19">
      <div class="chapter-header">
        <div class="ch-num">19</div>
        <div class="ch-meta">
          <h2>Integrations</h2>
          <p>Telegram, Slack, WhatsApp, and Discord bots</p>
        </div>
      </div>
      <div class="section">
        <h3>Telegram bot</h3>
        <div class="steps">
          <div class="step"><div class="step-num">1</div><div class="step-body"><h4>Create a bot</h4><p>Message @BotFather on Telegram → <code>/newbot</code> → follow the prompts → copy the API token.</p></div></div>
          <div class="step"><div class="step-num">2</div><div class="step-body"><h4>Add to Yuga</h4><p>Settings → Integrations → Telegram → paste the bot token → select which Yuga model to route messages to → save.</p></div></div>
          <div class="step"><div class="step-num">3</div><div class="step-body"><h4>Set webhook</h4><p>Click <strong>Register Telegram webhook</strong>. Yuga automatically registers your server's URL with Telegram. Now every message sent to your bot goes through Yuga's brain_chat.</p></div></div>
        </div>
      </div>
      <div class="section">
        <h3>Slack bot</h3>
        <ol>
          <li>Create an app at <strong>api.slack.com/apps</strong></li>
          <li>Enable Event Subscriptions → set Request URL to your Yuga webhook endpoint</li>
          <li>Subscribe to <code>message.im</code> and <code>app_mention</code> events</li>
          <li>Copy Bot Token and Signing Secret into Settings → Integrations → Slack</li>
        </ol>
      </div>
      <div class="section">
        <h3>WhatsApp (via Twilio)</h3>
        <p>Sign up at twilio.com → enable WhatsApp Sandbox → set webhook URL to <code>https://yourdomain.com/yuga/api/?action=twilio_webhook</code> → add Twilio Account SID and Auth Token in Settings → Integrations → WhatsApp.</p>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════
         CH 20 — SECURITY HARDENING
    ════════════════════════════════════════════════════════════ -->
    <div class="chapter" id="ch20">
      <div class="chapter-header">
        <div class="ch-num">20</div>
        <div class="ch-meta">
          <h2>Security Hardening</h2>
          <p>Production-ready security checklist</p>
        </div>
      </div>
      <div class="section">
        <h3>Checklist</h3>
        <ul>
          <li>&#9745; Delete <code>install.php</code> after installation</li>
          <li>&#9745; Set a strong, unique admin password (20+ chars)</li>
          <li>&#9745; Change the admin slug in Settings → Security (e.g. <code>xpadmin92</code>)</li>
          <li>&#9745; Enable HTTPS (free via Let's Encrypt in cPanel → SSL/TLS)</li>
          <li>&#9745; Set <code>data/</code> folder to not be web-accessible (already handled by .htaccess)</li>
          <li>&#9745; Rotate your admin API key every 90 days</li>
          <li>&#9745; Regularly back up your <code>data/</code> directory (contains the SQLite DB and model weights)</li>
          <li>&#9745; Configure rate limiting in Settings → Security</li>
          <li>&#9745; Enable IP allowlisting for admin access if possible</li>
        </ul>
      </div>
      <div class="section">
        <h3>CSRF protection</h3>
        <p>All forms in Yuga (admin and portal) include a CSRF token. Tokens are validated on every POST. Do not bypass this by making direct curl calls to POST endpoints — use the API instead.</p>
      </div>
      <div class="section">
        <h3>Data directory</h3>
        <p>The <code>data/</code> directory contains your SQLite database and all model weights. The included <code>.htaccess</code> blocks direct web access. If your server doesn't support .htaccess, move the <code>data/</code> directory above public_html and update <code>YUGA_ROOT</code> in <code>config.php</code>.</p>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════
         CH 21 — TROUBLESHOOTING
    ════════════════════════════════════════════════════════════ -->
    <div class="chapter" id="ch21">
      <div class="chapter-header">
        <div class="ch-num">21</div>
        <div class="ch-meta">
          <h2>Troubleshooting</h2>
          <p>Common issues and their solutions</p>
        </div>
      </div>
      <div class="section">
        <h3>Common issues</h3>
        <table class="guide-table">
          <tr><th>Problem</th><th>Cause</th><th>Solution</th></tr>
          <tr><td>Admin panel shows "Setup Required"</td><td>No admin_password in config.php</td><td>Run install.php, or manually add admin_password to config.php</td></tr>
          <tr><td>Training times out</td><td>max_execution_time too low</td><td>Increase in cPanel PHP settings to 300+</td></tr>
          <tr><td>Model not found errors</td><td>Model name doesn't exist in data/models/</td><td>Create the model first in Model manager</td></tr>
          <tr><td>API returns 403</td><td>Invalid or missing API key</td><td>Check X-API-Key header; verify key is active in Subscribers</td></tr>
          <tr><td>Widget not loading</td><td>CORS or wrong API URL</td><td>Check data-api attribute matches your actual API URL</td></tr>
          <tr><td>Emails not sending</td><td>SMTP misconfigured</td><td>Test SMTP in Settings → Email tab; check credentials</td></tr>
          <tr><td>Crawler returns empty</td><td>Site blocks bots or requires JS</td><td>Use manual text training; paste content directly</td></tr>
          <tr><td>High memory usage</td><td>Large model + small hosting</td><td>Reduce model size; upgrade hosting plan</td></tr>
          <tr><td>Upgrade file not writable</td><td>Permissions on PHP files</td><td>chmod 644 *.php in all directories</td></tr>
        </table>
      </div>
      <div class="section">
        <h3>Enabling debug mode</h3>
        <p>Add <code>'debug' => true</code> to your config.php. API errors will include stack traces in the response. <strong>Disable before going to production.</strong></p>
        <pre><code>// config.php
return [
  ...
  'debug' => true,  // remove this in production
];</code></pre>
      </div>
      <div class="section">
        <h3>Checking PHP error logs</h3>
        <p>In cPanel → Error Logs, or check <code>~/public_html/error_log</code>. PHP fatal errors (memory limit, class not found, etc.) will appear there.</p>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════
         CH 22 — UPGRADING
    ════════════════════════════════════════════════════════════ -->
    <div class="chapter" id="ch22">
      <div class="chapter-header">
        <div class="ch-num">22</div>
        <div class="ch-meta">
          <h2>Upgrading Yuga</h2>
          <p>How to apply updates without losing data</p>
        </div>
      </div>
      <div class="section">
        <h3>Safe upgrade procedure</h3>
        <div class="steps">
          <div class="step"><div class="step-num">1</div><div class="step-body"><h4>Backup first</h4><p>Download the entire <code>data/</code> folder (your database and model weights) and your current <code>config.php</code>. Keep this backup until the upgrade is verified.</p></div></div>
          <div class="step"><div class="step-num">2</div><div class="step-body"><h4>Upload new files</h4><p>Upload the new Yuga version's files, overwriting existing PHP files. <strong>Do not overwrite</strong> your <code>data/</code> directory or <code>config.php</code>.</p></div></div>
          <div class="step"><div class="step-num">3</div><div class="step-body"><h4>Run database migrations</h4><p>Visit the admin panel → <strong>Update</strong> page → click <strong>Run migrations</strong>. This applies any schema changes to the SQLite database safely.</p></div></div>
          <div class="step"><div class="step-num">4</div><div class="step-body"><h4>Clear PHP opcache</h4><p>If opcache is enabled, clear it: <code>opcache_reset()</code> via a PHP file, or restart the web server. New code won't run until cached PHP files are cleared.</p></div></div>
          <div class="step"><div class="step-num">5</div><div class="step-body"><h4>Test</h4><p>Verify admin login works, API endpoints respond, and model status shows correctly. Check PHP error logs for any issues.</p></div></div>
        </div>
        <div class="callout warn"><div class="ico">&#9888;</div><div class="body"><h4>Always backup before upgrading</h4><p>The data/ directory is the most valuable thing on your server. It contains every subscriber record and every trained model weight. Back it up before any upgrade, configuration change, or major training run.</p></div></div>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════
         CH 23 — EMAIL TEMPLATES
    ════════════════════════════════════════════════════════════ -->
    <div class="chapter" id="ch23">
      <div class="chapter-header">
        <div class="ch-num">23</div>
        <div class="ch-meta">
          <h2>Email Templates</h2>
          <p>Customise every transactional email sent by Yuga</p>
        </div>
      </div>
      <div class="section">
        <h3>Overview</h3>
        <p>Yuga sends transactional emails for key lifecycle events. Each email has a default template that you can override with your own HTML. Customised templates are saved to <code>data/</code> and survive upgrades.</p>
        <table class="guide-table">
          <tr><th>Template</th><th>Sent when</th></tr>
          <tr><td>Welcome</td><td>Subscriber registers a new account</td></tr>
          <tr><td>Usage warning</td><td>Subscriber reaches ~80% of their API quota</td></tr>
          <tr><td>Training complete</td><td>A model finishes a training run</td></tr>
          <tr><td>Receipt</td><td>A payment is confirmed</td></tr>
          <tr><td>Suspended</td><td>An admin suspends a subscriber account</td></tr>
        </table>
      </div>
      <div class="section">
        <h3>Editing a template</h3>
        <div class="steps">
          <div class="step"><div class="step-num">1</div><div class="step-body"><h4>Open Email templates</h4><p>Admin sidebar → <strong>Email templates</strong>. The left panel lists all available templates; an amber dot indicates a customised template.</p></div></div>
          <div class="step"><div class="step-num">2</div><div class="step-body"><h4>Edit subject and body</h4><p>Edit the subject line and the inner HTML body. The header/footer branding wrapper is added automatically — you only write the inner content.</p></div></div>
          <div class="step"><div class="step-num">3</div><div class="step-body"><h4>Use variable placeholders</h4><p>Each template has its own set of variables shown in the <em>Variables</em> panel on the left. Wrap them in curly braces, e.g. <code>{name}</code>, <code>{plan}</code>, <code>{api_key}</code>. They are substituted at send time.</p></div></div>
          <div class="step"><div class="step-num">4</div><div class="step-body"><h4>Preview before saving</h4><p>Click <strong>Preview email</strong> to render a live preview with sample data in the iframe below the editor. Verify layout and copy, then click <strong>Save template</strong>.</p></div></div>
        </div>
        <div class="callout tip">
          <div class="ico">&#9998;</div>
          <div class="body"><h4>Reset to default</h4><p>If a template is customised, a <strong>Reset to default</strong> button appears. Clicking it restores the built-in template and removes your saved override.</p></div>
        </div>
      </div>
      <div class="section">
        <h3>Common variables reference</h3>
        <table class="guide-table">
          <tr><th>Variable</th><th>Available in</th><th>Value example</th></tr>
          <tr><td><code>{name}</code></td><td>All templates</td><td>Jane Smith</td></tr>
          <tr><td><code>{platform}</code></td><td>All templates</td><td>Yuga</td></tr>
          <tr><td><code>{plan}</code></td><td>Welcome, Usage warning, Receipt</td><td>Pro</td></tr>
          <tr><td><code>{api_key}</code></td><td>Welcome</td><td>yk_live_xxx…</td></tr>
          <tr><td><code>{used}</code>, <code>{limit}</code>, <code>{pct}</code></td><td>Usage warning</td><td>8,200 / 10,000 / 82</td></tr>
          <tr><td><code>{model}</code>, <code>{steps}</code>, <code>{loss}</code></td><td>Training complete</td><td>yug10 / 50,000 / 1.48</td></tr>
          <tr><td><code>{amount}</code>, <code>{gateway}</code>, <code>{ref}</code>, <code>{date}</code></td><td>Receipt</td><td>Rs 999.00 / eSewa / ESW-… / 25 Mar 2026</td></tr>
          <tr><td><code>{reason}</code></td><td>Suspended</td><td>Payment overdue</td></tr>
        </table>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════
         CH 24 — PUSH NOTIFICATIONS
    ════════════════════════════════════════════════════════════ -->
    <div class="chapter" id="ch24">
      <div class="chapter-header">
        <div class="ch-num">24</div>
        <div class="ch-meta">
          <h2>Push Notifications</h2>
          <p>Browser push via OneSignal — free for up to 10,000 subscribers</p>
        </div>
      </div>
      <div class="section">
        <h3>Setup steps</h3>
        <div class="steps">
          <div class="step"><div class="step-num">1</div><div class="step-body"><h4>Create a OneSignal account</h4><p>Go to <strong>onesignal.com</strong> → New App → Web Push → enter your site URL. OneSignal is free for unlimited web push with up to 10,000 subscribers.</p></div></div>
          <div class="step"><div class="step-num">2</div><div class="step-body"><h4>Copy credentials</h4><p>In OneSignal dashboard → Settings → Keys &amp; IDs → copy the <strong>App ID</strong> and <strong>REST API Key</strong>.</p></div></div>
          <div class="step"><div class="step-num">3</div><div class="step-body"><h4>Paste into Yuga</h4><p>Admin → <strong>Push notifications</strong> → paste App ID and REST API Key → tick the events you want to trigger automatically → click <strong>Save push settings</strong>.</p></div></div>
          <div class="step"><div class="step-num">4</div><div class="step-body"><h4>Add SDK to your portal</h4><p>In OneSignal dashboard → Setup → Web → copy the SDK snippet and paste it inside <code>&lt;head&gt;</code> of <code>portal/index.php</code>. OneSignal then shows a native browser permission prompt to portal visitors.</p></div></div>
        </div>
      </div>
      <div class="section">
        <h3>Automatic event triggers</h3>
        <table class="guide-table">
          <tr><th>Event key</th><th>Fires when</th></tr>
          <tr><td>new_subscriber</td><td>A new subscriber registers</td></tr>
          <tr><td>payment</td><td>A payment is confirmed</td></tr>
          <tr><td>training_complete</td><td>A model training run finishes</td></tr>
          <tr><td>api_limit_warning</td><td>A subscriber hits 80% of their API quota</td></tr>
          <tr><td>api_limit_exceeded</td><td>A subscriber hits 100% of their API quota</td></tr>
        </table>
        <p>Toggle each event on or off in the settings form. Enabled events push a notification to <em>all</em> subscribed browser users automatically.</p>
      </div>
      <div class="section">
        <h3>Sending a manual broadcast</h3>
        <p>Once credentials are saved, the <em>Send broadcast</em> card becomes active. Fill in a title, message, and optional click URL, then click <strong>Send to all subscribers</strong>. The notification is delivered immediately via OneSignal.</p>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════
         CH 25 — BLOG MANAGER
    ════════════════════════════════════════════════════════════ -->
    <div class="chapter" id="ch25">
      <div class="chapter-header">
        <div class="ch-num">25</div>
        <div class="ch-meta">
          <h2>Blog Manager</h2>
          <p>Publish posts visible from your public portal</p>
        </div>
      </div>
      <div class="section">
        <h3>Creating a post</h3>
        <div class="steps">
          <div class="step"><div class="step-num">1</div><div class="step-body"><h4>Open Blog</h4><p>Admin sidebar → <strong>Blog</strong> → click <strong>+ New post</strong>.</p></div></div>
          <div class="step"><div class="step-num">2</div><div class="step-body"><h4>Fill in the fields</h4><p><strong>Title</strong> (required), <strong>Slug</strong> (auto-generated from title if left blank), <strong>Author</strong>, <strong>Excerpt</strong> (shown in listing), <strong>Body</strong> (HTML supported), and <strong>Tags</strong> (comma-separated).</p></div></div>
          <div class="step"><div class="step-num">3</div><div class="step-body"><h4>Set status</h4><p>Choose <em>Draft</em> to save without publishing, or <em>Published</em> to make it visible on the portal. You can switch status at any time.</p></div></div>
          <div class="step"><div class="step-num">4</div><div class="step-body"><h4>Save</h4><p>Click <strong>Save post</strong>. The post appears in the list with its status indicator (green = published, grey = draft).</p></div></div>
        </div>
      </div>
      <div class="section">
        <h3>Managing posts</h3>
        <p>The post list shows title, tags, status, and creation date. Click a title or the <strong>Edit</strong> button to re-open the editor. Click <strong>Del</strong> and confirm to permanently delete a post.</p>
        <div class="callout info">
          <div class="ico">&#8505;</div>
          <div class="body"><h4>Blog posts as training data</h4><p>Published blog posts can be fed back into your model training. Copy the post URL and use it as a training source in Train / crawl → URL tab. This lets your AI reference your own published content.</p></div>
        </div>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════
         CH 26 — SUPPORT TICKETS
    ════════════════════════════════════════════════════════════ -->
    <div class="chapter" id="ch26">
      <div class="chapter-header">
        <div class="ch-num">26</div>
        <div class="ch-meta">
          <h2>Support Tickets</h2>
          <p>Manage and reply to tickets raised by your subscribers</p>
        </div>
      </div>
      <div class="section">
        <h3>Ticket lifecycle</h3>
        <table class="guide-table">
          <tr><th>Status</th><th>Meaning</th></tr>
          <tr><td>open</td><td>New ticket awaiting a response</td></tr>
          <tr><td>in_progress</td><td>You have replied; conversation is ongoing</td></tr>
          <tr><td>resolved</td><td>Issue closed; subscriber was helped</td></tr>
          <tr><td>closed</td><td>Ticket archived (no further replies expected)</td></tr>
        </table>
      </div>
      <div class="section">
        <h3>Replying to a ticket</h3>
        <div class="steps">
          <div class="step"><div class="step-num">1</div><div class="step-body"><h4>Open the ticket</h4><p>Admin → <strong>Support tickets</strong> → click <strong>View</strong> on any ticket. The full message thread is shown on the right.</p></div></div>
          <div class="step"><div class="step-num">2</div><div class="step-body"><h4>Type your reply</h4><p>Use the reply textarea at the bottom of the thread. Click <strong>Send reply</strong>. The reply is stored in the database and — if SMTP is configured — emailed to the subscriber automatically.</p></div></div>
          <div class="step"><div class="step-num">3</div><div class="step-body"><h4>Update status</h4><p>Use the status dropdown in the ticket header to advance the ticket through its lifecycle. The change is applied immediately on selection without a separate save.</p></div></div>
        </div>
      </div>
      <div class="section">
        <h3>Priority badges</h3>
        <p>Tickets have a priority set by the subscriber: <em>normal</em>, <em>high</em>, or <em>urgent</em>. High and urgent are highlighted in amber. Sort the list by clicking column headers, or filter by status using the quick-filter buttons at the top.</p>
        <div class="callout warn">
          <div class="ico">&#9888;</div>
          <div class="body"><h4>Deletion is permanent</h4><p>Deleting a ticket removes the entire thread including all messages. There is no recycle bin. Export or screenshot important threads before deleting.</p></div>
        </div>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════
         CH 27 — CONTACT INBOX
    ════════════════════════════════════════════════════════════ -->
    <div class="chapter" id="ch27">
      <div class="chapter-header">
        <div class="ch-num">27</div>
        <div class="ch-meta">
          <h2>Contact Inbox</h2>
          <p>Read messages submitted via the portal contact form</p>
        </div>
      </div>
      <div class="section">
        <h3>How it works</h3>
        <p>When a visitor fills in the contact form on your public portal, the message is stored and appears in the Contact inbox. Unread messages are highlighted in bold with an amber <em>New</em> badge. The unread count is also shown in the sidebar nav item.</p>
      </div>
      <div class="section">
        <h3>Reading and replying</h3>
        <div class="steps">
          <div class="step"><div class="step-num">1</div><div class="step-body"><h4>Open Contact inbox</h4><p>Admin sidebar → <strong>Contact inbox</strong>. New (unread) messages are bolded.</p></div></div>
          <div class="step"><div class="step-num">2</div><div class="step-body"><h4>Click a message</h4><p>The full message body appears on the right panel. Opening a message automatically marks it as read.</p></div></div>
          <div class="step"><div class="step-num">3</div><div class="step-body"><h4>Reply by email</h4><p>Click <strong>Reply via email</strong>. This opens your local email client with the sender's address and a pre-filled subject (<em>Re: original subject</em>). Replies are sent from your own email — not stored in Yuga.</p></div></div>
          <div class="step"><div class="step-num">4</div><div class="step-body"><h4>Delete when done</h4><p>Click <strong>Delete</strong> and confirm to remove the message. Inbox is read-only — there is no way to archive without deleting.</p></div></div>
        </div>
      </div>
      <div class="section">
        <h3>Contact inbox vs Support tickets</h3>
        <table class="guide-table">
          <tr><th></th><th>Contact inbox</th><th>Support tickets</th></tr>
          <tr><td>Who submits</td><td>Any visitor (anonymous or logged-in)</td><td>Logged-in subscribers only</td></tr>
          <tr><td>Threading</td><td>Single message</td><td>Multi-message thread</td></tr>
          <tr><td>Replying</td><td>Opens your email client</td><td>In-app reply stored in DB</td></tr>
          <tr><td>Status tracking</td><td>Read / Unread</td><td>Open → In progress → Resolved → Closed</td></tr>
        </table>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════
         CH 28 — NEPALI PIPELINE
    ════════════════════════════════════════════════════════════ -->
    <div class="chapter" id="ch28">
      <div class="chapter-header">
        <div class="ch-num">28</div>
        <div class="ch-meta">
          <h2>Nepali Pipeline</h2>
          <p>One-click training from curated Nepali &amp; English sources</p>
        </div>
      </div>
      <div class="section">
        <h3>What is the Nepali pipeline?</h3>
        <p>The Nepali pipeline is a curated library of Nepali-language and bilingual (Nepali + English) web sources — news portals, government sites, encyclopaedia entries, educational content, and more. Instead of hunting for URLs manually, you pick sources from the catalog and Yuga trains your model on them in one click.</p>
      </div>
      <div class="section">
        <h3>Source catalog</h3>
        <p>Each source in the catalog has a label, URL, language tag (<em>नेपाली</em>, <em>English</em>, or <em>Both</em>), estimated page count, and descriptive tags. Sources are organised into named <strong>groups</strong> for quick batch selection.</p>
        <div class="callout info">
          <div class="ico">&#8505;</div>
          <div class="body"><h4>Language tags</h4><p>The <em>नेपाली</em> tag means Devanagari script content. The <em>Both</em> tag means the site publishes in both Nepali and English. All sources are safe for fine-tuning a general-purpose YugaLM model.</p></div>
        </div>
      </div>
      <div class="section">
        <h3>Running training from the pipeline</h3>
        <div class="steps">
          <div class="step"><div class="step-num">1</div><div class="step-body"><h4>Open Nepali pipeline</h4><p>Admin sidebar → <strong>Nepali pipeline</strong>.</p></div></div>
          <div class="step"><div class="step-num">2</div><div class="step-body"><h4>Choose a source or group</h4><p>To train on a single source, click its <strong>Train</strong> button in the table. To train on a curated group at once, click one of the <em>Quick groups</em> buttons at the top (e.g. <em>news</em>, <em>education</em>, <em>government</em>).</p></div></div>
          <div class="step"><div class="step-num">3</div><div class="step-body"><h4>Monitor progress</h4><p>A live output panel streams crawl and training progress. Each source is fetched, cleaned, and fed into the selected model. You can see token counts and loss metrics as training proceeds.</p></div></div>
        </div>
      </div>
      <div class="section">
        <h3>Combining with Workflows</h3>
        <p>Nepali pipeline sources can be used as steps inside the Workflow engine (Chapter 29). This lets you schedule daily or weekly runs that automatically keep your Nepali-language model up to date with the latest news and content.</p>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════
         CH 29 — WORKFLOWS
    ════════════════════════════════════════════════════════════ -->
    <div class="chapter" id="ch29">
      <div class="chapter-header">
        <div class="ch-num">29</div>
        <div class="ch-meta">
          <h2>Workflows</h2>
          <p>Multi-step automation chains — crawl, train, notify, and more</p>
        </div>
      </div>
      <div class="section">
        <h3>What is a workflow?</h3>
        <p>A workflow is a named sequence of steps that run in order against a chosen model. Each step performs one action — fetching a URL, training on text, sending a notification, etc. Workflows can be triggered manually or scheduled via the Scheduler (Chapter 18).</p>
      </div>
      <div class="section">
        <h3>Step types</h3>
        <table class="guide-table">
          <tr><th>Step type</th><th>What it does</th></tr>
          <tr><td>Crawl URL</td><td>Fetches a single URL and trains the model on its content</td></tr>
          <tr><td>Train text</td><td>Trains the model on a fixed block of text defined in the step</td></tr>
          <tr><td>Nepali source</td><td>Trains on a specific source or group from the Nepali pipeline catalog</td></tr>
          <tr><td>Notify (webhook)</td><td>POSTs a JSON payload to an external URL when the step is reached</td></tr>
          <tr><td>Push notification</td><td>Sends a push broadcast via OneSignal (requires push settings configured)</td></tr>
        </table>
      </div>
      <div class="section">
        <h3>Creating a workflow</h3>
        <div class="steps">
          <div class="step"><div class="step-num">1</div><div class="step-body"><h4>Open Workflows</h4><p>Admin sidebar → <strong>Workflows</strong> → click <strong>+ New</strong>.</p></div></div>
          <div class="step"><div class="step-num">2</div><div class="step-body"><h4>Name it and select a model</h4><p>Give the workflow a descriptive name (e.g. <em>Nepal AI daily training</em>) and pick the model it should operate on.</p></div></div>
          <div class="step"><div class="step-num">3</div><div class="step-body"><h4>Add steps</h4><p>Click the <strong>+ Step type</strong> buttons below the step list to add steps. Each added step shows an inline form for its parameters. Drag to reorder.</p></div></div>
          <div class="step"><div class="step-num">4</div><div class="step-body"><h4>Save</h4><p>Click <strong>Save workflow</strong>. The workflow appears in the list with an <em>Off</em> indicator by default (toggle on to enable scheduled runs).</p></div></div>
          <div class="step"><div class="step-num">5</div><div class="step-body"><h4>Run manually</h4><p>Click <strong>Run</strong> on any workflow to execute it immediately. A live output panel streams progress step by step. Results are also recorded in <em>Recent runs</em>.</p></div></div>
        </div>
      </div>
      <div class="section">
        <h3>Scheduling a workflow</h3>
        <p>Workflows integrate with the Scheduler (Chapter 18). In Admin → Scheduler, create a new scheduled job, set the cron expression, and set the action to <em>Run workflow</em> with the workflow ID. The workflow will then execute automatically on your chosen cadence.</p>
        <div class="callout tip">
          <div class="ico">&#9998;</div>
          <div class="body"><h4>Example: daily fresh model</h4><p>Create a workflow with steps: (1) Crawl URL → your homepage; (2) Nepali source → news group; (3) Push notification → "Model refreshed". Schedule it daily at 03:00. Your model stays current with zero manual effort.</p></div>
        </div>
      </div>
      <div class="section">
        <h3>Run history</h3>
        <p>The <em>Recent runs</em> card on the Workflows page shows the last 10 executions across all workflows, with status (<em>completed</em> or <em>failed</em>) and timestamp. A failed run does not prevent the next scheduled run from starting.</p>
      </div>
    </div>

    <!-- Footer -->
    <div style="border-top:1px solid var(--bd);padding-top:32px;margin-top:32px;text-align:center">
      <p style="font-size:13px;color:var(--di)">Yuga v1.0 Knowledge Base · 29 chapters · Built for operators running self-hosted AI on shared PHP hosting</p>
      <a href="index.php" style="display:inline-flex;align-items:center;gap:8px;margin-top:14px;font-size:13px;color:var(--mu);padding:8px 20px;background:rgba(255,255,255,.05);border:1px solid var(--bd2);border-radius:8px;transition:.2s" onmouseover="this.style.color='var(--pl)'" onmouseout="this.style.color='var(--mu)'">&#8592; Back to admin panel</a>
    </div>

  </div><!-- /content -->
</div><!-- /main -->

<script>
// Sidebar active link based on scroll
const chapters = document.querySelectorAll('.chapter');
const links = document.querySelectorAll('.sb-link');
function updateActive() {
  let current = '';
  chapters.forEach(c => { if (window.scrollY >= c.offsetTop - 120) current = c.id; });
  links.forEach(l => {
    const href = l.getAttribute('href').slice(1);
    l.classList.toggle('active', href === current);
  });
}
window.addEventListener('scroll', updateActive);
updateActive();

// Search filter
function filterGuide(q) {
  const lower = q.toLowerCase();
  document.querySelectorAll('.chapter').forEach(ch => {
    const text = ch.textContent.toLowerCase();
    ch.style.display = (!q || text.includes(lower)) ? '' : 'none';
  });
}
</script>
</body>
</html>
