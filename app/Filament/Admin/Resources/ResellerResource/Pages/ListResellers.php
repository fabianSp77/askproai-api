<?php

namespace App\Filament\Admin\Resources\ResellerResource\Pages;

use App\Filament\Admin\Resources\ResellerResource;
use App\Filament\Admin\Resources\ResellerResource\Widgets;
use Filament\Actions;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Pages\ListRecords;

class ListResellers extends ListRecords
{
    use ExposesTableToWidgets;
    
    protected static string $resource = ResellerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('New Reseller')
                ->icon('heroicon-o-plus')
                ->color('primary'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            Widgets\ResellerStatsOverview::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            Widgets\TopResellersWidget::class,
        ];
    }

    public function getTitle(): string
    {
        return 'Reseller Management';
    }

    public function getSubheading(): string
    {
        return 'Manage your reseller partners and track their performance';
    }
}