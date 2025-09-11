<?php

namespace App\Providers\Filament;

use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Navigation\NavigationGroup;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('admin')->path('admin')
            ->login()->default()
            ->authGuard('web')->middleware(['web'])
            ->brandName('AskProAI Admin')
            ->brandLogo(asset('images/logo.svg'))
            ->favicon(asset('favicon.ico'))
            ->colors([
                'primary' => Color::Sky,
                'gray' => Color::Gray,
                'info' => Color::Blue,
                'success' => Color::Emerald,
                'warning' => Color::Orange,
                'danger' => Color::Rose,
            ])
            ->font('Inter')
            ->darkMode(true)
            ->sidebarCollapsibleOnDesktop()
            ->navigationGroups([
                NavigationGroup::make()
                    ->label('Dashboard'),
                NavigationGroup::make()
                    ->label('Call Management'),
                NavigationGroup::make()
                    ->label('Customer Relations'),
                NavigationGroup::make()
                    ->label('System')
                    ->collapsed(),
            ])
            ->discoverPages(
                in: app_path('Filament/Admin/Pages'),
                for: 'App\\Filament\\Admin\\Pages'
            )
            ->discoverResources(
                in: app_path('Filament/Admin/Resources'),
                for: 'App\\Filament\\Admin\\Resources'
            )
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->spa()
            ->breadcrumbs(false)  // Disable default breadcrumbs to prevent duplication
            ->renderHook(
                \Filament\View\PanelsRenderHook::BODY_START,
                fn () => view('components.stripe-menu-init')
            );
    }
}
