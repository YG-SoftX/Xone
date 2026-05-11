<?php
/**
 * Yuga - A tiny Character-Level Neural Language Model
 * Inspired by Andrej Karpathy's makemore/nanoGPT
 * Designed to run on shared cPanel hosting (CPU only, no GPU)
 *
 * Architecture: Character-level MLP
 *   Input  -> Embedding lookup (context window)
 *   Embed  -> Flatten -> Linear + Tanh -> Linear -> Softmax
 *
 * Total parameters: ~50,000 (fits in memory on cheap hosting)
 */
class YugaLM {

    // Hyperparameters (tunable)
    public $embed_dim    = 24;    // embedding dimension per character
    public $context_size = 8;     // how many chars to look back
    public $hidden_dim   = 128;   // hidden layer neurons
    public $lr           = 0.01;  // learning rate (lower = more stable)
    public $clip_grad    = 1.0;   // gradient clipping

    // Model weights
    public $vocab   = [];          // char -> index
    public $i_vocab = [];          // index -> char
    public $vocab_size = 0;

    public $C  = [];  // Embedding matrix [vocab_size][embed_dim]
    public $W1 = [];  // [context*embed_dim][hidden_dim]
    public $b1 = [];  // [hidden_dim]
    public $W2 = [];  // [hidden_dim][vocab_size]
    public $b2 = [];  // [vocab_size]

    // Training stats
    public $trained_steps = 0;
    public $last_loss = 0.0;
    public $vocab_built = false;

    // -------------------------------------------------------------------
    // Vocab
    // -------------------------------------------------------------------
    public function buildVocab(string $text): void {
        $chars = array_unique(str_split($text));
        sort($chars);
        array_unshift($chars, "\0"); // index 0 = padding/unknown
        $this->vocab      = array_flip($chars);
        $this->i_vocab    = $chars;
        $this->vocab_size = count($chars);
        $this->vocab_built = true;
    }

    public function extendVocab(string $text): bool {
        $changed = false;
        foreach (str_split($text) as $ch) {
            if (!isset($this->vocab[$ch])) {
                $idx = $this->vocab_size;
                $this->vocab[$ch]  = $idx;
                $this->i_vocab[]   = $ch;
                $this->vocab_size++;
                $changed = true;
            }
        }
        return $changed;
    }

    public function encode(string $text): array {
        $out = [];
        foreach (str_split($text) as $ch) {
            $out[] = $this->vocab[$ch] ?? 0;
        }
        return $out;
    }

    public function decode(array $ids): string {
        $out = '';
        foreach ($ids as $id) {
            $out .= $this->i_vocab[$id] ?? '';
        }
        return $out;
    }

    // -------------------------------------------------------------------
    // Weight Initialisation (Xavier / He)
    // -------------------------------------------------------------------
    public function initWeights(): void {
        $ctx = $this->context_size;
        $e   = $this->embed_dim;
        $h   = $this->hidden_dim;
        $v   = $this->vocab_size;

        // Embedding
        $this->C = $this->randMatrix($v, $e, 0.1);

        // Layer 1: context*embed -> hidden
        $scale = sqrt(2.0 / ($ctx * $e));
        $this->W1 = $this->randMatrix($ctx * $e, $h, $scale);
        $this->b1 = array_fill(0, $h, 0.0);

        // Layer 2: hidden -> vocab
        $scale2 = sqrt(2.0 / $h);
        $this->W2 = $this->randMatrix($h, $v, $scale2);
        $this->b2 = array_fill(0, $v, 0.0);
    }

    private function randMatrix(int $rows, int $cols, float $scale = 1.0): array {
        $m = [];
        for ($i = 0; $i < $rows; $i++) {
            $row = [];
            for ($j = 0; $j < $cols; $j++) {
                // Box-Muller normal distribution
                $u1 = mt_rand() / mt_getrandmax();
                $u2 = mt_rand() / mt_getrandmax();
                $row[] = $scale * sqrt(-2 * log($u1)) * cos(2 * M_PI * $u2);
            }
            $m[] = $row;
        }
        return $m;
    }

