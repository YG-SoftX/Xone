#!/usr/bin/env php
<?php
/**
 * Yuga YugaGen Trainer
 *
 * Runs as a PHP CLI process — designed to be launched by cPanel cron jobs.
 * Saves a checkpoint every N steps so training can resume across runs.
 *
 * Usage:
 *   php train.php --model=mygpt --size=nano --steps=5000
 *   php train.php --model=mygpt --steps=5000   (resumes from checkpoint)
 *   php train.php --model=mygpt --generate="Once upon"
 *
 * cPanel cron (every 30 minutes, 5000 steps per run):
 *   *\/30 * * * * php /home/user/public_html/yuga/training/train.php --model=mygpt --steps=5000 >> /tmp/yuga_train.log 2>&1
 */

define('YUGA_ROOT', dirname(__DIR__));
require_once YUGA_ROOT . '/core/YugaGen.php';
require_once YUGA_ROOT . '/core/ModelStore.php';

// ── Parse arguments ──────────────────────────────────────────────────
$args = [];
foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--')) {
        [$k, $v] = array_pad(explode('=', substr($arg, 2), 2), 2, true);
        $args[$k] = $v;
    }
}

$model_name   = $args['model']    ?? 'nanogpt';
$size         = $args['size']     ?? 'nano';
$steps_this_run = (int)($args['steps'] ?? 5000);
$generate_seed  = $args['generate'] ?? null;
$corpus_file    = $args['corpus']   ?? YUGA_ROOT . '/training/corpus.txt';
$data_dir       = YUGA_ROOT . '/data';
$ckpt_file      = $data_dir . "/ckpt_{$model_name}.json.gz";
$lr_override    = isset($args['lr'])   ? (float)$args['lr']   : null;

// ── Load or create model ─────────────────────────────────────────────
function log_msg(string $msg): void {
    echo '[' . date('H:i:s') . '] ' . $msg . PHP_EOL;
}

log_msg("Yuga YugaGen Trainer");
log_msg("Model: $model_name  Size: $size  Steps this run: $steps_this_run");

$store = new ModelStore($data_dir);

if (file_exists($ckpt_file)) {
    log_msg("Loading checkpoint: $ckpt_file");
    $gz   = file_get_contents($ckpt_file);
    $data = json_decode(gzdecode($gz), true);
    $gpt  = YugaGen::fromArray($data);
    log_msg("Resumed. Steps so far: {$gpt->steps}  Loss: " . round($gpt->loss, 4));
} else {
    log_msg("No checkpoint found — starting fresh ($size preset)");
    $gpt = YugaGen::make($size);
    if ($lr_override) $gpt->lr = $lr_override;
}

// ── Generate mode ─────────────────────────────────────────────────────
if ($generate_seed !== null) {
    if (!$gpt->ready) { log_msg("Model not trained yet."); exit(1); }
    log_msg("Generating from seed: \"$generate_seed\"");
    echo "\n" . $gpt->generate($generate_seed, 500, 0.8, 0.9) . "\n\n";
    exit(0);
}

// ── Load corpus ───────────────────────────────────────────────────────
if (!file_exists($corpus_file)) {
    log_msg("ERROR: Corpus file not found: $corpus_file");
    log_msg("Create it with: php train.php --model=$model_name --build-corpus=https://yoursite.com");
    exit(1);
}

$corpus = file_get_contents($corpus_file);
$corpus_len = strlen($corpus);
log_msg("Corpus: $corpus_file  (" . number_format($corpus_len) . " chars)");

if ($corpus_len < 1000) {
    log_msg("WARNING: Corpus too small. Need at least 10,000 chars for meaningful training.");
}

// ── Build/extend vocab ────────────────────────────────────────────────
if (!$gpt->vocab_built) {
    $gpt->buildVocab($corpus);
    $gpt->initWeights();
    log_msg("Vocab built: {$gpt->V} unique characters");
    log_msg("Parameters: " . number_format($gpt->paramCount()));
} else {
    $before = $gpt->V;
    $gpt->extendVocab($corpus);
    if ($gpt->V > $before) {
        $gpt->growVocab($gpt->V);
        log_msg("Vocab extended: $before → {$gpt->V} chars");
    }
}

// ── Build training sequences ──────────────────────────────────────────
$CTX     = $gpt->CTX + 1;   // input + target
$ids     = $gpt->encode($corpus);
$n       = count($ids);
$seqs    = [];
$stride  = max(1, intdiv($CTX, 2));    // 50% overlap for more pairs

for ($i = 0; $i + $CTX <= $n; $i += $stride) {
    $seqs[] = array_slice($ids, $i, $CTX);
}

$num_seqs = count($seqs);
log_msg("Training sequences: $num_seqs  (stride=$stride, CTX={$gpt->CTX})");

// ── Training loop ─────────────────────────────────────────────────────
$start      = microtime(true);
$log_every  = 100;
$save_every = 500;
$sum_loss   = 0.0;
$step       = 0;

$epoch_seqs = $seqs;
shuffle($epoch_seqs);
$seq_idx = 0;

while ($step < $steps_this_run) {
    // Refill and reshuffle when epoch ends
    if ($seq_idx >= $num_seqs) {
        shuffle($epoch_seqs);
        $seq_idx = 0;
    }

    $loss = $gpt->trainStep($epoch_seqs[$seq_idx++]);
    $sum_loss += $loss;
    $step++;

    // Log
    if ($step % $log_every === 0) {
        $avg  = round($sum_loss / $log_every, 4);
        $elapsed = round(microtime(true) - $start, 1);
        $sps  = round($step / max($elapsed, 0.01), 1);
        $sum_loss = 0.0;

        // Generate a quick sample to see quality
        $sample = '';
        if ($gpt->steps > 500 && $step % ($log_every * 5) === 0) {
            $seed   = $gpt->id2ch[0] ?? '';
            $sample = '  sample: "' . substr(str_replace("\n", ' ', $gpt->generate($seed, 60, 0.8)), 0, 60) . '"';
        }

        log_msg("step {$gpt->steps}  loss=$avg  {$sps}steps/s  elapsed={$elapsed}s$sample");
    }

    // Save checkpoint
    if ($step % $save_every === 0) {
        $encoded = gzencode(json_encode($gpt->save()), 6);
        file_put_contents($ckpt_file, $encoded);
        log_msg("Checkpoint saved → $ckpt_file");
    }
}

// Final save
$encoded = gzencode(json_encode($gpt->save()), 6);
file_put_contents($ckpt_file, $encoded);

$total_time = round(microtime(true) - $start, 1);
log_msg("Done. Steps total: {$gpt->steps}  Loss: " . round($gpt->loss, 4) . "  Time: {$total_time}s");
log_msg("Checkpoint: $ckpt_file  (" . round(filesize($ckpt_file)/1024) . "KB)");

// Final generation sample
if ($gpt->ready && $gpt->steps > 200) {
    log_msg("Final sample (temp=0.8):");
    $seed = $gpt->id2ch[array_rand($gpt->id2ch)] ?? '';
    echo "\n" . $gpt->generate($seed, 300, 0.8, 0.9) . "\n\n";
}
