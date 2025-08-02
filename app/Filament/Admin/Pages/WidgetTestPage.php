<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;

class WidgetTestPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-beaker';
    protected static ?string $navigationLabel = 'Widget Test';
    protected static ?string $navigationGroup = 'System';
    protected static string $view = 'filament.admin.pages.widget-test-page';

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && ($user->hasRole(['Super Admin', 'super_admin', 'developer']) || $user->email === 'dev@askproai.de');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Admin\Widgets\CallLiveStatusWidget::class,
            \App\Filament\Admin\Widgets\GlobalFilterWidget::class,
            \App\Filament\Admin\Widgets\CallKpiWidget::class,
            \App\Filament\Admin\Resources\CallResource\Widgets\CallAnalyticsWidget::class,
        ];
    }
    
    public function getHeaderWidgetsColumns(): int
    {
        return 1;
    }
}