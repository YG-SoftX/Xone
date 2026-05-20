<?php

namespace App\Http\Controllers;

use App\Services\BrowserAgentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

/**
 * AgentController — Handles AI agent interactions for the agentic browser.
 *
 * Endpoints:
 *   POST /agent/run         — Execute an agent task (AJAX)
 *   GET  /agent/settings    — Show BYOK settings page
 *   POST /agent/settings    — Save BYOK API key to session
 *   GET  /agent/status      — Check agent configuration status
 */
class AgentController extends Controller
{
    /**
     * Run the AI agent on a user task (AJAX endpoint).
     */
    public function run(Request $request)
    {
        $request->validate([
            'task'        => 'required|string|max:2000',
            'current_url' => 'nullable|string|max:2000',
        ]);

        $task       = $request->input('task');
        $currentUrl = $request->input('current_url', '');

        try {
            $agent  = app(BrowserAgentService::class);
            $result = $agent->run($task, $currentUrl);

            return response()->json([
                'success'  => $result['success'],
                'response' => $result['response'],
                'steps'    => $result['steps'],
            ]);

        } catch (\Exception $e) {
            Log::error("Agent run failed: " . $e->getMessage(), [
                'task'  => $task,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success'  => false,
                'response' => 'An error occurred while running the agent. Please try again.',
                'steps'    => [],
            ], 500);
        }
    }

    /**
     * Show the BYOK (Bring Your Own Key) settings page.
     */
    public function settings()
    {
        $providers = [
            [
                'id'          => 'claude',
                'name'        => 'Anthropic Claude',
                'description' => 'Best for complex reasoning & tool use. Get a key at console.anthropic.com.',
                'icon'        => 'fas fa-brain',
                'models'      => ['claude-haiku-4-5-20251001' => 'Claude Haiku (Fast)', 'claude-sonnet-4-20250514' => 'Claude Sonnet (Balanced)'],
            ],
            [
                'id'          => 'openai',
                'name'        => 'OpenAI',
                'description' => 'Best for general-purpose tasks. Get a key at platform.openai.com.',
                'icon'        => 'fas fa-robot',
                'models'      => ['gpt-4o-mini' => 'GPT-4o Mini (Fast)', 'gpt-4o' => 'GPT-4o (Powerful)'],
            ],
            [
                'id'          => 'ollama',
                'name'        => 'Ollama (Local)',
                'description' => 'Run models locally — free & private. Requires Ollama running on your machine.',
                'icon'        => 'fas fa-server',
                'models'      => ['llama3.2' => 'Llama 3.2', 'mistral' => 'Mistral', 'phi3' => 'Phi-3'],
            ],
        ];

        return view('search.agent-settings', [
            'providers'      => $providers,
            'currentProvider' => Session::get('browser_agent_provider', ''),
            'hasApiKey'       => (bool) Session::get('browser_agent_api_key'),
            'currentModel'    => Session::get('browser_agent_model', ''),
        ]);
    }

    /**
     * Save BYOK settings to session.
     */
    public function saveSettings(Request $request)
    {
        $request->validate([
            'provider'    => 'required|in:claude,openai,ollama',
            'api_key'     => 'nullable|string|max:500',
            'model'       => 'nullable|string|max:100',
            'ollama_url'  => 'nullable|string|max:500',
        ]);

        $provider = $request->input('provider');

        // Clear existing settings
        Session::forget(['browser_agent_provider', 'browser_agent_api_key', 'browser_agent_model', 'browser_ollama_url']);

        // Save new settings
        Session::put('browser_agent_provider', $provider);

        if ($request->filled('api_key') && $provider !== 'ollama') {
            // Encrypt API key before storing in session
            Session::put('browser_agent_api_key', Crypt::encryptString(trim($request->input('api_key'))));
        }

        if ($request->filled('model')) {
            Session::put('browser_agent_model', trim($request->input('model')));
        }

        if ($request->filled('ollama_url') && $provider === 'ollama') {
            Session::put('browser_ollama_url', trim($request->input('ollama_url')));
        }

        return response()->json([
            'success'  => true,
            'message'  => 'Agent settings saved.',
            'provider' => $provider,
            'has_key'  => (bool) Session::get('browser_agent_api_key'),
        ]);
    }

    /**
     * Check agent configuration status.
     */
    public function status()
    {
        $provider = Session::get('browser_agent_provider');
        $hasKey   = (bool) Session::get('browser_agent_api_key');
        $model    = Session::get('browser_agent_model');

        // Also check ai/ config fallback (same pattern as BrowserAgentService::loadConfig)
        $hasFallback  = false;
        $aiConfigPath = base_path('../ai/config.php');
        if (file_exists($aiConfigPath)) {
            try {
                $aiConfig = require $aiConfigPath;
                $hasFallback = !empty($aiConfig['anthropic_api_key']) || !empty($aiConfig['openai_api_key']);
            } catch (\Exception $e) {
                Log::warning('Agent status: Could not load ai/config.php');
            }
        }

        return response()->json([
            'configured'   => $hasKey || $hasFallback,
            'provider'     => $provider ?: ($hasFallback ? 'system' : null),
            'model'        => $model ?: 'default',
            'using_byok'   => $hasKey,
            'using_system' => !$hasKey && $hasFallback,
            'ollama_url'   => Session::get('browser_ollama_url'),
        ]);
    }
}
