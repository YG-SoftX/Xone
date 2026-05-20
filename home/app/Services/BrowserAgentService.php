<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

/**
 * BrowserAgentService — AI agent loop for autonomous web browsing.
 *
 * Architecture (cPanel-friendly, PHP-only):
 *   User Task → LLM (Plan) → Parse Response → Execute Tool → Feed Result → LLM → ... → Final Response
 *
 * The LLM is instructed to use [[TOOL:name|arg=val]] directives.
 * Each tool result is fed back into the conversation until the LLM
 * responds without a tool call, meaning it has the final answer.
 *
 * Max 10 tool calls per task to prevent infinite loops.
 */
class BrowserAgentService
{
    private AgentToolService $tools;

    /** Max tool call iterations per task */
    private int $maxIterations = 10;

    /** LLM backend config */
    private array $config;

    /** Conversation history */
    private array $history = [];

    /** Results from the last run */
    private array $lastRun = [];

    public function __construct()
    {
        $this->tools = app(AgentToolService::class);
        $this->config = $this->loadConfig();
    }

    // ── Public API ───────────────────────────────────────────────────────────

    /**
     * Run the agent on a user task.
     * Returns the final response and step-by-step log.
     */
    public function run(string $task, string $currentUrl = ''): array
    {
        $this->history = [];
        $stepLog = [];
        $finalResponse = '';
        $success = false;

        // Build initial system prompt with tool definitions
        $system = $this->buildSystemPrompt($currentUrl);

        // Start conversation
        $this->history[] = ['role' => 'user', 'content' => $task];

        for ($i = 0; $i < $this->maxIterations; $i++) {
            // Call LLM
            $llmResponse = $this->callLLM($system, $this->history);

            // Parse for tool calls
            $toolCalls = $this->parseToolCalls($llmResponse);

            // Detect API errors masquerading as final responses
            if (empty($toolCalls) && $this->isApiError($llmResponse)) {
                // LLM call failed — break with error
                $finalResponse = 'Sorry, the AI service returned an error. Please check your API key and try again.';
                $success = false;
                $stepLog[] = [
                    'step'     => $i + 1,
                    'type'     => 'error',
                    'response' => $llmResponse,
                ];
                break;
            }

            if (empty($toolCalls)) {
                // No tool call — this is the final response
                $finalResponse = $llmResponse;
                $success = true;
                $stepLog[] = [
                    'step'     => $i + 1,
                    'type'     => 'final',
                    'response' => $finalResponse,
                ];
                break;
            }

            // Execute tools
            $toolResults = [];
            foreach ($toolCalls as $tc) {
                $result = $this->tools->execute($tc['tool'], $tc['args']);
                $toolResults[] = [
                    'tool'   => $tc['tool'],
                    'args'   => $tc['args'],
                    'result' => $result,
                ];

                $stepLog[] = [
                    'step'   => $i + 1,
                    'type'   => 'tool',
                    'tool'   => $tc['tool'],
                    'args'   => $tc['args'],
                    'result' => $result['success'] ?? false,
                ];
            }

            // Add assistant response + tool results to history
            $this->history[] = ['role' => 'assistant', 'content' => $llmResponse];

            // Format tool results for the LLM
            $toolResultText = $this->formatToolResults($toolResults);
            $this->history[] = ['role' => 'user', 'content' => "Tool results:\n\n{$toolResultText}\n\nContinue with the task. If you have enough information now, provide your final answer."];
        }

        if (!$success) {
            $finalResponse = "I attempted the task but reached the maximum number of steps. Here's what I found so far. You can try again with a more specific request.";
        }

        $this->lastRun = [
            'success'  => $success,
            'response' => $finalResponse,
            'steps'    => $stepLog,
        ];

        return $this->lastRun;
    }

    /**
     * Get the last run results.
     */
    public function getLastRun(): array
    {
        return $this->lastRun;
    }

    // ── LLM Integration ──────────────────────────────────────────────────────

