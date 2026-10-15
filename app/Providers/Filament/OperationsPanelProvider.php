<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Workspace;
use App\Filament\Resources\QuoteResource;
use App\Http\Middleware\EnsureActiveUser;
use App\Support\LocalAvatarProvider;
use Filament\Enums\ThemeMode;
use Filament\FontProviders\LocalFontProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class OperationsPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel->default()->id('operations')->path('app')->login()
            ->brandName('SOLVET · Gestión')->brandLogo(fn () => view('components.brand'))->brandLogoHeight('3.875rem')
            ->favicon(asset('brand/solvet-it-logo.png'))->colors(['primary' => Color::hex('#3B647F')])->font('Inter Variable', url: asset('fonts/filament/filament/inter/index.css'), provider: LocalFontProvider::class)
            ->defaultAvatarProvider(LocalAvatarProvider::class)
            ->navigationGroups(['Comercial', 'Administración'])
            ->homeUrl(fn () => QuoteResource::getUrl('index'))
            ->darkMode()
            ->defaultThemeMode(ThemeMode::Light)
            ->themeSwitcher(false)
            ->renderHook(PanelsRenderHook::TOPBAR_LOGO_AFTER, fn () => view('components.workspace-label'))
            ->renderHook(PanelsRenderHook::GLOBAL_SEARCH_BEFORE, fn () => view('components.theme-switcher'))
            ->renderHook(PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE, fn () => view('components.theme-switcher'))
            ->sidebarCollapsibleOnDesktop()->sidebarWidth('14rem')->maxContentWidth('full')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->pages([Workspace::class])
            ->middleware([EncryptCookies::class, AddQueuedCookiesToResponse::class, StartSession::class, AuthenticateSession::class, ShareErrorsFromSession::class, VerifyCsrfToken::class, SubstituteBindings::class, DisableBladeIconComponents::class, DispatchServingFilamentEvent::class])
            ->authMiddleware([Authenticate::class, EnsureActiveUser::class]);
    }
}
