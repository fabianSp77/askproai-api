<?php

namespace App\Filament\Resources\ValidationDashboardResource\Pages;

use App\Filament\Resources\ValidationDashboardResource;
use Filament\Resources\Pages\Page;
use Filament\Actions;

class ViewValidationDashboard extends Page
{
    protected static string $resource = ValidationDashboardResource::class;
    protected static string $view = 'filament.pages.validation-dashboard';
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('system_check')
                ->label('System-Check')
                ->icon('heroicon-m-arrow-path')
                ->color('primary')
                ->action(function () {
                    // Trigger system-wide validation
                    $this->dispatch('system-validation-triggered');
                }),
                
            Actions\Action::make('export')
                ->label('Report exportieren')
                ->icon('heroicon-m-arrow-down-tray')
                ->color('gray'),
        ];
    }
}