    /**
     * Build the system prompt with tool definitions.
     */
    private function buildSystemPrompt(string $currentUrl = ''): string
    {
        $prompt = "You are a web browsing AI agent. Your job is to complete tasks by browsing websites, extracting information, and using tools.\n\n";

        if ($currentUrl) {
            $prompt .= "The user is currently viewing: {$currentUrl}\n";
            $prompt .= "Use get_page_content to see what's on the current page.\n\n";
        }

        $prompt .= $this->tools->getToolDefinitions();
        $prompt .= "\n";

        $prompt .= "WORKFLOW:\n";
        $prompt .= "1. Think about what the user wants.\n";
        $prompt .= "2. Use tools step-by-step to accomplish the task.\n";
        $prompt .= "3. When you have the information, respond with a clear, concise answer (NOT a tool call).\n\n";

        $prompt .= "RULES:\n";
        $prompt .= "- Use ONLY the tools listed above.\n";
        $prompt .= "- One tool call per line in the format [[TOOL:name|arg1=val1|arg2=val2]].\n";
        $prompt .= "- You can make MULTIPLE tool calls in one response (one per line).\n";
        $prompt .= "- After you get results, decide if you need more tools or can answer.\n";
        $prompt .= "- When done, respond naturally — NO [[TOOL:...]] in your final answer.\n";
        $prompt .= "- Be concise. Don't narrate your plan, just execute.\n";
        $prompt .= "- Extract only what's asked. Don't over-collect data.\n\n";

        $prompt .= "EXAMPLE:\n";
        $prompt .= "User: Find the price of iPhone on Apple.com\n";
        $prompt .= "Agent: [[TOOL:navigate|url=https://apple.com/iphone]]\n";
        $prompt .= "[After getting page summary with forms and links...]\n";
        $prompt .= "Agent: The main iPhone page shows several models. I can see links for 'iPhone 16 Pro', 'iPhone 16', 'iPhone 15'. Would you like me to navigate to a specific model to find pricing?";

        return $prompt;
    }

    /**
     * Call the configured LLM backend.
     */
    private function callLLM(string $system, array $history): string
    {
        $backend = $this->config['llm_backend'] ?? 'claude';

        return match ($backend) {
            'claude' => $this->callClaude($system, $history),
            'openai' => $this->callOpenAI($system, $history),
            'ollama' => $this->callOllama($system, $history),
            default  => $this->callClaude($system, $history),
        };
    }

    private function callClaude(string $system, array $history): string
    {
        $apiKey = $this->config['api_key'] ?? '';
        if (!$apiKey) {
            return "No API key configured. Please add your API key in the agent settings.";
        }

        $payload = [
            'model'      => 'claude-haiku-4-5-20251001',
            'max_tokens' => 1000,
            'system'     => $system,
            'messages'   => $history,
        ];

        $response = $this->httpPost(
            'https://api.anthropic.com/v1/messages',
            $payload,
            [
                'x-api-key: ' . $apiKey,
                'anthropic-version: 2023-06-01',
                'content-type: application/json',
            ]
        );

        $data = json_decode($response, true);
        return $data['content'][0]['text']
            ?? $data['error']['message']
            ?? 'No response from Claude API.';
    }

    private function callOpenAI(string $system, array $history): string
    {
        $apiKey = $this->config['api_key'] ?? '';
        if (!$apiKey) {
            return "No API key configured. Please add your API key in the agent settings.";
        }

        $messages = array_merge(
            [['role' => 'system', 'content' => $system]],
            $history
        );

        $payload = [
            'model'       => 'gpt-4o-mini',
            'max_tokens'  => 1000,
            'messages'    => $messages,
        ];

        $response = $this->httpPost(
            'https://api.openai.com/v1/chat/completions',
            $payload,
            [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json',
            ]
        );

        $data = json_decode($response, true);
        return $data['choices'][0]['message']['content']
            ?? $data['error']['message']
            ?? 'No response from OpenAI API.';
    }

