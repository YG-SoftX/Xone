<?php
/**
 * Thinker — Chain-of-Thought structured reasoning for Yuga
 *
 * Adds genuine "thinking" capability to the SLM by decomposing questions
 * into structured reasoning steps before generating an answer.
 *
 * Works at ANY model size — this is PHP-level reasoning, not model-level.
 * Even a 65K param model produces dramatically better answers when the
 * retrieval and synthesis are structured correctly.
 *
 * Question types handled:
 *   factual       — "What is X?"  → retrieve + return
 *   definition    — "Define / Explain X" → extract defining sentences
 *   comparison    — "Compare X and Y / difference between" → side-by-side
 *   procedure     — "How do I / Steps to / Process for" → ordered steps
 *   causal        — "Why does / What causes / Reason for" → cause → effect
 *   recommendation — "Which is better / Should I / Best option" → options + verdict
 *   list          — "What are all / List all / Types of" → enumeration
 *
 * Each type has its own:
 *   - Sub-question decomposition strategy
 *   - Evidence gathering pattern
 *   - Reasoning structure
 *   - Answer synthesis template
 *
 * Usage:
 *   $thinker = new Thinker($brain);
 *   $result  = $thinker->think("What is the difference between the Free and Pro plan?");
 *   echo $result['answer'];
 *   print_r($result['thinking']); // full reasoning trace
 */
class Thinker {

    private Brain    $brain;
    private Reasoner $reasoner;

    // Question type constants
    const TYPE_FACTUAL        = 'factual';
    const TYPE_DEFINITION     = 'definition';
    const TYPE_COMPARISON     = 'comparison';
    const TYPE_PROCEDURE      = 'procedure';
    const TYPE_CAUSAL         = 'causal';
    const TYPE_RECOMMENDATION = 'recommendation';
    const TYPE_LIST           = 'list';

    public float $temperature = 0.65;
    public int   $maxEvidence = 5;

    public function __construct(Brain $brain) {
        $this->brain    = $brain;
        $this->reasoner = new Reasoner($brain);
        $this->reasoner->temperature = $this->temperature;
    }

    // ── Main entry: think through a question ───────────────────────────
    public function think(string $question): array {
        if ($this->brain->corpusSize() === 0) {
            return [
                'answer'    => "I haven't learned anything yet. Please train the model first.",
                'thinking'  => [],
                'type'      => 'unknown',
                'confidence'=> 0.0,
            ];
        }

        // Step 1 — Classify question type
        $type = $this->classify($question);

        // Step 2 — Build thinking plan (sub-questions + strategy)
        $plan = $this->plan($question, $type);

        // Step 3 — Gather evidence for each sub-question
        $evidence = $this->gatherEvidence($plan['sub_questions']);

        // Step 4 — Apply reasoning pattern for this type
        $reasoning = $this->applyReasoning($type, $question, $evidence, $plan);

        // Step 5 — Synthesize final answer
        $answer = $this->synthesize($type, $question, $reasoning);

        return [
            'answer'     => $answer,
            'type'       => $type,
            'confidence' => $reasoning['confidence'] ?? 0.0,
            'thinking'   => [
                'type'          => $type,
                'plan'          => $plan,
                'evidence'      => $evidence,
                'reasoning'     => $reasoning,
            ],
        ];
    }

    // =================================================================
    // STEP 1 — QUESTION CLASSIFICATION
    // =================================================================

