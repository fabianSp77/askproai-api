<?php

namespace App\Filament\Admin\Resources\CompanyResource\Pages;

use App\Filament\Admin\Resources\CompanyResource;
use App\Filament\Admin\Resources\CompanyResource\Widgets\CompanyStatsOverview;
use App\Filament\Admin\Resources\CompanyResource\Widgets\CompanyDetailsWidget;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Fieldset;
use Filament\Support\Enums\FontWeight;

class ViewCompany extends ViewRecord
{
    protected static string $resource = CompanyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Bearbeiten')
                ->icon('heroicon-o-pencil-square'),
                
            Actions\Action::make('manage_api')
                ->label('API Verwaltung')
                ->icon('heroicon-o-key')
                ->url(fn () => CompanyResource::getUrl('manage-api-credentials', ['record' => $this->record]))
                ->color('warning'),
        ];
    }
    
    protected function getHeaderWidgets(): array
    {
        return [
            CompanyStatsOverview::class,
        ];
    }
    
    protected function getFooterWidgets(): array
    {
        return [
            CompanyDetailsWidget::class,
        ];
    }
    
    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Unternehmensinformationen')
                    ->description('Grundlegende Informationen zum Unternehmen')
                    ->icon('heroicon-o-building-office')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('name')
                                    ->label('Unternehmensname')
                                    ->weight(FontWeight::Bold)
                                    ->size('lg'),
                                    
                                TextEntry::make('domain')
                                    ->label('Domain')
                                    ->icon('heroicon-o-globe-alt')
                                    ->url(fn ($state) => $state ? 'https://' . $state : null)
                                    ->openUrlInNewTab()
                                    ->placeholder('Keine Domain'),
                                    
                                TextEntry::make('is_active')
                                    ->label('Status')
                                    ->badge()
                                    ->color(fn (bool $state): string => $state ? 'success' : 'danger')
                                    ->formatStateUsing(fn (bool $state): string => $state ? 'Aktiv' : 'Inaktiv'),
                            ]),
                            
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('created_at')
                                    ->label('Erstellt am')
                                    ->dateTime('d.m.Y H:i')
                                    ->icon('heroicon-o-calendar'),
                                    
                                TextEntry::make('branches_count')
                                    ->label('Filialen')
                                    ->state(fn ($record) => $record->branches()->count())
                                    ->icon('heroicon-o-building-storefront'),
                                    
                                TextEntry::make('staff_count')
                                    ->label('Mitarbeiter')
                                    ->state(fn ($record) => $record->staff()->where('active', true)->count())
                                    ->icon('heroicon-o-users'),
                            ]),
                    ]),
                    
                Section::make('Kontaktinformationen')
                    ->description('Kontaktdaten des Unternehmens')
                    ->icon('heroicon-o-envelope')
                    ->collapsible()
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('email')
                                    ->label('E-Mail')
                                    ->icon('heroicon-o-envelope')
                                    ->copyable()
                                    ->placeholder('Keine E-Mail'),
                                    
                                TextEntry::make('phone')
                                    ->label('Telefon')
                                    ->icon('heroicon-o-phone')
                                    ->copyable()
                                    ->placeholder('Keine Telefonnummer'),
                                    
                                TextEntry::make('website')
                                    ->label('Website')
                                    ->icon('heroicon-o-globe-alt')
                                    ->url(fn ($state) => $state)
                                    ->openUrlInNewTab()
                                    ->placeholder('Keine Website'),
                            ]),
                    ]),
                    
                Section::make('Einstellungen')
                    ->description('Unternehmenseinstellungen und Konfiguration')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Fieldset::make('Zeiteinstellungen')
                                    ->schema([
                                        TextEntry::make('timezone')
                                            ->label('Zeitzone')
                                            ->default('Europe/Berlin'),
                                            
                                        TextEntry::make('locale')
                                            ->label('Sprache')
                                            ->default('de_DE'),
                                            
                                        TextEntry::make('date_format')
                                            ->label('Datumsformat')
                                            ->default('d.m.Y'),
                                    ]),
                                    
                                Fieldset::make('Benachrichtigungen')
                                    ->schema([
                                        TextEntry::make('settings.notifications.new_appointment')
                                            ->label('Neue Termine')
                                            ->badge()
                                            ->color(fn ($state) => $state ? 'success' : 'gray')
                                            ->formatStateUsing(fn ($state) => $state ? 'Aktiv' : 'Inaktiv'),
                                            
                                        TextEntry::make('settings.notifications.appointment_reminder')
                                            ->label('Terminerinnerungen')
                                            ->badge()
                                            ->color(fn ($state) => $state ? 'success' : 'gray')
                                            ->formatStateUsing(fn ($state) => $state ? 'Aktiv' : 'Inaktiv'),
                                            
                                        TextEntry::make('settings.notifications.daily_summary')
                                            ->label('Tägliche Zusammenfassung')
                                            ->badge()
                                            ->color(fn ($state) => $state ? 'success' : 'gray')
                                            ->formatStateUsing(fn ($state) => $state ? 'Aktiv' : 'Inaktiv'),
                                    ]),
                            ]),
                    ]),
            ]);
    }
}
