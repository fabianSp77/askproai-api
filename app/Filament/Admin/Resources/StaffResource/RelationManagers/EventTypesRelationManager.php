<?php

namespace App\Filament\Admin\Resources\StaffResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Notifications\Notification;
use App\Models\CalcomEventType;
use Illuminate\Support\HtmlString;

class EventTypesRelationManager extends RelationManager
{
    protected static string $relationship = 'eventTypes';
    
    protected static ?string $title = 'Zugeordnete Event-Types';
    
    protected static ?string $modelLabel = 'Event-Type';
    protected static ?string $pluralModelLabel = 'Event-Types';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('event_type_id')
                    ->label('Event-Type')
                    ->options(function () {
                        return CalcomEventType::where('company_id', $this->ownerRecord->company_id)
                            ->where('is_active', true)
                            ->pluck('name', 'id');
                    })
                    ->required()
                    ->searchable()
                    ->preload()
                    ->helperText('Wählen Sie einen Event-Type aus, der diesem Mitarbeiter zugeordnet werden soll.'),
                    
                Forms\Components\Toggle::make('is_primary')
                    ->label('Primärer Event-Type')
                    ->helperText('Markiert diesen Event-Type als primären Service des Mitarbeiters.'),
                    
                Forms\Components\TextInput::make('custom_duration')
                    ->label('Individuelle Dauer (Min.)')
                    ->numeric()
                    ->nullable()
                    ->helperText('Überschreibt die Standard-Dauer für diesen Mitarbeiter.'),
                    