    private function classify(string $q): string {
        $q = strtolower(trim($q));

        // Comparison patterns
        if (preg_match('/\b(difference|compare|versus|vs\.?|better|worse|contrast|'
                      .'similar|same as|unlike|whereas)\b/i', $q)) {
            return self::TYPE_COMPARISON;
        }

        // Recommendation patterns
        if (preg_match('/\b(should i|which (is|are|one|plan|option|product)|'
                      .'recommend|best for|right choice|suggest|advise)\b/i', $q)) {
            return self::TYPE_RECOMMENDATION;
        }

        // Procedure patterns
        if (preg_match('/\b(how (do|can|to|does)|steps?|process|procedure|'
                      .'guide|tutorial|instructions?|setup|configure|install)\b/i', $q)) {
            return self::TYPE_PROCEDURE;
        }

        // Causal patterns
        if (preg_match('/\b(why|reason|cause|because|leads? to|result(s?) in|'
                      .'effect of|what happens when|consequence)\b/i', $q)) {
            return self::TYPE_CAUSAL;
        }

        // List patterns
        if (preg_match('/\b(list|all (the|of)|types? of|kinds? of|examples? of|'
                      .'what are|features?|benefits?|options?|available)\b/i', $q)) {
            return self::TYPE_LIST;
        }

        // Definition patterns
        if (preg_match('/\b(what is|define|explain|meaning of|describe|'
                      .'tell me about|overview of|introduction to)\b/i', $q)) {
            return self::TYPE_DEFINITION;
        }

        return self::TYPE_FACTUAL;
    }

    // =================================================================
    // STEP 2 — BUILD THINKING PLAN
    // =================================================================

    private function plan(string $question, string $type): array {
        $subQuestions = match ($type) {
            self::TYPE_COMPARISON     => $this->planComparison($question),
            self::TYPE_PROCEDURE      => $this->planProcedure($question),
            self::TYPE_CAUSAL         => $this->planCausal($question),
            self::TYPE_RECOMMENDATION => $this->planRecommendation($question),
            self::TYPE_LIST           => $this->planList($question),
            self::TYPE_DEFINITION     => $this->planDefinition($question),
            default                   => [$question],
        };

        return [
            'type'          => $type,
            'sub_questions' => $subQuestions,
            'strategy'      => $this->strategyFor($type),
        ];
    }

    private function planComparison(string $q): array {
        // Extract subjects being compared
        // "difference between X and Y" → compare X, compare Y, what differs
        preg_match('/between\s+(.+?)\s+and\s+(.+?)(?:\s*\?|$)/i', $q, $m);
        if (isset($m[1], $m[2])) {
            return [
                'What is ' . trim($m[1]) . '?',
                'What is ' . trim($m[2]) . '?',
                'What does ' . trim($m[1]) . ' include or offer?',
                'What does ' . trim($m[2]) . ' include or offer?',
                $q, // original for final synthesis
            ];
        }
        // "X vs Y" pattern
        if (preg_match('/^(.+?)\s+(?:vs?\.?|versus|or)\s+(.+?)(?:\s*\?|$)/i', $q, $m)) {
            return [trim($m[1]), trim($m[2]), $q];
        }
        return [$q];
    }

    private function planProcedure(string $q): array {
        // Extract the action subject
        $subject = preg_replace('/^how\s+(do|can|to|does)\s+(i|you|we|one)?\s*/i', '', $q);
        $subject = preg_replace('/\?$/', '', $subject);
        return [
            $q,
            'steps to ' . $subject,
            'requirements for ' . $subject,
            'first step ' . $subject,
        ];
    }

    private function planCausal(string $q): array {
        $subject = preg_replace('/^why\s+/i', '', $q);
        $subject = preg_replace('/\?$/', '', $subject);
        return [
            $q,
            'reason for ' . $subject,
            'cause of ' . $subject,
            'result of ' . $subject,
        ];
    }

    private function planRecommendation(string $q): array {
        // Extract what's being recommended about
        $context = preg_replace('/^(which|should i|what)\s+(is|are|plan|product|option)?\s*/i', '', $q);
        $context = preg_replace('/\?$/', '', $context);
        return [
            $q,
            'benefits of ' . $context,
            'features ' . $context,
            'pricing ' . $context,
            'requirements ' . $context,
        ];
    }

    private function planList(string $q): array {
        $subject = preg_replace('/^(what are|list|all|types of|features of)\s*/i', '', $q);
        $subject = preg_replace('/\?$/', '', $subject);
        return [
            $q,
            $subject,
            'available ' . $subject,
            'all ' . $subject,
        ];
    }

