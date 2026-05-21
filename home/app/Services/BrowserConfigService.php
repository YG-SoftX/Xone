<?php

namespace App\Services;

/**
 * BrowserConfigService — Reads browser configuration from the shared JSON file
 * that the master admin Filament panel writes to.
 *
 * Config path: storage/app/browser-settings.json
 *
 * This service is read-only. The master panel at master.ygxone.com/admin
 * is the single writer.
 */
class BrowserConfigService
{
    private array $config = [];
    private bool $loaded = false;

    /**
     * Load config from the shared JSON file.
     */
    public function load(): self
    {
        if ($this->loaded) return $this;

        $defaults = $this->defaults();
        $path = storage_path('app/browser-settings.json');

        if (file_exists($path)) {
            $data = json_decode(file_get_contents($path), true);
            if (is_array($data)) {
                $this->config = array_merge($defaults, $data);
                $this->loaded = true;
                return $this;
            }
        }

        $this->config = $defaults;
        $this->loaded = true;
        return $this;
    }

    /**
     * Get a config value by key.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $this->load();
        return $this->config[$key] ?? $default;
    }

    /**
     * Get a boolean config value.
     */
    public function bool(string $key, bool $default = false): bool
    {
        return (bool) $this->get($key, $default);
    }

    /**
     * Get all config.
     */
    public function all(): array
    {
        $this->load();
        return $this->config;
    }

    /**
     * Get quick links as an array (parsed from JSON).
     */
    public function quickLinks(): array
    {
        $json = $this->get('quick_links_json', '');
        if (empty($json)) return $this->defaultQuickLinks();
        $decoded = json_decode($json, true);
        if (!is_array($decoded)) return $this->defaultQuickLinks();
        return array_filter($decoded, fn($link) => $link['enabled'] ?? true);
    }

    /**
     * Get default quick links.
     */
    public function defaultQuickLinks(): array
    {
        return [
            ['name' => 'Google',    'url' => 'https://google.com',    'icon' => 'fab fa-google',      'color' => '#4285f4'],
            ['name' => 'YouTube',   'url' => 'https://youtube.com',   'icon' => 'fab fa-youtube',     'color' => '#ff0000'],
            ['name' => 'Facebook',  'url' => 'https://facebook.com',  'icon' => 'fab fa-facebook',    'color' => '#1877f2'],
            ['name' => 'Twitter/X', 'url' => 'https://x.com',         'icon' => 'fab fa-x-twitter',   'color' => '#000000'],
            ['name' => 'Netflix',   'url' => 'https://netflix.com',   'icon' => 'fas fa-play',        'color' => '#e50914'],
            ['name' => 'GitHub',    'url' => 'https://github.com',    'icon' => 'fab fa-github',      'color' => '#333333'],
        ];
    }

    /**
     * Get default search engine config.
     */
    public function defaultSearchEngine(): array
    {
        $engine = $this->get('default_search_engine', 'ecosystem');
        $urls = [
            'google'     => 'https://www.google.com/search?q=',
            'bing'       => 'https://www.bing.com/search?q=',
            'duckduckgo' => 'https://duckduckgo.com/?q=',
            'yahoo'      => 'https://search.yahoo.com/search?p=',
            'brave'      => 'https://search.brave.com/search?q=',
            'ecosystem'  => '/search?q=',
        ];

        return [
            'engine' => $engine,
            'url'    => $urls[$engine] ?? $urls['ecosystem'],
        ];
    }

    /**
     * PWA manifest data (for dynamic manifest generation).
     */
    public function pwaManifest(): array
    {
        return [
            'name'             => $this->get('pwa_name', 'YGXONE Browser'),
            'short_name'       => $this->get('pwa_short_name', 'YGXONE'),
            'description'      => $this->get('pwa_description', 'AI-powered agentic browser'),
            'theme_color'      => $this->get('browser_theme_color', '#2563eb'),
            'background_color' => $this->get('browser_bg_color', '#ffffff'),
            'start_url'        => '/',
            'scope'            => '/',
            'display'          => 'standalone',
            'orientation'      => 'any',
        ];
    }

    /**
     * Splash screen config (fully manageable from master control).
     */
    public function splashConfig(): array
    {
        return [
            'enabled'       => $this->bool('splash_enabled', true),
            'title'         => $this->get('splash_title', 'YGXONE'),
            'subtitle'      => $this->get('splash_subtitle', 'AI-Powered Agentic Browser'),
            'logo_url'      => $this->get('splash_logo_url', ''),
            'bg_color'      => $this->get('splash_bg_color', '#0f172a'),
            'spinner_color' => $this->get('splash_spinner_color', '#2563eb'),
        ];
    }

    /**
     * Full PWA config merged (manifest + splash).
     */
    public function fullPwaConfig(): array
    {
        return array_merge($this->pwaManifest(), [
            'splash' => $this->splashConfig(),
        ]);
    }

    /**
     * Brave-style Shields configuration from master admin.
     */
    public function shieldsConfig(): array
    {
        return [
            'enabled'             => $this->bool('shields_enabled', true),
            'block_ads'           => $this->bool('block_ads', true),
            'block_trackers'      => $this->bool('block_trackers', true),
            'https_upgrade'       => $this->bool('https_upgrade', true),
            'block_fingerprinting'=> $this->bool('block_fingerprinting', true),
            'block_scripts'       => $this->bool('block_scripts', false),
            'speed_reader'        => $this->bool('speed_reader', false),
        ];
    }

    private function defaults(): array
    {
        return [
            'browser_brand_name'     => 'YGXONE Agentic Browser',
            'browser_logo_url'       => '',
            'browser_favicon_url'    => '',
            'browser_theme_color'    => '#2563eb',
            'browser_bg_color'       => '#ffffff',
            'default_search_engine'  => 'ecosystem',
            'enabled_search_engines' => ['google', 'ecosystem'],
            'quick_links_json'       => '',
            'agent_enabled'          => true,
            'byok_enabled'           => true,
            'pwa_install_enabled'    => true,
            'browse_quota_per_hour'  => 120,
            'agent_quota_per_hour'   => 30,
            'pwa_name'               => 'YGXONE Browser',
            'pwa_short_name'         => 'YGXONE',
            'pwa_description'        => 'AI-powered agentic browser — browse anything, automate everything',
            'splash_enabled'         => true,
            'splash_title'           => 'YGXONE',
            'splash_subtitle'        => 'AI-Powered Agentic Browser',
            'splash_logo_url'        => '',
            'splash_bg_color'        => '#0f172a',
            'splash_spinner_color'   => '#2563eb',
            'shields_enabled'        => true,
            'block_ads'              => true,
            'block_trackers'         => true,
            'https_upgrade'          => true,
            'block_fingerprinting'   => true,
            'block_scripts'          => false,
            'speed_reader'           => false,
        ];
    }
}
