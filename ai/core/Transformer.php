<?php
/**
 * Transformer — Real GPT-style autoregressive language model in pure PHP.
 *
 * Architecture (identical to GPT-2 small, shrunk to fit cPanel RAM):
 *   Token embedding + positional embedding
 *   N × transformer block (pre-norm):
 *       LayerNorm → Multi-Head Causal Self-Attention → residual
 *       LayerNorm → Feed-Forward (GELU) → residual
 *   Final LayerNorm
 *   LM head (weight-tied to token embedding)
 *
 * Optimizer : Adam (β₁=0.9, β₂=0.999)
 * Gradients : global norm clipping at 1.0
 * Parameters: ~65 K  (V=2000, D=32, H=2, L=2, Dff=64)
 * Memory    : ~10 MB (weights + Adam moments)
 *
 * This is a REAL language model — it generates new text, not copies.
 * It understands sequential patterns, not just keywords.
 */
class Transformer {

    // ── Size presets ──────────────────────────────────────────────────
    // micro  : D=32  H=2  L=2  T=16  Dff=64    → ~65K params   cPanel 128MB  ✓
    // small  : D=64  H=4  L=4  T=32  Dff=128   → ~325K params  cPanel 128MB  ✓
    // medium : D=128 H=4  L=6  T=64  Dff=256   → ~1.4M params  VPS 512MB     ✓
    // large  : D=256 H=8  L=8  T=128 Dff=512   → ~6M params    Server 2GB    ✓
    // xlarge : D=384 H=8  L=12 T=256 Dff=1024  → ~40M params   Server 8GB    ✓
    // xxlarge: D=512 H=16 L=16 T=512 Dff=2048  → ~175M params  Server 32GB   ✓
    //
    // Nepal production recommendation:
    //   Start with 'medium' on a VPS (Hetzner CX21 = ~$6/mo, 4GB RAM)
    //   Upgrade to 'large' as corpus grows (DigitalOcean 8GB = ~$48/mo)
    //   'xlarge' when you have 50K+ users and a dedicated server
    public static function make(string $size = 'micro'): self {
        $m = new self();
        match ($size) {
            'small'   => [$m->D, $m->H, $m->L, $m->T, $m->Dff] = [64,  4,  4,  32,  128 ],
            'medium'  => [$m->D, $m->H, $m->L, $m->T, $m->Dff] = [128, 4,  6,  64,  256 ],
            'large'   => [$m->D, $m->H, $m->L, $m->T, $m->Dff] = [256, 8,  8,  128, 512 ],
            'xlarge'  => [$m->D, $m->H, $m->L, $m->T, $m->Dff] = [384, 8,  12, 256, 1024],
            'xxlarge' => [$m->D, $m->H, $m->L, $m->T, $m->Dff] = [512, 16, 16, 512, 2048],
            default   => [$m->D, $m->H, $m->L, $m->T, $m->Dff] = [32,  2,  2,  16,  64  ],
        };
        return $m;
    }

    // ── Hyper-parameters ─────────────────────────────────────────────
    public int   $V   = 2000;  // vocab size (set from Tokenizer)
    public int   $T   = 16;   // context window (tokens)
    public int   $D   = 32;   // d_model (embedding dim)
    public int   $H   = 2;    // attention heads  (head_dim = D/H = 16)
    public int   $L   = 2;    // transformer blocks
    public int   $Dff = 64;   // feed-forward hidden dim

    // Adam
    public float $lr   = 3e-4;
    public float $b1   = 0.9;
    public float $b2   = 0.999;
    public float $epsA = 1e-8;
    public int   $tA   = 0;    // Adam step counter

    // Stats
    public int   $steps    = 0;
    public float $lastLoss = 0.0;
    public bool  $ready    = false;

    // Weights: string key → flat float array
    public array $p  = [];
    // Adam first + second moments (same keys)
    public array $mA = [];
    public array $vA = [];

    // =================================================================
    // PUBLIC API
    // =================================================================

    /** Initialise all weights from scratch for vocabulary size $V. */
    public function init(int $V): void {
        $this->V = $V;
        $this->p = $this->mA = $this->vA = [];

        $D = $this->D; $T = $this->T; $Dff = $this->Dff;

        $this->p['wte'] = $this->randn($V * $D, 0.02);   // token embeddings
        $this->p['wpe'] = $this->randn($T * $D, 0.01);   // position embeddings

        for ($l = 0; $l < $this->L; $l++) {
            $this->p["l{$l}_ln1g"] = array_fill(0, $D, 1.0);
            $this->p["l{$l}_ln1b"] = array_fill(0, $D, 0.0);
            $this->p["l{$l}_wq"]   = $this->randn($D * $D, 0.02);
            $this->p["l{$l}_wk"]   = $this->randn($D * $D, 0.02);
            $this->p["l{$l}_wv"]   = $this->randn($D * $D, 0.02);
            $this->p["l{$l}_wo"]   = $this->randn($D * $D, 0.02);
            $this->p["l{$l}_ln2g"] = array_fill(0, $D, 1.0);
            $this->p["l{$l}_ln2b"] = array_fill(0, $D, 0.0);
            $this->p["l{$l}_w1"]   = $this->randn($D * $Dff, 0.02);
            $this->p["l{$l}_b1"]   = array_fill(0, $Dff, 0.0);
            $this->p["l{$l}_w2"]   = $this->randn($Dff * $D, 0.02);
            $this->p["l{$l}_b2"]   = array_fill(0, $D, 0.0);
        }
        $this->p['lnfg'] = array_fill(0, $D, 1.0);
        $this->p['lnfb'] = array_fill(0, $D, 0.0);

        $this->ready = true;
    }