    private function planDefinition(string $q): array {
        $subject = preg_replace('/^(what is|define|explain|describe)\s*/i', '', $q);
        $subject = preg_replace('/\?$/', '', trim($subject));
        return [
            $q,
            $subject . ' definition',
            $subject . ' overview',
            $subject . ' purpose',
        ];
    }

    private function strategyFor(string $type): string {
        return match ($type) {
            self::TYPE_COMPARISON     => 'Gather info on each subject separately, then highlight differences',
            self::TYPE_PROCEDURE      => 'Gather ordered steps, requirements, and starting conditions',
            self::TYPE_CAUSAL         => 'Gather causes and effects, build cause→effect chain',
            self::TYPE_RECOMMENDATION => 'Gather options with features/benefits, apply suitability logic',
            self::TYPE_LIST           => 'Collect all distinct items from corpus, deduplicate',
            self::TYPE_DEFINITION     => 'Gather defining, descriptive, and explanatory sentences',
            default                   => 'Retrieve most relevant sentences and synthesize',
        };
    }

    // =================================================================
    // STEP 3 — EVIDENCE GATHERING
    // =================================================================

    private function gatherEvidence(array $subQuestions): array {
        $evidence = [];
        foreach ($subQuestions as $sq) {
            $hits = $this->brain->retrieve($sq, $this->maxEvidence);
            if (!empty($hits)) {
                $evidence[$sq] = $hits;
            }
        }
        return $evidence;
    }

    // =================================================================
    // STEP 4 — APPLY REASONING PATTERNS
    // =================================================================

    private function applyReasoning(string $type, string $question, array $evidence, array $plan): array {
        return match ($type) {
            self::TYPE_COMPARISON     => $this->reasonComparison($plan, $evidence),
            self::TYPE_PROCEDURE      => $this->reasonProcedure($question, $evidence),
            self::TYPE_CAUSAL         => $this->reasonCausal($question, $evidence),
            self::TYPE_RECOMMENDATION => $this->reasonRecommendation($question, $evidence),
            self::TYPE_LIST           => $this->reasonList($question, $evidence),
            self::TYPE_DEFINITION     => $this->reasonDefinition($question, $evidence),
            default                   => $this->reasonFactual($question, $evidence),
        };
    }

    // ── Comparison reasoning ────────────────────────────────────────────
    private function reasonComparison(array $plan, array $evidence): array {
        $subs = $plan['sub_questions'];
        $aKey = $subs[0] ?? '';
        $bKey = $subs[1] ?? '';

        $aFacts = $this->topTexts($evidence[$aKey] ?? []);
        $bFacts = $this->topTexts($evidence[$bKey] ?? []);
        $shared = $this->topTexts($evidence[$subs[count($subs)-1]] ?? []);

        $conf = max(
            $evidence[$aKey][0]['score'] ?? 0,
            $evidence[$bKey][0]['score'] ?? 0
        );

        return [
            'a_subject' => $aKey,
            'b_subject' => $bKey,
            'a_facts'   => $aFacts,
            'b_facts'   => $bFacts,
            'shared'    => $shared,
            'confidence'=> round($conf, 3),
        ];
    }

    // ── Procedure reasoning ─────────────────────────────────────────────
    private function reasonProcedure(string $question, array $evidence): array {
        $allTexts = $this->allTexts($evidence);

        // Try to detect ordered steps (numbered or sequential keywords)
        $steps    = $this->extractSteps($allTexts);
        $conf     = $this->avgConfidence($evidence);

        return [
            'steps'      => $steps,
            'raw_texts'  => array_slice($allTexts, 0, 5),
            'confidence' => $conf,
        ];
    }

