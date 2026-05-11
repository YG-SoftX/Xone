#!/usr/bin/env php
<?php
/**
 * Auto-retrain cron script
 * Add to cPanel cron: 0 * * * * php /path/to/yuga/training/auto_retrain.php
 */
define('YUGA_ROOT', dirname(__DIR__));
require_once YUGA_ROOT . '/core/YugaLM.php';
require_once YUGA_ROOT . '/core/ModelStore.php';
require_once YUGA_ROOT . '/core/Tokenizer.php';
require_once YUGA_ROOT . '/core/Transformer.php';
require_once YUGA_ROOT . '/core/Retriever.php';
require_once YUGA_ROOT . '/core/Brain.php';
require_once YUGA_ROOT . '/core/AutoRetrain.php';
require_once YUGA_ROOT . '/core/Feedback.php';

function log_r(string $msg): void { echo '[' . date('H:i:s') . '] ' . $msg . PHP_EOL; }

$store = new ModelStore(YUGA_ROOT . '/data');
$ar    = new AutoRetrain(YUGA_ROOT . '/data');
$fb    = new Feedback(YUGA_ROOT . '/data');

log_r("Auto-retrain check starting...");
$triggered = $ar->checkAll();

if (empty($triggered)) {
    log_r("No changes detected. Nothing to retrain.");
    exit(0);
}

foreach ($triggered as $job) {
    $model   = $job['model'];
    $trigger = $job['trigger'];
    $text    = $job['new_text'] ?? '';
    $steps   = $job['steps']    ?? 3000;

    log_r("Retraining '$model' (trigger: $trigger, chars: " . number_format(strlen($text)) . ")");

    $brain    = new Brain($model, $store);
    $status   = $brain->status();
    $loss_bef = $status['loss'] ?? 0;

    // Include positive feedback examples
    $good_examples = $fb->exportGoodExamples($model);
    if ($good_examples) {
        $text .= "\n\n" . $good_examples;
        log_r("  Added " . str_word_count($good_examples) . " words of feedback examples");
    }

    if (strlen(trim($text)) > 100) {
        $r        = $brain->learn($text, $steps);
        $loss_aft = $r['loss'] ?? 0;
        log_r("  Done. Steps: {$r['steps']} | Loss: $loss_bef → $loss_aft");
        $ar->logRetrain($model, $trigger, strlen($text), $r['steps'], $loss_bef, $loss_aft);
    } else {
        log_r("  Skipped — insufficient new content.");
    }
}

log_r("Auto-retrain complete.");
