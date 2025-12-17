<?php

namespace App\Filament\Resources\AppointmentResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Modifications Relation Manager for Appointments
 *
 * Displays all modifications (reschedules, cancellations) for an appointment
 * in a table format with detailed metadata.
 *
 * Implementation: 2025-10-11
 * Implemented for: Call 834 History Visualization
 */
class ModificationsRelationManager extends RelationManager
{
    protected static string $relationship = 'modifications';

    protected static ?string $title = '📊 Änderungs-Audit';

    protected static ?string $icon = 'heroicon-o-document-magnifying-glass';

    protected static ?string $recordTitleAttribute = 'id';

    /**
     * Get table heading with contextual help
     */
    public function getTableHeading(): ?string
    {
        return '📊 Änderungs-Audit (nur Umbuchungen/Stornierungen)';
    }

    /**
     * Get table description
     */
    public function getTableDescription(): ?string
    {
        return 'Filterbare Tabelle aller Änderungen für Compliance-Prüfung. Für vollständige Termin-Geschichte siehe "Termin-Lebenslauf" Widget unten.';
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('modification_type')
                    ->label('Typ')
                    ->options([
                        'reschedule' => 'Umbuchung',
                        'cancel' => 'Stornierung',
                        'create' => 'Erstellung',
                    ])
                    ->required()
                    ->disabled(),

                Forms\Components\DateTimePicker::make('created_at')
                    ->label('Zeitpunkt')
                    ->disabled(),

                Forms\Components\TextInput::make('modified_by_type')
                    ->label('Durchgeführt von')
                    ->disabled(),

                Forms\Components\Toggle::make('within_policy')
                    ->label('Innerhalb Richtlinien')
                    ->disabled(),

                Forms\Components\TextInput::make('fee_charged')
                    ->label('Gebühr')
                    ->numeric()
                    ->prefix('€')
                    ->disabled(),

                Forms\Components\Textarea::make('reason')
                    ->label('Grund')
                    ->rows(3)
                    ->disabled()
                    ->columnSpanFull(),

                Forms\Components\KeyValue::make('metadata')
                    ->label('Metadaten')
                    ->disabled()
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['appointment', 'customer']))
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Zeitpunkt')
                    ->dateTime('d.m.Y H:i:s')
                    ->sortable()
                    ->icon('heroicon-o-clock')
                    ->description(fn ($record) => $record->created_at->diffForHumans()),

                Tables\Columns\TextColumn::make('modification_type')
                    ->label('Typ')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'create' => 'success',
                        'reschedule' => 'info',
                        'cancel' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'create' => '✅ Erstellung',
                        'reschedule' => '🔄 Umbuchung',
                        'cancel' => '❌ Stornierung',
                        default => ucfirst($state),
                    })
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('modified_by_type')
                    ->label('Durchgeführt von')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'System' => '🤖 System',
                        'Customer' => '👤 Kunde',
                        'Admin' => '👨‍💼 Administrator',
                        'Staff' => '👥 Mitarbeiter',
                        null => '-',
                        default => $state,
                    })
                    ->searchable()
                    ->sortable(),

                Tables\Columns\IconColumn::make('within_policy')
                    ->label('Richtlinien')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('warning')
                    ->tooltip(fn ($record) => $this->getPolicyTooltipForModification($record))
                    ->sortable(),

                Tables\Columns\TextColumn::make('fee_charged')
                    ->label('Gebühr')
                    ->money('EUR')
                    ->sortable()
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'gray')
                    ->icon(fn ($state) => $state > 0 ? 'heroicon-o-currency-euro' : null),

                Tables\Columns\TextColumn::make('reason')
                    ->label('Grund')
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->reason)
                    ->searchable(),

                Tables\Columns\TextColumn::make('call_id')
                    ->label('Call')
                    ->state(fn ($record) => $record->metadata['call_id'] ?? null)
                    ->formatStateUsing(fn ($state) => $state ? "📞 #{$state}" : '-')
                    ->url(fn ($record) =>
                        isset($record->metadata['call_id'])
                            ? route('filament.admin.resources.calls.view', ['record' => $record->metadata['call_id']])
                            : null
                    )
                    ->openUrlInNewTab()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('modification_type')
                    ->label('Typ')
                    ->options([
                        'create' => 'Erstellung',
                        'reschedule' => 'Umbuchung',
                        'cancel' => 'Stornierung',
                    ])
                    ->multiple(),

                Tables\Filters\TernaryFilter::make('within_policy')
                    ->label('Richtlinien-Status')
                    ->placeholder('Alle')
                    ->trueLabel('Innerhalb Richtlinien')
                    ->falseLabel('Außerhalb Richtlinien'),

                Tables\Filters\Filter::make('has_fee')
                    ->label('Mit Gebühr')
                    ->query(fn (Builder $query): Builder => $query->where('fee_charged', '>', 0)),
            ])
            ->headerActions([
                // No create action - modifications are created automatically by the system
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalHeading('Änderungs-Details')
                    ->modalContent(fn ($record) => view('filament.resources.appointment-resource.modals.modification-details', [
                        'modification' => $record,
                    ])),
            ])
            ->bulkActions([
                // No bulk actions needed for read-only modifications
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s') // Auto-refresh every 30 seconds
            ->emptyStateHeading('Keine Änderungen')
            ->emptyStateDescription('Für diesen Termin wurden noch keine Änderungen vorgenommen.')
            ->emptyStateIcon('heroicon-o-check-circle');
    }

    /**
     * Get policy tooltip for modification record
     *
     * Shows rule-by-rule breakdown of policy compliance
     * User Request: 2025-10-11 - Show which rules passed/failed
     *
     * @param \App\Models\AppointmentModification $record
     * @return string Formatted tooltip text
     */
    protected function getPolicyTooltipForModification($record): string
    {
        $metadata = $record->metadata;

        // FIX 2025-10-11: Validate metadata is array (could be NULL from legacy data)
        if (!is_array($metadata)) {
            \Log::warning('ModificationsRM: Invalid metadata type', [
                'modification_id' => $record->id,
                'type' => gettype($metadata)
            ]);
            return "⚠️ Metadaten ungültig";
        }

        $withinPolicy = $record->within_policy;

        $rules = [];
        $passedCount = 0;
        $totalCount = 0;

        // Rule 1: Hours Notice
        if (isset($metadata['hours_notice']) && isset($metadata['policy_required'])) {
            $totalCount++;
            $hours = round($metadata['hours_notice'], 1);
            $required = $metadata['policy_required'];

            if ($hours >= $required) {
                $passedCount++;
                $buffer = round($hours - $required, 1);
                $rules[] = "✅ Vorwarnzeit: {$hours}h (min. {$required}h) +{$buffer}h Puffer";
            } else {
                $shortage = round($required - $hours, 1);
                $rules[] = "❌ Vorwarnzeit: {$hours}h (min. {$required}h) -{$shortage}h zu kurz";
            }
        }

        // Rule 2: Quota (if in metadata)
        if (isset($metadata['quota_used']) && isset($metadata['quota_max'])) {
            $totalCount++;
            $used = $metadata['quota_used'];
            $max = $metadata['quota_max'];

            if ($used <= $max) {
                $passedCount++;
                $rules[] = "✅ Monatslimit: {$used}/{$max}";
            } else {
                $rules[] = "❌ Monatslimit: {$used}/{$max} überschritten";
            }
        }

        // Rule 3: Fee
        $totalCount++;
        if ($record->fee_charged == 0) {
            $passedCount++;
            $rules[] = "✅ Gebühr: Keine";
        } else {
            $passedCount++; // Fee doesn't mean failure if within_policy
            $rules[] = "⚠️ Gebühr: " . number_format($record->fee_charged, 2) . " €";
        }

        // Summary
        if ($withinPolicy) {
            $summary = "✅ {$passedCount} von {$totalCount} Regeln erfüllt";
        } else {
            $failedCount = $totalCount - $passedCount;
            $summary = "⚠️ {$failedCount} von {$totalCount} Regeln verletzt";
        }

        return $summary . "\n\n" . implode("\n", $rules);
    }
}
