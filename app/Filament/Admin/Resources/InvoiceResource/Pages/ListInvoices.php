<?php

namespace App\Filament\Admin\Resources\InvoiceResource\Pages;

use App\Filament\Admin\Resources\InvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListInvoices extends ListRecords
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Neue Rechnung')
                ->icon('heroicon-o-plus'),
        ];
    }
    
    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Alle')
                ->icon('heroicon-o-document-text'),
                
            'draft' => Tab::make('Entwürfe')
                ->icon('heroicon-o-pencil')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'draft'))
                ->badge(fn () => static::getResource()::getEloquentQuery()->where('status', 'draft')->count())
                ->badgeColor('gray'),
                
            'open' => Tab::make('Offen')
                ->icon('heroicon-o-clock')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'open'))
                ->badge(fn () => static::getResource()::getEloquentQuery()->where('status', 'open')->count())
                ->badgeColor('warning'),
                
            'paid' => Tab::make('Bezahlt')
                ->icon('heroicon-o-check-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'paid'))
                ->badge(fn () => static::getResource()::getEloquentQuery()->where('status', 'paid')->count())
                ->badgeColor('success'),
                
            'overdue' => Tab::make('Überfällig')
                ->icon('heroicon-o-exclamation-circle')
                ->modifyQueryUsing(fn (Builder $query) => 
                    $query->where('status', 'open')
                          ->where('due_date', '<', now())
                )
                ->badge(fn () => static::getResource()::getEloquentQuery()
                    ->where('status', 'open')
                    ->where('due_date', '<', now())
                    ->count()
                )
                ->badgeColor('danger'),
        ];
    }
    
    protected function getHeaderWidgets(): array
    {
        return [
            // Temporarily disabled to isolate error
            // InvoiceResource\Widgets\InvoiceStatsOverview::class,
            // InvoiceResource\Widgets\InvoicePipelineWidget::class,
        ];
    }
}