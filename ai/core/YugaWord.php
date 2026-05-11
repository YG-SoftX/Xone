<?php
/**
 * YugaWord — Word-level MLP Language Model
 *
 * Same Karpathy MLP architecture as Yuga but operating on WORDS not chars.
 * This is the critical upgrade: "pricing starts at" is 3 tokens, not 21 chars.
 * The model learns full phrases and sentence patterns instead of letter sequences.
 *
 * Architecture:
 *   Context: 6 previous word tokens
 *   Embedding: 32-dim per word
 *   Hidden 1: 384 → 256 (ReLU)
 *   Hidden 2: 256 → 128 (ReLU)  ← extra layer for word-level semantics
 *   Output: 128 → vocab_size (Softmax)
 *
 * Parameters: ~vocab * 32 + 384*256 + 256*128 + 128*vocab ≈ 250K–500K
 * Still fits in 32MB RAM on shared hosting.
 */
class YugaWord {

    // Hyperparameters
    public int   $context    = 6;     // word context window
    public int   $embed_dim  = 32;    // embedding dims per word
    public int   $hidden1    = 256;   // first hidden layer
    public int   $hidden2    = 128;   // second hidden layer
    public float $lr         = 0.001; // learning rate
    public float $clip       = 0.5;   // gradient clip

    // Weights
    public array $E  = [];   // Embedding [vocab × embed_dim]
    public array $W1 = [];   // [context*embed → hidden1]
    public array $b1 = [];
    public array $W2 = [];   // [hidden1 → hidden2]
    public array $b2 = [];
    public array $W3 = [];   // [hidden2 → vocab]
    public array $b3 = [];

    // Stats
    public int   $steps     = 0;
    public float $last_loss = 0.0;
    public int   $vocab_size = 0;
    public bool  $ready     = false;

    // -------------------------------------------------------------------
    // Initialise weights for a given vocab size
    // -------------------------------------------------------------------
    public function init(int $vocab_size): void {
        $this->vocab_size = $vocab_size;
        $ctx  = $this->context;
        $e    = $this->embed_dim;
        $h1   = $this->hidden1;
        $h2   = $this->hidden2;
        $v    = $vocab_size;
        $inp  = $ctx * $e;

        $this->E  = $this->rand2d($v,   $e,   0.02);
        $this->W1 = $this->rand2d($inp, $h1,  sqrt(2.0 / $inp));
        $this->b1 = array_fill(0, $h1, 0.0);
        $this->W2 = $this->rand2d($h1,  $h2,  sqrt(2.0 / $h1));
        $this->b2 = array_fill(0, $h2, 0.0);
        $this->W3 = $this->rand2d($h2,  $v,   sqrt(2.0 / $h2));
        $this->b3 = array_fill(0, $v,   0.0);
        $this->ready = true;
    }

    // -------------------------------------------------------------------
    // Grow weights when vocab expands
    // -------------------------------------------------------------------
    public function growVocab(int $new_size): void {
        $old = $this->vocab_size;
        if ($new_size <= $old) return;

        $e = $this->embed_dim;
        $h2 = $this->hidden2;

        // Extend embedding rows
        for ($i = $old; $i < $new_size; $i++) {
            $row = [];
            for ($j = 0; $j < $e; $j++) {
                $row[] = (mt_rand() / mt_getrandmax() - 0.5) * 0.05;
            }
            $this->E[$i] = $row;
        }

        // Extend W3 columns and b3
        for ($i = 0; $i < $h2; $i++) {
            for ($j = $old; $j < $new_size; $j++) {
                $this->W3[$i][] = (mt_rand() / mt_getrandmax() - 0.5) * 0.02;
            }
        }
        for ($j = $old; $j < $new_size; $j++) {
            $this->b3[] = 0.0;
        }

        $this->vocab_size = $new_size;
    }

    // -------------------------------------------------------------------
    // Forward pass → returns [logits, emb, h1_pre, h1, h2_pre, h2]
    // -------------------------------------------------------------------
    public function forward(array $ctx_ids): array {
        $v  = $this->vocab_size;
        $e  = $this->embed_dim;
        $h1 = $this->hidden1;
        $h2 = $this->hidden2;

        // 1. Embed + flatten
        $emb = [];
        foreach ($ctx_ids as $id) {
            $id = max(0, min($id, $v - 1));
            foreach ($this->E[$id] as $val) {
                $emb[] = $val;
            }
        }

        // 2. Layer 1: emb → h1 (ReLU)
        $h1_pre = $this->b1;
        for ($j = 0; $j < $h1; $j++) {
            for ($i = 0, $ei = count($emb); $i < $ei; $i++) {
                $h1_pre[$j] += $emb[$i] * $this->W1[$i][$j];
            }
        }
        $h1_act = array_map(fn($x) => max(0.0, $x), $h1_pre); // ReLU

        // 3. Layer 2: h1 → h2 (ReLU)
        $h2_pre = $this->b2;
        for ($j = 0; $j < $h2; $j++) {
            for ($i = 0; $i < $h1; $i++) {
                $h2_pre[$j] += $h1_act[$i] * $this->W2[$i][$j];
            }
        }
        $h2_act = array_map(fn($x) => max(0.0, $x), $h2_pre); // ReLU

        // 4. Output layer: h2 → logits
        $logits = $this->b3;
        for ($j = 0; $j < $v; $j++) {
            for ($i = 0; $i < $h2; $i++) {
                $logits[$j] += $h2_act[$i] * $this->W3[$i][$j];
            }
        }

        return [$logits, $emb, $h1_pre, $h1_act, $h2_pre, $h2_act, $ctx_ids];
    }