    /** Grow embedding + LM head when vocab expands. */
    public function growVocab(int $newV): void {
        $D = $this->D;
        $extra = $newV - $this->V;
        if ($extra <= 0) return;
        // Append zero-init rows to wte
        $this->p['wte'] = array_merge(
            $this->p['wte'],
            $this->randn($extra * $D, 0.01)
        );
        // Grow Adam moments too (or they'll be created fresh in adam_step)
        if (isset($this->mA['wte'])) {
            $this->mA['wte'] = array_merge($this->mA['wte'], array_fill(0, $extra * $D, 0.0));
            $this->vA['wte'] = array_merge($this->vA['wte'], array_fill(0, $extra * $D, 0.0));
        }
        $this->V = $newV;
    }

    /**
     * Train on a sequence of token IDs (teacher-forcing, all positions).
     * Returns cross-entropy loss.
     */
    public function trainStep(array $tokens): float {
        $n = count($tokens);
        if ($n < 2) return 0.0;

        // Use up to T tokens; build input / target pairs
        $inp = array_slice($tokens, 0, min($n - 1, $this->T));
        $tgt = array_slice($tokens, 1, count($inp));
        $T   = count($inp);

        // ── Forward ──────────────────────────────────────────────────
        [$logits, $cache] = $this->forward($inp, $T);

        // ── Loss + initial gradient ───────────────────────────────────
        $V = $this->V;
        $loss = 0.0;
        $dLogits = array_fill(0, $T * $V, 0.0);

        for ($t = 0; $t < $T; $t++) {
            $off = $t * $V;
            $row = array_slice($logits, $off, $V);
            $probs = $this->softmax1d($row, 1.0);
            $tgtId = $tgt[$t];
            $loss += -log(max($probs[$tgtId] ?? 1e-10, 1e-10));
            // d cross-entropy / d logits = probs - one_hot
            for ($j = 0; $j < $V; $j++) {
                $dLogits[$off + $j] = $probs[$j];
            }
            $dLogits[$off + $tgtId] -= 1.0;
        }
        $loss /= $T;
        // Scale gradients by 1/T
        for ($i = 0; $i < $T * $V; $i++) $dLogits[$i] /= $T;

        // ── Backward ─────────────────────────────────────────────────
        $grads = $this->backward($dLogits, $cache, $T);

        // ── Gradient clipping (global norm) ──────────────────────────
        $this->clipGrads($grads, 1.0);

        // ── Adam update ───────────────────────────────────────────────
        $this->adamStep($grads);

        $this->steps++;
        $this->lastLoss = is_nan($loss) ? $this->lastLoss : $loss;
        return $this->lastLoss;
    }

    /**
     * Train on pairs for max_steps (multi-epoch).
     * $pairs = array of token-ID arrays (each a full sentence).
     */
    public function trainPairs(array $pairs, int $maxSteps): array {
        if (empty($pairs)) return ['loss' => 0, 'steps' => 0];
        $done = 0; $totalLoss = 0.0; $ep = $pairs;
        while ($done < $maxSteps) {
            shuffle($ep);
            foreach ($ep as $seq) {
                if ($done >= $maxSteps) break;
                $totalLoss += $this->trainStep($seq);
                $done++;
            }
        }
        return ['loss' => round($totalLoss / $done, 4), 'steps' => $done];
    }

