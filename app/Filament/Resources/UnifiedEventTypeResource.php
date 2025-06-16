<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UnifiedEventTypeResource\Pages;
use App\Models\UnifiedEventType;
use App\Models\CalcomEventType;
use App\Models\Staff;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class UnifiedEventTypeResource extends Resource
{
    protected static ?string $model = CalcomEventType::class;
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationLabel = 'Event-Types & Mitarbeiter';
    protected static ?string $navigationGroup = 'Terminverwaltung';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Event-Type Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Event-Name')
                            ->required()
                            ->maxLength(255),
                        
                        Forms\Components\TextInput::make('slug')
                            ->label('URL-Slug')
                            ->maxLength(255),
                        
                        Forms\Components\Textarea::make('description')
                            ->label('Beschreibung')
                            ->rows(3),
                        
                        Forms\Components\TextInput::make('duration_minutes')
                            ->label('Dauer (Minuten)')
                            ->numeric()
                            ->default(30),
                        
                        Forms\Components\TextInput::make('price')
                            ->label('Preis')
                            ->numeric()
                            ->prefix('€'),
                        
                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktiv')
                            ->default(true),
                    ])->columns(2),

                Forms\Components\Section::make('Mitarbeiter-Zuordnung')
                    ->schema([
                        Forms\Components\CheckboxList::make('assigned_staff')
                            ->label('Zugeordnete Mitarbeiter')
                            ->options(
                                Staff::where('active', true)
                                    ->pluck('name', 'id')
                                    ->toArray()
                            )
                            ->columns(2)
                            ->searchable(),
                    ]),

                Forms\Components\Section::make('Cal.com Integration')
                    ->schema([
                        Forms\Components\TextInput::make('calcom_numeric_event_type_id')
                            ->label('Cal.com Event-Type ID')
                            ->numeric()
                            ->disabled(),
                        
                        Forms\Components\Select::make('sync_status')
                            ->label('Sync-Status')
                            ->options([
                                'pending' => 'Ausstehend',
                                'synced' => 'Synchronisiert',
                                'failed' => 'Fehlgeschlagen',
                                'deleted' => 'Gelöscht'
                            ])
                            ->disabled(),
                        
                        Forms\Components\DateTimePicker::make('last_synced_at')
                            ->label('Zuletzt synchronisiert')
                            ->disabled(),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Event-Name')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('duration_minutes')
                    ->label('Dauer')
                    ->suffix(' Min')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('price')
                    ->label('Preis')
                    ->prefix('€ ')
                    ->sortable(),
                
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktiv')
                    ->boolean(),
                
                Tables\Columns\BadgeColumn::make('sync_status')
                    ->label('Sync-Status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'synced',
                        'danger' => 'failed',
                        'secondary' => 'deleted',
                    ]),
                
                Tables\Columns\TextColumn::make('assigned_staff_count')
                    ->label('Mitarbeiter')
                    ->getStateUsing(function ($record) {
                        return $record->assignedStaff()->count();
                    }),
                
                Tables\Columns\TextColumn::make('last_synced_at')
                    ->label('Sync')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('sync_status')
                    ->options([
                        'pending' => 'Ausstehend',
                        'synced' => 'Synchronisiert',
                        'failed' => 'Fehlgeschlagen',
                    ]),
                
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Aktiv'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                
                Tables\Actions\Action::make('sync_calcom')
                    ->label('Mit Cal.com synchen')
                    ->icon('heroicon-o-arrow-path')
                    ->action(function ($record) {
                        $syncService = new \App\Services\CalcomEventSyncService();
                        if ($syncService->syncAllEventTypes()) {
                            Notification::make()
                                ->title('Synchronisation erfolgreich')
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Synchronisation fehlgeschlagen')
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    
                    Tables\Actions\BulkAction::make('sync_selected')
                        ->label('Ausgewählte synchronisieren')
                        ->icon('heroicon-o-arrow-path')
                        ->action(function () {
                            $syncService = new \App\Services\CalcomEventSyncService();
                            $syncService->syncAllEventTypes();
                            
                            Notification::make()
                                ->title('Bulk-Synchronisation abgeschlossen')
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->headerActions([
                Tables\Actions\Action::make('full_sync')
                    ->label('Vollständige Synchronisation')
                    ->icon('heroicon-o-arrow-path')
                    ->color('primary')
                    ->action(function () {
                        \Artisan::call('calcom:sync', ['--reset' => true]);
                        
                        Notification::make()
                            ->title('Vollständige Synchronisation gestartet')
                            ->body('Event-Types und Mitarbeiter werden synchronisiert...')
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUnifiedEventTypes::route('/'),
            'create' => Pages\CreateUnifiedEventType::route('/create'),
            'edit' => Pages\EditUnifiedEventType::route('/{record}/edit'),
        ];
    }
}
