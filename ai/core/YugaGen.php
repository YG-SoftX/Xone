<?php
/**
 * YugaGen — Karpathy-style character-level GPT in pure PHP.
 *
 * This is a REAL language model. It learns to generate text
 * character by character, building real statistical language
 * understanding from whatever corpus you train it on.
 *
 * Architecture:
 *   Character-level tokenizer (vocab ~70-100 chars)
 *   Token + position embeddings
 *   N × GPT block (LayerNorm → Causal Attention → LayerNorm → FFN)
 *   Tied LM head
 *   Optimizer: AdamW with weight decay
 *
 * Size presets (choose at init):
 *   'nano'   → D=64  H=4  L=4  CTX=64   params ≈  400K  (fast on cPanel)
 *   'small'  → D=128 H=4  L=4  CTX=128  params ≈  1.5M  (good quality)
 *   'medium' → D=256 H=8  L=6  CTX=256  params ≈ 10M   (needs VPS)
 *
 * Training data requirement:
 *   nano:   ≥ 100K characters   (~50KB text file)
 *   small:  ≥ 500K characters   (~250KB text file)
 *   medium: ≥ 2M characters     (~1MB text file)
 */
class YugaGen {

    // ── Architecture ─────────────────────────────────────────────────
    public int   $V   = 0;     // vocab size (set from corpus)
    public int   $D   = 64;    // d_model
    public int   $H   = 4;     // attention heads
    public int   $L   = 4;     // transformer blocks
    public int   $CTX = 64;    // context window (chars)
    public int   $Dff = 0;     // feed-forward dim (auto = 4*D)

    // ── AdamW ─────────────────────────────────────────────────────────
    public float $lr      = 3e-4;
    public float $wd      = 0.1;    // weight decay
    public float $b1      = 0.9;
    public float $b2      = 0.999;
    public float $eps_adam = 1e-8;
    public float $grad_clip = 1.0;
    public int   $t_adam  = 0;

    // ── Char tokenizer ─────────────────────────────────────────────────
    public array $ch2id = [];   // char → int
    public array $id2ch = [];   // int → char
    public bool  $vocab_built = false;

    // ── Weights (string key → flat float[]) ──────────────────────────
    public array $p  = [];
    public array $m1 = [];   // Adam first moment
    public array $m2 = [];   // Adam second moment

    // ── Stats ─────────────────────────────────────────────────────────
    public int   $steps     = 0;
    public float $loss      = 0.0;
    public float $best_loss = INF;
    public bool  $ready     = false;

    // =================================================================
    // PUBLIC: init
    // =================================================================

    public static function make(string $size = 'nano'): self {
        $m = new self();
        match ($size) {
            'small'  => [$m->D, $m->H, $m->L, $m->CTX] = [128, 4, 4, 128],
            'medium' => [$m->D, $m->H, $m->L, $m->CTX] = [256, 8, 6, 256],
            default  => [$m->D, $m->H, $m->L, $m->CTX] = [64,  4, 4, 64 ],
        };
        return $m;
    }

    public function buildVocab(string $corpus): void {
        $chars = array_unique(str_split($corpus));
        sort($chars);
        $this->ch2id = array_flip($chars);
        $this->id2ch = $chars;
        $this->V     = count($chars);
        $this->vocab_built = true;
    }

    public function extendVocab(string $text): void {
        foreach (str_split($text) as $ch) {
            if (!isset($this->ch2id[$ch])) {
                $id = $this->V;
                $this->ch2id[$ch] = $id;
                $this->id2ch[$id] = $ch;
                $this->V++;
            }
        }
    }