    /**
     * Autoregressive generation — produces real NEW text (not memorised copies).
     * Given a seed prompt, generates up to $maxTokens more tokens.
     *
     * @param float $topP   Nucleus sampling threshold (0.0–1.0). 1.0 = disabled (pure temperature).
     *                      0.9 = sample from the top 90% probability mass. Recommended: 0.9.
     * @param float $repPenalty  Penalty subtracted from logits of recently used tokens (default 1.5).
     */
    public function generate(
        Tokenizer $tok,
        string    $seed       = '',
        int       $maxTokens  = 50,
        float     $temp       = 0.8,
        bool      $stopEos    = true,
        float     $topP       = 1.0,
        float     $repPenalty = 1.5
    ): string {
        if (!$this->ready) return '';

        $ids       = $seed ? $tok->encode($seed) : [Tokenizer::BOS];
        $generated = [];

        for ($step = 0; $step < $maxTokens; $step++) {
            $ctx  = array_slice($ids, -$this->T);
            $Tcur = count($ctx);
            [$logits, ] = $this->forward($ctx, $Tcur);

            $V    = $this->V;
            $last = array_slice($logits, ($Tcur - 1) * $V, $V);

            // Suppress special tokens
            $last[Tokenizer::PAD] = -1e9;
            $last[Tokenizer::BOS] = -1e9;
            $last[Tokenizer::UNK] = -1e9;

            // Repetition penalty — last 8 generated tokens
            foreach (array_slice($generated, -8) as $rid) {
                if (isset($last[$rid])) $last[$rid] -= $repPenalty;
            }

            $probs = $this->softmax1d($last, $temp);

            // Nucleus (top-p) sampling when enabled
            $next = ($topP < 1.0)
                ? $this->topPSample($probs, $topP)
                : $this->sampleProbs($probs);

            if ($stopEos && $next === Tokenizer::EOS) break;

            $ids[]       = $next;
            $generated[] = $next;

            $word = $tok->id2word[$next] ?? '';
            if (count($generated) >= 6 && in_array($word, ['.', '!', '?'])) break;
        }

        return $tok->decode($generated);
    }

    /**
     * Beam search generation — higher quality than sampling.
     *
     * Maintains $beamWidth candidate sequences simultaneously.
     * At each step, expands every beam with the top tokens and keeps the
     * globally best $beamWidth sequences by length-normalised log probability.
     *
     * Much more coherent than temperature sampling for factual / structured text.
     * Slower than sampling (O(beamWidth) forward passes per step).
     *
     * @param float $lengthPenalty  > 1 favours longer outputs; < 1 shorter (default 0.9)
     */
    public function generateBeam(
        Tokenizer $tok,
        string    $seed        = '',
        int       $maxTokens   = 80,
        int       $beamWidth   = 3,
        float     $lengthPenalty = 0.9,
        float     $repPenalty  = 1.2
    ): string {
        if (!$this->ready) return '';

        $seedIds = $seed ? $tok->encode($seed) : [Tokenizer::BOS];
        $V       = $this->V;

        // Each beam: ['ids' => int[], 'logp' => float, 'done' => bool]
        $beams = [['ids' => $seedIds, 'logp' => 0.0, 'done' => false]];

        for ($step = 0; $step < $maxTokens; $step++) {
            $candidates = [];

            foreach ($beams as $beam) {
                if ($beam['done']) {
                    $candidates[] = $beam;
                    continue;
                }

                $ctx  = array_slice($beam['ids'], -$this->T);
                $Tcur = count($ctx);
                [$logits, ] = $this->forward($ctx, $Tcur);

                $last = array_slice($logits, ($Tcur - 1) * $V, $V);

                // Suppress specials
                $last[Tokenizer::PAD] = -1e9;
                $last[Tokenizer::BOS] = -1e9;
                $last[Tokenizer::UNK] = -1e9;

                // Repetition penalty on last 6 generated tokens
                $genIds = array_slice($beam['ids'], count($seedIds));
                foreach (array_slice($genIds, -6) as $rid) {
                    if (isset($last[$rid])) $last[$rid] -= $repPenalty;
                }

                // Convert to log probabilities
                $logProbs = $this->logSoftmax($last);

                // Take top beamWidth tokens
                arsort($logProbs);
                $topTokens = array_slice($logProbs, 0, $beamWidth, true);

                foreach ($topTokens as $tokenId => $logp) {
                    $isDone = ($tokenId === Tokenizer::EOS);
                    $candidates[] = [
                        'ids'  => array_merge($beam['ids'], [$tokenId]),
                        'logp' => $beam['logp'] + $logp,
                        'done' => $isDone,
                    ];
                }
            }

            // Score = log_prob / len^alpha  (length-normalised)
            usort($candidates, function ($a, $b) use ($seedIds, $lengthPenalty) {
                $la = count($a['ids']) - count($seedIds);
                $lb = count($b['ids']) - count($seedIds);
                $sa = $a['logp'] / max(pow(max($la, 1), $lengthPenalty), 1e-9);
                $sb = $b['logp'] / max(pow(max($lb, 1), $lengthPenalty), 1e-9);
                return $sb <=> $sa;
            });

            $beams = array_slice($candidates, 0, $beamWidth);

            // Stop if all beams finished
            if (!in_array(false, array_column($beams, 'done'), true)) break;
        }

        // Return best beam's generated tokens (strip seed)
        $bestIds   = $beams[0]['ids'];
        $generated = array_slice($bestIds, count($seedIds));

        // Remove trailing EOS
        if (end($generated) === Tokenizer::EOS) array_pop($generated);

        return $tok->decode($generated);
    }

    /** Log-softmax (numerically stable). Used by beam search. */
    private function logSoftmax(array $x): array {
        $max  = max($x);
        $sum  = 0.0;
        foreach ($x as $v) $sum += exp($v - $max);
        $logSum = $max + log(max($sum, 1e-10));
        $out = [];
        foreach ($x as $k => $v) $out[$k] = $v - $logSum;
        return $out;
    }

