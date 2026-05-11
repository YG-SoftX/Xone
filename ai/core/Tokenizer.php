<?php
/**
 * Tokenizer — converts text ↔ integer token IDs
 *
 * Strategy: word-level with special tokens
 *   <PAD> = 0   padding / unknown
 *   <BOS> = 1   beginning of sequence
 *   <EOS> = 2   end of sequence
 *   <UNK> = 3   unknown word
 *   real words start at index 4
 *
 * Nepali / multilingual support:
 *   - Devanagari script (Unicode range U+0900–U+097F) handled natively
 *   - Romanized Nepali (transliteration) works as regular words
 *   - Nepali-specific stopwords filtered during vocabulary building
 *   - mb_* functions used throughout for correct Unicode handling
 *
 * Vocab size increased to 8000 for better coverage of Nepali + English.
 */
class Tokenizer {

    public array $word2id  = [];
    public array $id2word  = [];
    public int   $vocab_size = 0;
    public int   $max_vocab  = 16000; // bilingual NP+EN needs larger vocab
    public bool  $built      = false;
    public bool  $nepali     = false; // set true when Devanagari detected in corpus

    const PAD = 0;
    const BOS = 1;
    const EOS = 2;
    const UNK = 3;

    // -------------------------------------------------------------------
    // Build vocabulary from a corpus
    // -------------------------------------------------------------------
    public function build(string $corpus): void {
        $words = $this->tokenizeRaw($corpus);
        $freq  = array_count_values($words);
        arsort($freq);

        // Reserve special tokens
        $this->word2id = ['<PAD>' => 0, '<BOS>' => 1, '<EOS>' => 2, '<UNK>' => 3];
        $this->id2word = [0 => '<PAD>', 1 => '<BOS>', 2 => '<EOS>', 3 => '<UNK>'];

        $idx = 4;
        foreach ($freq as $word => $count) {
            if ($idx >= $this->max_vocab) break;
            if ($count < 2) break; // skip hapax legomena
            $this->word2id[$word] = $idx;
            $this->id2word[$idx]  = $word;
            $idx++;
        }

        $this->vocab_size = $idx;
        $this->built = true;
    }

    // Extend vocabulary with new words (incremental learning)
    public function extend(string $corpus): bool {
        $words   = $this->tokenizeRaw($corpus);
        $freq    = array_count_values($words);
        $changed = false;

        foreach ($freq as $word => $count) {
            if ($this->vocab_size >= $this->max_vocab) break;
            if (!isset($this->word2id[$word]) && $count >= 2) {
                $idx = $this->vocab_size;
                $this->word2id[$word] = $idx;
                $this->id2word[$idx]  = $word;
                $this->vocab_size++;
                $changed = true;
            }
        }
        return $changed;
    }

    // -------------------------------------------------------------------
    // Encode text → array of int token IDs
    // -------------------------------------------------------------------
    public function encode(string $text, bool $add_bos = false, bool $add_eos = false): array {
        $words = $this->tokenizeRaw($text);
        $ids   = $add_bos ? [self::BOS] : [];
        foreach ($words as $w) {
            $ids[] = $this->word2id[$w] ?? self::UNK;
        }
        if ($add_eos) $ids[] = self::EOS;
        return $ids;
    }

    // -------------------------------------------------------------------
    // Decode array of int token IDs → text
    // -------------------------------------------------------------------
    public function decode(array $ids): string {
        $words = [];
        foreach ($ids as $id) {
            $w = $this->id2word[$id] ?? '';
            if ($w && $w !== '<PAD>' && $w !== '<BOS>' && $w !== '<EOS>' && $w !== '<UNK>') {
                $words[] = $w;
            }
        }
        return $this->detokenize($words);
    }

