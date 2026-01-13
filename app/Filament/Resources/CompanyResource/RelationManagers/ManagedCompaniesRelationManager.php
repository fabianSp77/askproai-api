<?php

namespace App\Filament\Resources\CompanyResource\RelationManagers;

use App\Models\Company;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ManagedCompaniesRelationManager extends RelationManager
{
    protected static string $relationship = 'managedCompanies';

    protected static ?string $inverseRelationship = 'managingPartner';

    protected static ?string $title = 'Verwaltete Unternehmen';

    protected static ?string $recordTitleAttribute = 'name';

    public static function canViewForRecord($ownerRecord, string $pageClass): bool
    {
        return $ownerRecord->is_partner;
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Unternehmen')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('contact_email')
                    ->label('E-Mail')
                    ->searchable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt')
                    ->date('d.m.Y')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\AssociateAction::make()
                    ->label('Unternehmen zuordnen')
                    ->preloadRecordSelect()
                    ->recordSelectOptionsQuery(fn (Builder $query) =>
                        $query->where('is_partner', false)
                              ->whereNull('managed_by_company_id')
                    )
                    ->recordTitle(fn (Company $record) => "{$record->name} (ID: {$record->id})")
                    ->modalHeading('Unternehmen diesem Partner zuordnen')
                    ->modalSubmitActionLabel('Zuordnen')
                    ->successNotificationTitle('Unternehmen erfolgreich zugeordnet'),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Ansehen')
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record) => route('filament.admin.resources.companies.edit', $record)),

                Tables\Actions\DissociateAction::make()
                    ->label('Entfernen')
                    ->modalHeading('Zuordnung entfernen')
                    ->modalDescription('Soll dieses Unternehmen nicht mehr von diesem Partner verwaltet werden?')
                    ->modalSubmitActionLabel('Zuordnung entfernen')
                    ->successNotificationTitle('Zuordnung entfernt'),
            ])
            ->bulkActions([
                Tables\Actions\DissociateBulkAction::make()
                    ->label('Zuordnung entfernen')
                    ->modalHeading('Zuordnungen entfernen')
                    ->modalDescription('Sollen die ausgewählten Unternehmen nicht mehr von diesem Partner verwaltet werden?'),
            ])
            ->emptyStateHeading('Keine Unternehmen zugeordnet')
            ->emptyStateDescription('Diesem Partner sind noch keine Unternehmen zur Verwaltung zugeordnet.')
            ->emptyStateActions([
                Tables\Actions\AssociateAction::make()
                    ->label('Erstes Unternehmen zuordnen')
                    ->preloadRecordSelect()
                    ->recordSelectOptionsQuery(fn (Builder $query) =>
                        $query->where('is_partner', false)
                              ->whereNull('managed_by_company_id')
                    )
                    ->recordTitle(fn (Company $record) => "{$record->name} (ID: {$record->id})"),
            ]);
    }
}
