<?php
/**
 * Pipeline — Multi-step tool chaining for Yuga
 *
 * Lets you define workflows where each step's output feeds into the next.
 * Think of it as a lightweight workflow engine — no LLM decides the flow,
 * PHP defines it explicitly.
 *
 * Built-in step types:
 *   search    — BM25 corpus search via Brain
 *   tool      — call a ToolKit tool
 *   ingest    — ingest a document into the corpus
 *   generate  — run TextGenerator
 *   reason    — run Reasoner multi-hop
 *   transform — apply a PHP function to reshape context
 *   condition — branch based on context value
 *
 * Example — "Customer support with CRM lookup":
 *   $pipeline->define('support', [
 *     ['type'=>'tool',   'tool'=>'get_customer',  'params'=>['email'=>'{{input}}']],
 *     ['type'=>'search', 'query'=>'{{input}} {{crm_result}}'],
 *     ['type'=>'reason', 'question'=>'{{input}}'],
 *   ]);
 *   $result = $pipeline->run('support', 'What is the status of my order?', ['email'=>'user@x.com']);
 *
 * {{placeholders}} are resolved from:
 *   input   — original user input
 *   Any previous step's output key
 */
class Pipeline {

    private Brain     $brain;
    private ToolKit   $toolkit;
    private Reasoner  $reasoner;

    private array $definitions = []; // name => steps[]
    private array $log         = [];

    public float $temperature = 0.7;

    public function __construct(Brain $brain, ToolKit $toolkit) {
        $this->brain    = $brain;
        $this->toolkit  = $toolkit;
        $this->reasoner = new Reasoner($brain);
    }

    // ── Define a named pipeline ─────────────────────────────────────────
    public function define(string $name, array $steps): void {
        $this->definitions[$name] = $steps;
    }

    // ── Run a named pipeline ────────────────────────────────────────────
    public function run(string $name, string $input, array $vars = []): array {
        $steps = $this->definitions[$name] ?? null;
        if (!$steps) {
            return ['error' => "Pipeline '$name' not defined", 'answer' => ''];
        }
        return $this->execute($steps, $input, $vars);
    }

    // ── Run an inline (ad-hoc) pipeline ─────────────────────────────────
    public function execute(array $steps, string $input, array $vars = []): array {
        $this->log = [];

        // Context carries values between steps
        $ctx = array_merge($vars, ['input' => $input]);

        foreach ($steps as $i => $step) {
            $type   = $step['type'] ?? 'search';
            $result = $this->runStep($type, $step, $ctx, $input);

            $this->log[] = [
                'step'   => $i + 1,
                'type'   => $type,
                'output' => $result['output'] ?? '',
                'ok'     => $result['ok'] ?? true,
            ];

            // Abort pipeline on hard failure
            if (isset($result['abort']) && $result['abort']) break;

            // Merge step outputs into context
            $stepKey       = $step['as'] ?? $type . '_' . ($i + 1);
            $ctx[$stepKey] = $result['output'] ?? '';

            // Also make common keys available by type (last-wins)
            $ctx[$type . '_result'] = $result['output'] ?? '';
        }

        // Final answer: last non-empty output
        $answer = '';
        foreach (array_reverse($this->log) as $entry) {
            if (!empty($entry['output'])) {
                $answer = $entry['output'];
                break;
            }
        }

        return [
            'answer'  => $answer,
            'context' => $ctx,
            'steps'   => $this->log,
        ];
    }

    // ── Execute a single step ───────────────────────────────────────────
    private function runStep(string $type, array $step, array $ctx, string $input): array {
        // Resolve {{placeholders}} in step config from context
        $step = $this->resolvePlaceholders($step, $ctx);

        return match ($type) {
            'search'    => $this->stepSearch($step, $ctx),
            'tool'      => $this->stepTool($step),
            'ingest'    => $this->stepIngest($step),
            'generate'  => $this->stepGenerate($step),
            'reason'    => $this->stepReason($step, $ctx),
            'transform' => $this->stepTransform($step, $ctx),
            'condition' => $this->stepCondition($step, $ctx),
            'merge'     => $this->stepMerge($step, $ctx),
            default     => ['ok' => false, 'output' => "Unknown step type: $type"],
        };
    }

    // ── Step: BM25 corpus search ────────────────────────────────────────
    private function stepSearch(array $step, array $ctx): array {
        $query  = $step['query'] ?? ($ctx['input'] ?? '');
        $answer = $this->brain->answer($query, $this->temperature);
        return ['ok' => true, 'output' => $answer];
    }

