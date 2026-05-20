<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;

/**
 * BrowserSettings — Master Admin controls for the YGXONE Agentic Browser.
 *
 * Settings are persisted to a shared JSON file read by the home module.
 * Path: ../home/storage/app/browser-settings.json
 *
 * Controls:
 *   - Branding (name, logo, favicon, theme color)
 *   - Search engines (default + enabled list)
 *   - Quick-link platform presets
 *   - Feature toggles (AI Agent, BYOK, PWA install)
 *   - Quotas (browse + agent per hour)
 *   - PWA configuration (app name, short name, description)
 */
class BrowserSettings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';
    protected static ?string $navigationLabel = 'Browser Settings';
    protected static ?string $title = 'YGXONE Browser Controls';
    protected static ?string $navigationGroup = 'Ecosystem Management';
    protected static ?int $navigationSort = 20;

    protected static string $view = 'filament.pages.browser-settings';

    public array $data = [];

    public function mount(): void
    {
        $this->form->fill($this->loadSettings());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Branding')
                    ->description('Browser name, logo, and appearance')
                    ->icon('heroicon-o-paint-brush')
                    ->schema([
                        Forms\Components\TextInput::make('browser_brand_name')
                            ->label('Browser Name')
                            ->placeholder('YGXONE Agentic Browser')
                            ->helperText('Shown in the PWA app name, title bar, and install prompt'),
                        Forms\Components\TextInput::make('browser_logo_url')
                            ->label('Custom Logo URL')
                            ->placeholder('Leave empty for default YG logo')
                            ->helperText('Absolute URL to a custom logo image'),
                        Forms\Components\TextInput::make('browser_favicon_url')
                            ->label('Favicon URL')
                            ->placeholder('Leave empty for default')
                            ->helperText('Custom browser tab icon'),
                        Forms\Components\ColorPicker::make('browser_theme_color')
                            ->label('Theme Color')
                            ->default('#2563eb')
                            ->helperText('PWA status bar + browser accent color'),
                        Forms\Components\ColorPicker::make('browser_bg_color')
                            ->label('Background Color')
                            ->default('#ffffff')
                            ->helperText('PWA splash screen background'),
                    ])->columns(2),

                Forms\Components\Section::make('Search Engines')
                    ->description('Default search engine and available options')
                    ->icon('heroicon-o-magnifying-glass')
                    ->schema([
                        Forms\Components\Select::make('default_search_engine')
                            ->label('Default Search Engine')
                            ->options([
                                'google'     => 'Google',
                                'bing'       => 'Bing',
                                'duckduckgo' => 'DuckDuckGo',
                                'yahoo'      => 'Yahoo',
                                'brave'      => 'Brave',
                                'ecosystem'  => 'YG Ecosystem Search',
                            ])
                            ->default('ecosystem')
                            ->helperText('Where search queries in the omnibox are sent'),

                        Forms\Components\CheckboxList::make('enabled_search_engines')
                            ->label('Available Search Engines')
                            ->options([
                                'google'     => 'Google',
                                'bing'       => 'Bing',
                                'duckduckgo' => 'DuckDuckGo',
                                'yahoo'      => 'Yahoo',
                                'brave'      => 'Brave',
                                'ecosystem'  => 'YG Ecosystem',
                            ])
                            ->default(['google', 'ecosystem'])
                            ->columns(2)
                            ->helperText('Search engines that appear in the quick-switch dropdown'),
                    ]),

                Forms\Components\Section::make('Quick-Link Presets')
                    ->description('Platform shortcuts shown on the browser home screen')
                    ->icon('heroicon-o-link')
                    ->schema([
                        Forms\Components\Textarea::make('quick_links_json')
                            ->label('Quick Links (JSON)')
                            ->rows(14)
                            ->default(json_encode([
                                ['name' => 'Google',    'url' => 'https://google.com',    'icon' => 'fab fa-google',      'color' => '#4285f4', 'enabled' => true],
                                ['name' => 'YouTube',   'url' => 'https://youtube.com',   'icon' => 'fab fa-youtube',     'color' => '#ff0000', 'enabled' => true],
                                ['name' => 'Facebook',  'url' => 'https://facebook.com',  'icon' => 'fab fa-facebook',    'color' => '#1877f2', 'enabled' => true],
                                ['name' => 'Twitter/X', 'url' => 'https://x.com',         'icon' => 'fab fa-x-twitter',   'color' => '#000000', 'enabled' => true],
                                ['name' => 'Netflix',   'url' => 'https://netflix.com',   'icon' => 'fas fa-play',        'color' => '#e50914', 'enabled' => true],
                                ['name' => 'GitHub',    'url' => 'https://github.com',    'icon' => 'fab fa-github',      'color' => '#333333', 'enabled' => true],
                                ['name' => 'Wikipedia', 'url' => 'https://wikipedia.org', 'icon' => 'fab fa-wikipedia-w', 'color' => '#000000', 'enabled' => false],
                                ['name' => 'Reddit',    'url' => 'https://reddit.com',    'icon' => 'fab fa-reddit',      'color' => '#ff4500', 'enabled' => false],
                                ['name' => 'Amazon',    'url' => 'https://amazon.com',    'icon' => 'fab fa-amazon',      'color' => '#ff9900', 'enabled' => false],
                                ['name' => 'LinkedIn',  'url' => 'https://linkedin.com',  'icon' => 'fab fa-linkedin',    'color' => '#0a66c2', 'enabled' => false],
                            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))
                            ->helperText('JSON array of {name, url, icon, color, enabled}. These appear as quick-launch tiles on the browser home page.'),
                    ]),

                Forms\Components\Section::make('Feature Toggles')
                    ->description('Enable/disable browser features for all users')
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->schema([
                        Forms\Components\Toggle::make('agent_enabled')
                            ->label('AI Agent')
                            ->default(true)
                            ->helperText('Allow users to run AI browsing agents in the side panel'),

                        Forms\Components\Toggle::make('byok_enabled')
                            ->label('BYOK (Bring Your Own Key)')
                            ->default(true)
                            ->helperText('Allow users to add their own API keys in agent settings'),

                        Forms\Components\Toggle::make('pwa_install_enabled')
                            ->label('PWA Install Prompt')
                            ->default(true)
                            ->helperText('Show "Install App" floating button in the browser'),

                        Forms\Components\TextInput::make('browse_quota_per_hour')
                            ->label('Browse Quota (req/hour)')
                            ->numeric()
                            ->default(120)
                            ->helperText('Max proxied page loads per user per hour. 0 = unlimited.'),

                        Forms\Components\TextInput::make('agent_quota_per_hour')
                            ->label('Agent Quota (runs/hour)')
                            ->numeric()
                            ->default(30)
                            ->helperText('Max AI agent runs per user per hour. 0 = unlimited.'),
                    ])->columns(2),

                Forms\Components\Section::make('PWA Configuration')
                    ->description('Progressive Web App install settings')
                    ->icon('heroicon-o-device-phone-mobile')
                    ->schema([
                        Forms\Components\TextInput::make('pwa_name')
                            ->label('App Name')
                            ->default('YGXONE Browser')
                            ->helperText('Full name shown in install prompt'),
                        Forms\Components\TextInput::make('pwa_short_name')
                            ->label('Short Name')
                            ->default('YGXONE')
                            ->helperText('Short label under the app icon'),
                        Forms\Components\TextInput::make('pwa_description')
                            ->label('App Description')
                            ->default('AI-powered agentic browser — browse anything, automate everything')
                            ->helperText('Description shown in app install dialog'),
                    ])->columns(2),

                Forms\Components\Section::make('Splash Screen')
                    ->description('Customize the PWA launch splash screen (iOS + Android)')
                    ->icon('heroicon-o-sparkles')
                    ->schema([
                        Forms\Components\Toggle::make('splash_enabled')
                            ->label('Enable Custom Splash Screen')
                            ->default(true)
                            ->helperText('Show a branded splash screen while the PWA loads'),

                        Forms\Components\TextInput::make('splash_title')
                            ->label('Splash Title')
                            ->default('YGXONE')
                            ->helperText('Large heading text on the splash screen'),

                        Forms\Components\TextInput::make('splash_subtitle')
                            ->label('Splash Subtitle')
                            ->default('AI-Powered Agentic Browser')
                            ->helperText('Smaller text below the title'),

                        Forms\Components\TextInput::make('splash_logo_url')
                            ->label('Splash Logo URL')
                            ->placeholder('Leave empty for default YG logo')
                            ->helperText('Absolute URL to a logo image. SVG recommended. Falls back to CSS-rendered logo.'),

                        Forms\Components\ColorPicker::make('splash_bg_color')
                            ->label('Splash Background')
                            ->default('#0f172a')
                            ->helperText('Background color of the splash screen overlay'),

                        Forms\Components\ColorPicker::make('splash_spinner_color')
                            ->label('Spinner Color')
                            ->default('#2563eb')
                            ->helperText('Color of the loading spinner'),
                    ])->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        // Convert arrays to appropriate types
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $value;
            } elseif (is_bool($value)) {
                $data[$key] = (bool) $value;
            }
        }

        // Write to shared JSON config file (home module reads this)
        $configPath = base_path('../home/storage/app/browser-settings.json');
        $dir = dirname($configPath);

        if (!is_dir($dir)) {
            if (!mkdir($dir, 0755, true) && !is_dir($dir)) {
                Notification::make()
                    ->title('Failed to save settings')
                    ->body('Could not create storage directory. Check file permissions.')
                    ->danger()
                    ->send();
                return;
            }
        }

        $written = file_put_contents(
            $configPath,
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        if ($written === false) {
            Notification::make()
                ->title('Failed to save settings')
                ->body('Could not write configuration file. Check file permissions on: ' . $configPath)
                ->danger()
                ->send();
            return;
        }

        Notification::make()
            ->title('Browser settings saved')
            ->body('All changes take effect immediately for all users.')
            ->success()
            ->send();
    }

    protected function loadSettings(): array
    {
        $configPath = base_path('../home/storage/app/browser-settings.json');

        if (file_exists($configPath)) {
            $saved = json_decode(file_get_contents($configPath), true);
            if (is_array($saved)) {
                // Type-cast booleans (JSON stores them as real booleans, but ensure safety)
                foreach (['agent_enabled', 'byok_enabled', 'pwa_install_enabled'] as $boolKey) {
                    if (isset($saved[$boolKey])) {
                        $saved[$boolKey] = (bool) $saved[$boolKey];
                    }
                }
                return array_merge($this->getDefaults(), $saved);
            }
        }

        return $this->getDefaults();
    }

    protected function getDefaults(): array
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
        ];
    }
}
