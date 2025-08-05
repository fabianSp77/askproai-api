<?php

namespace App\Filament\Admin\Resources\PrepaidBalanceResource\Pages;

use App\Filament\Admin\Resources\PrepaidBalanceResource;
use App\Models\BalanceTransaction;
use App\Models\BalanceTopup;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\ViewEntry;
use Illuminate\Support\HtmlString;
use Filament\Forms;

class ViewPrepaidBalanceEnhanced extends ViewRecord
{
    protected static string $resource = PrepaidBalanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('viewPortal')
                ->label('Portal öffnen')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->action(function () {
                    $token = bin2hex(random_bytes(32));
                    
                    cache()->put('admin_portal_access_' . $token, [
                        'admin_id' => auth()->id(),
                        'company_id' => $this->record->company_id,
                        'created_at' => now(),
                    ], now()->addMinutes(15));
                    
                    return redirect('/business/admin-access?token=' . $token);
                }),
                
            Actions\Action::make('exportTransactions')
                ->label('Export')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->form([
                    \Filament\Forms\Components\Select::make('format')
                        ->label('Format')
                        ->options([
                            'csv' => 'CSV',
                            'pdf' => 'PDF',
                        ])
                        ->default('csv')
                        ->required(),
                    \Filament\Forms\Components\DatePicker::make('from')
                        ->label('Von')
                        ->default(now()->startOfMonth()),
                    \Filament\Forms\Components\DatePicker::make('to')
                        ->label('Bis')
                        ->default(now()),
                ])
                ->action(function (array $data) {
                    $params = [
                        'company_id' => $this->record->company_id,
                        'from' => $data['from']?->format('Y-m-d'),
                        'to' => $data['to']?->format('Y-m-d'),
                        'type' => 'all'
                    ];
                    
                    $route = $data['format'] === 'csv' 
                        ? route('admin.api.transactions.export.csv', $params)
                        : route('admin.api.transactions.export.pdf', $params);
                    
                    return redirect($route);
                }),
        ];
    }
    
    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // Hauptstatistiken
                Section::make('Guthaben-Übersicht')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('balance')
                                    ->label('Aktuelles Guthaben')
                                    ->money('EUR')
                                    ->size('2xl')
                                    ->weight('bold')
                                    ->color(fn ($state) => $state > 0 ? 'success' : 'danger'),
                                    
                                TextEntry::make('reserved_balance')
                                    ->label('Reserviert')
                                    ->money('EUR')
                                    ->size('lg')
                                    ->icon('heroicon-o-lock-closed'),
                                    
                                TextEntry::make('effective_balance')
                                    ->label('Verfügbar')
                                    ->getStateUsing(fn ($record) => $record->effective_balance)
                                    ->money('EUR')
                                    ->size('lg')
                                    ->color(fn ($state) => $state > 20 ? 'success' : ($state > 0 ? 'warning' : 'danger'))
                                    ->icon('heroicon-o-check-circle'),
                                    
                                TextEntry::make('low_balance_threshold')
                                    ->label('Warnschwelle')
                                    ->money('EUR')
                                    ->icon('heroicon-o-exclamation-triangle')
                                    ->color('warning'),
                            ]),
                    ]),
                    
                // Statistiken
                Section::make('Nutzungsstatistiken')
                    ->collapsible()
                    ->schema([
                        ViewEntry::make('usage_stats')
                            ->label(false)
                            ->view('filament.resources.prepaid-balance.usage-stats'),
                    ]),
                    
                // Transaktionsverlauf
                Section::make('Transaktionsverlauf')
                    ->schema([
                        ViewEntry::make('transactions')
                            ->label(false)
                            ->view('filament.resources.prepaid-balance.transaction-history'),
                    ]),
                    
                // Aufladungen
                Section::make('Aufladungen')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        ViewEntry::make('topups')
                            ->label(false)
                            ->view('filament.resources.prepaid-balance.topup-history'),
                    ]),
            ]);
    }
}