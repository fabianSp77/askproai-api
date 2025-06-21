<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use App\Models\Customer;
use App\Services\CustomerPortalService;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Actions\BulkAction;
use Illuminate\Database\Eloquent\Collection;

class CustomerPortalManagement extends Page implements HasTable
{
    use InteractsWithTable;
    
    protected static ?string $navigationIcon = 'heroicon-o-key';
    protected static ?string $navigationGroup = 'Verwaltung';
    protected static ?string $navigationLabel = 'Kundenportal';
    protected static string $view = 'filament.admin.pages.customer-portal-management';
    protected static ?int $navigationSort = 50;
    
    public function table(Table $table): Table
    {
        return $table
            ->query(Customer::query())
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('email')
                    ->label('E-Mail')
                    ->searchable(),
                    
                Tables\Columns\IconColumn::make('portal_enabled')
                    ->label('Portal aktiv')
                    ->boolean(),
                    
                Tables\Columns\TextColumn::make('last_portal_login_at')
                    ->label('Letzter Login')
                    ->dateTime('d.m.Y H:i')
                    ->placeholder('Noch nie'),
                    
                Tables\Columns\TextColumn::make('appointments_count')
                    ->label('Termine')
                    ->counts('appointments')
                    ->badge(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('portal_enabled')
                    ->label('Portal-Status')
                    ->options([
                        '1' => 'Aktiviert',
                        '0' => 'Deaktiviert',
                    ]),
                    
                Tables\Filters\Filter::make('has_email')
                    ->label('Mit E-Mail')
                    ->query(fn ($query) => $query->whereNotNull('email')),
                    
                Tables\Filters\Filter::make('never_logged_in')
                    ->label('Noch nie eingeloggt')
                    ->query(fn ($query) => $query->where('portal_enabled', true)
                        ->whereNull('last_portal_login_at')),
            ])
            ->actions([
                Tables\Actions\Action::make('toggle_portal')
                    ->label(fn ($record) => $record->portal_enabled ? 'Deaktivieren' : 'Aktivieren')
                    ->icon(fn ($record) => $record->portal_enabled ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn ($record) => $record->portal_enabled ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $portalService = app(CustomerPortalService::class);
                        $customerAuth = \App\Models\CustomerAuth::find($record->id);
                        
                        if ($record->portal_enabled) {
                            $portalService->disablePortalAccess($customerAuth);
                            Notification::make()
                                ->title('Portal deaktiviert')
                                ->success()
                                ->send();
                        } else {
                            if (empty($record->email)) {
                                Notification::make()
                                    ->title('Fehler')
                                    ->body('Kunde hat keine E-Mail-Adresse.')
                                    ->danger()
                                    ->send();
                                return;
                            }
                            
                            $portalService->enablePortalAccess($customerAuth);
                            Notification::make()
                                ->title('Portal aktiviert')
                                ->body('Zugangsdaten wurden per E-Mail gesendet.')
                                ->success()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
                BulkAction::make('bulk_enable_portal')
                    ->label('Portal aktivieren')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Portal für ausgewählte Kunden aktivieren')
                    ->modalDescription('Allen ausgewählten Kunden mit E-Mail-Adresse wird der Zugang aktiviert.')
                    ->action(function (Collection $records) {
                        $portalService = app(CustomerPortalService::class);
                        $success = 0;
                        $failed = 0;
                        
                        foreach ($records as $record) {
                            if (empty($record->email)) {
                                $failed++;
                                continue;
                            }
                            
                            $customerAuth = \App\Models\CustomerAuth::find($record->id);
                            if ($portalService->enablePortalAccess($customerAuth)) {
                                $success++;
                            } else {
                                $failed++;
                            }
                        }
                        
                        Notification::make()
                            ->title('Bulk-Aktivierung abgeschlossen')
                            ->body("Erfolgreich: {$success}, Fehlgeschlagen: {$failed}")
                            ->success()
                            ->send();
                    })
                    ->deselectRecordsAfterCompletion(),
                    
                BulkAction::make('bulk_disable_portal')
                    ->label('Portal deaktivieren')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (Collection $records) {
                        $portalService = app(CustomerPortalService::class);
                        $count = 0;
                        
                        foreach ($records as $record) {
                            $customerAuth = \App\Models\CustomerAuth::find($record->id);
                            if ($portalService->disablePortalAccess($customerAuth)) {
                                $count++;
                            }
                        }
                        
                        Notification::make()
                            ->title('Portal deaktiviert')
                            ->body("Portal-Zugang für {$count} Kunden deaktiviert.")
                            ->success()
                            ->send();
                    })
                    ->deselectRecordsAfterCompletion(),
            ]);
    }
    
    public function getStats(): array
    {
        $customers = Customer::query();
        
        return [
            'total' => $customers->count(),
            'with_email' => $customers->whereNotNull('email')->count(),
            'portal_enabled' => $customers->where('portal_enabled', true)->count(),
            'active_users' => $customers->where('portal_enabled', true)
                ->whereNotNull('last_portal_login_at')
                ->where('last_portal_login_at', '>=', now()->subDays(30))
                ->count(),
        ];
    }
}