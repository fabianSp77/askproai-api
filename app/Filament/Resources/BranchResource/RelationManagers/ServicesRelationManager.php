<?php

namespace App\Filament\Resources\BranchResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ServicesRelationManager extends RelationManager
{
    protected static string $relationship = 'services';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('display_name')
                    ->label('Anzeigename (optional)')
                    ->placeholder('Leer lassen für cal.com Namen')
                    ->maxLength(255)
                    ->helperText('Optionaler Name für die Plattform-Anzeige'),
                Forms\Components\Textarea::make('description')
                    ->maxLength(500)
                    ->rows(3),
                Forms\Components\TextInput::make('duration_minutes')
                    ->numeric()
                    ->suffix('minutes')
                    ->required(),
                Forms\Components\TextInput::make('price')
                    ->numeric()
                    ->prefix('€')
                    ->required(),
                Forms\Components\TextInput::make('buffer_time_minutes')
                    ->numeric()
                    ->suffix('minutes')
                    ->default(0),
                Forms\Components\Select::make('category')
                    ->options([
                        'consultation' => 'Consultation',
                        'treatment' => 'Treatment',
                        'diagnostic' => 'Diagnostic',
                        'preventive' => 'Preventive',
                        'cosmetic' => 'Cosmetic',
                        'emergency' => 'Emergency',
                    ]),
                Forms\Components\ColorPicker::make('color')
                    ->default('#3B82F6'),
                Forms\Components\Toggle::make('is_active')
                    ->default(true),
                Forms\Components\Toggle::make('is_online')
                    ->label('Online Booking')
                    ->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\IconColumn::make('pivot.is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-minus-circle')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->tooltip(fn ($state) => $state ? 'Service ist aktiv' : 'Service ist inaktiv'),

                Tables\Columns\TextColumn::make('name')
                    ->label('Service')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(function ($record) {
                        $displayName = $record->display_name ?? $record->name;
                        // Limit display name if too long
                        return Str::limit($displayName, 40, '...');
                    })
                    ->description(function ($record) {
                        if ($record->calcom_name) {
                            return 'Cal.com: ' . Str::limit($record->calcom_name, 45, '...');
                        }
                        return null;
                    }),

                Tables\Columns\TextColumn::make('calcom_event_type_id')
                    ->label('Event ID')
                    ->badge()
                    ->color('info')
                    ->searchable(),

                Tables\Columns\TextColumn::make('duration_minutes')
                    ->label('Dauer')
                    ->suffix(' min')
                    ->sortable(),

                Tables\Columns\TextColumn::make('price')
                    ->money('EUR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('category')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Service Aktiv')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('is_online')
                    ->label('Online')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->options([
                        'consultation' => 'Consultation',
                        'treatment' => 'Treatment',
                        'diagnostic' => 'Diagnostic',
                        'preventive' => 'Preventive',
                        'cosmetic' => 'Cosmetic',
                        'emergency' => 'Emergency',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Aktiv')
                    ->boolean()
                    ->trueLabel('Active services')
                    ->falseLabel('Inactive services')
                    ->native(false),
                Tables\Filters\TernaryFilter::make('is_online')
                    ->label('Online Booking')
                    ->boolean()
                    ->trueLabel('Online booking enabled')
                    ->falseLabel('Online booking disabled')
                    ->native(false),
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->label('Service hinzufügen')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->preloadRecordSelect()
                    ->recordSelectOptionsQuery(function ($query) {
                        // Get the company from the branch
                        $company = $this->ownerRecord->company;

                        // Filter by company_id first
                        $query->where('company_id', $company->id);

                        // If company has a team, only show services with cal.com event types
                        if ($company && $company->calcom_team_id > 0) {
                            $query->whereNotNull('calcom_event_type_id');
                        }

                        return $query;
                    })
                    ->modalHeading('Service zu Filiale hinzufügen')
                    ->modalDescription(function () {
                        $company = $this->ownerRecord->company;
                        if ($company && $company->calcom_team_id > 0) {
                            return "Wählen Sie einen Service aus dem Team-Pool (Team ID: {$company->calcom_team_id}) aus.";
                        }
                        return 'Wählen Sie einen Service aus dem Unternehmens-Pool aus.';
                    })
                    ->form(fn (Tables\Actions\AttachAction $action): array => [
                        $action->getRecordSelect()
                            ->label('Service auswählen')
                            ->helperText('Nur Services des Unternehmens werden angezeigt'),

                        Forms\Components\Section::make('Filial-Einstellungen')
                            ->description('Diese Einstellungen gelten nur für diese Filiale')
                            ->schema([
                                Forms\Components\Toggle::make('is_active')
                                    ->label('Service sofort aktivieren')
                                    ->default(true)
                                    ->helperText('Der Service ist nach dem Hinzufügen direkt buchbar'),

                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('duration_override_minutes')
                                            ->label('Dauer überschreiben')
                                            ->numeric()
                                            ->suffix('Minuten')
                                            ->placeholder('Standard verwenden')
                                            ->helperText('Optional: Filial-spezifische Dauer'),

                                        Forms\Components\TextInput::make('price_override')
                                            ->label('Preis überschreiben')
                                            ->numeric()
                                            ->prefix('€')
                                            ->placeholder('Standard verwenden')
                                            ->helperText('Optional: Filial-spezifischer Preis'),
                                    ]),
                            ])
                    ]),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    // Toggle Aktivierung mit klarer Semantik
                    Tables\Actions\Action::make('toggleActive')
                        ->label(fn ($record) => $record->pivot->is_active ? 'Deaktivieren' : 'Aktivieren')
                        ->icon(fn ($record) => $record->pivot->is_active ? 'heroicon-o-pause' : 'heroicon-o-play')
                        ->color(fn ($record) => $record->pivot->is_active ? 'warning' : 'success')
                        ->requiresConfirmation()
                        ->modalHeading(fn ($record) => $record->pivot->is_active ? 'Service deaktivieren?' : 'Service aktivieren?')
                        ->modalDescription(fn ($record) => $record->pivot->is_active
                            ? 'Der Service wird für diese Filiale deaktiviert und ist nicht mehr buchbar.'
                            : 'Der Service wird für diese Filiale aktiviert und ist buchbar.')
                        ->modalSubmitActionLabel(fn ($record) => $record->pivot->is_active ? 'Ja, deaktivieren' : 'Ja, aktivieren')
                        ->action(function ($record) {
                            $record->pivot->is_active = !$record->pivot->is_active;
                            $record->pivot->save();
                        }),

