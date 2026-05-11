<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
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
            ->colors([
                'primary' => Color::Red,
            ])
            ->brandName(fn () => \App\Models\MailSetting::where('user_id', \Illuminate\Support\Facades\Auth::id())->first()?->brand_name ?? 'YGXONE Mail')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
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
            ->authMiddleware([
                Authenticate::class,
            ])
            ->renderHook(
                'panels::head.done',
                function (): string {
                    $settings = \App\Models\MailSetting::where('user_id', \Illuminate\Support\Facades\Auth::id())->first();
                    $primaryColor = $settings?->primary_color ?? '#9B1B30';
                    $sidebarType = $settings?->sidebar_type ?? 'glass';
                    
                    $sidebarCss = match($sidebarType) {
                        'glass' => 'background: linear-gradient(180deg, rgba(255,255,255,0.8) 0%, rgba(243,244,246,0.9) 100%) !important; backdrop-filter: blur(10px) !important;',
                        'gradient' => 'background: linear-gradient(180deg, ' . $primaryColor . ' 0%, #000 100%) !important; color: white !important;',
                        'solid' => 'background: white !important;',
                        default => 'background: white !important;',
                    };

                    $rgb = $this->hexToRgb($primaryColor);

                    return '
                        <style>
                            :root {
                                --p-600: ' . $rgb . ';
                            }
                            .fi-sidebar {
                                ' . $sidebarCss . '
                                border-right: 1px solid rgba(0,0,0,0.05) !important;
                            }
                            .fi-topbar {
                                background: rgba(255,255,255,0.7) !important;
                                backdrop-filter: blur(12px) !important;
                                border-bottom: 1px solid rgba(0,0,0,0.05) !important;
                            }
                            .fi-btn {
                                border-radius: 12px !important;
                                transition: all 0.3s ease !important;
                            }
                            .fi-btn:hover {
                                transform: translateY(-1px) !important;
                                box-shadow: 0 4px 12px rgba(' . $rgb . ', 0.2) !important;
                            }
                            .fi-main-ctn {
                                background: #fafafa !important;
                            }
                            .fi-section {
                                border-radius: 16px !important;
                                box-shadow: 0 1px 3px rgba(0,0,0,0.02), 0 1px 2px rgba(0,0,0,0.04) !important;
                            }
                        </style>
                    ';
                },
            );
    }

    private function hexToRgb($hex) {
        $hex = str_replace("#", "", $hex);
        if(strlen($hex) == 3) {
            $r = hexdec(substr($hex,0,1).substr($hex,0,1));
            $g = hexdec(substr($hex,1,1).substr($hex,1,1));
            $b = hexdec(substr($hex,2,1).substr($hex,2,1));
        } else {
            $r = hexdec(substr($hex,0,2));
            $g = hexdec(substr($hex,2,2));
            $b = hexdec(substr($hex,4,2));
        }
        return "$r, $g, $b";
    }
}
