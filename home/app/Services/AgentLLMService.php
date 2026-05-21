<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use App\Services\BrowserConfigService;

class AgentLLMService
{
    private array $config;
    private array $toolSchemas = [];

    public function __construct()
    {
        $this->config = $this->loadConfig();
        $this->toolSchemas = $this->buildToolSchemas();
    }

    public function callWithTools(string $system, array $history): array
    {
        $backend = $this->config['llm_backend'] ?? 'claude';
        return match ($backend) {
            'openai' => $this->callOpenAI($system, $history),
            'claude' => $this->callClaude($system, $history),
            'ollama' => $this->callOllama($system, $history),
            default  => ['text' => 'No LLM backend configured.', 'toolCalls' => [], 'error' => 'unknown_backend'],
        };
    }

    public function getOpenAIToolSchemas(): array { return $this->toolSchemas['openai'] ?? []; }
    public function getClaudeToolSchemas(): array  { return $this->toolSchemas['anthropic'] ?? []; }
    public function getToolSchemas(): array         { return $this->toolSchemas; }

    public function parseToolCalls(string $raw): array
    {
        $calls = [];
        if (preg_match_all('/\\[\\[TOOL: (\\w+)\\((.*?)\\]\\]/', $raw, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $calls[] = ['name' => $m[1], 'arguments' => $this->parseJsonSafe($m[2])];
            }
        }
        return $calls;
    }

    private function callOpenAI(string $system, array $history): array
    {
        $apiKey = $this->config['openai_api_key'] ?? '';
        $model  = $this->config['openai_model']  ?? 'gpt-4o';
        if (empty($apiKey)) return ['text' => 'OpenAI API key not configured.', 'toolCalls' => [], 'error' => 'missing_key'];
        $messages = array_merge([['role' => 'system', 'content' => $system]], $history);
        try {
            $response = Http::withToken($apiKey)->timeout(60)->post('https://api.openai.com/v1/chat/completions', [
                'model' => $model, 'messages' => $messages,
                'tools' => $this->toolSchemas['openai'], 'tool_choice' => 'auto',
            ]);
            if (!$response->successful()) {
                Log::error('OpenAI API error', ['status' => $response->status()]);
                return ['text' => 'OpenAI error: ' . $response->status(), 'toolCalls' => [], 'error' => 'api_error'];
            }
            $data = $response->json();
            $choice = $data['choices'][0] ?? null;
            if (!$choice) return ['text' => 'No response from OpenAI.', 'toolCalls' => [], 'error' => 'empty_response'];
            $msg = $choice['message'];
            $toolCalls = [];
            if (!empty($msg['tool_calls'])) {
                foreach ($msg['tool_calls'] as $tc) {
                    $toolCalls[] = ['name' => $tc['function']['name'], 'arguments' => $this->parseJsonSafe($tc['function']['arguments'])];
                }
            }
            return ['text' => $msg['content'] ?? '', 'toolCalls' => $toolCalls, 'error' => null];
        } catch (\Exception $e) {
            return ['text' => 'OpenAI error: ' . $e->getMessage(), 'toolCalls' => [], 'error' => 'exception'];
        }
    }

    private function callClaude(string $system, array $history): array
    {
        $apiKey = $this->config['claude_api_key'] ?? '';
        $model  = $this->config['claude_model']   ?? 'claude-sonnet-4-20250514';
        if (empty($apiKey)) return ['text' => 'Claude API key not configured.', 'toolCalls' => [], 'error' => 'missing_key'];
        try {
            $payload = ['model' => $model, 'messages' => $history, 'max_tokens' => 4096, 'tools' => $this->toolSchemas['anthropic']];
            if (!empty($system)) $payload['system'] = $system;
            $response = Http::withToken($apiKey)->timeout(60)->post('https://api.anthropic.com/v1/messages', $payload);
            if (!$response->successful()) {
                Log::error('Claude API error', ['status' => $response->status()]);
                return ['text' => 'Claude error: ' . $response->status(), 'toolCalls' => [], 'error' => 'api_error'];
            }
            $data = $response->json();
            $text = ''; $toolCalls = [];
            foreach (($data['content'] ?? []) as $block) {
                if ($block['type'] === 'text') $text .= $block['text'];
                elseif ($block['type'] === 'tool_use') $toolCalls[] = ['name' => $block['name'], 'arguments' => $block['input'] ?? []];
            }
            return ['text' => trim($text), 'toolCalls' => $toolCalls, 'error' => null];
        } catch (\Exception $e) {
            return ['text' => 'Claude error: ' . $e->getMessage(), 'toolCalls' => [], 'error' => 'exception'];
        }
    }

    private function callOllama(string $system, array $history): array
    {
        $baseUrl = $this->config['ollama_url'] ?? 'http://localhost:11434';
        $model   = $this->config['ollama_model'] ?? 'llama3';
        try {
            $messages = array_merge([['role' => 'system', 'content' => $system]], $history);
            $response = Http::timeout(90)->post($baseUrl . '/api/chat', ['model' => $model, 'messages' => $messages, 'stream' => false]);
            if (!$response->successful()) return ['text' => 'Ollama error: ' . $response->status(), 'toolCalls' => [], 'error' => 'api_error'];
            $raw = $response->json()['message']['content'] ?? '';
            return ['text' => $raw, 'toolCalls' => $this->parseToolCalls($raw), 'error' => null];
        } catch (\Exception $e) {
            return ['text' => 'Ollama error: ' . $e->getMessage(), 'toolCalls' => [], 'error' => 'exception'];
        }
    }