    // ── Causal reasoning ────────────────────────────────────────────────
    private function reasonCausal(string $question, array $evidence): array {
        $allTexts = $this->allTexts($evidence);
        $causes   = $this->matchPattern($allTexts, '/\b(because|due to|caused by|reason is|result of)\b/i');
        $effects  = $this->matchPattern($allTexts, '/\b(therefore|thus|so|leads to|results in|causes)\b/i');
        $conf     = $this->avgConfidence($evidence);

        return [
            'causes'     => $causes ?: array_slice($allTexts, 0, 2),
            'effects'    => $effects ?: array_slice($allTexts, 0, 2),
            'confidence' => $conf,
        ];
    }

    // ── Recommendation reasoning ────────────────────────────────────────
    private function reasonRecommendation(string $question, array $evidence): array {
        $allTexts = $this->allTexts($evidence);
        $benefits = $this->matchPattern($allTexts, '/\b(include|offer|provide|support|allow|feature|benefit)\b/i');
        $conf     = $this->avgConfidence($evidence);

        return [
            'options'    => array_slice($allTexts, 0, 4),
            'benefits'   => $benefits,
            'confidence' => $conf,
        ];
    }

    // ── List reasoning ──────────────────────────────────────────────────
    private function reasonList(string $question, array $evidence): array {
        $allTexts = $this->allTexts($evidence);
        // Extract list-like items (sentences with commas or bullet-style content)
        $items    = $this->extractListItems($allTexts);
        $conf     = $this->avgConfidence($evidence);

        return [
            'items'      => $items,
            'raw_texts'  => array_slice($allTexts, 0, 5),
            'confidence' => $conf,
        ];
    }

    // ── Definition reasoning ────────────────────────────────────────────
    private function reasonDefinition(string $question, array $evidence): array {
        $allTexts = $this->allTexts($evidence);
        // Prefer sentences with "is a", "refers to", "means", "defined as"
        $defining = $this->matchPattern($allTexts, '/\b(is a|refers to|means|defined as|is an|consists of)\b/i');
        $conf     = $this->avgConfidence($evidence);

        return [
            'defining'   => $defining ?: array_slice($allTexts, 0, 2),
            'supporting' => array_slice($allTexts, 0, 3),
            'confidence' => $conf,
        ];
    }

    // ── Factual reasoning ───────────────────────────────────────────────
    private function reasonFactual(string $question, array $evidence): array {
        $allTexts = $this->allTexts($evidence);
        $conf     = $this->avgConfidence($evidence);
        return [
            'facts'      => array_slice($allTexts, 0, 3),
            'confidence' => $conf,
        ];
    }

    // =================================================================
    // STEP 5 — ANSWER SYNTHESIS
    // =================================================================

    private function synthesize(string $type, string $question, array $reasoning): string {
        $conf = $reasoning['confidence'] ?? 0.0;

        if ($conf === 0.0) {
            return "I don't have enough information in my knowledge base to answer that. "
                 . "Please train the model with relevant content first.";
        }

        return match ($type) {
            self::TYPE_COMPARISON     => $this->buildComparison($reasoning),
            self::TYPE_PROCEDURE      => $this->buildProcedure($reasoning),
            self::TYPE_CAUSAL         => $this->buildCausal($reasoning),
            self::TYPE_RECOMMENDATION => $this->buildRecommendation($question, $reasoning),
            self::TYPE_LIST           => $this->buildList($reasoning),
            self::TYPE_DEFINITION     => $this->buildDefinition($reasoning),
            default                   => $this->buildFactual($reasoning),
        };
    }

    private function buildComparison(array $r): string {
        $out  = '';
        if (!empty($r['a_facts'])) {
            $a = $this->subjectLabel($r['a_subject']);
            $out .= $a . ': ' . implode(' ', array_slice($r['a_facts'], 0, 2)) . ' ';
        }
        if (!empty($r['b_facts'])) {
            $b = $this->subjectLabel($r['b_subject']);
            $out .= $b . ': ' . implode(' ', array_slice($r['b_facts'], 0, 2)) . ' ';
        }
        if (!empty($r['shared'])) {
            $out .= implode(' ', array_slice($r['shared'], 0, 1));
        }
        return $this->polish($out);
    }