    // -------------------------------------------------------------------
    // Forward Pass  (returns [logits, cache])
    // -------------------------------------------------------------------
    public function forward(array $context_ids): array {
        $ctx = $this->context_size;
        $e   = $this->embed_dim;
        $h   = $this->hidden_dim;
        $v   = $this->vocab_size;

        // 1. Embedding lookup + flatten
        $emb = [];
        foreach ($context_ids as $id) {
            $id = max(0, min($id, $v - 1));
            foreach ($this->C[$id] as $val) {
                $emb[] = $val;
            }
        }

        // 2. Hidden layer: h_pre = emb @ W1 + b1
        $h_pre = $this->b1;
        for ($j = 0; $j < $h; $j++) {
            for ($i = 0; $i < count($emb); $i++) {
                $h_pre[$j] += $emb[$i] * $this->W1[$i][$j];
            }
        }

        // 3. Tanh activation
        $h_act = array_map('tanh', $h_pre);

        // 4. Output layer: logits = h_act @ W2 + b2
        $logits = $this->b2;
        for ($j = 0; $j < $v; $j++) {
            for ($i = 0; $i < $h; $i++) {
                $logits[$j] += $h_act[$i] * $this->W2[$i][$j];
            }
        }

        return [$logits, $emb, $h_pre, $h_act, $context_ids];
    }

    // -------------------------------------------------------------------
    // Softmax + Cross-entropy loss
    // -------------------------------------------------------------------
    public function softmax(array $logits): array {
        $max = max($logits);
        $exp = array_map(fn($x) => exp($x - $max), $logits);
        $sum = array_sum($exp);
        return array_map(fn($x) => $x / ($sum + 1e-10), $exp);
    }

    public function loss(array $logits, int $target): float {
        $probs = $this->softmax($logits);
        $p = max($probs[$target] ?? 1e-10, 1e-10);
        return -log($p);
    }

    // -------------------------------------------------------------------
    // Backward Pass + Weight Update (SGD)
    // -------------------------------------------------------------------
    public function backward(array $cache, int $target): void {
        [$logits, $emb, $h_pre, $h_act, $ctx_ids] = $cache;

        $v = $this->vocab_size;
        $h = $this->hidden_dim;

        // dL/dlogits = probs - one_hot(target)
        $probs = $this->softmax($logits);
        $d_logits = $probs;
        $d_logits[$target] -= 1.0;

        // Clip gradients
        $d_logits = array_map(fn($g) => max(-$this->clip_grad, min($this->clip_grad, $g)), $d_logits);

        // dL/dW2, dL/db2
        for ($i = 0; $i < $h; $i++) {
            for ($j = 0; $j < $v; $j++) {
                $this->W2[$i][$j] -= $this->lr * $d_logits[$j] * $h_act[$i];
            }
        }
        for ($j = 0; $j < $v; $j++) {
            $this->b2[$j] -= $this->lr * $d_logits[$j];
        }

        // dL/dh_act
        $d_h_act = array_fill(0, $h, 0.0);
        for ($i = 0; $i < $h; $i++) {
            for ($j = 0; $j < $v; $j++) {
                $d_h_act[$i] += $this->W2[$i][$j] * $d_logits[$j];
            }
        }

        // dL/dh_pre (tanh backprop: 1 - tanh^2)
        $d_h_pre = array_fill(0, $h, 0.0);
        for ($i = 0; $i < $h; $i++) {
            $d_h_pre[$i] = $d_h_act[$i] * (1 - $h_act[$i] ** 2);
        }
        $d_h_pre = array_map(fn($g) => max(-$this->clip_grad, min($this->clip_grad, $g)), $d_h_pre);

        // dL/dW1, dL/db1
        $emb_size = count($emb);
        for ($i = 0; $i < $emb_size; $i++) {
            for ($j = 0; $j < $h; $j++) {
                $this->W1[$i][$j] -= $this->lr * $d_h_pre[$j] * $emb[$i];
            }
        }
        for ($j = 0; $j < $h; $j++) {
            $this->b1[$j] -= $this->lr * $d_h_pre[$j];
        }

        // dL/demb
        $d_emb = array_fill(0, $emb_size, 0.0);
        for ($i = 0; $i < $emb_size; $i++) {
            for ($j = 0; $j < $h; $j++) {
                $d_emb[$i] += $this->W1[$i][$j] * $d_h_pre[$j];
            }
        }

        // dL/dC (embeddings)
        $e = $this->embed_dim;
        foreach ($ctx_ids as $pos => $id) {
            $id = max(0, min($id, $v - 1));
            for ($k = 0; $k < $e; $k++) {
                $this->C[$id][$k] -= $this->lr * ($d_emb[$pos * $e + $k] ?? 0.0);
            }
        }
    }

    // -------------------------------------------------------------------
    // Training step (single example)
    // -------------------------------------------------------------------
    public function trainStep(array $context_ids, int $target): float {
        [$logits, $emb, $h_pre, $h_act] = $cache = $this->forward($context_ids);
        $cache[] = $context_ids;
        $l = $this->loss($logits, $target);
        $this->backward([$logits, $emb, $h_pre, $h_act, $context_ids], $target);
        $this->trained_steps++;
        $this->last_loss = $l;
        return $l;
    }

