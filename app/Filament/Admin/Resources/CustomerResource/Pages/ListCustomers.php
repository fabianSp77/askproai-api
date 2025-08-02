<?php

namespace App\Filament\Admin\Resources\CustomerResource\Pages;

use App\Filament\Admin\Resources\CustomerResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Admin\Widgets\CustomerInsightsWidget;
use Filament\Notifications\Notification;

class ListCustomers extends ListRecords
{
    protected static string $resource = CustomerResource::class;
    
    protected function getViewData(): array
    {
        return [
            ...parent::getViewData(),
            'contentContainerClasses' => 'fi-resource-customers',
        ];
    }

    public function mount(): void
    {
        // Emergency Company Context Fix
        if (auth()->check() && auth()->user()->company_id) {
            app()->instance('current_company_id', auth()->user()->company_id);
            app()->instance('company_context_source', 'web_auth');
        }
        parent::mount();
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('duplicates')
                ->label('Duplikate finden')
                ->icon('heroicon-o-magnifying-glass-circle')
                ->color('warning')
                ->url(fn () => CustomerResource::getUrl('duplicates')),
            Actions\Action::make('export')
                ->label('Exportieren')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function () {
                    // Export logic would go here
                    Notification::make()
                        ->title('Export wird vorbereitet...')
                        ->success()
                        ->send();
                }),
        ];
    }
    
    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Admin\Widgets\GlobalFilterWidget::class,
            \App\Filament\Admin\Widgets\CustomerKpiWidget::class,
            \App\Filament\Admin\Widgets\CustomerFunnelWidget::class,
            \App\Filament\Admin\Widgets\CustomerSourceWidget::class,
        ];
    }
    
    public function getTitle(): string
    {
        return 'Kunden-Intelligence';
    }
    
    public function getSubheading(): ?string
    {
        return 'Verwalten Sie Ihre Endkunden mit intelligenten Insights und Analysen';
    }
}