    private function buildProcedure(array $r): string {
        if (!empty($r['steps'])) {
            return implode(' ', $r['steps']);
        }
        return $this->polish(implode(' ', $r['raw_texts']));
    }

    private function buildCausal(array $r): string {
        $causes  = implode(' ', array_slice($r['causes'], 0, 2));
        $effects = implode(' ', array_slice($r['effects'], 0, 1));
        return $this->polish($causes . ' ' . $effects);
    }

    private function buildRecommendation(string $question, array $r): string {
        $options  = implode(' ', array_slice($r['options'], 0, 3));
        $benefits = implode(' ', array_slice($r['benefits'], 0, 2));
        return $this->polish($options . ' ' . $benefits);
    }

    private function buildList(array $r): string {
        if (!empty($r['items'])) {
            return implode('. ', array_slice($r['items'], 0, 5)) . '.';
        }
        return $this->polish(implode(' ', $r['raw_texts']));
    }

    private function buildDefinition(array $r): string {
        $defining   = implode(' ', array_slice($r['defining'],   0, 2));
        $supporting = implode(' ', array_slice($r['supporting'], 0, 1));
        return $this->polish($defining . ' ' . $supporting);
    }

    private function buildFactual(array $r): string {
        return $this->polish(implode(' ', $r['facts']));
    }

    // =================================================================
    // HELPERS
    // =================================================================

    private function topTexts(array $hits, int $n = 3): array {
        return array_slice(array_column($hits, 'text'), 0, $n);
    }

    private function allTexts(array $evidence): array {
        $all  = [];
        $seen = [];
        foreach ($evidence as $hits) {
            foreach ($hits as $hit) {
                $key = substr($hit['text'], 0, 50);
                if (!isset($seen[$key])) {
                    $seen[$key] = true;
                    $all[]      = $hit['text'];
                }
            }
        }
        return $all;
    }

    private function avgConfidence(array $evidence): float {
        $scores = [];
        foreach ($evidence as $hits) {
            if (!empty($hits)) $scores[] = $hits[0]['score'] ?? 0;
        }
        return $scores ? round(array_sum($scores) / count($scores), 3) : 0.0;
    }

    private function matchPattern(array $texts, string $pattern): array {
        return array_values(array_filter($texts, fn($t) => preg_match($pattern, $t)));
    }

    private function extractSteps(array $texts): array {
        // Look for numbered or signal-word steps
        $steps = [];
        foreach ($texts as $t) {
            if (preg_match('/^\d+[\.\)]\s+/i', $t)) {
                $steps[] = $t;
            } elseif (preg_match('/\b(first|second|third|then|next|finally|step \d)\b/i', $t)) {
                $steps[] = $t;
            }
        }
        // Fallback: just use top texts as steps
        return $steps ?: array_slice($texts, 0, 4);
    }

    private function extractListItems(array $texts): array {
        $items = [];
        foreach ($texts as $t) {
            // Split comma-separated items within a sentence
            if (substr_count($t, ',') >= 2) {
                $parts = preg_split('/,\s+/', $t);
                foreach ($parts as $p) {
                    $p = trim($p, " \t.,");
                    if (strlen($p) > 3) $items[] = $p;
                }
            } else {
                $items[] = $t;
            }
        }
        return array_slice(array_unique($items), 0, 8);
    }

    private function subjectLabel(string $q): string {
        // Clean sub-question into a usable label
        $label = preg_replace('/^(what is|tell me about)\s*/i', '', $q);
        $label = rtrim($label, '?');
        return ucfirst(trim($label));
    }

    private function polish(string $t): string {
        $t = trim($t);
        if (!$t) return $t;
        $t = preg_replace('/\s+/', ' ', $t);
        $t[0] = strtoupper($t[0]);
        $t = preg_replace('/\s+([.!?,;:])/', '$1', $t);
        if (!in_array(substr($t, -1), ['.', '!', '?'])) $t .= '.';
        $sents = preg_split('/(?<=[.!?])\s+/', $t);
        return implode(' ', array_slice($sents, 0, 4));
    }
}