    // -------------------------------------------------------------------
    // Train on a chunk of text (multi-epoch SGD)
    // -------------------------------------------------------------------
    public function trainOnText(string $text, int $max_steps = 2000): array {
        if (!$this->vocab_built) {
            $this->buildVocab($text);
            $this->initWeights();
        } else {
            if ($this->extendVocab($text)) {
                $this->growWeights();
            }
        }

        $ids = $this->encode($text);
        $n   = count($ids);
        $ctx = $this->context_size;

        // Build all (context, target) pairs
        $pairs = [];
        for ($i = $ctx; $i < $n; $i++) {
            $context = array_slice($ids, max(0, $i - $ctx), $ctx);
            while (count($context) < $ctx) array_unshift($context, 0);
            $pairs[] = [$context, $ids[$i]];
        }

        if (empty($pairs)) return ['loss' => 0, 'steps' => 0];

        // Run multiple passes (epochs) through data until max_steps consumed
        $total_loss  = 0.0;
        $steps_done  = 0;
        $epoch_pairs = $pairs;

        while ($steps_done < $max_steps) {
            shuffle($epoch_pairs);
            foreach ($epoch_pairs as $p) {
                if ($steps_done >= $max_steps) break;
                $total_loss += $this->trainStep($p[0], $p[1]);
                $steps_done++;
            }
        }

        $avg_loss = $total_loss / $steps_done;
        return ['loss' => round($avg_loss, 4), 'steps' => $steps_done];
    }

    // Grow weights when vocab expands
    private function growWeights(): void {
        $v = $this->vocab_size;
        $e = $this->embed_dim;
        $h = $this->hidden_dim;

        // Extend C
        while (count($this->C) < $v) {
            $row = [];
            for ($j = 0; $j < $e; $j++) {
                $row[] = (mt_rand() / mt_getrandmax() - 0.5) * 0.1;
            }
            $this->C[] = $row;
        }

        // Extend W2 rows
        while (count($this->W2[0] ?? []) < $v) {
            for ($i = 0; $i < $h; $i++) {
                $this->W2[$i][] = (mt_rand() / mt_getrandmax() - 0.5) * 0.01;
            }
            $this->b2[] = 0.0;
        }
    }

    // -------------------------------------------------------------------
    // Generation / Inference
    // -------------------------------------------------------------------
    public function generate(string $seed = '', int $max_chars = 200, float $temperature = 0.8): string {
        if (empty($this->vocab)) return '';

        $ctx = $this->context_size;
        $ids = $seed ? $this->encode($seed) : [0];

        // Pad context
        $context = array_fill(0, $ctx, 0);
        foreach (array_slice($ids, -$ctx) as $i => $id) {
            $context[$ctx - count(array_slice($ids, -$ctx)) + $i] = $id;
        }

        $output = $seed;
        for ($i = 0; $i < $max_chars; $i++) {
            [$logits] = $this->forward($context);

            // Temperature sampling
            $logits = array_map(fn($x) => $x / max($temperature, 0.01), $logits);

            // Mask out the padding/null token (index 0) — never generate it
            $logits[0] = -1e9;

            $probs  = $this->softmax($logits);

            // Sample from distribution
            $r   = mt_rand() / mt_getrandmax();
            $cum = 0.0;
            $next = 0;
            foreach ($probs as $idx => $p) {
                $cum += $p;
                if ($r <= $cum) { $next = $idx; break; }
            }

            $ch = $this->i_vocab[$next] ?? '';
            $output .= $ch;

            // Slide context window
            array_shift($context);
            $context[] = $next;

            if ($ch === "\0") break;
        }

        return $output;
    }

    // Smart completion: answer from known context
    public function complete(string $prompt, int $max_chars = 300, float $temp = 0.7): string {
        return $this->generate($prompt, $max_chars, $temp);
    }

    // -------------------------------------------------------------------
    // Serialise / Load
    // -------------------------------------------------------------------
    public function save(): array {
        return [
            'vocab'        => $this->vocab,
            'i_vocab'      => $this->i_vocab,
            'vocab_size'   => $this->vocab_size,
            'vocab_built'  => $this->vocab_built,
            'embed_dim'    => $this->embed_dim,
            'context_size' => $this->context_size,
            'hidden_dim'   => $this->hidden_dim,
            'lr'           => $this->lr,
            'C'            => $this->C,
            'W1'           => $this->W1,
            'b1'           => $this->b1,
            'W2'           => $this->W2,
            'b2'           => $this->b2,
            'trained_steps'=> $this->trained_steps,
            'last_loss'    => $this->last_loss,
        ];
    }

    public function load(array $data): void {
        foreach ($data as $k => $v) {
            $this->$k = $v;
        }
    }

    public static function fromArray(array $data): self {
        $m = new self();
        $m->load($data);
        return $m;
    }
}