    public function initWeights(): void {
        $D   = $this->D;
        $V   = $this->V;
        $CTX = $this->CTX;
        $H   = $this->H;
        $Dff = $this->Dff = 4 * $D;

        // Embeddings
        $this->p['wte'] = $this->gauss($V * $D,   0.02);
        $this->p['wpe'] = $this->gauss($CTX * $D, 0.01);

        // Transformer blocks
        for ($l = 0; $l < $this->L; $l++) {
            $s = "l{$l}_";
            // LayerNorm 1
            $this->p["{$s}ln1g"] = array_fill(0, $D, 1.0);
            $this->p["{$s}ln1b"] = array_fill(0, $D, 0.0);
            // Attention: combined QKV projection (more efficient)
            $this->p["{$s}qkv"]  = $this->gauss($D * 3 * $D, 0.02);
            $this->p["{$s}wo"]   = $this->gauss($D * $D, 0.02 / sqrt(2 * $this->L));
            // LayerNorm 2
            $this->p["{$s}ln2g"] = array_fill(0, $D, 1.0);
            $this->p["{$s}ln2b"] = array_fill(0, $D, 0.0);
            // FFN
            $this->p["{$s}w1"]   = $this->gauss($D * $Dff, 0.02);
            $this->p["{$s}b1"]   = array_fill(0, $Dff, 0.0);
            $this->p["{$s}w2"]   = $this->gauss($Dff * $D, 0.02 / sqrt(2 * $this->L));
            $this->p["{$s}b2"]   = array_fill(0, $D, 0.0);
        }
        // Final LN
        $this->p['lnfg'] = array_fill(0, $D, 1.0);
        $this->p['lnfb'] = array_fill(0, $D, 0.0);
        // LM head = wte (weight-tied, no separate weights)

        $this->ready = true;
    }

    public function growVocab(int $newV): void {
        $D = $this->D;
        $add = $newV - $this->V;
        if ($add <= 0) return;
        // Extend embedding table
        $this->p['wte'] = array_merge($this->p['wte'], $this->gauss($add * $D, 0.01));
        $this->V = $newV;
    }

    public function paramCount(): int {
        return array_sum(array_map('count', $this->p));
    }

    // =================================================================
    // ENCODE / DECODE
    // =================================================================

    public function encode(string $text): array {
        $ids = [];
        foreach (str_split($text) as $ch) {
            $ids[] = $this->ch2id[$ch] ?? 0;
        }
        return $ids;
    }

    public function decode(array $ids): string {
        $out = '';
        foreach ($ids as $id) $out .= $this->id2ch[$id] ?? '';
        return $out;
    }

    // =================================================================
    // FORWARD PASS
    // =================================================================

    private function forward(array $ids): array {
        $T   = count($ids);
        $D   = $this->D;
        $V   = $this->V;
        $CTX = $this->CTX;
        $Dff = $this->Dff;

        // 1. Embeddings
        $x = array_fill(0, $T * $D, 0.0);
        foreach ($ids as $t => $id) {
            $id  = max(0, min($id, $V - 1));
            $pos = min($t, $CTX - 1);
            $xo  = $t * $D;
            $eo  = $id * $D;
            $po  = $pos * $D;
            for ($d = 0; $d < $D; $d++) {
                $x[$xo + $d] = $this->p['wte'][$eo + $d] + $this->p['wpe'][$po + $d];
            }
        }

        // 2. Blocks
        $cache = ['ids' => $ids, 'x0' => $x];
        $blockCaches = [];
        for ($l = 0; $l < $this->L; $l++) {
            [$x, $bc] = $this->blockFwd($x, $T, $l);
            $blockCaches[$l] = $bc;
        }

        // 3. Final LN
        [$xf, $lnfc] = $this->layernorm($x, $T, $D, $this->p['lnfg'], $this->p['lnfb']);

        // 4. LM head (weight-tied with wte): logits[T,V]
        $logits = $this->mm($xf, $T, $D, $this->p['wte'], $V);

        $cache['blockCaches'] = $blockCaches;
        $cache['xf']          = $xf;
        $cache['lnfc']        = $lnfc;

        return [$logits, $cache];
    }

    // =================================================================
    // TRAIN STEP — teacher forcing over a sequence
    // =================================================================

