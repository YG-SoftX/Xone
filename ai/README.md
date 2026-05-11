# Yuga — v1.0

> A self-learning language model built from scratch in pure PHP.
> Trains and runs on shared cPanel hosting. No GPU. No cloud API. No dependencies.

---

## What is Yuga?

Yuga is a real transformer-based language model (GPT-style architecture) that:

- Trains itself by crawling your website or platform
- Runs entirely in PHP — no Python, no pip, no external services
- Deploys on any cPanel shared host in under 5 minutes
- Embeds on any website, app, or platform with one line of code

The name comes from Sanskrit — *yuga* means a cosmic age or era.
This is the first one. v1.0.

---

## Architecture

```
Yuga v1.0 — Transformer LM
  Token embedding + positional embedding
  2 × transformer block:
      LayerNorm → Multi-Head Causal Self-Attention (2 heads) → residual
      LayerNorm → Feed-Forward (GELU) → residual
  Final LayerNorm → LM head (weight-tied)
  Optimizer: Adam (β₁=0.9, β₂=0.999)
  ~18,000 parameters (scales with vocab)
```

Grounded generation: BM25 retrieval anchors every answer in real
platform content. The transformer extends it into fluent prose.

---

## Quick start (cPanel)

1. Upload the `yuga/` folder to your `public_html`
2. Visit `https://yoursite.com/yuga/install.php`
3. Open `https://yoursite.com/yuga/admin/`
4. Paste your site URL under **Self-Learning → Crawl Site** and train

---

## Embed on any platform

```html
<script
  src="https://yoursite.com/yuga/widget/yuga-widget.js"
  data-api="https://yoursite.com/yuga/api/"
  data-model="default"
  data-theme="dark"
  data-auto-learn="true"
></script>
```

---

## API

```
POST /api/?action=brain_chat     — ChatGPT-style answer (transformer + BM25)
POST /api/?action=chat           — Raw LM completion
POST /api/?action=learn_text     — Train on raw text
POST /api/?action=learn_url      — Train on a URL
POST /api/?action=learn_site     — Crawl and train on a full site
GET  /api/?action=status         — Model status
```

---

## Files

```
yuga/
  core/
    Transformer.php   ← Real GPT-style transformer (forward + backprop + Adam)
    Tokenizer.php     ← Word-level tokeniser
    Retriever.php     ← BM25 full-text retrieval
    Brain.php         ← Ties everything together
    YugaLM.php        ← Char-level LM (legacy, still available)
    ModelStore.php    ← SQLite / JSON persistence
    SelfLearner.php   ← Auto-crawler
  api/index.php       ← REST API
  admin/index.php     ← Admin dashboard
  widget/yuga-widget.js ← Embeddable chat widget
  install.php         ← One-click installer
  config.php          ← Configuration
```

---

Built with Andrej Karpathy's makemore/nanoGPT as inspiration.
Pure PHP. Your model. Your server. Your data.

**Yuga v1.0**