    // -------------------------------------------------------------------
    // Softmax
    // -------------------------------------------------------------------
    public function softmax(array $logits, float $temp = 1.0): array {
        if ($temp !== 1.0) {
            $logits = array_map(fn($x) => $x / max($temp, 0.001), $logits);
        }
        $max = max($logits);
        $exp = array_map(fn($x) => exp($x - $max), $logits);
        $sum = array_sum($exp) + 1e-10;
        return array_map(fn($x) => $x / $sum, $exp);
    }

    // -------------------------------------------------------------------
    // Cross-entropy loss
    // -------------------------------------------------------------------
    public function loss(array $logits, int $target): float {
        $probs = $this->softmax($logits);
        $p = $probs[$target] ?? 1e-10;
        $p = max(min($p, 1.0 - 1e-10), 1e-10);
        return -log($p);
    }

    // -------------------------------------------------------------------
    // Backward pass
    // -------------------------------------------------------------------
    public function backward(array $cache, int $target): void {
        [$logits, $emb, $h1_pre, $h1_act, $h2_pre, $h2_act, $ctx_ids] = $cache;

        $v  = $this->vocab_size;
        $h1 = $this->hidden1;
        $h2 = $this->hidden2;
        $e  = $this->embed_dim;

        // dL/dlogits
        $probs    = $this->softmax($logits);
        $d_logits = $probs;
        $d_logits[$target] -= 1.0;
        $d_logits = array_map(fn($g) => max(-$this->clip, min($this->clip, $g)), $d_logits);

        // W3, b3
        for ($i = 0; $i < $h2; $i++) {
            for ($j = 0; $j < $v; $j++) {
                $this->W3[$i][$j] -= $this->lr * $d_logits[$j] * $h2_act[$i];
            }
        }
        for ($j = 0; $j < $v; $j++) {
            $this->b3[$j] -= $this->lr * $d_logits[$j];
        }

        // dL/dh2_act
        $d_h2 = array_fill(0, $h2, 0.0);
        for ($i = 0; $i < $h2; $i++) {
            for ($j = 0; $j < $v; $j++) {
                $d_h2[$i] += $this->W3[$i][$j] * $d_logits[$j];
            }
        }
        // ReLU backprop
        $d_h2_pre = array_map(fn($a, $g) => $a > 0 ? max(-$this->clip, min($this->clip, $g)) : 0.0, $h2_act, $d_h2);

        // W2, b2
        for ($i = 0; $i < $h1; $i++) {
            for ($j = 0; $j < $h2; $j++) {
                $this->W2[$i][$j] -= $this->lr * $d_h2_pre[$j] * $h1_act[$i];
            }
        }
        for ($j = 0; $j < $h2; $j++) {
            $this->b2[$j] -= $this->lr * $d_h2_pre[$j];
        }

        // dL/dh1_act
        $d_h1 = array_fill(0, $h1, 0.0);
        for ($i = 0; $i < $h1; $i++) {
            for ($j = 0; $j < $h2; $j++) {
                $d_h1[$i] += $this->W2[$i][$j] * $d_h2_pre[$j];
            }
        }
        $d_h1_pre = array_map(fn($a, $g) => $a > 0 ? max(-$this->clip, min($this->clip, $g)) : 0.0, $h1_act, $d_h1);

        // W1, b1
        $ei = count($emb);
        for ($i = 0; $i < $ei; $i++) {
            for ($j = 0; $j < $h1; $j++) {
                $this->W1[$i][$j] -= $this->lr * $d_h1_pre[$j] * $emb[$i];
            }
        }
        for ($j = 0; $j < $h1; $j++) {
            $this->b1[$j] -= $this->lr * $d_h1_pre[$j];
        }

        // dL/dEmb → update embedding rows
        $d_emb = array_fill(0, $ei, 0.0);
        for ($i = 0; $i < $ei; $i++) {
            for ($j = 0; $j < $h1; $j++) {
                $d_emb[$i] += $this->W1[$i][$j] * $d_h1_pre[$j];
            }
        }
        foreach ($ctx_ids as $pos => $id) {
            $id = max(0, min($id, $v - 1));
            for ($k = 0; $k < $e; $k++) {
                $this->E[$id][$k] -= $this->lr * ($d_emb[$pos * $e + $k] ?? 0.0);
            }
        }
    }