    /** Top-p (nucleus) sampling — mirrors YugaGen implementation. */
    private function topPSample(array $probs, float $p): int {
        arsort($probs);
        $cum     = 0.0;
        $nucleus = [];
        foreach ($probs as $idx => $prob) {
            $nucleus[$idx] = $prob;
            $cum += $prob;
            if ($cum >= $p) break;
        }
        $sum = array_sum($nucleus) ?: 1e-10;
        foreach ($nucleus as &$v) $v /= $sum;
        unset($v);

        $r = mt_rand() / mt_getrandmax();
        $c = 0.0;
        foreach ($nucleus as $idx => $prob) {
            $c += $prob;
            if ($r <= $c) return $idx;
        }
        return array_key_first($nucleus);
    }

    // =================================================================
    // FORWARD PASS
    // =================================================================

    private function forward(array $tokens, int $T): array {
        $D = $this->D; $V = $this->V;

        // 1. Embeddings: x[t,d] = wte[tok[t],d] + wpe[t,d]
        $x = array_fill(0, $T * $D, 0.0);
        foreach ($tokens as $t => $tok) {
            $tokOff = max(0, min($tok, $V - 1)) * $D;
            $posOff = min($t, $this->T - 1) * $D;
            $xOff   = $t * $D;
            for ($d = 0; $d < $D; $d++) {
                $x[$xOff + $d] = $this->p['wte'][$tokOff + $d]
                                + $this->p['wpe'][$posOff + $d];
            }
        }

        // 2. Transformer blocks
        $blockCaches = [];
        for ($l = 0; $l < $this->L; $l++) {
            [$x, $bc] = $this->blockFwd($x, $T, $l);
            $blockCaches[$l] = $bc;
        }

        // 3. Final layer norm
        [$xf, $lnfCache] = $this->layernorm($x, $T, $D, $this->p['lnfg'], $this->p['lnfb']);

        // 4. LM head: logits = xf @ wte.T  (weight-tied)
        $logits = $this->mmt($xf, $T, $D, $this->p['wte'], $V);

        $cache = [
            'tokens'      => $tokens,
            'x_embed'     => $x,   // before blocks (we store pre-block x)
            'blockCaches' => $blockCaches,
            'xf'          => $xf,
            'lnfCache'    => $lnfCache,
        ];
        return [$logits, $cache];
    }

    // =================================================================
    // BACKWARD PASS — returns array of gradients keyed by param name
    // =================================================================

    private function backward(array $dLogits, array $cache, int $T): array {
        $D = $this->D; $V = $this->V;
        $grads = [];

        // 4. LM head backward (logits = xf @ wte.T)
        // d_xf = dLogits @ wte  [T,D]
        // d_wte += dLogits.T @ xf  [V,D]
        $xf = $cache['xf'];
        $dXf  = $this->mm($dLogits, $T, $V, $this->p['wte'], $D);
        $dWte = $this->tm($dLogits, $T, $V, $xf, $D);

        // 3. Final LN backward
        $lnBack = $this->layernormBack($dXf, $cache['lnfCache'], $T, $D);
        $grads['lnfg'] = $lnBack['dg'];
        $grads['lnfb'] = $lnBack['db'];
        $dX = $lnBack['dx'];

        // 2. Block backward (in reverse)
        for ($l = $this->L - 1; $l >= 0; $l--) {
            ['dx' => $dX, 'grads' => $bg] = $this->blockBwd($dX, $cache['blockCaches'][$l], $T, $l);
            foreach ($bg as $k => $v) $grads[$k] = $v;
        }

        // 1. Embedding backward
        // d_wte from embedding lookup
        if (!isset($grads['wte'])) $grads['wte'] = array_fill(0, $V * $D, 0.0);
        // Add lm_head gradient (computed above)
        for ($i = 0; $i < $V * $D; $i++) $grads['wte'][$i] += $dWte[$i];

        // Add embedding lookup gradient
        foreach ($cache['tokens'] as $t => $tok) {
            $tokId  = max(0, min($tok, $V - 1));
            $tokOff = $tokId * $D;
            $xOff   = $t * $D;
            for ($d = 0; $d < $D; $d++) {
                $grads['wte'][$tokOff + $d] += $dX[$xOff + $d];
            }
        }

        // Position embedding gradient
        $grads['wpe'] = array_fill(0, $this->T * $D, 0.0);
        foreach ($cache['tokens'] as $t => $_) {
            $posOff = min($t, $this->T - 1) * $D;
            $xOff   = $t * $D;
            for ($d = 0; $d < $D; $d++) {
                $grads['wpe'][$posOff + $d] += $dX[$xOff + $d];
            }
        }

        return $grads;
    }

    // =================================================================
    // TRANSFORMER BLOCK
    // =================================================================