    private function callOllama(string $system, array $history): string
    {
        $baseUrl = $this->config['ollama_url'] ?? 'http://localhost:11434';

        $messages = array_merge(
            [['role' => 'system', 'content' => $system]],
            $history
        );

        $payload = [
            'model'    => $this->config['ollama_model'] ?? 'llama3.2',
            'messages' => $messages,
            'stream'   => false,
        ];

        $response = $this->httpPost(
            $baseUrl . '/api/chat',
            $payload,
            ['Content-Type: application/json']
        );

        $data = json_decode($response, true);
        return $data['message']['content'] ?? 'No response from Ollama.';
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Parse [[TOOL:name|arg1=val1|arg2=val2]] directives from LLM response.
     */
    private function parseToolCalls(string $response): array
    {
        $calls = [];

        if (preg_match_all('/\[\[TOOL:(\w+)((?:\|[^=\]]+=[^\[\]\]]+)*)\]\]/', $response, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $tool  = $m[1];
                $args  = [];

                // Parse key=value pairs
                $argStr = $m[2] ?? '';
                if (preg_match_all('/\|([^=]+)=([^|\]]+)/', $argStr, $argMatches, PREG_SET_ORDER)) {
                    foreach ($argMatches as $am) {
                        $key = trim($am[1]);
                        $val = trim($am[2]);
                        $args[$key] = $val;
                    }
                }

                $calls[] = ['tool' => $tool, 'args' => $args];
            }
        }

        return $calls;
    }

    /**
     * Format tool execution results for the LLM.
     */
    private function formatToolResults(array $results): string
    {
        $lines = [];
        foreach ($results as $r) {
            $status = $r['result']['success'] ? '✓' : '✗';
            $lines[] = "{$status} Tool: {$r['tool']}(" . json_encode($r['args']) . ")";
            if (isset($r['result']['error'])) {
                $lines[] = "   Error: {$r['result']['error']}";
            }
            if (isset($r['result']['title'])) {
                $lines[] = "   Title: {$r['result']['title']}";
            }
            if (isset($r['result']['summary'])) {
                $summary = is_array($r['result']['summary']) ? json_encode($r['result']['summary']) : $r['result']['summary'];
                $lines[] = "   Summary: " . substr($summary, 0, 800);
            }
            if (isset($r['result']['content'])) {
                $lines[] = "   Content: " . substr($r['result']['content'], 0, 1000);
            }
            if (isset($r['result']['results'])) {
                $lines[] = "   Results (" . $r['result']['total'] . " found):";
                foreach ($r['result']['results'] as $sr) {
                    $lines[] = "     - {$sr['title']}: {$sr['url']}";
                }
            }
            if (isset($r['result']['links'])) {
                $links = $r['result']['links'];
                if (is_array($links)) {
                    $lines[] = "   Top links:";
                    foreach (array_slice($links, 0, 5) as $l) {
                        $lines[] = "     - {$l['text']} → {$l['url']}";
                    }
                }
            }
            $lines[] = '';
        }

        return implode("\n", $lines);
    }

    /**
     * Load configuration. Supports BYOK (Bring Your Own Key) from session,
     * falling back to the ai/ config.
     */
    private function loadConfig(): array
    {
        // Check for user's BYOK settings in session
        $byokProvider = Session::get('browser_agent_provider');
        $byokKey      = Session::get('browser_agent_api_key');
        $byokModel    = Session::get('browser_agent_model');
        $byokOllamaUrl = Session::get('browser_ollama_url');

        // Decrypt BYOK key if present
        if ($byokKey) {
            try {
                $byokKey = Crypt::decryptString($byokKey);
            } catch (\Exception $e) {
                Log::warning('BrowserAgent: Failed to decrypt API key, using fallback.');
                $byokKey = null;
            }
        }

        // Load ai/ module config as fallback
        $aiConfig = [];
        $aiConfigPath = base_path('../ai/config.php');
        if (file_exists($aiConfigPath)) {
            try {
                $aiConfig = require $aiConfigPath;
            } catch (\Exception $e) {
                Log::warning('BrowserAgent: Could not load ai/config.php');
            }
        }

        return [
            'llm_backend'  => $byokProvider ?: ($aiConfig['llm_backend'] ?? 'claude'),
            'api_key'      => $byokKey ?: ($aiConfig['anthropic_api_key'] ?? $aiConfig['openai_api_key'] ?? ''),
            'ollama_url'   => $byokOllamaUrl ?: ($aiConfig['ollama_url'] ?? 'http://localhost:11434'),
            'ollama_model' => $byokModel ?: ($aiConfig['ollama_model'] ?? 'llama3.2'),
        ];
    }

    /**
     * Check if an LLM response is an API error (not a valid final answer).
     */
    private function isApiError(string $response): bool
    {
        $errorPatterns = [
            'No API key configured',
            'No response from',
            'Please add your API key',
            'API key not found',
        ];

        foreach ($errorPatterns as $pattern) {
            if (str_contains($response, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * HTTP POST helper.
     */
    private function httpPost(string $url, array $body, array $headers): string
    {
        $json = json_encode($body);

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $json,
                CURLOPT_HTTPHEADER     => $headers,
                CURLOPT_TIMEOUT        => 60,
                CURLOPT_CONNECTTIMEOUT => 15,
                CURLOPT_SSL_VERIFYPEER => true,
            ]);
            $res = curl_exec($ch);
            $err = curl_error($ch);
            curl_close($ch);

            if ($err) {
                Log::error("BrowserAgent LLM call failed: {$err}");
                return '{}';
            }
            return $res ?: '{}';
        }

        // Fallback: stream context
        $ctx = stream_context_create([
            'http' => [
                'method'  => 'POST',
                'header'  => implode("\r\n", $headers),
                'content' => $json,
                'timeout' => 60,
            ],
        ]);
        $res = @file_get_contents($url, false, $ctx);
        return $res ?: '{}';
    }
}
