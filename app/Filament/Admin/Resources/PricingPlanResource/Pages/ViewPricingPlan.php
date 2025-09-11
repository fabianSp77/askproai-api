<?php

namespace App\Filament\Admin\Resources\PricingPlanResource\Pages;

use App\Filament\Admin\Resources\PricingPlanResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use App\Filament\Concerns\InteractsWithInfolist;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\KeyValueEntry;

class ViewPricingPlan extends ViewRecord
{
    use InteractsWithInfolist;

    protected static string $resource = PricingPlanResource::class;

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
                Section::make('Grundinformationen')
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('name')
                                ->label('Name')
                                ->weight('bold'),
                            TextEntry::make('slug')
                                ->label('Slug')
                                ->badge(),
                            TextEntry::make('billing_type')
                                ->label('Abrechnungstyp')
                                ->badge()
                                ->color(fn ($state) => match($state) {
                                    'prepaid' => 'primary',
                                    'postpaid' => 'success',
                                    'hybrid' => 'warning',
                                }),
                        ]),
                        TextEntry::make('description')
                            ->label('Beschreibung')
                            ->columnSpanFull(),
                    ]),
                
                Section::make('Preise')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('price_per_minute_cents')
                            ->label('Preis pro Minute')
                            ->formatStateUsing(fn ($state) => number_format($state / 100, 2) . ' €'),
                        TextEntry::make('price_per_call_cents')
                            ->label('Preis pro Anruf')
                            ->formatStateUsing(fn ($state) => number_format($state / 100, 2) . ' €'),
                        TextEntry::make('price_per_appointment_cents')
                            ->label('Preis pro Termin')
                            ->formatStateUsing(fn ($state) => number_format($state / 100, 2) . ' €'),
                    ]),
                
                Section::make('Status')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('is_active')
                            ->label('Aktiv')
                            ->badge()
                            ->color(fn ($state) => $state ? 'success' : 'danger')
                            ->formatStateUsing(fn ($state) => $state ? 'Ja' : 'Nein'),
                        TextEntry::make('is_default')
                            ->label('Standard-Plan')
                            ->badge()
                            ->color(fn ($state) => $state ? 'warning' : 'gray')
                            ->formatStateUsing(fn ($state) => $state ? 'Ja' : 'Nein'),
                        TextEntry::make('tenants_count')
                            ->label('Anzahl Tenants')
                            ->badge()
                            ->color('success'),
                    ]),
                
                Section::make('Features')
                    ->schema([
                        KeyValueEntry::make('features')
                            ->label('Features'),
                    ]),
            ]);
    }
}