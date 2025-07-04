<?php

namespace App\Filament\Admin\Resources\BranchResource\Pages;

use App\Filament\Admin\Resources\BranchResource;
use App\Filament\Admin\Resources\BranchResource\Widgets\BranchStatsWidget;
use App\Filament\Admin\Resources\BranchResource\Widgets\BranchDetailsWidget;
use App\Services\IntegrationTestService;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Actions\Action;
use Filament\Infolists\Components\Fieldset;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Infolists\Components\Split;
use Filament\Notifications\Notification;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\IconPosition;

class ViewBranch extends ViewRecord
{
    protected static string $resource = BranchResource::class;
    
    protected function getHeaderWidgets(): array
    {
        return [
            BranchStatsWidget::class,
        ];
    }
    
    protected function getFooterWidgets(): array
    {
        return [
            BranchDetailsWidget::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Bearbeiten')
                ->icon('heroicon-o-pencil-square'),
            
            Actions\DeleteAction::make()
                ->label('Löschen')
                ->icon('heroicon-o-trash')
                ->requiresConfirmation()
                ->modalHeading('Filiale löschen')
                ->modalDescription('Sind Sie sicher, dass Sie diese Filiale löschen möchten? Diese Aktion kann nicht rückgängig gemacht werden.')
                ->modalSubmitActionLabel('Ja, löschen')
                ->successNotificationTitle('Filiale gelöscht'),
            
            Actions\Action::make('test_integration')
                ->label('Integrationen testen')
                ->icon('heroicon-o-beaker')
                ->color('success')
                ->action(function () {
                    try {
                        $testService = new IntegrationTestService();
                        $result = $testService->createTestBooking($this->record);
                        
                        if ($result['success']) {
                            Notification::make()
                                ->title('Integration erfolgreich getestet')
                                ->body($result['message'] ?? 'Test-Termin wurde erfolgreich erstellt.')
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Integrationsfehler')
                                ->body($result['message'] ?? 'Fehler beim Testen der Integration.')
                                ->danger()
                                ->send();
                        }
                        
                        $this->record->integrations_tested_at = now();
                        $this->record->save();
                        
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Fehler beim Testen')
                            ->body('Ein unerwarteter Fehler ist aufgetreten: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
                ->requiresConfirmation()
                ->modalHeading('Integrationen testen')
                ->modalDescription('Dies erstellt einen Test-Termin für morgen 10:00 Uhr. Möchten Sie fortfahren?')
                ->modalSubmitActionLabel('Ja, testen'),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // Hauptinformationen
                Section::make('Filialinformationen')
                    ->description('Grundlegende Informationen zur Filiale')
                    ->icon('heroicon-o-building-office')
                    ->collapsible()
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('company.name')
                                    ->label('Unternehmen')
                                    ->icon('heroicon-o-building-office-2')
                                    ->weight(FontWeight::Medium),

                                TextEntry::make('name')
                                    ->label('Filialname')
                                    ->icon('heroicon-o-home')
                                    ->weight(FontWeight::Bold)
                                    ->size('lg'),

                                TextEntry::make('phone_number')
                                    ->label('Telefonnummer')
                                    ->icon('heroicon-o-phone')
                                    ->copyable()
                                    ->copyMessage('Kopiert!')
                                    ->copyMessageDuration(1500),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextEntry::make('city')
                                    ->label('Stadt')
                                    ->icon('heroicon-o-map-pin')
                                    ->default('Nicht angegeben'),

                                TextEntry::make('active')
                                    ->label('Status')
                                    ->badge()
                                    ->color(fn (bool $state): string => $state ? 'success' : 'danger')
                                    ->icon(fn (bool $state): string => $state ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')
                                    ->formatStateUsing(fn (bool $state): string => $state ? 'Aktiv' : 'Inaktiv'),
                            ]),
                    ]),

                // Integration Status
                Section::make('Integration Status')
                    ->description('Status der verbundenen Dienste')
                    ->icon('heroicon-o-link')
                    ->collapsible()
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('calcom_status')
                                    ->label('Cal.com')
                                    ->badge()
                                    ->color(fn () => $this->record->calcom_api_key ? 'success' : 'gray')
                                    ->icon(fn () => $this->record->calcom_api_key ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')
                                    ->formatStateUsing(fn () => $this->record->calcom_api_key ? 'Konfiguriert' : 'Nicht konfiguriert'),

                                TextEntry::make('retell_status')
                                    ->label('Retell.ai')
                                    ->badge()
                                    ->color(fn () => $this->record->retell_agent_id ? 'success' : 'gray')
                                    ->icon(fn () => $this->record->retell_agent_id ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')
                                    ->formatStateUsing(fn () => $this->record->retell_agent_id ? 'Konfiguriert' : 'Nicht konfiguriert'),

                                TextEntry::make('integrations_tested_at')
                                    ->label('Zuletzt getestet')
                                    ->dateTime('d.m.Y H:i')
                                    ->placeholder('Noch nicht getestet')
                                    ->icon('heroicon-o-clock')
                                    ->color('gray'),
                            ]),
                    ]),
            ]);
    }
}