    public function trainStep(array $ids): float {
        $n = count($ids);
        if ($n < 2) return 0.0;
        $T = min($n - 1, $this->CTX);

        $inp = array_slice($ids, 0, $T);
        $tgt = array_slice($ids, 1, $T);

        [$logits, $cache] = $this->forward($inp);

        // Loss + dLogits
        $V    = $this->V;
        $loss = 0.0;
        $dL   = array_fill(0, $T * $V, 0.0);
        for ($t = 0; $t < $T; $t++) {
            $off   = $t * $V;
            $row   = array_slice($logits, $off, $V);
            $probs = $this->softmax($row, 1.0);
            $ti    = $tgt[$t];
            $p     = max($probs[$ti] ?? 1e-10, 1e-10);
            $loss -= log($p);
            for ($j = 0; $j < $V; $j++) $dL[$off + $j] = $probs[$j];
            $dL[$off + $ti] -= 1.0;
        }
        $loss /= $T;
        for ($i = 0, $n2 = $T * $V; $i < $n2; $i++) $dL[$i] /= $T;

        if (is_nan($loss) || is_infinite($loss)) return $this->loss;

        $grads = $this->backward($dL, $cache, $T);
        $this->clipNorm($grads);
        $this->adamW($grads);

        $this->steps++;
        $this->loss = $loss;
        if ($loss < $this->best_loss) $this->best_loss = $loss;
        return $loss;
    }

    // =================================================================
    // GENERATE — real autoregressive text generation
    // =================================================================

    public function generate(
        string $seed     = '',
        int    $maxChars = 200,
        float  $temp     = 0.8,
        float  $topP     = 0.9       // nucleus sampling
    ): string {
        if (!$this->ready || $this->V === 0) return '';

        $ids  = $seed ? $this->encode($seed) : [0];
        $out  = $seed;

        for ($step = 0; $step < $maxChars; $step++) {
            $ctx     = array_slice($ids, -$this->CTX);
            $T       = count($ctx);
            [$logits,] = $this->forward($ctx);

            // Last position logits
            $last = array_slice($logits, ($T - 1) * $this->V, $this->V);
            $probs = $this->softmax($last, $temp);

            // Nucleus (top-p) sampling
            $next = $this->topPSample($probs, $topP);

            $ch   = $this->id2ch[$next] ?? '';
            $out .= $ch;
            $ids[] = $next;
        }

        return $out;
    }

    // Top-p (nucleus) sampling — much better than pure temperature
    private function topPSample(array $probs, float $p): int {
        // Sort by probability descending
        arsort($probs);
        $cum  = 0.0;
        $nucleus = [];
        foreach ($probs as $idx => $prob) {
            $nucleus[$idx] = $prob;
            $cum += $prob;
            if ($cum >= $p) break;
        }
        // Renormalise
        $sum = array_sum($nucleus);
        foreach ($nucleus as &$v) $v /= $sum;
        unset($v);
        // Sample
        $r = mt_rand() / mt_getrandmax();
        $c = 0.0;
        foreach ($nucleus as $idx => $prob) {
            $c += $prob;
            if ($r <= $c) return $idx;
        }
        return array_key_first($nucleus);
    }

    // =================================================================
    // BACKWARD PASS
    // =================================================================