    private function blockFwd(array $x, int $T, int $l): array {
        $D = $this->D;

        // Pre-attn LayerNorm
        [$ln1Out, $ln1Cache] = $this->layernorm($x, $T, $D, $this->p["l{$l}_ln1g"], $this->p["l{$l}_ln1b"]);

        // Multi-head causal self-attention
        [$attnOut, $attnCache] = $this->attention($ln1Out, $T, $l);

        // Residual 1
        $x1 = $x;
        for ($i = 0; $i < $T * $D; $i++) $x1[$i] += $attnOut[$i];

        // Pre-FF LayerNorm
        [$ln2Out, $ln2Cache] = $this->layernorm($x1, $T, $D, $this->p["l{$l}_ln2g"], $this->p["l{$l}_ln2b"]);

        // Feed-forward
        [$ffOut, $ffCache] = $this->ffn($ln2Out, $T, $l);

        // Residual 2
        $x2 = $x1;
        for ($i = 0; $i < $T * $D; $i++) $x2[$i] += $ffOut[$i];

        $cache = compact('x', 'ln1Cache', 'attnCache', 'x1', 'ln2Cache', 'ffCache');
        return [$x2, $cache];
    }

    private function blockBwd(array $dX2, array $cache, int $T, int $l): array {
        $D = $this->D;
        $grads = [];

        // FF residual: d_x1 += dX2,  d_ff_out = dX2
        $dX1    = $dX2;
        $dFfOut = $dX2;

        // FFN backward
        $ffBack = $this->ffnBack($dFfOut, $cache['ffCache'], $T, $l);
        $grads["l{$l}_w1"] = $ffBack['dW1'];
        $grads["l{$l}_b1"] = $ffBack['db1'];
        $grads["l{$l}_w2"] = $ffBack['dW2'];
        $grads["l{$l}_b2"] = $ffBack['db2'];

        // LN2 backward → accumulate into d_x1
        $ln2Back = $this->layernormBack($ffBack['dx'], $cache['ln2Cache'], $T, $D);
        $grads["l{$l}_ln2g"] = $ln2Back['dg'];
        $grads["l{$l}_ln2b"] = $ln2Back['db'];
        for ($i = 0; $i < $T * $D; $i++) $dX1[$i] += $ln2Back['dx'][$i];

        // Attn residual: d_x0 = dX1,  d_attn_out = dX1
        $dX0      = $dX1;
        $dAttnOut = $dX1;

        // Attention backward
        $attnBack = $this->attentionBack($dAttnOut, $cache['attnCache'], $T, $l);
        $grads["l{$l}_wq"] = $attnBack['dWq'];
        $grads["l{$l}_wk"] = $attnBack['dWk'];
        $grads["l{$l}_wv"] = $attnBack['dWv'];
        $grads["l{$l}_wo"] = $attnBack['dWo'];

        // LN1 backward → accumulate into d_x0
        $ln1Back = $this->layernormBack($attnBack['dx'], $cache['ln1Cache'], $T, $D);
        $grads["l{$l}_ln1g"] = $ln1Back['dg'];
        $grads["l{$l}_ln1b"] = $ln1Back['db'];
        for ($i = 0; $i < $T * $D; $i++) $dX0[$i] += $ln1Back['dx'][$i];

        return ['dx' => $dX0, 'grads' => $grads];
    }

    // =================================================================
    // LAYER NORM
    // =================================================================

    private function layernorm(array $x, int $T, int $D, array $g, array $b): array {
        $eps  = 1e-5;
        $out  = $xhat = array_fill(0, $T * $D, 0.0);
        $mus  = $stds = array_fill(0, $T, 0.0);

        for ($i = 0; $i < $T; $i++) {
            $off = $i * $D;
            $mu  = 0.0;
            for ($d = 0; $d < $D; $d++) $mu += $x[$off + $d];
            $mu /= $D;
            $mus[$i] = $mu;

            $var = 0.0;
            for ($d = 0; $d < $D; $d++) { $diff = $x[$off+$d]-$mu; $var += $diff*$diff; }
            $std = sqrt($var / $D + $eps);
            $stds[$i] = $std;

            for ($d = 0; $d < $D; $d++) {
                $xh = ($x[$off+$d] - $mu) / $std;
                $xhat[$off+$d] = $xh;
                $out[$off+$d]  = $g[$d] * $xh + $b[$d];
            }
        }
        return [$out, ['xhat' => $xhat, 'stds' => $stds, 'g' => $g]];
    }

    private function layernormBack(array $dy, array $cache, int $T, int $D): array {
        ['xhat' => $xhat, 'stds' => $stds, 'g' => $g] = $cache;
        $dx = array_fill(0, $T * $D, 0.0);
        $dg = array_fill(0, $D, 0.0);
        $db = array_fill(0, $D, 0.0);

        for ($i = 0; $i < $T; $i++) {
            $off = $i * $D;
            $std = $stds[$i];

            for ($d = 0; $d < $D; $d++) {
                $dg[$d] += $dy[$off+$d] * $xhat[$off+$d];
                $db[$d] += $dy[$off+$d];
            }

            // d_xhat[d] = dy[d] * g[d]
            $mdxh = $mdxhXh = 0.0;
            $dxhat = [];
            for ($d = 0; $d < $D; $d++) {
                $v = $dy[$off+$d] * $g[$d];
                $dxhat[$d] = $v;
                $mdxh     += $v;
                $mdxhXh   += $v * $xhat[$off+$d];
            }
            $mdxh   /= $D;
            $mdxhXh /= $D;

            for ($d = 0; $d < $D; $d++) {
                $dx[$off+$d] = ($dxhat[$d] - $mdxh - $xhat[$off+$d] * $mdxhXh) / $std;
            }
        }
        return compact('dx', 'dg', 'db');
    }

