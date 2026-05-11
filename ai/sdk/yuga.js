/**
 * Yuga JavaScript SDK
 * 
 * Install (copy to your project):
 *   <script src="https://yoursite.com/yuga/sdk/yuga.js"></script>
 *
 * Or use directly in Node.js:
 *   const Yuga = require('./yuga.js');
 *
 * Usage:
 *   const yuga = new Yuga({ apiKey: 'yuga_live_xxx', apiUrl: 'https://yoursite.com/yuga/api/' });
 *   const reply = await yuga.chat('What is your pricing?');
 *   console.log(reply.reply);
 */

(function (root, factory) {
  if (typeof module !== 'undefined' && module.exports) {
    module.exports = factory(); // Node.js
  } else {
    root.Yuga = factory(); // Browser
  }
}(typeof self !== 'undefined' ? self : this, function () {

  class Yuga {

    constructor(options = {}) {
      this.apiUrl  = (options.apiUrl  || options.api_url || '').replace(/\/?$/, '/');
      this.apiKey  = options.apiKey   || options.api_key  || '';
      this.model   = options.model    || 'default';
      this.timeout = options.timeout  || 30000;
      this._history = []; // conversation memory
    }

    // ── Core request ─────────────────────────────────────────────────
    async _request(action, body = {}, method = 'POST') {
      const url     = this.apiUrl + '?action=' + action;
      const headers = { 'Content-Type': 'application/json' };
      if (this.apiKey) headers['X-API-Key'] = this.apiKey;

      body.model = body.model || this.model;

      const ctrl = new AbortController();
      const timer = setTimeout(() => ctrl.abort(), this.timeout);

      try {
        const res  = await fetch(url, { method, headers, body: JSON.stringify(body), signal: ctrl.signal });
        const data = await res.json();
        clearTimeout(timer);
        if (!data.ok && data.error) throw new Error(data.error);
        return data;
      } catch (e) {
        clearTimeout(timer);
        throw e;
      }
    }

    // ── Chat (ChatGPT-style with memory) ─────────────────────────────
    async chat(message, options = {}) {
      const body = {
        message,
        history: this._history,
        model:   options.model || this.model,
      };
      const r = await this._request('brain_chat', body);
      if (r.history) this._history = r.history;
      return r;
    }

    // ── Generate (raw LM text generation) ─────────────────────────────
    async generate(prompt, options = {}) {
      return this._request('yugagen_chat', {
        prompt,
        max_chars:   options.maxChars   || 200,
        temperature: options.temperature || 0.8,
        top_p:       options.topP        || 0.9,
        model:       options.model       || this.model,
      });
    }

    // ── Learn from text ───────────────────────────────────────────────
    async learnText(text, source = 'sdk') {
      return this._request('learn_text', { text, source });
    }

    // ── Learn from URL ────────────────────────────────────────────────
    async learnUrl(url) {
      return this._request('learn_url', { url });
    }

    // ── Crawl entire site ─────────────────────────────────────────────
    async learnSite(url, maxPages = 20) {
      return this._request('learn_site', { url, max_pages: maxPages });
    }

    // ── Model status ──────────────────────────────────────────────────
    async status(model) {
      return this._request('status', { model: model || this.model });
    }

    // ── List models ───────────────────────────────────────────────────
    async models() {
      return this._request('models', {});
    }

    // ── Submit feedback (thumbs up/down) ──────────────────────────────
    async feedback(question, answer, score, sessionId = '') {
      return this._request('feedback', { question, answer, score, session_id: sessionId });
    }

    // ── Reset conversation memory ─────────────────────────────────────
    resetMemory() {
      this._history = [];
    }

    // ── Get conversation history ──────────────────────────────────────
    getHistory() {
      return [...this._history];
    }

    // ── Streaming chat (Server-Sent Events) ───────────────────────────
    async *stream(message, options = {}) {
      const url     = this.apiUrl + '?action=brain_chat_stream';
      const headers = { 'Content-Type': 'application/json' };
      if (this.apiKey) headers['X-API-Key'] = this.apiKey;

      const res = await fetch(url, {
        method: 'POST',
        headers,
        body: JSON.stringify({ message, model: options.model || this.model, stream: true }),
      });

      if (!res.ok) throw new Error('Stream request failed: ' + res.status);
      if (!res.body) throw new Error('Streaming not supported');

      const reader  = res.body.getReader();
      const decoder = new TextDecoder();
      let   buffer  = '';

      while (true) {
        const { done, value } = await reader.read();
        if (done) break;
        buffer += decoder.decode(value, { stream: true });
        const lines = buffer.split('\n');
        buffer = lines.pop();
        for (const line of lines) {
          if (line.startsWith('data: ')) {
            const data = line.slice(6).trim();
            if (data === '[DONE]') return;
            try {
              yield JSON.parse(data);
            } catch {}
          }
        }
      }
    }
  }

  // ── Convenience factory ───────────────────────────────────────────
  Yuga.create = (options) => new Yuga(options);

  return Yuga;
}));