    private function backward(array $dL, array $cache, int $T): array {
        $D   = $this->D;
        $V   = $this->V;
        $grads = [];

        // LM head / wte gradient: dWte = xf.T @ dL
        $dXf   = $this->mm($dL, $T, $V, $this->p['wte'], $D);     // [T,D]
        $grads['wte'] = $this->tm($cache['xf'], $T, $D, $dL, $V); // [V,D]

        // Final LN backward
        $lnb  = $this->lnBack($dXf, $cache['lnfc'], $T, $D);
        $grads['lnfg'] = $lnb['dg'];
        $grads['lnfb'] = $lnb['db'];
        $dX   = $lnb['dx'];

        // Blocks backward (reverse)
        for ($l = $this->L - 1; $l >= 0; $l--) {
            [$dX, $bg] = $this->blockBack($dX, $cache['blockCaches'][$l], $T, $l);
            foreach ($bg as $k => $v) $grads[$k] = $v;
        }

        // Embedding gradient (token + position)
        if (!isset($grads['wte'])) $grads['wte'] = array_fill(0, $V * $D, 0.0);
        $grads['wpe'] = array_fill(0, $this->CTX * $D, 0.0);

        foreach ($cache['ids'] as $t => $id) {
            $id  = max(0, min($id, $V - 1));
            $pos = min($t, $this->CTX - 1);
            $xo  = $t * $D;
            for ($d = 0; $d < $D; $d++) {
                $grads['wte'][$id  * $D + $d] += $dX[$xo + $d];
                $grads['wpe'][$pos * $D + $d] += $dX[$xo + $d];
            }
        }

        return $grads;
    }

    // =================================================================
    // BLOCK FORWARD / BACKWARD
    // =================================================================

    private function blockFwd(array $x, int $T, int $l): array {
        $D = $this->D; $s = "l{$l}_";

        // Pre-Attn LN
        [$ln1, $ln1c] = $this->layernorm($x, $T, $D, $this->p["{$s}ln1g"], $this->p["{$s}ln1b"]);

        // Attention
        [$attn, $attnc] = $this->attn($ln1, $T, $l);

        // Residual 1
        $x1 = $x;
        for ($i = 0; $i < $T * $D; $i++) $x1[$i] += $attn[$i];

        // Pre-FFN LN
        [$ln2, $ln2c] = $this->layernorm($x1, $T, $D, $this->p["{$s}ln2g"], $this->p["{$s}ln2b"]);

        // FFN
        [$ff, $ffc] = $this->ffn($ln2, $T, $l);

        // Residual 2
        $x2 = $x1;
        for ($i = 0; $i < $T * $D; $i++) $x2[$i] += $ff[$i];

        return [$x2, compact('x', 'ln1c', 'attnc', 'x1', 'ln2c', 'ffc')];
    }

    private function blockBack(array $dX2, array $c, int $T, int $l): array {
        $D = $this->D; $s = "l{$l}_"; $g = [];

        // FF residual
        $dX1 = $dX2; $dFf = $dX2;
        $fb = $this->ffnBack($dFf, $c['ffc'], $T, $l);
        $g["{$s}w1"] = $fb['dW1']; $g["{$s}b1"] = $fb['db1'];
        $g["{$s}w2"] = $fb['dW2']; $g["{$s}b2"] = $fb['db2'];
        $ln2b = $this->lnBack($fb['dx'], $c['ln2c'], $T, $D);
        $g["{$s}ln2g"] = $ln2b['dg']; $g["{$s}ln2b"] = $ln2b['db'];
        for ($i = 0; $i < $T * $D; $i++) $dX1[$i] += $ln2b['dx'][$i];

        // Attn residual
        $dX0 = $dX1; $dAttn = $dX1;
        $ab = $this->attnBack($dAttn, $c['attnc'], $T, $l);
        $g["{$s}qkv"] = $ab['dQKV']; $g["{$s}wo"] = $ab['dWo'];
        $ln1b = $this->lnBack($ab['dx'], $c['ln1c'], $T, $D);
        $g["{$s}ln1g"] = $ln1b['dg']; $g["{$s}ln1b"] = $ln1b['db'];
        for ($i = 0; $i < $T * $D; $i++) $dX0[$i] += $ln1b['dx'][$i];

        return [$dX0, $g];
    }

    // =================================================================
    // ATTENTION (combined QKV projection)
    // =================================================================

