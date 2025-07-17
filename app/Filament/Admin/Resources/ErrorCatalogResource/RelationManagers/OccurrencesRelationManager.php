<?php

namespace App\Filament\Admin\Resources\ErrorCatalogResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OccurrencesRelationManager extends RelationManager
{
    protected static string $relationship = 'occurrences';

    protected static ?string $recordTitleAttribute = 'id';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->label('Occurred At'),
                Tables\Columns\TextColumn::make('company.name')
                    ->searchable()
                    ->placeholder('N/A'),
                Tables\Columns\TextColumn::make('user.email')
                    ->searchable()
                    ->placeholder('Anonymous'),
                Tables\Columns\TextColumn::make('environment')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'production' => 'danger',
                        'staging' => 'warning',
                        'local' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\IconColumn::make('was_resolved')
                    ->boolean()
                    ->label('Resolved'),
                Tables\Columns\TextColumn::make('resolution_time')
                    ->formatStateUsing(fn ($state) => $state ? gmdate('H:i:s', $state) : '-')
                    ->label('Resolution Time'),
                Tables\Columns\TextColumn::make('request_url')
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->request_url),
                Tables\Columns\TextColumn::make('ip_address')
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('was_resolved')
                    ->label('Resolved Status'),
                Tables\Filters\SelectFilter::make('environment')
                    ->options([
                        'production' => 'Production',
                        'staging' => 'Staging',
                        'local' => 'Local',
                    ]),
                Tables\Filters\Filter::make('recent')
                    ->query(fn (Builder $query): Builder => $query->where('created_at', '>=', now()->subDays(7)))
                    ->label('Last 7 days'),
            ])
            ->actions([
                Tables\Actions\Action::make('view_details')
                    ->label('Details')
                    ->icon('heroicon-o-eye')
                    ->modalContent(fn ($record) => view('filament.modals.error-occurrence-details', ['occurrence' => $record]))
                    ->modalHeading('Occurrence Details'),
                Tables\Actions\Action::make('mark_resolved')
                    ->label('Mark Resolved')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn ($record) => !$record->was_resolved)
                    ->action(fn ($record) => $record->markAsResolved())
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('mark_resolved_bulk')
                    ->label('Mark as Resolved')
                    ->icon('heroicon-o-check')
                    ->action(fn ($records) => $records->each->markAsResolved())
                    ->deselectRecordsAfterCompletion()
                    ->requiresConfirmation(),
            ]);
    }
}