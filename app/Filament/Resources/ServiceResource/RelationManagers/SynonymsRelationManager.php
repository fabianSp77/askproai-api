<?php

namespace App\Filament\Resources\ServiceResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SynonymsRelationManager extends RelationManager
{
    protected static string $relationship = 'synonyms';

    protected static ?string $title = 'Synonyme & Alternative Begriffe';

    protected static ?string $modelLabel = 'Synonym';

    protected static ?string $pluralModelLabel = 'Synonyme';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Synonym Information')
                    ->description('Alternative Begriffe, die Kunden für diese Dienstleistung verwenden könnten')
                    ->schema([
                        Forms\Components\TextInput::make('synonym')
                            ->label('Synonym / Alternativer Begriff')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('z.B. "Herrenschnitt" für "Herrenhaarschnitt"')
                            ->helperText('Wie könnte ein Kunde diese Dienstleistung am Telefon nennen?'),

                        Forms\Components\Select::make('confidence')
                            ->label('Confidence Score')
                            ->options([
                                '1.00' => '100% - Exaktes Synonym',
                                '0.95' => '95% - Sehr häufig verwendet',
                                '0.90' => '90% - Häufig verwendet',
                                '0.85' => '85% - Regelmäßig verwendet',
                                '0.80' => '80% - Gelegentlich verwendet',
                                '0.75' => '75% - Manchmal verwendet',
                                '0.70' => '70% - Selten verwendet',
                                '0.65' => '65% - Sehr selten verwendet',
                                '0.60' => '60% - Möglich, aber ungewöhnlich',
                            ])
                            ->required()
                            ->default('0.85')
                            ->helperText('Wie sicher ist es, dass der Kunde genau diese Dienstleistung meint?'),

                        Forms\Components\Textarea::make('notes')
                            ->label('Notizen (optional)')
                            ->maxLength(500)
                            ->rows(2)
                            ->placeholder('z.B. "Wird von älteren Kunden häufiger verwendet"')
                            ->helperText('Zusätzliche Informationen zur Verwendung'),
                    ])
                    ->columns(1),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('synonym')
            ->columns([
                Tables\Columns\TextColumn::make('synonym')
                    ->label('Synonym / Alternativer Begriff')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable()
                    ->copyMessage('Synonym kopiert!')
                    ->description(fn ($record) => $record->notes ?? null),

                Tables\Columns\TextColumn::make('confidence')
                    ->label('Confidence')
                    ->sortable()
                    ->badge()
                    ->color(fn (string $state): string => match (true) {
                        (float)$state >= 0.95 => 'success',
                        (float)$state >= 0.85 => 'info',
                        (float)$state >= 0.75 => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => number_format((float)$state * 100, 0) . '%'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Aktualisiert')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('confidence')
                    ->label('Confidence Level')
                    ->options([
                        '0.95-1.00' => '95-100% (Sehr häufig)',
                        '0.85-0.94' => '85-94% (Häufig)',
                        '0.75-0.84' => '75-84% (Gelegentlich)',
                        '0.60-0.74' => '60-74% (Selten)',
                    ])
                    ->query(function (Builder $query, array $data) {
                        $value = $data['value'] ?? null;
                        if (!$value) return $query;

                        return match ($value) {
                            '0.95-1.00' => $query->whereBetween('confidence', [0.95, 1.00]),
                            '0.85-0.94' => $query->whereBetween('confidence', [0.85, 0.94]),
                            '0.75-0.84' => $query->whereBetween('confidence', [0.75, 0.84]),
                            '0.60-0.74' => $query->whereBetween('confidence', [0.60, 0.74]),
                            default => $query,
                        };
                    }),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Synonym hinzufügen')
                    ->icon('heroicon-o-plus-circle')
                    ->modalHeading('Neues Synonym hinzufügen')
                    ->modalDescription('Fügen Sie einen alternativen Begriff hinzu, den Kunden am Telefon verwenden könnten')
                    ->successNotificationTitle('Synonym hinzugefügt')
                    ->mutateFormDataUsing(function (array $data): array {
                        // Normalize synonym to lowercase for consistency
                        $data['synonym'] = trim($data['synonym']);
                        return $data;
                    }),

                Tables\Actions\Action::make('import_suggestions')
                    ->label('Vorschläge importieren')
                    ->icon('heroicon-o-light-bulb')
                    ->color('info')
                    ->action(function () {
                        // TODO: Implement AI-powered synonym suggestions based on service name
                        \Filament\Notifications\Notification::make()
                            ->title('Feature in Entwicklung')
                            ->body('KI-gestützte Synonym-Vorschläge kommen bald!')
                            ->info()
                            ->send();
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->modalHeading('Synonym bearbeiten'),

                Tables\Actions\DeleteAction::make()
                    ->modalHeading('Synonym löschen')
                    ->modalDescription('Sind Sie sicher, dass Sie dieses Synonym löschen möchten?'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->modalHeading('Synonyme löschen')
                        ->modalDescription('Sind Sie sicher, dass Sie die ausgewählten Synonyme löschen möchten?'),
                ]),
            ])
            ->emptyStateHeading('Keine Synonyme vorhanden')
            ->emptyStateDescription('Fügen Sie alternative Begriffe hinzu, die Kunden am Telefon verwenden könnten, um diese Dienstleistung zu finden.')
            ->emptyStateIcon('heroicon-o-language')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Erstes Synonym hinzufügen')
                    ->icon('heroicon-o-plus-circle'),
            ])
            ->defaultSort('confidence', 'desc');
    }
}
