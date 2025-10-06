<?php

namespace App\Filament\Resources\ServiceResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;

class StaffRelationManager extends RelationManager
{
    protected static string $relationship = 'staff';

    protected static ?string $title = 'Mitarbeiter';

    protected static ?string $icon = 'heroicon-o-users';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Mitarbeiter-Service Zuordnung')
                    ->description('Weisen Sie Mitarbeiter zu diesem Service zu und konfigurieren Sie spezifische Einstellungen')
                    ->schema([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\Select::make('staff_id')
                                ->label('Mitarbeiter')
                                ->relationship('staff', 'name')
                                ->required()
                                ->searchable()
                                ->preload()
                                ->disabled(fn ($operation) => $operation === 'edit'),

                            Forms\Components\Toggle::make('is_primary')
                                ->label('Primärer Mitarbeiter')
                                ->helperText('Hauptverantwortlicher für diesen Service')
                                ->inline(),
                        ]),

                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\Toggle::make('can_book')
                                ->label('Kann Termine buchen')
                                ->helperText('Darf Termine für diesen Service entgegennehmen')
                                ->default(true)
                                ->inline(),

                            Forms\Components\Toggle::make('is_active')
                                ->label('Aktiv')
                                ->helperText('Mitarbeiter ist aktiv für diesen Service')
                                ->default(true)
                                ->inline(),
                        ]),

                        Forms\Components\Section::make('Service-spezifische Anpassungen')
                            ->description('Optional: Überschreiben Sie die Standard-Service-Einstellungen für diesen Mitarbeiter')
                            ->schema([
                                Forms\Components\Grid::make(3)->schema([
                                    Forms\Components\TextInput::make('custom_price')
                                        ->label('Spezieller Preis')
                                        ->prefix('€')
                                        ->numeric()
                                        ->step(0.01)
                                        ->placeholder('Standard: €' . $this->ownerRecord->price ?? '0'),

                                    Forms\Components\TextInput::make('custom_duration_minutes')
                                        ->label('Spezielle Dauer')
                                        ->suffix('min')
                                        ->numeric()
                                        ->placeholder('Standard: ' . ($this->ownerRecord->duration_minutes ?? '30') . ' min'),

                                    Forms\Components\TextInput::make('commission_rate')
                                        ->label('Provision')
                                        ->suffix('%')
                                        ->numeric()
                                        ->step(0.01)
                                        ->min(0)
                                        ->max(100)
                                        ->placeholder('z.B. 15.5'),
                                ]),

                                Forms\Components\KeyValue::make('specialization_notes')
                                    ->label('Spezialisierungs-Notizen')
                                    ->addActionLabel('Notiz hinzufügen')
                                    ->keyLabel('Thema')
                                    ->valueLabel('Notiz')
                                    ->reorderable()
                                    ->columnSpanFull(),

                                Forms\Components\DateTimePicker::make('assigned_at')
                                    ->label('Zugewiesen am')
                                    ->default(now())
                                    ->native(false)
                                    ->displayFormat('d.m.Y H:i'),
                            ])
                            ->collapsed(),
                    ])
                    ->columns(1),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->modifyQueryUsing(fn (Builder $query) =>
                $query->with(['branch:id,name', 'company:id,name'])
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-m-user')
                    ->description(fn ($record) => $record->email)
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('position')
                    ->label('Position')
                    ->badge()
                    ->color('info')
                    ->placeholder('Nicht angegeben'),

                Tables\Columns\IconColumn::make('pivot.is_primary')
                    ->label('Primär')
                    ->boolean()
                    ->trueIcon('heroicon-o-star')
                    ->falseIcon('heroicon-o-minus')
                    ->trueColor('warning')
                    ->falseColor('gray'),

                Tables\Columns\IconColumn::make('pivot.can_book')
                    ->label('Kann buchen')
                    ->boolean()
                    ->trueIcon('heroicon-o-calendar-days')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('pivot.custom_price')
                    ->label('Spezieller Preis')
                    ->money('EUR')
                    ->placeholder(fn () => '€' . number_format($this->ownerRecord->price ?? 0, 2))
                    ->description('Standard wenn leer')
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('pivot.custom_duration_minutes')
                    ->label('Spezielle Dauer')
                    ->suffix(' min')
                    ->placeholder(fn () => ($this->ownerRecord->duration_minutes ?? 30) . ' min')
                    ->description('Standard wenn leer')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('pivot.commission_rate')
                    ->label('Provision')
                    ->suffix('%')
                    ->placeholder('Keine')
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('branch.name')
                    ->label('Filiale')
                    ->badge()
                    ->color('gray')
                    ->toggleable(),

                Tables\Columns\IconColumn::make('pivot.is_active')
                    ->label('Aktiv')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('pivot.assigned_at')
                    ->label('Zugewiesen')
                    ->dateTime('d.m.Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('primary')
                    ->label('Nur primäre Mitarbeiter')
                    ->query(fn (Builder $query): Builder => $query->wherePivot('is_primary', true)),

                Tables\Filters\Filter::make('can_book')
                    ->label('Kann Termine buchen')
                    ->query(fn (Builder $query): Builder => $query->wherePivot('can_book', true))
                    ->default(),

                Tables\Filters\Filter::make('active')
                    ->label('Aktive Mitarbeiter')
                    ->query(fn (Builder $query): Builder => $query->wherePivot('is_active', true))
                    ->default(),

                Tables\Filters\Filter::make('has_custom_price')
                    ->label('Mit speziellem Preis')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('service_staff.custom_price')),

                Tables\Filters\Filter::make('has_commission')
                    ->label('Mit Provision')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('service_staff.commission_rate')),
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->label('Mitarbeiter hinzufügen')
                    ->icon('heroicon-m-plus')
                    ->modalHeading('Mitarbeiter zu Service zuweisen')
                    ->modalDescription('Fügen Sie einen Mitarbeiter zu diesem Service hinzu und konfigurieren Sie die Einstellungen')
                    ->preloadRecordSelect()
                    ->recordSelectSearchColumns(['name', 'email', 'position'])
                    ->form(fn (Forms\Form $form): Forms\Form => $this->form($form))
                    ->after(function () {
                        Notification::make()
                            ->title('Mitarbeiter zugewiesen')
                            ->body('Der Mitarbeiter wurde erfolgreich zu diesem Service hinzugefügt.')
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make()
                        ->label('Einstellungen bearbeiten')
                        ->icon('heroicon-m-cog-6-tooth')
                        ->modalHeading('Service-Einstellungen für Mitarbeiter bearbeiten'),

                    Tables\Actions\Action::make('togglePrimary')
                        ->label(fn ($record) => $record->pivot->is_primary ? 'Als sekundär markieren' : 'Als primär markieren')
                        ->icon('heroicon-m-star')
                        ->color(fn ($record) => $record->pivot->is_primary ? 'warning' : 'gray')
                        ->action(function ($record) {
                            // If making primary, remove primary status from others
                            if (!$record->pivot->is_primary) {
                                $this->ownerRecord->staff()->updateExistingPivot(
                                    $this->ownerRecord->staff()->wherePivot('is_primary', true)->pluck('staff.id'),
                                    ['is_primary' => false]
                                );
                            }

                            $this->ownerRecord->staff()->updateExistingPivot($record->id, [
                                'is_primary' => !$record->pivot->is_primary
                            ]);

                            Notification::make()
                                ->title('Status geändert')
                                ->body($record->name . ' wurde als ' . (!$record->pivot->is_primary ? 'primärer' : 'sekundärer') . ' Mitarbeiter markiert.')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\Action::make('toggleBooking')
                        ->label(fn ($record) => $record->pivot->can_book ? 'Buchung deaktivieren' : 'Buchung aktivieren')
                        ->icon('heroicon-m-calendar-days')
                        ->color(fn ($record) => $record->pivot->can_book ? 'success' : 'danger')
                        ->action(function ($record) {
                            $this->ownerRecord->staff()->updateExistingPivot($record->id, [
                                'can_book' => !$record->pivot->can_book
                            ]);

                            Notification::make()
                                ->title('Buchungsstatus geändert')
                                ->body($record->name . ' kann ' . (!$record->pivot->can_book ? 'jetzt' : 'nicht mehr') . ' Termine für diesen Service buchen.')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\Action::make('viewAppointments')
                        ->label('Termine anzeigen')
                        ->icon('heroicon-m-calendar')
                        ->color('info')
                        ->url(fn ($record) => route('filament.admin.resources.appointments.index', [
                            'tableFilters[staff_id][value]' => $record->id,
                            'tableFilters[service_id][value]' => $this->ownerRecord->id,
                        ]))
                        ->openUrlInNewTab(),

                    Tables\Actions\DetachAction::make()
                        ->label('Entfernen')
                        ->icon('heroicon-m-trash')
                        ->requiresConfirmation()
                        ->modalDescription('Sind Sie sicher, dass Sie diesen Mitarbeiter von diesem Service entfernen möchten?'),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('bulkToggleBooking')
                        ->label('Buchung umschalten')
                        ->icon('heroicon-o-calendar-days')
                        ->color('info')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $this->ownerRecord->staff()->updateExistingPivot($record->id, [
                                    'can_book' => !$record->pivot->can_book
                                ]);
                            }

                            Notification::make()
                                ->title('Buchungsstatus geändert')
                                ->body('Der Buchungsstatus für ' . count($records) . ' Mitarbeiter wurde geändert.')
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation(),

                    Tables\Actions\BulkAction::make('bulkSetCommission')
                        ->label('Provision setzen')
                        ->icon('heroicon-o-currency-euro')
                        ->color('warning')
                        ->form([
                            Forms\Components\TextInput::make('commission_rate')
                                ->label('Provision (%)')
                                ->numeric()
                                ->step(0.01)
                                ->min(0)
                                ->max(100)
                                ->required()
                                ->suffix('%'),
                        ])
                        ->action(function ($records, array $data) {
                            foreach ($records as $record) {
                                $this->ownerRecord->staff()->updateExistingPivot($record->id, [
                                    'commission_rate' => $data['commission_rate']
                                ]);
                            }

                            Notification::make()
                                ->title('Provision gesetzt')
                                ->body('Die Provision wurde für ' . count($records) . ' Mitarbeiter auf ' . $data['commission_rate'] . '% gesetzt.')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\DetachBulkAction::make()
                        ->label('Entfernen'),
                ]),
            ])
            ->emptyStateHeading('Keine Mitarbeiter zugewiesen')
            ->emptyStateDescription('Weisen Sie Mitarbeiter zu diesem Service zu, damit sie Termine entgegennehmen können.')
            ->emptyStateIcon('heroicon-o-users');
    }

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        return $ownerRecord->staff()->count();
    }
}