                    // Filial-spezifische Einstellungen bearbeiten
                    Tables\Actions\EditAction::make()
                        ->label('Filial-Einstellungen')
                        ->icon('heroicon-o-adjustments-horizontal')
                        ->color('gray')
                        ->modalHeading(fn ($record) => 'Filial-Einstellungen für: ' . ($record->display_name ?? $record->name))
                        ->form(fn ($record) => [
                            Forms\Components\Section::make('Status')
                                ->schema([
                                    Forms\Components\Toggle::make('pivot.is_active')
                                        ->label('Service für Filiale aktiv')
                                        ->helperText('Aktiviert oder deaktiviert den Service für diese Filiale'),
                                ])
                                ->columns(1),

                            Forms\Components\Section::make('Überschreibungen')
                                ->description('Leer lassen um Standard-Werte zu verwenden')
                                ->schema([
                                    Forms\Components\TextInput::make('pivot.duration_override_minutes')
                                        ->label('Dauer überschreiben')
                                        ->numeric()
                                        ->suffix('Minuten')
                                        ->placeholder($record->duration_minutes . ' min (Standard)')
                                        ->helperText('Standard: ' . $record->duration_minutes . ' Minuten'),

                                    Forms\Components\TextInput::make('pivot.price_override')
                                        ->label('Preis überschreiben')
                                        ->numeric()
                                        ->prefix('€')
                                        ->placeholder(number_format($record->price, 2) . ' (Standard)')
                                        ->helperText('Standard: €' . number_format($record->price, 2)),
                                ])
                                ->columns(2),
                        ]),

                    // Service von Filiale entfernen
                    Tables\Actions\DetachAction::make()
                        ->label('Entfernen')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Service von Filiale entfernen?')
                        ->modalDescription('Der Service wird von dieser Filiale entfernt. Die Service-Stammdaten bleiben erhalten.')
                        ->modalSubmitActionLabel('Ja, entfernen'),
                ])
                    ->icon('heroicon-m-ellipsis-horizontal')
                    ->size('sm')
                    ->color('gray')
                    ->button()
                    ->label('Aktionen'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('activateSelected')
                        ->label('Ausgewählte aktivieren')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->pivot->is_active = true;
                                $record->pivot->save();
                            }
                        }),
                    Tables\Actions\BulkAction::make('deactivateSelected')
                        ->label('Ausgewählte deaktivieren')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->pivot->is_active = false;
                                $record->pivot->save();
                            }
                        }),
                    Tables\Actions\DetachBulkAction::make(),
                ]),
            ]);
    }
}