                Forms\Components\TextInput::make('custom_price')
                    ->label('Individueller Preis')
                    ->numeric()
                    ->prefix('€')
                    ->nullable()
                    ->helperText('Überschreibt den Standard-Preis für diesen Mitarbeiter.'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->heading(fn () => new HtmlString(
                'Event-Types für <strong>' . $this->ownerRecord->name . '</strong> bei ' . $this->ownerRecord->company->name
            ))
            ->description('Diese Zuordnungen gelten nur für dieses Unternehmen. Der Mitarbeiter kann in anderen Unternehmen andere Event-Types haben.')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Event-Type')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->description),
                    
                Tables\Columns\TextColumn::make('duration_minutes')
                    ->label('Dauer')
                    ->suffix(' Min.')
                    ->sortable()
                    ->alignCenter(),
                    
                Tables\Columns\TextColumn::make('price')
                    ->label('Preis')
                    ->money('EUR')
                    ->sortable()
                    ->alignCenter(),
                    
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktiv')
                    ->boolean()
                    ->alignCenter(),
                    
                Tables\Columns\IconColumn::make('is_team_event')
                    ->label('Team')
                    ->boolean()
                    ->tooltip('Team-Event')
                    ->alignCenter(),
                    
                Tables\Columns\TextColumn::make('pivot.calcom_user_id')
                    ->label('Cal.com ID')
                    ->placeholder('Nicht verknüpft')
                    ->copyable()
                    ->copyMessage('Cal.com ID kopiert')
                    ->alignCenter(),
                    
                Tables\Columns\IconColumn::make('pivot.is_primary')
                    ->label('Primär')
                    ->boolean()
                    ->trueIcon('heroicon-o-star')
                    ->falseIcon('heroicon-o-star')
                    ->trueColor('warning')
                    ->falseColor('gray')
                    ->alignCenter(),
                    
                Tables\Columns\TextColumn::make('assignedStaff_count')
                    ->label('Mitarbeiter')
                    ->counts('assignedStaff')
                    ->suffix(' zugeordnet')
                    ->alignCenter(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Nur aktive'),
                    
                Tables\Filters\TernaryFilter::make('is_team_event')
                    ->label('Team-Events'),
                    
                Tables\Filters\Filter::make('has_calcom')
                    ->label('Mit Cal.com Verknüpfung')
                    ->query(fn (Builder $query): Builder => 
                        $query->whereNotNull('pivot.calcom_user_id')
                    ),
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->label('Event-Type zuordnen')
                    ->modalHeading('Event-Type zuordnen')
                    ->modalDescription('Wählen Sie einen Event-Type aus, der diesem Mitarbeiter zugeordnet werden soll.')
                    ->form(fn (Tables\Actions\AttachAction $action): array => [
                        $action->getRecordSelect()
                            ->label('Event-Type')
                            ->options(function () {
                                // Get already attached event type IDs
                                $attachedIds = $this->ownerRecord->eventTypes()->pluck('calcom_event_types.id');
                                
                                return CalcomEventType::where('company_id', $this->ownerRecord->company_id)
                                    ->where('is_active', true)
                                    ->whereNotIn('id', $attachedIds)
                                    ->pluck('name', 'id');
                            })
                            ->helperText('Nur aktive Event-Types des Unternehmens werden angezeigt.'),
                            
                        Forms\Components\Toggle::make('is_primary')
                            ->label('Als primären Event-Type markieren')
                            ->default(false),
                            
                        Forms\Components\TextInput::make('custom_duration')
                            ->label('Individuelle Dauer (Min.)')
                            ->numeric()
                            ->nullable(),
                            
                        Forms\Components\TextInput::make('custom_price')
                            ->label('Individueller Preis (€)')
                            ->numeric()
                            ->prefix('€')
                            ->nullable(),
                    ])
                    ->preloadRecordSelect()
                    ->successNotificationTitle('Event-Type erfolgreich zugeordnet'),
                    
                Tables\Actions\Action::make('sync_from_calcom')
                    ->label('Von Cal.com synchronisieren')
                    ->icon('heroicon-o-arrow-path')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('Von Cal.com synchronisieren')
                    ->modalDescription('Dies wird die Event-Type-Zuordnungen von Cal.com abrufen und mit diesem Mitarbeiter verknüpfen.')
                    ->modalSubmitActionLabel('Synchronisieren')
                    ->action(function () {
                        if (!$this->ownerRecord->calcom_user_id) {
                            Notification::make()
                                ->title('Keine Cal.com Verknüpfung')
                                ->body('Dieser Mitarbeiter hat keine Cal.com User ID. Bitte verknüpfen Sie zuerst den Cal.com Account.')
                                ->danger()
                                ->send();
                            return;
                        }
                        
                        // TODO: Implement Cal.com sync logic
                        Notification::make()
                            ->title('Synchronisation gestartet')
                            ->body('Die Event-Types werden von Cal.com abgerufen...')
                            ->success()
                            ->send();
                    })
                    ->visible(fn () => !empty($this->ownerRecord->calcom_user_id)),
            ])
            ->actions([
                Tables\Actions\Action::make('make_primary')
                    ->label('Primär')
                    ->icon('heroicon-o-star')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Als primären Event-Type markieren')
                    ->modalDescription('Möchten Sie diesen Event-Type als primären Service für diesen Mitarbeiter markieren?')
                    ->action(function ($record) {
                        // Remove primary flag from all other event types
                        \DB::table('staff_event_types')
                            ->where('staff_id', $this->ownerRecord->id)
                            ->update(['is_primary' => false]);
                            
                        // Set this one as primary
                        \DB::table('staff_event_types')
                            ->where('staff_id', $this->ownerRecord->id)
                            ->where('event_type_id', $record->id)
                            ->update(['is_primary' => true]);
                            
                        Notification::make()
                            ->title('Primärer Event-Type gesetzt')
                            ->success()
                            ->send();
                    })
                    ->visible(fn ($record) => !$record->pivot->is_primary),
                    
                Tables\Actions\EditAction::make()
                    ->label('Anpassen')
                    ->modalHeading('Event-Type Anpassungen')
                    ->form([
                        Forms\Components\Toggle::make('is_primary')
                            ->label('Primärer Event-Type'),
                            
                        Forms\Components\TextInput::make('custom_duration')
                            ->label('Individuelle Dauer (Min.)')
                            ->numeric()
                            ->nullable(),
                            
                        Forms\Components\TextInput::make('custom_price')
                            ->label('Individueller Preis (€)')
                            ->numeric()
                            ->prefix('€')
                            ->nullable(),
                    ])
                    ->using(function ($record, array $data) {
                        \DB::table('staff_event_types')
                            ->where('staff_id', $this->ownerRecord->id)
                            ->where('event_type_id', $record->id)
                            ->update([
                                'is_primary' => $data['is_primary'] ?? false,
                                'custom_duration' => $data['custom_duration'],
                                'custom_price' => $data['custom_price'],
                                'updated_at' => now(),
                            ]);
                            
                        return $record;
                    }),
                    
                Tables\Actions\DetachAction::make()
                    ->label('Entfernen')
                    ->modalHeading('Event-Type entfernen')
                    ->modalDescription('Möchten Sie diese Zuordnung wirklich entfernen?')
                    ->successNotificationTitle('Event-Type entfernt'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make()
                        ->label('Ausgewählte entfernen')
                        ->modalHeading('Event-Types entfernen')
                        ->modalDescription('Möchten Sie die ausgewählten Zuordnungen wirklich entfernen?')
                        ->successNotificationTitle('Event-Types entfernt'),
                ]),
            ])
            ->emptyStateHeading('Keine Event-Types zugeordnet')
            ->emptyStateDescription('Ordnen Sie diesem Mitarbeiter Event-Types zu, damit er für diese Services gebucht werden kann.')
            ->emptyStateIcon('heroicon-o-calendar')
            ->emptyStateActions([
                Tables\Actions\AttachAction::make()
                    ->label('Ersten Event-Type zuordnen'),
            ]);
    }
    
    protected function canAttach(): bool
    {
        return true;
    }
    
    protected function canDetach($record): bool
    {
        return true;
    }
    
    protected function canEdit($record): bool
    {
        return true;
    }
}