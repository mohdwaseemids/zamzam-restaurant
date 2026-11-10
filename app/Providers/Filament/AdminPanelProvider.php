<?php

namespace App\Providers\Filament;

use App\Filament\Widgets\LatestOrders;
use App\Filament\Widgets\RestaurantStats;
use App\Filament\Widgets\SalesChart;
use App\Filament\Widgets\TopDishes;
use Filament\Enums\ThemeMode;
use Filament\View\PanelsRenderHook;
use Filament\Actions\Action;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
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
            ->login()
            ->profile()
            ->brandName('Zam Zam · Admin')
            ->brandLogo(fn () => view('filament.brand'))
            ->brandLogoHeight('2.6rem')
            ->favicon(asset('favicon.svg'))
            ->colors([
                'primary' => Color::hex('#b8893a'),
                'gray' => Color::Stone,
            ])
            ->font('Manrope')
            ->defaultThemeMode(ThemeMode::Dark)
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->renderHook(PanelsRenderHook::HEAD_END, fn (): string => '<link rel="preconnect" href="https://fonts.bunny.net" crossorigin><link href="https://fonts.bunny.net/css?family=cinzel:500,600&display=swap" rel="stylesheet"><link rel="stylesheet" href="'.e(asset('css/zz-admin.css')).'?v='.@filemtime(public_path('css/zz-admin.css')).'">')
            ->sidebarCollapsibleOnDesktop()
            ->navigationGroups(['Restaurant', 'Menu', 'Media', 'Customers', 'Administration'])
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
            ->userMenuItems([
                Action::make('website')->label('View website')->icon('heroicon-o-globe-alt')->url(fn () => url('/'), shouldOpenInNewTab: true),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->widgets([
                AccountWidget::class,
                RestaurantStats::class,
                SalesChart::class,
                LatestOrders::class,
                TopDishes::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