    private function attn(array $x, int $T, int $l): array {
        $D = $this->D; $H = $this->H; $dh = intdiv($D, $H); $s = "l{$l}_";

        // QKV = x @ [W_q; W_k; W_v]  → [T, 3D]
        $qkv = $this->mm($x, $T, $D, $this->p["{$s}qkv"], 3 * $D);

        // Split into Q, K, V each [T, D]
        $Q = $K = $V = array_fill(0, $T * $D, 0.0);
        for ($t = 0; $t < $T; $t++) {
            $qo = $t * 3 * $D; $to = $t * $D;
            for ($d = 0; $d < $D; $d++) {
                $Q[$to + $d] = $qkv[$qo + $d];
                $K[$to + $d] = $qkv[$qo + $D + $d];
                $V[$to + $d] = $qkv[$qo + 2 * $D + $d];
            }
        }

        $scale  = 1.0 / sqrt($dh);
        $concat = array_fill(0, $T * $D, 0.0);
        $headD  = [];

        for ($h = 0; $h < $H; $h++) {
            $ho = $h * $dh;
            $Qh = $Kh = $Vh = [];
            for ($t = 0; $t < $T; $t++) {
                for ($d = 0; $d < $dh; $d++) {
                    $Qh[] = $Q[$t * $D + $ho + $d];
                    $Kh[] = $K[$t * $D + $ho + $d];
                    $Vh[] = $V[$t * $D + $ho + $d];
                }
            }
            // Scores [T,T], masked
            $sc  = $this->mmt($Qh, $T, $dh, $Kh, $T);
            $att = array_fill(0, $T * $T, 0.0);
            for ($t = 0; $t < $T; $t++) {
                $off = $t * $T; $mx = -INF;
                for ($s2 = 0; $s2 <= $t; $s2++) { $v = $sc[$off+$s2]*$scale; if($v>$mx) $mx=$v; }
                $sm = 0.0;
                for ($s2 = 0; $s2 <= $t; $s2++) { $att[$off+$s2] = exp($sc[$off+$s2]*$scale-$mx); $sm += $att[$off+$s2]; }
                for ($s2 = 0; $s2 <= $t; $s2++) $att[$off+$s2] /= ($sm + 1e-10);
            }
            $outH = $this->mm($att, $T, $T, $Vh, $dh);
            for ($t = 0; $t < $T; $t++) {
                for ($d = 0; $d < $dh; $d++) $concat[$t*$D + $ho + $d] = $outH[$t*$dh + $d];
            }
            $headD[$h] = compact('Qh','Kh','Vh','att');
        }
        $proj = $this->mm($concat, $T, $D, $this->p["{$s}wo"], $D);
        return [$proj, compact('x','Q','K','V','qkv','concat','headD')];
    }

