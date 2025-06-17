<?php

namespace App\Filament\Admin\Resources\CompanyPricingResource\Pages;

use App\Filament\Admin\Resources\CompanyPricingResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\ViewEntry;

class ViewCompanyPricing extends ViewRecord
{
    protected static string $resource = CompanyPricingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
    
    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Preismodell-Details')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('company.name')
                                    ->label('Firma')
                                    ->weight('bold')
                                    ->size('lg'),
                                    
                                TextEntry::make('valid_period')
                                    ->label('Gültigkeitszeitraum')
                                    ->getStateUsing(function ($record) {
                                        $from = $record->valid_from->format('d.m.Y');
                                        $until = $record->valid_until ? $record->valid_until->format('d.m.Y') : 'unbegrenzt';
                                        return "{$from} - {$until}";
                                    })
                                    ->badge()
                                    ->color(function ($record) {
                                        if (!$record->is_active) return 'gray';
                                        if ($record->valid_until && $record->valid_until->isPast()) return 'danger';
                                        if ($record->valid_from->isFuture()) return 'warning';
                                        return 'success';
                                    }),
                                    
                                TextEntry::make('is_active')
                                    ->label('Status')
                                    ->badge()
                                    ->formatStateUsing(fn ($state) => $state ? 'Aktiv' : 'Inaktiv')
                                    ->color(fn ($state) => $state ? 'success' : 'danger'),
                            ]),
                    ]),
                    
                Section::make('Preisgestaltung')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Section::make('Minutenpreise')
                                    ->heading(false)
                                    ->schema([
                                        TextEntry::make('price_per_minute')
                                            ->label('Standard-Minutenpreis')
                                            ->money('EUR')
                                            ->size('lg')
                                            ->weight('bold')
                                            ->icon('heroicon-o-clock'),
                                            
                                        TextEntry::make('included_minutes')
                                            ->label('Inkludierte Minuten')
                                            ->formatStateUsing(fn ($state) => $state . ' Min/Monat')
                                            ->icon('heroicon-o-gift')
                                            ->badge()
                                            ->color('success'),
                                            
                                        TextEntry::make('overage_price_per_minute')
                                            ->label('Preis für zusätzliche Minuten')
                                            ->money('EUR')
                                            ->placeholder('= Standard-Minutenpreis')
                                            ->icon('heroicon-o-plus-circle'),
                                    ])
                                    ->extraAttributes(['class' => 'bg-blue-50 dark:bg-blue-900/20']),
                                    
                                Section::make('Gebühren')
                                    ->heading(false)
                                    ->schema([
                                        TextEntry::make('setup_fee')
                                            ->label('Einrichtungsgebühr')
                                            ->money('EUR')
                                            ->placeholder('Keine')
                                            ->icon('heroicon-o-wrench'),
                                            
                                        TextEntry::make('monthly_base_fee')
                                            ->label('Monatliche Grundgebühr')
                                            ->money('EUR')
                                            ->placeholder('Keine')
                                            ->icon('heroicon-o-calendar'),
                                    ])
                                    ->extraAttributes(['class' => 'bg-green-50 dark:bg-green-900/20']),
                            ]),
                    ]),
                    
                Section::make('Preisbeispiele')
                    ->schema([
                        ViewEntry::make('pricing_examples')
                            ->label(false)
                            ->view('filament.infolists.pricing-examples')
                            ->viewData([
                                'pricePerMinute' => $this->record->price_per_minute,
                                'includedMinutes' => $this->record->included_minutes,
                                'overagePrice' => $this->record->overage_price_per_minute ?? $this->record->price_per_minute,
                                'monthlyFee' => $this->record->monthly_base_fee ?? 0,
                                'setupFee' => $this->record->setup_fee ?? 0,
                            ]),
                    ])
                    ->collapsible(),
                    
                Section::make('Zusätzliche Informationen')
                    ->schema([
                        TextEntry::make('notes')
                            ->label('Notizen')
                            ->placeholder('Keine Notizen vorhanden')
                            ->columnSpanFull(),
                            
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('created_at')
                                    ->label('Erstellt am')
                                    ->dateTime('d.m.Y H:i'),
                                    
                                TextEntry::make('updated_at')
                                    ->label('Zuletzt geändert')
                                    ->dateTime('d.m.Y H:i'),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}