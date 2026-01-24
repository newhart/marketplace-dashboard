<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
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

class TransporteurPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('transporteur')
            ->path('transporteur')
            ->login(\App\Filament\Pages\CustomLogin::class)
            ->brandName('OnaMarketplace - Transporteur')
            ->brandLogo(asset('images/logo.png'))
            ->brandLogoHeight('3rem')
            ->colors([
                'primary' => Color::hex('#02C6B0'),
                'secondary' => Color::hex('#fcf3ef'),
                'tertiary' => Color::hex('#FFE6C7'),
                'gray' => Color::Gray,
            ])
            ->darkMode(false)
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->discoverPages(in: app_path('Filament/TransporteurPanel/Pages'), for: 'App\\Filament\\TransporteurPanel\\Pages')
            ->discoverWidgets(in: app_path('Filament/TransporteurPanel/Widgets'), for: 'App\\Filament\\TransporteurPanel\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
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
            ]);
    }
}