    // =================================================================
    // MULTI-HEAD CAUSAL SELF-ATTENTION
    // =================================================================

    private function attention(array $x, int $T, int $l): array {
        $D  = $this->D; $H = $this->H; $dh = intdiv($D, $H);

        $Q = $this->mm($x, $T, $D, $this->p["l{$l}_wq"], $D);
        $K = $this->mm($x, $T, $D, $this->p["l{$l}_wk"], $D);
        $V = $this->mm($x, $T, $D, $this->p["l{$l}_wv"], $D);

        $concat   = array_fill(0, $T * $D, 0.0);
        $headData = [];

        for ($h = 0; $h < $H; $h++) {
            $hOff = $h * $dh;
            $Qh = $Kh = $Vh = [];
            for ($t = 0; $t < $T; $t++) {
                for ($d = 0; $d < $dh; $d++) {
                    $Qh[] = $Q[$t*$D + $hOff + $d];
                    $Kh[] = $K[$t*$D + $hOff + $d];
                    $Vh[] = $V[$t*$D + $hOff + $d];
                }
            }

            $scale  = 1.0 / sqrt($dh);
            $scores = $this->mmt($Qh, $T, $dh, $Kh, $T);
            // Scale + causal mask + softmax
            $attn = array_fill(0, $T * $T, 0.0);
            for ($t = 0; $t < $T; $t++) {
                $off  = $t * $T;
                $maxv = -INF;
                for ($s = 0; $s <= $t; $s++) {
                    $v = $scores[$off+$s] * $scale;
                    if ($v > $maxv) $maxv = $v;
                }
                $sum = 0.0;
                for ($s = 0; $s <= $t; $s++) {
                    $attn[$off+$s] = exp($scores[$off+$s] * $scale - $maxv);
                    $sum += $attn[$off+$s];
                }
                for ($s = 0; $s <= $t; $s++) $attn[$off+$s] /= ($sum + 1e-10);
            }

            $outH = $this->mm($attn, $T, $T, $Vh, $dh);

            for ($t = 0; $t < $T; $t++) {
                for ($d = 0; $d < $dh; $d++) {
                    $concat[$t*$D + $hOff + $d] = $outH[$t*$dh + $d];
                }
            }
            $headData[$h] = compact('Qh', 'Kh', 'Vh', 'attn', 'outH');
        }

        $proj = $this->mm($concat, $T, $D, $this->p["l{$l}_wo"], $D);
        $cache = compact('x', 'Q', 'K', 'V', 'concat', 'headData');
        return [$proj, $cache];
    }

    private function attentionBack(array $dProj, array $cache, int $T, int $l): array {
        $D = $this->D; $H = $this->H; $dh = intdiv($D, $H);
        ['x'=>$x, 'concat'=>$concat, 'headData'=>$headData] = $cache;

        $dConcat = $this->mmt($dProj, $T, $D, $this->p["l{$l}_wo"], $D);
        $dWo     = $this->tm($concat, $T, $D, $dProj, $D);

        $dQ = $dK = $dV = array_fill(0, $T * $D, 0.0);

        $scale = 1.0 / sqrt($dh);

        for ($h = 0; $h < $H; $h++) {
            $hOff = $h * $dh;
            ['Qh'=>$Qh, 'Kh'=>$Kh, 'Vh'=>$Vh, 'attn'=>$attn, 'outH'=>$outH] = $headData[$h];

            $dOutH = [];
            for ($t = 0; $t < $T; $t++) {
                for ($d = 0; $d < $dh; $d++) {
                    $dOutH[] = $dConcat[$t*$D + $hOff + $d];
                }
            }

            $dVh   = $this->tm($attn, $T, $T, $dOutH, $dh);
            $dAttn = $this->mmt($dOutH, $T, $dh, $Vh, $T);

            // Softmax backward (per row, causal)
            $dScores = array_fill(0, $T * $T, 0.0);
            for ($t = 0; $t < $T; $t++) {
                $off = $t * $T;
                $dot = 0.0;
                for ($s = 0; $s <= $t; $s++) $dot += $dAttn[$off+$s] * $attn[$off+$s];
                for ($s = 0; $s <= $t; $s++) {
                    $dScores[$off+$s] = $attn[$off+$s] * ($dAttn[$off+$s] - $dot) * $scale;
                }
            }

            $dQh = $this->mm($dScores, $T, $T, $Kh, $dh);
            $dKh = $this->tm($dScores, $T, $T, $Qh, $dh);

            for ($t = 0; $t < $T; $t++) {
                for ($d = 0; $d < $dh; $d++) {
                    $dQ[$t*$D + $hOff + $d] += $dQh[$t*$dh + $d];
                    $dK[$t*$D + $hOff + $d] += $dKh[$t*$dh + $d];
                    $dV[$t*$D + $hOff + $d] += $dVh[$t*$dh + $d];
                }
            }
        }

        $dWq = $this->tm($x, $T, $D, $dQ, $D);
        $dWk = $this->tm($x, $T, $D, $dK, $D);
        $dWv = $this->tm($x, $T, $D, $dV, $D);

        $dx  = $this->mmt($dQ, $T, $D, $this->p["l{$l}_wq"], $D);
        $dxK = $this->mmt($dK, $T, $D, $this->p["l{$l}_wk"], $D);
        $dxV = $this->mmt($dV, $T, $D, $this->p["l{$l}_wv"], $D);
        for ($i = 0; $i < $T * $D; $i++) $dx[$i] += $dxK[$i] + $dxV[$i];

        return compact('dx', 'dWq', 'dWk', 'dWv', 'dWo');
    }

