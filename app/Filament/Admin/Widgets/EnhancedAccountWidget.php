<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\AccountWidget as BaseAccountWidget;

class EnhancedAccountWidget extends BaseAccountWidget
{
    protected static ?int $sort = -2;
    
    protected static bool $isLazy = false;
    
    protected static string $view = 'filament.admin.widgets.enhanced-account-widget';
    
    protected function getColumns(): int
    {
        return 1;
    }
    
    public function getDisplayName(): string
    {
        return auth()->user()?->name ?? 'Guest';
    }
    
    public function getProfileUrl(): ?string
    {
        return filament()->getProfileUrl();
    }
    
    protected function getViewData(): array
    {
        return [
            'user' => auth()->user(),
        ];
    }
}