"""
Yuga Python SDK
pip install requests  (only dependency)

Usage:
    from yuga import Yuga

    yuga = Yuga(api_key='yuga_live_xxx', api_url='https://yoursite.com/yuga/api/')
    reply = yuga.chat('What is your pricing?')
    print(reply['reply'])

    # Multi-turn conversation (memory is automatic)
    r1 = yuga.chat('What is the pro plan?')
    r2 = yuga.chat('How much does it cost?')   # remembers context

    # Text generation (real LM)
    text = yuga.generate('Our pricing starts at', max_chars=200, temperature=0.8)

    # Train on your platform
    yuga.learn_site('https://yourplatform.com', max_pages=30)
"""

import json
import time
from typing import Generator, Optional

try:
    import requests as _requests
    _has_requests = True
except ImportError:
    import urllib.request
    import urllib.error
    _has_requests = False


class YugaError(Exception):
    pass


class Yuga:

    def __init__(
        self,
        api_key: str = '',
        api_url: str = '',
        model:   str = 'default',
        timeout: int = 30,
    ):
        self.api_key = api_key
        self.api_url = api_url.rstrip('/') + '/'
        self.model   = model
        self.timeout = timeout
        self._history = []  # conversation memory

    # ── Core request ─────────────────────────────────────────────────
    def _req(self, action: str, body: dict = None, method: str = 'POST') -> dict:
        url     = self.api_url + '?action=' + action
        headers = {'Content-Type': 'application/json', 'Accept': 'application/json'}
        if self.api_key:
            headers['X-API-Key'] = self.api_key

        payload = body or {}
        payload.setdefault('model', self.model)
        data = json.dumps(payload).encode()

        if _has_requests:
            r = _requests.request(method, url, headers=headers, data=data, timeout=self.timeout)
            result = r.json()
        else:
            req = urllib.request.Request(url, data=data, headers=headers, method=method)
            try:
                with urllib.request.urlopen(req, timeout=self.timeout) as resp:
                    result = json.loads(resp.read())
            except urllib.error.HTTPError as e:
                result = json.loads(e.read())

        if not result.get('ok') and result.get('error'):
            raise YugaError(result['error'])
        return result

    # ── Chat ─────────────────────────────────────────────────────────
    def chat(self, message: str, model: str = None) -> dict:
        """Send a message. Conversation memory is automatic."""
        r = self._req('brain_chat', {
            'message': message,
            'history': self._history,
            'model':   model or self.model,
        })
        if 'history' in r:
            self._history = r['history']
        return r

    # ── Generate ─────────────────────────────────────────────────────
    def generate(
        self,
        prompt:      str,
        max_chars:   int   = 200,
        temperature: float = 0.8,
        top_p:       float = 0.9,
        model:       str   = None,
    ) -> dict:
        """Generate text using the trained YugaGen model."""
        return self._req('yugagen_chat', {
            'prompt':      prompt,
            'max_chars':   max_chars,
            'temperature': temperature,
            'top_p':       top_p,
            'model':       model or self.model,
        })

    # ── Learn ─────────────────────────────────────────────────────────
    def learn_text(self, text: str, source: str = 'sdk') -> dict:
        """Train on raw text."""
        return self._req('learn_text', {'text': text, 'source': source})

    def learn_url(self, url: str) -> dict:
        """Fetch and train on a single URL."""
        return self._req('learn_url', {'url': url})

    def learn_site(self, url: str, max_pages: int = 20) -> dict:
        """Crawl a full website and train on all pages."""
        return self._req('learn_site', {'url': url, 'max_pages': max_pages})

    # ── Status ────────────────────────────────────────────────────────
    def status(self, model: str = None) -> dict:
        """Get model training status."""
        return self._req('status', {'model': model or self.model})

    def models(self) -> list:
        """List all available models."""
        r = self._req('models', {})
        return r.get('models', [])

    def yugagen_status(self, model: str = None) -> dict:
        """Get YugaGen checkpoint status."""
        return self._req('yugagen_status', {'model': model or self.model})

    # ── Feedback ──────────────────────────────────────────────────────
    def feedback(self, question: str, answer: str, score: int, session_id: str = '') -> dict:
        """Submit thumbs up (1) or thumbs down (-1) on an answer."""
        return self._req('feedback', {
            'question':   question,
            'answer':     answer,
            'score':      score,
            'session_id': session_id,
        })

    # ── Memory ────────────────────────────────────────────────────────
    def reset_memory(self):
        """Clear conversation history."""
        self._history = []

    def get_history(self) -> list:
        """Get current conversation history."""
        return list(self._history)

    # ── Async batch ───────────────────────────────────────────────────
    def batch_learn(self, texts: list, delay: float = 0.2) -> list:
        """Train on multiple texts with a delay between each."""
        results = []
        for text in texts:
            results.append(self.learn_text(text))
            time.sleep(delay)
        return results

    def __repr__(self):
        return f"Yuga(api_url='{self.api_url}', model='{self.model}')"


# ── Async variant (requires aiohttp) ─────────────────────────────────
try:
    import aiohttp
    import asyncio

    class AsyncYuga(Yuga):
        """Async version of the Yuga SDK. Requires aiohttp."""

        async def _req_async(self, action: str, body: dict = None) -> dict:
            url     = self.api_url + '?action=' + action
            headers = {'Content-Type': 'application/json'}
            if self.api_key:
                headers['X-API-Key'] = self.api_key
            payload = body or {}
            payload.setdefault('model', self.model)

            async with aiohttp.ClientSession() as sess:
                async with sess.post(url, json=payload, headers=headers, timeout=aiohttp.ClientTimeout(total=self.timeout)) as resp:
                    result = await resp.json()
            if not result.get('ok') and result.get('error'):
                raise YugaError(result['error'])
            return result

        async def chat(self, message: str, model: str = None) -> dict:
            r = await self._req_async('brain_chat', {'message': message, 'history': self._history, 'model': model or self.model})
            if 'history' in r:
                self._history = r['history']
            return r

        async def generate(self, prompt: str, **kwargs) -> dict:
            return await self._req_async('yugagen_chat', {'prompt': prompt, **kwargs})

        async def learn_site(self, url: str, max_pages: int = 20) -> dict:
            return await self._req_async('learn_site', {'url': url, 'max_pages': max_pages})

except ImportError:
    pass  # aiohttp not available