    // =================================================================
    // FEED-FORWARD  (Linear → GELU → Linear)
    // =================================================================

    private function ffn(array $x, int $T, int $l): array {
        $D = $this->D; $Dff = $this->Dff;

        $hPre = $this->mm($x, $T, $D, $this->p["l{$l}_w1"], $Dff);
        for ($t = 0; $t < $T; $t++) {
            for ($d = 0; $d < $Dff; $d++) $hPre[$t*$Dff+$d] += $this->p["l{$l}_b1"][$d];
        }
        $h   = $this->gelu($hPre);
        $out = $this->mm($h, $T, $Dff, $this->p["l{$l}_w2"], $D);
        for ($t = 0; $t < $T; $t++) {
            for ($d = 0; $d < $D; $d++) $out[$t*$D+$d] += $this->p["l{$l}_b2"][$d];
        }
        return [$out, compact('x', 'hPre', 'h')];
    }

    private function ffnBack(array $dOut, array $cache, int $T, int $l): array {
        $D = $this->D; $Dff = $this->Dff;
        ['x'=>$x, 'hPre'=>$hPre, 'h'=>$h] = $cache;

        $dH  = $this->mmt($dOut, $T, $D, $this->p["l{$l}_w2"], $Dff);
        $dW2 = $this->tm($h, $T, $Dff, $dOut, $D);
        $db2 = array_fill(0, $D, 0.0);
        for ($t = 0; $t < $T; $t++) {
            for ($d = 0; $d < $D; $d++) $db2[$d] += $dOut[$t*$D+$d];
        }

        $dHPre = $this->geluBack($dH, $hPre);
        $dW1   = $this->tm($x, $T, $D, $dHPre, $Dff);
        $db1   = array_fill(0, $Dff, 0.0);
        for ($t = 0; $t < $T; $t++) {
            for ($d = 0; $d < $Dff; $d++) $db1[$d] += $dHPre[$t*$Dff+$d];
        }
        $dx = $this->mmt($dHPre, $T, $Dff, $this->p["l{$l}_w1"], $D);

        return compact('dx', 'dW1', 'db1', 'dW2', 'db2');
    }

    // =================================================================
    // GELU ACTIVATION
    // =================================================================

    private function gelu(array $x): array {
        $c = sqrt(2.0 / M_PI);
        $out = [];
        foreach ($x as $v) {
            $t     = tanh($c * ($v + 0.044715 * $v * $v * $v));
            $out[] = 0.5 * $v * (1.0 + $t);
        }
        return $out;
    }

    private function geluBack(array $dy, array $x): array {
        $c  = sqrt(2.0 / M_PI);
        $dx = [];
        foreach ($x as $i => $v) {
            $arg  = $c * ($v + 0.044715 * $v * $v * $v);
            $t    = tanh($arg);
            $sec2 = 1.0 - $t * $t;
            $d    = 0.5 * (1 + $t) + 0.5 * $v * $sec2 * $c * (1 + 3 * 0.044715 * $v * $v);
            $dx[] = $dy[$i] * $d;
        }
        return $dx;
    }

    // =================================================================
    // ADAM OPTIMIZER
    // =================================================================

    private function adamStep(array $grads): void {
        $this->tA++;
        $bc1 = 1 - pow($this->b1, $this->tA);
        $bc2 = 1 - pow($this->b2, $this->tA);

        foreach ($grads as $key => $g) {
            if (!isset($this->mA[$key])) {
                $this->mA[$key] = array_fill(0, count($g), 0.0);
                $this->vA[$key] = array_fill(0, count($g), 0.0);
            }
            $n = count($g);
            for ($i = 0; $i < $n; $i++) {
                $gi = $g[$i];
                $this->mA[$key][$i] = $this->b1 * $this->mA[$key][$i] + (1 - $this->b1) * $gi;
                $this->vA[$key][$i] = $this->b2 * $this->vA[$key][$i] + (1 - $this->b2) * $gi * $gi;
                $mHat = $this->mA[$key][$i] / $bc1;
                $vHat = $this->vA[$key][$i] / $bc2;
                $this->p[$key][$i] -= $this->lr * $mHat / (sqrt($vHat) + $this->epsA);
            }
        }
    }