    private function attnBack(array $dProj, array $c, int $T, int $l): array {
        $D = $this->D; $H = $this->H; $dh = intdiv($D, $H); $s = "l{$l}_";

        $dConcat = $this->mmt($dProj, $T, $D, $this->p["{$s}wo"], $D);
        $dWo     = $this->tm($c['concat'], $T, $D, $dProj, $D);

        $dQ = $dK = $dV = array_fill(0, $T * $D, 0.0);
        $scale = 1.0 / sqrt($dh);

        for ($h = 0; $h < $H; $h++) {
            $ho = $h * $dh;
            ['Qh'=>$Qh,'Kh'=>$Kh,'Vh'=>$Vh,'att'=>$att] = $c['headD'][$h];
            $dOut = [];
            for ($t = 0; $t < $T; $t++) for ($d = 0; $d < $dh; $d++) $dOut[] = $dConcat[$t*$D+$ho+$d];
            $dVh  = $this->tm($att, $T, $T, $dOut, $dh);
            $dAtt = $this->mmt($dOut, $T, $dh, $Vh, $T);
            $dSc  = array_fill(0, $T*$T, 0.0);
            for ($t = 0; $t < $T; $t++) {
                $off = $t * $T; $dot = 0.0;
                for ($s2=0;$s2<=$t;$s2++) $dot += $dAtt[$off+$s2]*$att[$off+$s2];
                for ($s2=0;$s2<=$t;$s2++) $dSc[$off+$s2] = $att[$off+$s2]*($dAtt[$off+$s2]-$dot)*$scale;
            }
            $dQh = $this->mm($dSc, $T, $T, $Kh, $dh);
            $dKh = $this->tm($dSc, $T, $T, $Qh, $dh);
            for ($t=0;$t<$T;$t++) for ($d=0;$d<$dh;$d++) {
                $dQ[$t*$D+$ho+$d] += $dQh[$t*$dh+$d];
                $dK[$t*$D+$ho+$d] += $dKh[$t*$dh+$d];
                $dV[$t*$D+$ho+$d] += $dVh[$t*$dh+$d];
            }
        }

        // Backprop through QKV split
        $dQKV_flat = array_fill(0, $T * 3 * $D, 0.0);
        for ($t = 0; $t < $T; $t++) {
            $qo = $t * 3 * $D; $to = $t * $D;
            for ($d = 0; $d < $D; $d++) {
                $dQKV_flat[$qo + $d]         = $dQ[$to + $d];
                $dQKV_flat[$qo + $D + $d]    = $dK[$to + $d];
                $dQKV_flat[$qo + 2*$D + $d]  = $dV[$to + $d];
            }
        }
        $dQKV = $this->tm($c['x'], $T, $D, $dQKV_flat, 3 * $D);
        $dx   = $this->mmt($dQKV_flat, $T, 3*$D, $this->p["{$s}qkv"], $D);

        return ['dx'=>$dx, 'dQKV'=>$dQKV, 'dWo'=>$dWo];
    }

    // =================================================================
    // FEED-FORWARD (GELU)
    // =================================================================

    private function ffn(array $x, int $T, int $l): array {
        $D = $this->D; $Dff = $this->Dff; $s = "l{$l}_";
        $h = $this->mm($x, $T, $D, $this->p["{$s}w1"], $Dff);
        for ($t=0;$t<$T;$t++) for ($d=0;$d<$Dff;$d++) $h[$t*$Dff+$d] += $this->p["{$s}b1"][$d];
        $hg = $this->gelu($h);
        $o  = $this->mm($hg, $T, $Dff, $this->p["{$s}w2"], $D);
        for ($t=0;$t<$T;$t++) for ($d=0;$d<$D;$d++) $o[$t*$D+$d] += $this->p["{$s}b2"][$d];
        return [$o, compact('x','h','hg')];
    }

    private function ffnBack(array $dO, array $c, int $T, int $l): array {
        $D = $this->D; $Dff = $this->Dff; $s = "l{$l}_";
        $dH  = $this->mmt($dO, $T, $D, $this->p["{$s}w2"], $Dff);
        $dW2 = $this->tm($c['hg'], $T, $Dff, $dO, $D);
        $db2 = array_fill(0, $D, 0.0);
        for ($t=0;$t<$T;$t++) for ($d=0;$d<$D;$d++) $db2[$d] += $dO[$t*$D+$d];
        $dHg = $this->geluBack($dH, $c['h']);
        $dW1 = $this->tm($c['x'], $T, $D, $dHg, $Dff);
        $db1 = array_fill(0, $Dff, 0.0);
        for ($t=0;$t<$T;$t++) for ($d=0;$d<$Dff;$d++) $db1[$d] += $dHg[$t*$Dff+$d];
        $dx  = $this->mmt($dHg, $T, $Dff, $this->p["{$s}w1"], $D);
        return compact('dx','dW1','db1','dW2','db2');
    }

    // =================================================================
    // LAYER NORM
    // =================================================================

