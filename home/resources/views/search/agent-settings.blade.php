@php
    $themeService = app(\App\Services\HomeThemeService::class);
    $theme = $themeService->getTheme();
    $headerLogo = $themeService->getHeaderLogoHtml('h-7');
@endphp

@extends('layouts.app')

@section('title', 'Agent Settings — YGXONE Browser')

@section('content')
<div class="min-h-screen bg-[var(--yg-surface)]/30">
    {{-- Header --}}
    <header class="bg-white border-b border-[var(--yg-border)] sticky top-0 z-40">
        <div class="max-w-3xl mx-auto px-4 py-3 flex items-center gap-3">
            <a href="{{ route('browser.home') }}" class="flex-shrink-0">
                {!! $headerLogo !!}
            </a>
            <div class="flex-1"></div>
            <a href="{{ route('browser.home') }}" class="text-xs text-[var(--yg-text-dim)] hover:text-[var(--yg-text)] transition-colors">
                <i class="fas fa-arrow-left mr-1 text-[10px]"></i>Back to Browser
            </a>
        </div>
    </header>

    <div class="max-w-3xl mx-auto px-4 py-8">
        {{-- Page Title --}}
        <div class="mb-8">
            <h1 class="text-2xl font-heading font-black text-[var(--yg-text)] mb-2">
                <i class="fas fa-sparkles text-[var(--yg-primary)] mr-2"></i>AI Agent Settings
            </h1>
            <p class="text-sm text-[var(--yg-text-dim)] leading-relaxed">
                Bring your own API key to power the YG browsing agent. Your key is stored in your session only — never saved to our servers.
            </p>
        </div>

        {{-- Status Card --}}
        <div id="agent-status-card" class="rounded-2xl border border-[var(--yg-border)] bg-white p-5 mb-6">
            <div class="flex items-center gap-3">
                <div id="status-indicator" class="w-3 h-3 rounded-full bg-gray-300 flex-shrink-0"></div>
                <div>
                    <div id="status-text" class="text-sm font-bold text-[var(--yg-text)]">Checking configuration...</div>
                    <div id="status-detail" class="text-xs text-[var(--yg-text-dim)] mt-0.5"></div>
                </div>
            </div>
        </div>

        {{-- Provider Selection --}}
        <div class="mb-6">
            <h2 class="text-sm font-bold text-[var(--yg-text)] uppercase tracking-wider mb-4">Choose Provider</h2>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3" id="provider-grid">
                @foreach($providers as $p)
                <button type="button"
                        data-provider="{{ $p['id'] }}"
                        data-models="{{ json_encode($p['models']) }}"
                        class="provider-card group relative rounded-xl border-2 p-4 text-left transition-all duration-200 hover:shadow-md
                               {{ $currentProvider === $p['id'] ? 'border-[var(--yg-primary)] bg-[var(--yg-primary)]/5' : 'border-[var(--yg-border)] bg-white' }}">
                    <i class="{{ $p['icon'] }} text-xl mb-3 block {{ $currentProvider === $p['id'] ? 'text-[var(--yg-primary)]' : 'text-[var(--yg-text-dim)] group-hover:text-[var(--yg-text)]' }}"></i>
                    <div class="text-sm font-bold text-[var(--yg-text)] mb-1">{{ $p['name'] }}</div>
                    <div class="text-[11px] text-[var(--yg-text-dim)] leading-relaxed">{{ $p['description'] }}</div>
                    @if($currentProvider === $p['id'])
                    <div class="absolute top-3 right-3 w-5 h-5 rounded-full bg-[var(--yg-primary)] flex items-center justify-center">
                        <i class="fas fa-check text-white text-[9px]"></i>
                    </div>
                    @endif
                </button>
                @endforeach
            </div>
        </div>

        {{-- API Key Input --}}
        <div id="api-key-section" class="mb-6 {{ !$currentProvider || $currentProvider === 'ollama' ? 'hidden' : '' }}">
            <h2 class="text-sm font-bold text-[var(--yg-text)] uppercase tracking-wider mb-4">API Key</h2>
            <div class="rounded-xl border border-[var(--yg-border)] bg-white p-4">
                <div class="relative">
                    <input type="password"
                           id="api-key-input"
                           value="{{ $hasApiKey ? '••••••••••••••••' : '' }}"
                           autocomplete="off"
                           placeholder="sk-..."
                           class="w-full bg-transparent border-none outline-none text-sm font-mono text-[var(--yg-text)] placeholder:text-[var(--yg-text-dim)]/30 pr-10">
                    <button type="button"
                            id="toggle-key-visibility"
                            class="absolute right-0 top-1/2 -translate-y-1/2 w-8 h-8 rounded-lg flex items-center justify-center text-[var(--yg-text-dim)] hover:bg-[var(--yg-surface)] transition-colors">
                        <i class="fas fa-eye text-xs"></i>
                    </button>
                </div>
                <p class="text-[10px] text-[var(--yg-text-dim)] mt-2">
                    <i class="fas fa-shield-alt mr-1"></i>
                    Your key is stored in your browser session only. It's never saved to our database or logs.
                    @if($hasApiKey)
                    <br><span class="text-[var(--yg-success)] font-semibold">✓ Key is currently set</span>
                    @endif
                </p>
            </div>
        </div>

        {{-- Model Selection --}}
        <div id="model-section" class="mb-6 {{ !$currentProvider || $currentProvider === 'ollama' ? '' : '' }}">
            <h2 class="text-sm font-bold text-[var(--yg-text)] uppercase tracking-wider mb-4">Model</h2>
            <div class="rounded-xl border border-[var(--yg-border)] bg-white overflow-hidden">
                <select id="model-select"
                        class="w-full bg-transparent border-none outline-none px-4 py-3 text-sm text-[var(--yg-text)] cursor-pointer">
                    @php
                        $models = [];
                        foreach ($providers as $p) {
                            if ($p['id'] === ($currentProvider ?: 'claude')) {
                                $models = $p['models'];
                                break;
                            }
                        }
                        if (empty($models)) $models = $providers[0]['models'];
                    @endphp
                    @foreach($models as $value => $label)
                    <option value="{{ $value }}" {{ $currentModel === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- Ollama URL (only for Ollama) --}}
        <div id="ollama-url-section" class="mb-6 {{ $currentProvider !== 'ollama' ? 'hidden' : '' }}">
            <h2 class="text-sm font-bold text-[var(--yg-text)] uppercase tracking-wider mb-4">Ollama Server URL</h2>
            <div class="rounded-xl border border-[var(--yg-border)] bg-white p-4">
                <input type="text"
                       id="ollama-url-input"
                       value="{{ config('browser.ollama_url', 'http://localhost:11434') }}"
                       autocomplete="off"
                       placeholder="http://localhost:11434"
                       class="w-full bg-transparent border-none outline-none text-sm font-mono text-[var(--yg-text)] placeholder:text-[var(--yg-text-dim)]/30">
                <p class="text-[10px] text-[var(--yg-text-dim)] mt-2">
                    <i class="fas fa-info-circle mr-1"></i>
                    Ollama must be running on this URL. No API key needed.
                </p>
            </div>
        </div>

        {{-- Save + Test Buttons --}}
        <div class="flex items-center gap-3 mb-8">
            <button type="button" id="save-settings-btn"
                    class="flex items-center gap-2 px-6 py-2.5 rounded-xl bg-gradient-to-r from-[var(--yg-primary)] to-[var(--yg-secondary)] text-white text-sm font-bold hover:opacity-90 transition-all shadow-sm">
                <i class="fas fa-save text-xs"></i> Save Settings
            </button>
            <button type="button" id="test-agent-btn"
                    class="flex items-center gap-2 px-4 py-2.5 rounded-xl border border-[var(--yg-border)] text-[var(--yg-text)] text-sm font-semibold hover:bg-[var(--yg-surface)] transition-all">
                <i class="fas fa-flask text-xs"></i> Test Agent
            </button>
            <span id="save-status" class="text-xs text-[var(--yg-text-dim)] hidden"></span>
        </div>

        {{-- Test Output --}}
        <div id="test-output" class="hidden rounded-xl border border-[var(--yg-border)] bg-[var(--yg-surface)]/30 p-4 mb-8">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold text-[var(--yg-text)]">Test Result</span>
                <button type="button" id="clear-test-btn" class="text-[10px] text-[var(--yg-text-dim)] hover:text-[var(--yg-text)]">
                    <i class="fas fa-times mr-1"></i>Clear
                </button>
            </div>
            <div id="test-output-content" class="text-xs text-[var(--yg-text)] leading-relaxed whitespace-pre-wrap"></div>
        </div>

        {{-- Info Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-8">
            <div class="rounded-xl border border-[var(--yg-border)] bg-white p-4">
                <div class="flex items-center gap-2 mb-2">
                    <i class="fas fa-key text-[var(--yg-primary)] text-sm"></i>
                    <span class="text-xs font-bold text-[var(--yg-text)]">Where to get API keys</span>
                </div>
                <div class="text-[11px] text-[var(--yg-text-dim)] space-y-1.5">
                    <p><strong>Anthropic:</strong> <a href="https://console.anthropic.com" target="_blank" class="text-[var(--yg-primary)] hover:underline">console.anthropic.com</a></p>
                    <p><strong>OpenAI:</strong> <a href="https://platform.openai.com/api-keys" target="_blank" class="text-[var(--yg-primary)] hover:underline">platform.openai.com/api-keys</a></p>
                    <p><strong>Ollama:</strong> <a href="https://ollama.com/download" target="_blank" class="text-[var(--yg-primary)] hover:underline">ollama.com/download</a></p>
                </div>
            </div>
            <div class="rounded-xl border border-[var(--yg-border)] bg-white p-4">
                <div class="flex items-center gap-2 mb-2">
                    <i class="fas fa-lightbulb text-amber-500 text-sm"></i>
                    <span class="text-xs font-bold text-[var(--yg-text)]">Pricing (pay-as-you-go)</span>
                </div>
                <div class="text-[11px] text-[var(--yg-text-dim)] space-y-1.5">
                    <p><strong>Claude Haiku:</strong> ~$0.25/million tokens</p>
                    <p><strong>GPT-4o Mini:</strong> ~$0.15/million tokens</p>
                    <p><strong>Ollama:</strong> FREE (runs on your machine)</p>
                    <p class="text-[10px] italic mt-1">Typical agent task: ~2,000 tokens = fractions of a cent</p>
                </div>
            </div>
        </div>

        <div class="text-center pb-8">
            <a href="{{ route('browser.home') }}" class="text-xs text-[var(--yg-text-dim)] hover:text-[var(--yg-primary)] transition-colors">
                <i class="fas fa-arrow-left mr-1"></i>Back to YGXONE Browser
            </a>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let selectedProvider = '{{ $currentProvider ?: 'claude' }}';
    let keyRevealed = false;

    // ── Status check ──
    fetch('/agent/status')
        .then(r => r.json())
        .then(data => {
            const indicator = document.getElementById('status-indicator');
            const statusText = document.getElementById('status-text');
            const statusDetail = document.getElementById('status-detail');

            if (data.configured) {
                indicator.className = 'w-3 h-3 rounded-full bg-green-500 flex-shrink-0 shadow-sm shadow-green-500/30';
                statusText.textContent = data.using_byok ? 'Agent ready — using your API key' : 'Agent ready — using system configuration';
                statusDetail.textContent = 'Provider: ' + data.provider + ' | Model: ' + data.model;
            } else {
                indicator.className = 'w-3 h-3 rounded-full bg-amber-500 flex-shrink-0';
                statusText.textContent = 'Agent not configured';
                statusDetail.textContent = 'Add your API key below to enable the browsing agent.';
            }
        })
        .catch(() => {
            document.getElementById('status-text').textContent = 'Could not check agent status.';
        });

    // ── Provider selection ──
    document.querySelectorAll('.provider-card').forEach(card => {
        card.addEventListener('click', function() {
            const provider = this.dataset.provider;

            // Update selection
            document.querySelectorAll('.provider-card').forEach(c => {
                c.classList.remove('border-[var(--yg-primary)]', 'bg-[var(--yg-primary)]/5');
                c.classList.add('border-[var(--yg-border)]', 'bg-white');
                const check = c.querySelector('.absolute');
                if (check) check.remove();
            });
            this.classList.add('border-[var(--yg-primary)]', 'bg-[var(--yg-primary)]/5');
            this.classList.remove('border-[var(--yg-border)]', 'bg-white');
            const check = document.createElement('div');
            check.className = 'absolute top-3 right-3 w-5 h-5 rounded-full bg-[var(--yg-primary)] flex items-center justify-center';
            check.innerHTML = '<i class="fas fa-check text-white text-[9px]"></i>';
            this.appendChild(check);

            selectedProvider = provider;

            // Toggle API key section
            const keySection = document.getElementById('api-key-section');
            if (provider === 'ollama') {
                keySection.classList.add('hidden');
                document.getElementById('ollama-url-section').classList.remove('hidden');
            } else {
                keySection.classList.remove('hidden');
                document.getElementById('ollama-url-section').classList.add('hidden');
            }

            // Update model options
            const models = JSON.parse(this.dataset.models);
            const modelSelect = document.getElementById('model-select');
            modelSelect.innerHTML = '';
            for (const [value, label] of Object.entries(models)) {
                const opt = document.createElement('option');
                opt.value = value;
                opt.textContent = label;
                modelSelect.appendChild(opt);
            }
        });
    });

    // ── API key visibility toggle ──
    document.getElementById('toggle-key-visibility').addEventListener('click', function() {
        const input = document.getElementById('api-key-input');
        keyRevealed = !keyRevealed;
        this.innerHTML = keyRevealed
            ? '<i class="fas fa-eye-slash text-xs"></i>'
            : '<i class="fas fa-eye text-xs"></i>';
    });

    // Clear placeholder on focus
    document.getElementById('api-key-input').addEventListener('focus', function() {
        if (this.value === '••••••••••••••••') {
            this.value = '';
        }
    });

    // ── Save settings ──
    document.getElementById('save-settings-btn').addEventListener('click', function() {
        const btn = this;
        const status = document.getElementById('save-status');
        btn.disabled = true;
        btn.querySelector('i').className = 'fas fa-circle-notch fa-spin text-xs';
        status.classList.add('hidden');

        const apiKey = document.getElementById('api-key-input').value;
        const model = document.getElementById('model-select').value;
        const ollamaUrl = document.getElementById('ollama-url-input')?.value || '';

        const payload = {
            provider: selectedProvider,
            api_key: apiKey === '••••••••••••••••' ? '' : apiKey,
            model: model,
        };

        if (selectedProvider === 'ollama' && ollamaUrl) {
            payload.ollama_url = ollamaUrl;
        }

        fetch('/agent/settings', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            },
            body: JSON.stringify(payload),
        })
        .then(r => r.json())
        .then(data => {
            btn.querySelector('i').className = 'fas fa-check text-xs';
            btn.disabled = false;
            status.textContent = '✓ ' + data.message;
            status.classList.remove('hidden', 'text-red-500');
            status.classList.add('text-green-600');

            // Update status card
            const indicator = document.getElementById('status-indicator');
            indicator.className = 'w-3 h-3 rounded-full bg-green-500 flex-shrink-0 shadow-sm shadow-green-500/30';
            document.getElementById('status-text').textContent = 'Agent ready — using your API key';
            document.getElementById('status-detail').textContent = 'Provider: ' + data.provider;

            setTimeout(() => status.classList.add('hidden'), 3000);
        })
        .catch(err => {
            btn.querySelector('i').className = 'fas fa-exclamation-triangle text-xs';
            btn.disabled = false;
            status.textContent = '✗ Failed to save. Try again.';
            status.classList.remove('hidden');
            status.classList.add('text-red-500');
        });
    });

    // ── Test agent ──
    document.getElementById('test-agent-btn').addEventListener('click', function() {
        const btn = this;
        const output = document.getElementById('test-output');
        const content = document.getElementById('test-output-content');
        btn.disabled = true;
        btn.querySelector('i').className = 'fas fa-circle-notch fa-spin text-xs';

        output.classList.remove('hidden');
        content.textContent = 'Running test...\n\n';

        fetch('/agent/run', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            },
            body: JSON.stringify({ task: 'What is 2+2? Reply with just the number.' }),
        })
        .then(r => r.json())
        .then(data => {
            content.textContent = 'Response: ' + (data.response || 'No response') +
                '\n\nSteps: ' + JSON.stringify(data.steps, null, 2);
            btn.querySelector('i').className = 'fas fa-check text-xs';
            btn.disabled = false;
        })
        .catch(err => {
            content.textContent = 'Error: ' + err.message;
            btn.querySelector('i').className = 'fas fa-exclamation-triangle text-xs';
            btn.disabled = false;
        });
    });

    // ── Clear test output ──
    document.getElementById('clear-test-btn').addEventListener('click', function() {
        document.getElementById('test-output').classList.add('hidden');
    });
});
</script>
@endsection