    // ── Step: ToolKit tool call ─────────────────────────────────────────
    private function stepTool(array $step): array {
        $name   = $step['tool'] ?? '';
        $params = $step['params'] ?? [];
        if (!$name) return ['ok' => false, 'output' => 'Tool name required'];

        $start  = microtime(true);
        $result = $this->toolkit->call($name, $params);
        $ms     = (int)((microtime(true) - $start) * 1000);
        $this->toolkit->logCall($name, $params, $result, $ms);

        return [
            'ok'     => $result['ok'] ?? false,
            'output' => $result['output'] ?? '',
        ];
    }

    // ── Step: ingest a document or URL ─────────────────────────────────
    private function stepIngest(array $step): array {
        $ingester = new Ingester($this->brain);

        if (!empty($step['url'])) {
            $result = $ingester->ingestUrl($step['url'], !empty($step['crawl']));
        } elseif (!empty($step['file'])) {
            $result = $ingester->ingestFile($step['file']);
        } elseif (!empty($step['text'])) {
            $result = $ingester->ingestText($step['text']);
        } else {
            return ['ok' => false, 'output' => 'Ingest step requires url, file, or text'];
        }

        $msg = isset($result['error'])
            ? $result['error']
            : "Ingested " . ($result['chars'] ?? '?') . " chars, " . ($result['sentences'] ?? '?') . " sentences.";

        return ['ok' => !isset($result['error']), 'output' => $msg];
    }

    // ── Step: text generation ───────────────────────────────────────────
    private function stepGenerate(array $step): array {
        $gen    = new TextGenerator($this->brain);
        $prompt = $step['prompt'] ?? '';
        $mode   = $step['mode']   ?? 'complete';
        $opts   = $step['opts']   ?? [];

        $result = match ($mode) {
            'expand'  => $gen->expand($prompt, $opts),
            'best_of' => $gen->bestOf($prompt, $step['n'] ?? 2, $opts),
            'fill'    => $gen->fill($prompt, $opts),
            default   => $gen->complete($prompt, $opts),
        };

        return ['ok' => true, 'output' => $result['best'] ?? $result['full'] ?? $result['text'] ?? ''];
    }

    // ── Step: multi-hop reasoning ───────────────────────────────────────
    private function stepReason(array $step, array $ctx): array {
        $question = $step['question'] ?? ($ctx['input'] ?? '');
        $result   = $this->reasoner->reason($question);
        return [
            'ok'         => true,
            'output'     => $result['answer'],
            'confidence' => $result['confidence'],
        ];
    }

    // ── Step: transform context with a PHP closure ──────────────────────
    // For ad-hoc pipelines only (not serializable, used in-process)
    private function stepTransform(array $step, array $ctx): array {
        if (!isset($step['fn']) || !is_callable($step['fn'])) {
            return ['ok' => false, 'output' => 'transform step requires a callable fn'];
        }
        $output = ($step['fn'])($ctx);
        return ['ok' => true, 'output' => is_string($output) ? $output : json_encode($output)];
    }

    // ── Step: conditional branch ────────────────────────────────────────
    // Runs 'if_steps' when condition is truthy, 'else_steps' otherwise.
    private function stepCondition(array $step, array $ctx): array {
        $key   = $step['key']       ?? '';
        $value = $step['equals']    ?? null;
        $ctxVal = $ctx[$key]         ?? '';

        $match = ($value !== null)
            ? ($ctxVal === $value)
            : (!empty($ctxVal));

        $subSteps = $match
            ? ($step['if_steps']   ?? [])
            : ($step['else_steps'] ?? []);

        if (empty($subSteps)) {
            return ['ok' => true, 'output' => ''];
        }

        $sub = $this->execute($subSteps, $ctx['input'] ?? '', $ctx);
        return ['ok' => true, 'output' => $sub['answer']];
    }

    // ── Step: merge multiple context values into one ────────────────────
    private function stepMerge(array $step, array $ctx): array {
        $keys  = $step['keys'] ?? [];
        $parts = [];
        foreach ($keys as $k) {
            if (!empty($ctx[$k])) $parts[] = $ctx[$k];
        }
        return ['ok' => true, 'output' => implode(' ', $parts)];
    }

    // ── Resolve {{placeholder}} strings from context ────────────────────
    private function resolvePlaceholders(array $step, array $ctx): array {
        array_walk_recursive($step, function (&$val) use ($ctx) {
            if (!is_string($val)) return;
            $val = preg_replace_callback('/\{\{(\w+)\}\}/', function ($m) use ($ctx) {
                return $ctx[$m[1]] ?? $m[0];
            }, $val);
        });
        return $step;
    }

    public function getLog(): array { return $this->log; }
}
