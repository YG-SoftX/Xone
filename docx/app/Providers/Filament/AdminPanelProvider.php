<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors([
                'primary' => Color::hex('#9B1B30'),
                'danger'  => Color::Red,
                'info'    => Color::Sky,
                'success' => Color::Emerald,
                'warning' => Color::Amber,
            ])
            ->brandName('YG DocX — Sovereign')
            ->font('Outfit')
            ->renderHook('panels::body.start', fn() => view('components.ecosystem-nav'))
            ->renderHook('panels::head.done', fn() => new \Illuminate\Support\HtmlString('
                <style>
                    .fi-sidebar { background: rgba(8, 8, 8, 0.95) !important; backdrop-filter: blur(20px) !important; border-right: 1px solid rgba(155, 27, 48, 0.1) !important; }
                    .fi-topbar { background: rgba(5, 5, 5, 0.8) !important; backdrop-filter: blur(20px) !important; border-bottom: 1px solid rgba(255, 255, 255, 0.05) !important; border-radius: 0 !important; }
                    .fi-main { background: radial-gradient(circle at 50% 50%, #0a0a0a 0%, #050505 100%) !important; }
                    .fi-section { background: rgba(255, 255, 255, 0.02) !important; border: 1px solid rgba(255, 255, 255, 0.05) !important; border-radius: 1.5rem !important; backdrop-filter: blur(10px); }
                    .fi-btn { border-radius: 0.75rem !important; font-weight: 700 !important; text-transform: uppercase !important; letter-spacing: 0.05em !important; }
                    .prose { color: #d1d5db !important; }
                </style>
            '))
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([Pages\Dashboard::class])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                \App\Filament\Widgets\DocxStats::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([Authenticate::class])
            ->navigationGroups([
                'Documents',
                'Organization',
                'Users',
                'System',
            ]);
    }
}
