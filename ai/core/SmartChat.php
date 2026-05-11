<?php
/**
 * SmartChat — ChatGPT-style conversational AI for Yuga
 *
 * Architecture:
 *   Yuga SelfLearner  →  collects & stores platform knowledge
 *   Claude / OpenAI API →  the reasoning brain
 *   SmartChat           →  injects platform knowledge into every prompt (RAG)
 *
 * This gives you:
 *   ✅ Real, intelligent, conversational answers (like ChatGPT)
 *   ✅ Answers grounded in YOUR platform's content
 *   ✅ No hallucination about your specific prices / policies / docs
 *   ✅ Works on cPanel with no special PHP extensions
 *
 * Supported backends (set in config.php):
 *   'claude'  — Anthropic Claude API (recommended, best quality)
 *   'openai'  — OpenAI ChatGPT API
 *   'ollama'  — Local Ollama (for self-hosted, no API key needed)
 */
class SmartChat {

    private ModelStore $store;
    private string     $model_name;
    private array      $config;

    // Conversation history for multi-turn chat
    private array $history = [];

    // How many chars of platform knowledge to inject
    public int $context_chars = 4000;

    public function __construct(string $model_name, ModelStore $store, array $config = []) {
        $this->model_name = $model_name;
        $this->store      = $store;
        $this->config     = $config;
    }

    // ---------------------------------------------------------------
    // Main chat method — returns a string reply
    // ---------------------------------------------------------------
    public function chat(string $user_message, array $options = []): array {
        $backend = $this->config['llm_backend'] ?? 'claude';

        // 1. Retrieve platform knowledge from the stored corpus
        $knowledge = $this->getKnowledge();

        // 2. Build system prompt = instructions + injected knowledge
        $system = $this->buildSystemPrompt($knowledge);

        // 3. Add user message to history
        $this->history[] = ['role' => 'user', 'content' => $user_message];

        // 4. Call the LLM
        $reply = match ($backend) {
            'claude' => $this->callClaude($system, $this->history, $options),
            'openai' => $this->callOpenAI($system, $this->history, $options),
            'ollama' => $this->callOllama($system, $this->history, $options),
            default  => $this->fallbackReply($user_message),
        };

        // 5. Append reply to history (multi-turn)
        $this->history[] = ['role' => 'assistant', 'content' => $reply];

        // Keep history to last 20 turns to avoid token limits
        if (count($this->history) > 40) {
            $this->history = array_slice($this->history, -40);
        }

        return [
            'reply'   => $reply,
            'backend' => $backend,
            'turns'   => count($this->history) / 2,
        ];
    }

    public function resetHistory(): void { $this->history = []; }
    public function getHistory(): array  { return $this->history; }
    public function setHistory(array $h): void { $this->history = $h; }

    // ---------------------------------------------------------------
    // Build system prompt with injected knowledge (RAG)
    // ---------------------------------------------------------------
    private function buildSystemPrompt(string $knowledge): string {
        $platform_name = $this->config['platform_name'] ?? 'this platform';
        $assistant_name = $this->config['assistant_name'] ?? 'AI Assistant';
        $tone = $this->config['tone'] ?? 'helpful and friendly';

        $prompt = "You are {$assistant_name}, a {$tone} AI assistant for {$platform_name}.\n\n";
        $prompt .= "You have been trained on the following knowledge about {$platform_name}. ";
        $prompt .= "Use this knowledge to answer questions accurately. ";
        $prompt .= "If the answer is not in the knowledge base, say so honestly rather than guessing.\n\n";
        $prompt .= "=== PLATFORM KNOWLEDGE ===\n";
        $prompt .= $knowledge ?: "No platform knowledge loaded yet. Ask the user to set up the knowledge base first.";
        $prompt .= "\n=== END KNOWLEDGE ===\n\n";
        $prompt .= "Guidelines:\n";
        $prompt .= "- Answer questions directly and concisely\n";
        $prompt .= "- Use the platform knowledge above for facts (prices, policies, features)\n";
        $prompt .= "- Be conversational and helpful\n";
        $prompt .= "- If asked something outside the knowledge base, say you don't have that info\n";
        $prompt .= "- Never make up prices, policies, or features not in the knowledge base\n";

        return $prompt;
    }

    // ---------------------------------------------------------------
    // Retrieve stored platform knowledge
    // ---------------------------------------------------------------
    private function getKnowledge(): string {
        $meta    = $this->store->loadMeta($this->model_name);
        $corpus  = $meta['corpus'] ?? '';

        // Fallback: get from model training data summary
        if (empty($corpus)) {
            $sources = $meta['sources'] ?? [];
            $parts   = [];
            foreach ($sources as $s) {
                if (!empty($s['text'])) {
                    $parts[] = $s['text'];
                }
            }
            $corpus = implode("\n\n", $parts);
        }

        // Trim to context limit
        if (strlen($corpus) > $this->context_chars) {
            $corpus = substr($corpus, 0, $this->context_chars) . '...';
        }

        return $corpus;
    }