    // -------------------------------------------------------------------
    // Raw tokenization — Unicode-safe, handles Devanagari (Nepali)
    // -------------------------------------------------------------------
    public function tokenizeRaw(string $text): array {
        // Detect Devanagari presence
        if (preg_match('/[\x{0900}-\x{097F}]/u', $text)) {
            $this->nepali = true;
        }

        // Lowercase (mb_strtolower handles Unicode correctly incl. Devanagari)
        $text = mb_strtolower($text, 'UTF-8');

        // Normalize Devanagari: remove zero-width joiner/non-joiner, normalize nukta
        $text = str_replace(["\u{200C}", "\u{200D}"], ' ', $text);

        // Normalize Devanagari digits → ASCII (optional, keeps numerals uniform)
        $dv_digits = ['०','१','२','३','४','५','६','७','८','९'];
        $text = str_replace($dv_digits, range(0, 9), $text);

        // FIXED: separate script boundaries — Latin touching Devanagari
        // Bug was: second group was not a character class (missing [])
        $text = preg_replace('/([^\x{0900}-\x{097F}\s])([\x{0900}-\x{097F}])/u', '$1 $2', $text);
        $text = preg_replace('/([\x{0900}-\x{097F}])([^\x{0900}-\x{097F}\s])/u', '$1 $2', $text);

        // Nepali sentence stops (। ॥) and ASCII punctuation → own tokens
        $text = preg_replace('/([.!?,;:।॥])/u', ' $1 ', $text);

        // Collapse whitespace
        $text = preg_replace('/\s+/', ' ', trim($text));
        if (!$text) return [];

        $tokens = explode(' ', $text);

        // Filter stopwords (both Nepali and English) when bilingual content detected
        $stop = $this->nepali
            ? array_merge(self::NEPALI_STOP, self::ENGLISH_STOP)
            : self::ENGLISH_STOP;
        $tokens = array_filter($tokens, fn($t) => strlen($t) > 0 && !in_array($t, $stop));

        return array_values($tokens);
    }

    // Nepali (Devanagari) stopwords
    private const NEPALI_STOP = [
        // verb forms
        'छ','छन्','छु','थियो','थिए','थिइन्','भयो','भए','भइन्','गर्छ','गर्छन्','गर्छु',
        'हो','हुन्','हुँ','हुनेछ','हुन्छ','हुँदैन','भयो','गर्नुभयो','गर्नुहोस्',
        // postpositions / particles
        'को','मा','लाई','बाट','सँग','देखि','सम्म','भन्दा','बारे','विरुद्ध',
        'द्वारा','साथ','मध्ये','भित्र','बाहिर','माथि','तल','अगाडि','पछाडि',
        // conjunctions
        'र','तर','किनभने','वा','अथवा','यद्यपि','त्यसैले','अनि','नभए',
        // pronouns / determiners
        'यो','यी','त्यो','त्यी','ऊ','उनी','हामी','तपाईं','हजुर','आफू',
        'के','कस्तो','कसरी','कहाँ','कहिले','कति','कुन','कुनै','कसैले',
        // common adjectives/adverbs
        'एक','दुई','तीन','पनि','नै','नि','धेरै','थोरै','अझ','झन्','मात्र',
        // possessives
        'मेरो','तपाईंको','उसको','उनको','हाम्रो','तिमीहरूको','यहाँको',
        // negation
        'छैन','थिएन','नहीं','होइन','हुँदैन',
    ];

    // English stopwords (commonly filtered in NLP)
    private const ENGLISH_STOP = [
        'a','an','the','and','or','but','in','on','at','to','for','of','with',
        'by','from','is','are','was','were','be','been','being','have','has',
        'had','do','does','did','will','would','could','should','may','might',
        'this','that','these','those','i','you','he','she','it','we','they',
        'my','your','his','her','its','our','their','me','him','us','them',
        'what','which','who','how','when','where','why','not','no','so','as',
    ];

    // Check if text contains Devanagari
    public static function isNepali(string $text): bool {
        return (bool)preg_match('/[\x{0900}-\x{097F}]/u', $text);
    }

    // -------------------------------------------------------------------
    // Detokenize: re-attach punctuation sensibly
    // -------------------------------------------------------------------
    private function detokenize(array $words): string {
        $punct = ['.', ',', '!', '?', ';', ':', '।', '॥'];
        $out   = '';
        foreach ($words as $w) {
            if (in_array($w, $punct)) {
                $out = rtrim($out) . $w . ' ';
            } else {
                $out .= $w . ' ';
            }
        }
        $out = trim($out);
        // Capitalise first ASCII letter only (Devanagari has no case)
        if ($out && ord($out[0]) < 128) $out[0] = strtoupper($out[0]);
        return $out;
    }

    // -------------------------------------------------------------------
    // Serialize / load
    // -------------------------------------------------------------------
    public function save(): array {
        return [
            'word2id'    => $this->word2id,
            'id2word'    => $this->id2word,
            'vocab_size' => $this->vocab_size,
            'max_vocab'  => $this->max_vocab,
            'built'      => $this->built,
        ];
    }

    public function load(array $d): void {
        foreach ($d as $k => $v) $this->$k = $v;
    }

    public static function fromArray(array $d): self {
        $t = new self();
        $t->load($d);
        return $t;
    }
}