    private function layernorm(array $x, int $T, int $D, array $g, array $b): array {
        $eps = 1e-5;
        $out = $xh = array_fill(0, $T*$D, 0.0);
        $mu = $std = array_fill(0, $T, 0.0);
        for ($t=0;$t<$T;$t++) {
            $o = $t*$D; $m = 0.0;
            for ($d=0;$d<$D;$d++) $m += $x[$o+$d];
            $m /= $D; $mu[$t] = $m;
            $v = 0.0;
            for ($d=0;$d<$D;$d++) { $df=$x[$o+$d]-$m; $v+=$df*$df; }
            $s = sqrt($v/$D+$eps); $std[$t] = $s;
            for ($d=0;$d<$D;$d++) { $xhv=($x[$o+$d]-$m)/$s; $xh[$o+$d]=$xhv; $out[$o+$d]=$g[$d]*$xhv+$b[$d]; }
        }
        return [$out, ['xh'=>$xh,'std'=>$std,'g'=>$g]];
    }

    private function lnBack(array $dy, array $c, int $T, int $D): array {
        $dx=$dg=$db=array_fill(0,$D,0.0);
        $dx=array_fill(0,$T*$D,0.0);
        for ($t=0;$t<$T;$t++) {
            $o=$t*$D; $s=$c['std'][$t];
            for ($d=0;$d<$D;$d++) { $dg[$d]+=$dy[$o+$d]*$c['xh'][$o+$d]; $db[$d]+=$dy[$o+$d]; }
            $m1=$m2=0.0;
            $dxh=[];
            for ($d=0;$d<$D;$d++) { $v=$dy[$o+$d]*$c['g'][$d]; $dxh[$d]=$v; $m1+=$v; $m2+=$v*$c['xh'][$o+$d]; }
            $m1/=$D; $m2/=$D;
            for ($d=0;$d<$D;$d++) $dx[$o+$d]=($dxh[$d]-$m1-$c['xh'][$o+$d]*$m2)/$s;
        }
        return compact('dx','dg','db');
    }

    // =================================================================
    // GELU
    // =================================================================

    private function gelu(array $x): array {
        $c = sqrt(2/M_PI); $out=[];
        foreach ($x as $v) { $t=tanh($c*($v+0.044715*$v*$v*$v)); $out[]=0.5*$v*(1+$t); }
        return $out;
    }

    private function geluBack(array $dy, array $x): array {
        $c=sqrt(2/M_PI); $dx=[];
        foreach ($x as $i=>$v) {
            $a=$c*($v+0.044715*$v*$v*$v); $t=tanh($a); $s2=1-$t*$t;
            $dx[]=($dy[$i])*(0.5*(1+$t)+0.5*$v*$s2*$c*(1+3*0.044715*$v*$v));
        }
        return $dx;
    }

    // =================================================================
    // AdamW OPTIMIZER
    // =================================================================

    private function adamW(array &$grads): void {
        $this->t_adam++;
        $bc1 = 1 - $this->b1 ** $this->t_adam;
        $bc2 = 1 - $this->b2 ** $this->t_adam;
        // Weight decay only on weight matrices (not biases, embeddings, LN params)
        $no_wd = ['wte','wpe','lnfg','lnfb'];
        for ($l=0;$l<$this->L;$l++) { $no_wd[]="l{$l}_ln1g"; $no_wd[]="l{$l}_ln1b"; $no_wd[]="l{$l}_ln2g"; $no_wd[]="l{$l}_ln2b"; $no_wd[]="l{$l}_b1"; $no_wd[]="l{$l}_b2"; }

        foreach ($grads as $k => &$g) {
            if (!isset($this->p[$k])) continue;
            if (!isset($this->m1[$k])) { $this->m1[$k] = array_fill(0,count($g),0.0); $this->m2[$k] = array_fill(0,count($g),0.0); }
            $wd = in_array($k, $no_wd) ? 0.0 : $this->wd;
            $n  = count($g);
            for ($i=0;$i<$n;$i++) {
                $gi = $g[$i];
                $this->m1[$k][$i] = $this->b1 * $this->m1[$k][$i] + (1-$this->b1) * $gi;
                $this->m2[$k][$i] = $this->b2 * $this->m2[$k][$i] + (1-$this->b2) * $gi*$gi;
                $mh = $this->m1[$k][$i] / $bc1;
                $vh = $this->m2[$k][$i] / $bc2;
                $this->p[$k][$i] -= $this->lr * ($mh/(sqrt($vh)+$this->eps_adam) + $wd * $this->p[$k][$i]);
            }
        }
    }