    private function buildToolSchemas(): array
    {
        $openai = [
            [
                'type' => 'function',
                'function' => [
                    'name' => 'browse_url',
                    'description' => 'Navigate browser to URL and extract structured content',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'url' => ['type' => 'string'],
                            'prompt' => ['type' => 'string'],
                        ],
                        'required' => ['url'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'search_web',
                    'description' => 'Search the web for information',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'query' => ['type' => 'string'],
                            'num' => ['type' => 'integer'],
                        ],
                        'required' => ['query'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'click_element',
                    'description' => 'Click a button or link by CSS selector',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'selector' => ['type' => 'string'],
                            'text' => ['type' => 'string'],
                        ],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'fill_form',
                    'description' => 'Fill a form field',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'selector' => ['type' => 'string'],
                            'value' => ['type' => 'string'],
                        ],
                        'required' => ['selector', 'value'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'extract_content',
                    'description' => 'Extract data via CSS selector',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'selector' => ['type' => 'string'],
                            'type' => ['type' => 'string', 'enum' => ['text', 'html', 'href', 'src', 'table']],
                        ],
                        'required' => ['selector'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'execute_js',
                    'description' => 'Execute JavaScript on page',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'code' => ['type' => 'string'],
                        ],
                        'required' => ['code'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_page_info',
                    'description' => 'Get page metadata',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [],
                        'required' => [],
                    ],
                ],
            ],
        ];

        $anthropic = [
            [
                'name' => 'browse_url',
                'description' => 'Navigate browser to URL and extract structured content',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'url' => ['type' => 'string'],
                        'prompt' => ['type' => 'string'],
                    ],
                    'required' => ['url'],
                ],
            ],
            [
                'name' => 'search_web',
                'description' => 'Search the web for information',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'query' => ['type' => 'string'],
                        'num' => ['type' => 'integer'],
                    ],
                    'required' => ['query'],
                ],
            ],
            [
                'name' => 'click_element',
                'description' => 'Click a button or link by CSS selector',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'selector' => ['type' => 'string'],
                        'text' => ['type' => 'string'],
                    ],
                ],
            ],
            [
                'name' => 'fill_form',
                'description' => 'Fill a form field',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'selector' => ['type' => 'string'],
                        'value' => ['type' => 'string'],
                    ],
                    'required' => ['selector', 'value'],
                ],
            ],
            [
                'name' => 'extract_content',
                'description' => 'Extract data via CSS selector',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'selector' => ['type' => 'string'],
                        'type' => ['type' => 'string', 'enum' => ['text', 'html', 'href', 'src', 'table']],
                    ],
                    'required' => ['selector'],
                ],
            ],
            [
                'name' => 'execute_js',
                'description' => 'Execute JavaScript on page',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'code' => ['type' => 'string'],
                    ],
                    'required' => ['code'],
                ],
            ],
            [
                'name' => 'get_page_info',
                'description' => 'Get page metadata',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [],
                    'required' => [],
                ],
            ],
        ];

        return ['openai' => $openai, 'anthropic' => $anthropic];
    }

    private function loadConfig(): array
    {
        if ($key = session('llm_api_key')) {
            $provider = session('llm_provider', 'openai');
            $out = ['llm_backend' => $provider];
            if ($provider === 'openai') {
                try { $out['openai_api_key'] = Crypt::decryptString($key); } catch (\Exception $e) { $out['openai_api_key'] = ''; }
                $out['openai_model'] = session('llm_model', 'gpt-4o');
            } elseif ($provider === 'claude') {
                try { $out['claude_api_key'] = Crypt::decryptString($key); } catch (\Exception $e) { $out['claude_api_key'] = ''; }
                $out['claude_model'] = session('llm_model', 'claude-sonnet-4-20250514');
            }
            return $out;
        }
        try {
            $cfg = app(BrowserConfigService::class);
            return [
                'llm_backend'    => $cfg->get('llm_backend', 'claude'),
                'openai_api_key' => $cfg->get('openai_api_key', ''),
                'openai_model'   => $cfg->get('openai_model', 'gpt-4o'),
                'claude_api_key' => $cfg->get('claude_api_key', ''),
                'claude_model'   => $cfg->get('claude_model', 'claude-sonnet-4-20250514'),
                'ollama_url'     => $cfg->get('ollama_url', 'http://localhost:11434'),
                'ollama_model'   => $cfg->get('ollama_model', 'llama3'),
            ];
        } catch (\Exception $e) {
            return ['llm_backend' => 'claude'];
        }
    }

    private function parseJsonSafe(string $json): array
    {
        try {
            $d = json_decode($json, true);
            return is_array($d) ? $d : [];
        } catch (\Exception $e) {
            return [];
        }
    }
}