    // -------------------------------------------------------------------
    // Train one step
    // -------------------------------------------------------------------
    public function step(array $ctx_ids, int $target): float {
        $cache = $this->forward($ctx_ids);
        $l     = $this->loss($cache[0], $target);
        if (is_nan($l) || is_infinite($l)) {
            $this->steps++;
            return $this->last_loss;
        }
        $this->backward($cache, $target);
        $this->steps++;
        $this->last_loss = $l;
        return $l;
    }

    // -------------------------------------------------------------------
    // Train on encoded pairs (multi-epoch)
    // -------------------------------------------------------------------
    public function trainPairs(array $pairs, int $max_steps): array {
        if (empty($pairs)) return ['loss' => 0, 'steps' => 0];

        $done  = 0;
        $total = 0.0;
        $ep    = $pairs;

        while ($done < $max_steps) {
            shuffle($ep);
            foreach ($ep as [$ctx, $tgt]) {
                if ($done >= $max_steps) break;
                $total += $this->step($ctx, $tgt);
                $done++;
            }
        }
        return ['loss' => round($total / $done, 4), 'steps' => $done];
    }

    // -------------------------------------------------------------------
    // Generate: given a seed text, produce max_words more words
    // -------------------------------------------------------------------
    public function generate(
        Tokenizer $tok,
        string    $seed      = '',
        int       $max_words = 60,
        float     $temp      = 0.8,
        bool      $stop_eos  = true
    ): string {
        if (!$this->ready) return '';

        $ctx = $this->context;
        $v   = $this->vocab_size;

        // Encode seed
        $ids     = $seed ? $tok->encode($seed) : [Tokenizer::BOS];
        $context = array_fill(0, $ctx, Tokenizer::PAD);
        foreach (array_slice($ids, -$ctx) as $i => $id) {
            $context[$ctx - min($ctx, count($ids)) + $i] = $id;
        }

        $generated = $ids;

        for ($step = 0; $step < $max_words; $step++) {
            [$logits] = $this->forward($context);

            // Mask pad/unk to prevent degenerate outputs
            $logits[Tokenizer::PAD] = -1e9;
            $logits[Tokenizer::UNK] = -1e9;
            $logits[Tokenizer::BOS] = -1e9;

            $probs = $this->softmax($logits, $temp);

            // Sample
            $r   = mt_rand() / mt_getrandmax();
            $cum = 0.0;
            $nxt = Tokenizer::EOS;
            foreach ($probs as $idx => $p) {
                $cum += $p;
                if ($r <= $cum) { $nxt = $idx; break; }
            }

            if ($stop_eos && $nxt === Tokenizer::EOS) break;

            $generated[] = $nxt;
            array_shift($context);
            $context[] = $nxt;

            // Stop at sentence end (after at least 5 new words)
            if ($step >= 5) {
                $word = $tok->id2word[$nxt] ?? '';
                if (in_array($word, ['.', '!', '?'])) break;
            }
        }

        return $tok->decode(array_slice($generated, count($ids)));
    }

    // -------------------------------------------------------------------
    // Serialize
    // -------------------------------------------------------------------
    public function save(): array {
        return [
            'context'    => $this->context,
            'embed_dim'  => $this->embed_dim,
            'hidden1'    => $this->hidden1,
            'hidden2'    => $this->hidden2,
            'lr'         => $this->lr,
            'clip'       => $this->clip,
            'E'          => $this->E,
            'W1'         => $this->W1,
            'b1'         => $this->b1,
            'W2'         => $this->W2,
            'b2'         => $this->b2,
            'W3'         => $this->W3,
            'b3'         => $this->b3,
            'steps'      => $this->steps,
            'last_loss'  => $this->last_loss,
            'vocab_size' => $this->vocab_size,
            'ready'      => $this->ready,
        ];
    }

    public function load(array $d): void {
        foreach ($d as $k => $v) $this->$k = $v;
    }

    public static function fromArray(array $d): self {
        $m = new self();
        $m->load($d);
        return $m;
    }

    // -------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------
    private function rand2d(int $rows, int $cols, float $scale): array {
        $m = [];
        for ($i = 0; $i < $rows; $i++) {
            $row = [];
            for ($j = 0; $j < $cols; $j++) {
                $u1    = mt_rand() / mt_getrandmax();
                $u2    = mt_rand() / mt_getrandmax();
                $row[] = $scale * sqrt(-2 * log(max($u1, 1e-10))) * cos(2 * M_PI * $u2);
            }
            $m[] = $row;
        }
        return $m;
    }
}