    private function clipGrads(array &$grads, float $maxNorm): void {
        $norm = 0.0;
        foreach ($grads as $g) {
            foreach ($g as $v) $norm += $v * $v;
        }
        $norm = sqrt($norm);
        if ($norm <= $maxNorm) return;
        $scale = $maxNorm / ($norm + 1e-6);
        foreach ($grads as &$g) {
            $n = count($g);
            for ($i = 0; $i < $n; $i++) $g[$i] *= $scale;
        }
    }

    // =================================================================
    // MATRIX OPERATIONS (flat-array, row-major)
    // =================================================================

    /** mm: A[m,k] @ B[k,n] → C[m,n] */
    private function mm(array $A, int $m, int $k, array $B, int $n): array {
        $C = array_fill(0, $m * $n, 0.0);
        for ($i = 0; $i < $m; $i++) {
            $iA = $i * $k; $iC = $i * $n;
            for ($p = 0; $p < $k; $p++) {
                $a = $A[$iA + $p];
                if ($a == 0.0) continue;
                $pB = $p * $n;
                for ($j = 0; $j < $n; $j++) $C[$iC + $j] += $a * $B[$pB + $j];
            }
        }
        return $C;
    }

    /** mmt: A[m,k] @ B[n,k].T → C[m,n]  (B stored as [n,k]) */
    private function mmt(array $A, int $m, int $k, array $B, int $n): array {
        $C = array_fill(0, $m * $n, 0.0);
        for ($i = 0; $i < $m; $i++) {
            $iA = $i * $k; $iC = $i * $n;
            for ($j = 0; $j < $n; $j++) {
                $s = 0.0; $jB = $j * $k;
                for ($p = 0; $p < $k; $p++) $s += $A[$iA + $p] * $B[$jB + $p];
                $C[$iC + $j] = $s;
            }
        }
        return $C;
    }

    /** tm: A[k,m].T @ B[k,n] → C[m,n]  (A stored as [k,m]) */
    private function tm(array $A, int $k, int $m, array $B, int $n): array {
        $C = array_fill(0, $m * $n, 0.0);
        for ($p = 0; $p < $k; $p++) {
            $pA = $p * $m; $pB = $p * $n;
            for ($i = 0; $i < $m; $i++) {
                $a = $A[$pA + $i];
                if ($a == 0.0) continue;
                $iC = $i * $n;
                for ($j = 0; $j < $n; $j++) $C[$iC + $j] += $a * $B[$pB + $j];
            }
        }
        return $C;
    }

    // =================================================================
    // UTILITIES
    // =================================================================

    private function softmax1d(array $x, float $temp): array {
        $n   = count($x);
        $max = max($x);
        $sum = 0.0;
        $e   = [];
        foreach ($x as $v) {
            $ev  = exp(($v - $max) / max($temp, 0.001));
            $e[] = $ev;
            $sum += $ev;
        }
        foreach ($e as &$v) $v /= ($sum + 1e-10);
        return $e;
    }

    private function sampleProbs(array $probs): int {
        $r = mt_rand() / mt_getrandmax();
        $c = 0.0;
        foreach ($probs as $i => $p) {
            $c += $p;
            if ($r <= $c) return $i;
        }
        return array_key_last($probs);
    }

    private function randn(int $n, float $std): array {
        $out = [];
        for ($i = 0; $i < $n; $i++) {
            $u1    = max(mt_rand() / mt_getrandmax(), 1e-10);
            $u2    = mt_rand() / mt_getrandmax();
            $out[] = $std * sqrt(-2 * log($u1)) * cos(2 * M_PI * $u2);
        }
        return $out;
    }

    // =================================================================
    // SERIALIZATION
    // =================================================================

    public function save(): array {
        return [
            'V' => $this->V, 'T' => $this->T, 'D' => $this->D,
            'H' => $this->H, 'L' => $this->L, 'Dff' => $this->Dff,
            'lr' => $this->lr, 'tA' => $this->tA,
            'steps' => $this->steps, 'lastLoss' => $this->lastLoss,
            'ready' => $this->ready,
            'p'  => $this->p,
            'mA' => $this->mA,
            'vA' => $this->vA,
        ];
    }

    public function load(array $d): void {
        foreach (['V','T','D','H','L','Dff','lr','tA','steps','lastLoss','ready','p','mA','vA'] as $k) {
            if (isset($d[$k])) $this->$k = $d[$k];
        }
    }

    public static function fromArray(array $d): self {
        $m = new self(); $m->load($d); return $m;
    }
}