    // ---------------------------------------------------------------
    // Store text into the knowledge base (called by SelfLearner)
    // ---------------------------------------------------------------
    public function addKnowledge(string $text, string $source = ''): void {
        $meta   = $this->store->loadMeta($this->model_name);
        $corpus = $meta['corpus'] ?? '';

        // Append new text, deduplicate
        $new_text = trim($text);
        if (!str_contains($corpus, substr($new_text, 0, 100))) {
            $corpus .= "\n\n" . ($source ? "[Source: {$source}]\n" : '') . $new_text;
        }

        // Cap corpus at 50KB for performance
        if (strlen($corpus) > 50000) {
            $corpus = substr($corpus, -50000);
        }

        $meta['corpus'] = $corpus;
        $this->store->saveMeta($this->model_name, $meta);
    }

    // ---------------------------------------------------------------
    // Backend: Anthropic Claude API
    // ---------------------------------------------------------------
    private function callClaude(string $system, array $history, array $opts): string {
        $api_key = $this->config['anthropic_api_key'] ?? '';
        if (!$api_key) return $this->noKeyError('Anthropic');

        $model   = $opts['model']      ?? 'claude-haiku-4-5-20251001'; // fast & cheap
        $max_tok = $opts['max_tokens'] ?? 600;

        $payload = [
            'model'      => $model,
            'max_tokens' => $max_tok,
            'system'     => $system,
            'messages'   => $history,
        ];

        $response = $this->httpPost(
            'https://api.anthropic.com/v1/messages',
            $payload,
            [
                'x-api-key: ' . $api_key,
                'anthropic-version: 2023-06-01',
                'content-type: application/json',
            ]
        );

        $data = json_decode($response, true);
        return $data['content'][0]['text']
            ?? $data['error']['message']
            ?? 'No response from Claude API.';
    }

    // ---------------------------------------------------------------
    // Backend: OpenAI ChatGPT API
    // ---------------------------------------------------------------
    private function callOpenAI(string $system, array $history, array $opts): string {
        $api_key = $this->config['openai_api_key'] ?? '';
        if (!$api_key) return $this->noKeyError('OpenAI');

        $model   = $opts['model']      ?? 'gpt-4o-mini'; // cheap & fast
        $max_tok = $opts['max_tokens'] ?? 600;

        // OpenAI format: system as first message
        $messages = array_merge(
            [['role' => 'system', 'content' => $system]],
            $history
        );

        $payload = [
            'model'      => $model,
            'max_tokens' => $max_tok,
            'messages'   => $messages,
        ];

        $response = $this->httpPost(
            'https://api.openai.com/v1/chat/completions',
            $payload,
            [
                'Authorization: Bearer ' . $api_key,
                'Content-Type: application/json',
            ]
        );

        $data = json_decode($response, true);
        return $data['choices'][0]['message']['content']
            ?? $data['error']['message']
            ?? 'No response from OpenAI API.';
    }

    // ---------------------------------------------------------------
    // Backend: Ollama (local, no API key needed)
    // ---------------------------------------------------------------
    private function callOllama(string $system, array $history, array $opts): string {
        $base    = $this->config['ollama_url'] ?? 'http://localhost:11434';
        $model   = $opts['model'] ?? $this->config['ollama_model'] ?? 'llama3.2';

        // Ollama uses OpenAI-compatible /api/chat
        $messages = array_merge(
            [['role' => 'system', 'content' => $system]],
            $history
        );

        $payload = [
            'model'    => $model,
            'messages' => $messages,
            'stream'   => false,
        ];

        $response = $this->httpPost(
            $base . '/api/chat',
            $payload,
            ['Content-Type: application/json']
        );

        $data = json_decode($response, true);
        return $data['message']['content']
            ?? 'No response from Ollama.';
    }

    // ---------------------------------------------------------------
    // Fallback when no backend configured
    // ---------------------------------------------------------------
    private function fallbackReply(string $msg): string {
        return "Smart chat is not configured yet. Please add your API key to config.php. "
             . "I received your message: \"" . substr($msg, 0, 100) . "\"";
    }

    private function noKeyError(string $provider): string {
        return "No {$provider} API key configured. Please add it to config.php under '{$provider}_api_key'.";
    }

    // ---------------------------------------------------------------
    // HTTP POST helper (cURL or file_get_contents)
    // ---------------------------------------------------------------
    private function httpPost(string $url, array $body, array $headers): string {
        $json = json_encode($body);

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $json,
                CURLOPT_HTTPHEADER     => $headers,
                CURLOPT_TIMEOUT        => 30,
                CURLOPT_SSL_VERIFYPEER => true,
            ]);
            $res = curl_exec($ch);
            curl_close($ch);
            return $res ?: '{}';
        }

        // Fallback: stream context
        $header_str = implode("\r\n", $headers);
        $ctx = stream_context_create(['http' => [
            'method'  => 'POST',
            'header'  => $header_str,
            'content' => $json,
            'timeout' => 30,
        ]]);
        return @file_get_contents($url, false, $ctx) ?: '{}';
    }
}
