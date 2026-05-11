# Yuga YugaGen — Training Guide

## What this is

YugaGen is a real character-level GPT transformer. Same architecture as GPT-2.
It generates text by learning character patterns from your corpus.

## What you should expect

| Training steps | What it looks like | Time on cPanel |
|---|---|---|
| 0 | Random characters | — |
| 1,000 | Letters start grouping | ~2 min |
| 5,000 | Word-like patterns emerge | ~10 min |
| 20,000 | Domain words appear | ~40 min |
| 50,000 | Phrases and sentences form | ~1.5 hrs |
| 200,000 | Coherent domain-specific prose | ~6 hrs |
| 500,000+ | Good quality generation | ~16 hrs |

Training accumulates across cron runs. Run it overnight and come back to a trained model.

## Step 1: Build your corpus

```bash
# Crawl your platform (run from SSH or cPanel terminal)
php training/build_corpus.php --url=https://yourplatform.com --pages=50

# Check what you have
php training/build_corpus.php --stats

# Add extra content manually (FAQs, docs, product descriptions)
php training/build_corpus.php --append --text="Your pricing is $29/month..."
php training/build_corpus.php --append --file=/path/to/your/docs.txt
```

**Aim for at least 100KB of text.** The more the better.
The corpus file is saved to `training/corpus.txt`.

## Step 2: Start training

```bash
# First run — creates the model (nano = 27K params, fast)
php training/train.php --model=mygpt --size=nano --steps=5000

# Resume on next run (just omit --size, it auto-resumes)
php training/train.php --model=mygpt --steps=5000
```

You'll see the loss dropping and sample text improving.

## Step 3: Set up a cron job (cPanel)

In cPanel → Cron Jobs, add this (runs every 30 minutes):

```
*/30 * * * * php /home/USERNAME/public_html/yuga/training/train.php --model=mygpt --steps=3000 >> /tmp/yuga_train.log 2>&1
```

Replace USERNAME with your cPanel username. This runs 3000 steps every 30 minutes.
Overnight (8 hours) = 48,000 steps. That's where you start seeing real generation.

## Step 4: Test generation

```bash
php training/train.php --model=mygpt --generate="Our pricing"
php training/train.php --model=mygpt --generate="Contact support"
php training/train.php --model=mygpt --generate="To reset your password"
```

## Model sizes

| Size | Params | Speed | Good for |
|---|---|---|---|
| nano | ~27K | 114ms/step | Fast experimentation |
| small | ~1.5M | ~2s/step | Longer cron runs (use --steps=500) |
| medium | ~10M | ~15s/step | VPS/dedicated server only |

Start with `nano`. Once you're happy with the corpus and training loop, you
can retrain from scratch with `small` for better quality text.

## Why it takes time

Karpathy trained nanoGPT on an A100 GPU. At 100,000 steps/second that's
500,000 steps in 5 seconds. On cPanel CPU at ~9 steps/second, the same
500,000 steps takes 15 hours — spread across cron jobs over a few nights.

The architecture is identical. The math is identical. Only the hardware differs.

## What the checkpoint contains

`data/ckpt_MODELNAME.json.gz` — gzip-compressed JSON of all weights,
Adam moments, step count, and vocab. Resumes exactly where it left off.

## How to use the trained model in your app

```php
require_once 'core/YugaGen.php';

$gz  = file_get_contents('data/ckpt_mygpt.json.gz');
$gpt = YugaGen::fromArray(json_decode(gzdecode($gz), true));

// Generate text
echo $gpt->generate('Our pricing', 200, 0.8, 0.9);

// Lower temperature = more predictable
echo $gpt->generate('Contact us', 200, 0.4, 0.95);
```

## API endpoint

Once trained, the model is accessible via the API:

```json
POST /api/?action=yugagen_chat
{
  "model": "mygpt",
  "prompt": "Our pricing",
  "max_chars": 200,
  "temperature": 0.8
}
```
