<?php

namespace App\Filament\Admin\Resources\BillingPeriodResource\Pages;

use App\Filament\Admin\Resources\BillingPeriodResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Split;
use Filament\Infolists\Components\Tabs;

class ViewBillingPeriod extends ViewRecord
{
    protected static string $resource = BillingPeriodResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Tabs::make('Tabs')
                    ->tabs([
                        Tabs\Tab::make('Overview')
                            ->icon('heroicon-o-chart-bar')
                            ->schema([
                                Section::make('Period Information')
                                    ->columns(3)
                                    ->schema([
                                        TextEntry::make('company.name')
                                            ->label('Company')
                                            ->weight('bold')
                                            ->size('lg'),
                                        TextEntry::make('period_display')
                                            ->label('Period')
                                            ->state(fn ($record) => $record->start_date->format('F Y'))
                                            ->badge()
                                            ->color('primary'),
                                        TextEntry::make('status')
                                            ->badge()
                                            ->color(fn (string $state): string => match ($state) {
                                                'pending' => 'gray',
                                                'active' => 'info',
                                                'processed' => 'warning',
                                                'invoiced' => 'success',
                                                'closed' => 'secondary',
                                                default => 'gray',
                                            }),
                                        TextEntry::make('date_range')
                                            ->label('Date Range')
                                            ->state(fn ($record) => 
                                                $record->start_date->format('d.m.Y') . ' - ' . 
                                                $record->end_date->format('d.m.Y')
                                            ),
                                        TextEntry::make('days_in_period')
                                            ->label('Days')
                                            ->state(fn ($record) => 
                                                $record->start_date->diffInDays($record->end_date) + 1
                                            )
                                            ->suffix(' days'),
                                        IconEntry::make('is_prorated')
                                            ->label('Prorated')
                                            ->boolean(),
                                    ]),
                                    
                                Section::make('Usage Summary')
                                    ->columns(2)
                                    ->schema([
                                        Split::make([
                                            Section::make('Minutes Usage')
                                                ->schema([
                                                    TextEntry::make('used_minutes')
                                                        ->label('Used Minutes')
                                                        ->numeric()
                                                        ->suffix(' min')
                                                        ->size('lg')
                                                        ->weight('bold'),
                                                    TextEntry::make('included_minutes')
                                                        ->label('Included Minutes')
                                                        ->numeric()
                                                        ->suffix(' min'),
                                                    TextEntry::make('overage_minutes')
                                                        ->label('Overage Minutes')
                                                        ->numeric()
                                                        ->suffix(' min')
                                                        ->color(fn ($state) => $state > 0 ? 'danger' : 'success'),
                                                    TextEntry::make('usage_percentage')
                                                        ->label('Usage %')
                                                        ->state(fn ($record) => 
                                                            $record->included_minutes > 0
                                                                ? round(($record->used_minutes / $record->included_minutes) * 100, 1)
                                                                : 0
                                                        )
                                                        ->suffix('%')
                                                        ->color(fn ($state) => 
                                                            $state >= 100 ? 'danger' : 
                                                            ($state >= 80 ? 'warning' : 'success')
                                                        ),
                                                ]),
                                            Section::make('Financial Summary')
                                                ->schema([
                                                    TextEntry::make('total_cost')
                                                        ->label('Total Cost')
                                                        ->money('EUR')
                                                        ->size('lg')
                                                        ->weight('bold'),
                                                    TextEntry::make('base_fee')
                                                        ->label('Base Fee')
                                                        ->money('EUR'),
                                                    TextEntry::make('overage_cost')
                                                        ->label('Overage Cost')
                                                        ->money('EUR')
                                                        ->color(fn ($state) => $state > 0 ? 'warning' : 'gray'),
                                                    TextEntry::make('price_per_minute')
                                                        ->label('Price per Minute')
                                                        ->money('EUR')
                                                        ->suffix('/min'),
                                                ]),
                                        ]),
                                    ]),
                            ]),
                            
                        Tabs\Tab::make('Profitability')
                            ->icon('heroicon-o-currency-euro')
                            ->schema([
                                Section::make('Revenue & Margin Analysis')
                                    ->columns(2)
                                    ->schema([
                                        TextEntry::make('total_revenue')
                                            ->label('Total Revenue')
                                            ->money('EUR')
                                            ->size('lg')
                                            ->weight('bold'),
                                        TextEntry::make('total_cost')
                                            ->label('Total Cost')
                                            ->money('EUR')
                                            ->size('lg'),
                                        TextEntry::make('margin')
                                            ->label('Margin')
                                            ->money('EUR')
                                            ->size('lg')
                                            ->color(fn ($state) => $state >= 0 ? 'success' : 'danger'),
                                        TextEntry::make('margin_percentage')
                                            ->label('Margin %')
                                            ->suffix('%')
                                            ->size('lg')
                                            ->weight('bold')
                                            ->color(fn ($state) => 
                                                $state >= 30 ? 'success' : 
                                                ($state >= 0 ? 'warning' : 'danger')
                                            ),
                                    ]),
                            ]),
                            
                        Tabs\Tab::make('Invoice Details')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Section::make('Invoice Information')
                                    ->schema([
                                        IconEntry::make('is_invoiced')
                                            ->label('Invoice Status')
                                            ->boolean()
                                            ->trueIcon('heroicon-o-check-circle')
                                            ->falseIcon('heroicon-o-x-circle')
                                            ->trueColor('success')
                                            ->falseColor('gray'),
                                        TextEntry::make('invoice.number')
                                            ->label('Invoice Number')
                                            ->placeholder('Not invoiced')
                                            ->url(fn ($record) => 
                                                $record->invoice_id 
                                                    ? route('filament.admin.resources.invoices.edit', $record->invoice_id)
                                                    : null
                                            )
                                            ->openUrlInNewTab(),
                                        TextEntry::make('invoiced_at')
                                            ->label('Invoiced Date')
                                            ->dateTime()
                                            ->placeholder('Not invoiced'),
                                        TextEntry::make('stripe_invoice_id')
                                            ->label('Stripe Invoice ID')
                                            ->copyable()
                                            ->placeholder('No Stripe invoice'),
                                    ]),
                            ]),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('process')
                ->label('Process Period')
                ->icon('heroicon-o-cog')
                ->color('warning')
                ->visible(fn ($record) => 
                    $record->status === 'active' && $record->end_date->isPast()
                )
                ->requiresConfirmation()
                ->action(function ($record) {
                    $service = app(\App\Services\Billing\BillingPeriodService::class);
                    $service->processPeriod($record);
                    $this->refreshFormData(['status']);
                }),
                
            Actions\Action::make('createInvoice')
                ->label('Create Invoice')
                ->icon('heroicon-o-document-plus')
                ->color('success')
                ->visible(fn ($record) => 
                    $record->status === 'processed' && !$record->is_invoiced
                )
                ->requiresConfirmation()
                ->action(function ($record) {
                    $service = app(\App\Services\Billing\BillingPeriodService::class);
                    $invoice = $service->createInvoice($record);
                    $this->refreshFormData(['is_invoiced', 'invoice_id']);
                }),
                
            Actions\EditAction::make(),
        ];
    }
}