    private function clipNorm(array &$grads): void {
        $norm = 0.0;
        foreach ($grads as $g) foreach ($g as $v) $norm += $v*$v;
        $norm = sqrt($norm);
        if ($norm <= $this->grad_clip) return;
        $s = $this->grad_clip / ($norm + 1e-6);
        foreach ($grads as &$g) { $n=count($g); for($i=0;$i<$n;$i++) $g[$i]*=$s; }
    }

    // =================================================================
    // MATRIX OPS
    // =================================================================

    private function mm(array $A,int $m,int $k,array $B,int $n): array {
        $C=array_fill(0,$m*$n,0.0);
        for($i=0;$i<$m;$i++){$iA=$i*$k;$iC=$i*$n;for($p=0;$p<$k;$p++){$a=$A[$iA+$p];if($a==0.0)continue;$pB=$p*$n;for($j=0;$j<$n;$j++)$C[$iC+$j]+=$a*$B[$pB+$j];}}
        return $C;
    }
    private function mmt(array $A,int $m,int $k,array $B,int $n): array {
        $C=array_fill(0,$m*$n,0.0);
        for($i=0;$i<$m;$i++){$iA=$i*$k;$iC=$i*$n;for($j=0;$j<$n;$j++){$s=0.0;$jB=$j*$k;for($p=0;$p<$k;$p++)$s+=$A[$iA+$p]*$B[$jB+$p];$C[$iC+$j]=$s;}}
        return $C;
    }
    private function tm(array $A,int $k,int $m,array $B,int $n): array {
        $C=array_fill(0,$m*$n,0.0);
        for($p=0;$p<$k;$p++){$pA=$p*$m;$pB=$p*$n;for($i=0;$i<$m;$i++){$a=$A[$pA+$i];if($a==0.0)continue;$iC=$i*$n;for($j=0;$j<$n;$j++)$C[$iC+$j]+=$a*$B[$pB+$j];}}
        return $C;
    }

    private function softmax(array $x, float $t): array {
        $mx=max($x); $s=0.0; $e=[];
        foreach($x as $v){$ev=exp(($v-$mx)/max($t,1e-4));$e[]=$ev;$s+=$ev;}
        foreach($e as &$v) $v/=($s+1e-10);
        return $e;
    }

    private function gauss(int $n, float $std): array {
        $out=[];
        for($i=0;$i<$n;$i++){$u=max(mt_rand()/mt_getrandmax(),1e-10);$v=mt_rand()/mt_getrandmax();$out[]=$std*sqrt(-2*log($u))*cos(2*M_PI*$v);}
        return $out;
    }

    // =================================================================
    // SAVE / LOAD
    // =================================================================

    public function save(): array {
        return ['V'=>$this->V,'D'=>$this->D,'H'=>$this->H,'L'=>$this->L,'CTX'=>$this->CTX,'Dff'=>$this->Dff,
                'lr'=>$this->lr,'wd'=>$this->wd,'t_adam'=>$this->t_adam,'steps'=>$this->steps,
                'loss'=>$this->loss,'best_loss'=>$this->best_loss,'ready'=>$this->ready,
                'ch2id'=>$this->ch2id,'id2ch'=>$this->id2ch,'vocab_built'=>$this->vocab_built,
                'p'=>$this->p,'m1'=>$this->m1,'m2'=>$this->m2];
    }

    public function load(array $d): void {
        foreach(['V','D','H','L','CTX','Dff','lr','wd','t_adam','steps','loss','best_loss',
                 'ready','ch2id','id2ch','vocab_built','p','m1','m2'] as $k) {
            if(isset($d[$k])) $this->$k=$d[$k];
        }
    }

    public static function fromArray(array $d): self {
        $m=new self(); $m->load($d); return $m;
    }
}
