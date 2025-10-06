<?php

namespace App\Filament\Widgets;

use App\Models\Service;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class CalcomSyncActivityWidget extends BaseWidget
{
    protected static ?string $heading = 'Recent Cal.com Sync Activity';

    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Service::query()
                    ->whereNotNull('last_calcom_sync')
                    ->orderBy('last_calcom_sync', 'desc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Service')
                    ->searchable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('company.name')
                    ->label('Company')
                    ->searchable()
                    ->limit(20),

                Tables\Columns\TextColumn::make('calcom_event_type_id')
                    ->label('Cal.com ID')
                    ->copyable()
                    ->copyMessage('Event Type ID copied')
                    ->copyMessageDuration(1500),

                Tables\Columns\BadgeColumn::make('sync_status')
                    ->label('Status')
                    ->colors([
                        'success' => 'synced',
                        'warning' => 'pending',
                        'danger' => 'error',
                        'secondary' => 'never',
                    ])
                    ->icons([
                        'heroicon-o-check-circle' => 'synced',
                        'heroicon-o-clock' => 'pending',
                        'heroicon-o-x-circle' => 'error',
                        'heroicon-o-minus-circle' => 'never',
                    ]),

                Tables\Columns\TextColumn::make('last_calcom_sync')
                    ->label('Last Sync')
                    ->dateTime('M j, H:i')
                    ->sortable()
                    ->description(fn ($record) => $record->last_calcom_sync?->diffForHumans()),

                Tables\Columns\TextColumn::make('sync_error')
                    ->label('Error')
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->sync_error)
                    ->visible(fn () => Service::where('sync_status', 'error')->exists()),

                Tables\Columns\TextColumn::make('duration_minutes')
                    ->label('Duration')
                    ->suffix(' min')
                    ->alignEnd(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('sync_status')
                    ->label('Status')
                    ->options([
                        'synced' => 'Synced',
                        'pending' => 'Pending',
                        'error' => 'Error',
                        'never' => 'Never',
                    ]),

                Tables\Filters\Filter::make('has_error')
                    ->label('Has Error')
                    ->query(fn (Builder $query) => $query->where('sync_status', 'error')),

                Tables\Filters\Filter::make('synced_today')
                    ->label('Synced Today')
                    ->query(fn (Builder $query) => $query->whereDate('last_calcom_sync', today())),
            ])
            ->actions([
                Tables\Actions\Action::make('sync')
                    ->label('Sync')
                    ->icon('heroicon-o-arrow-path')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        // Dispatch sync job
                        \App\Jobs\UpdateCalcomEventTypeJob::dispatch($record);

                        // Update status
                        $record->update(['sync_status' => 'pending']);

                        // Show notification
                        \Filament\Notifications\Notification::make()
                            ->title('Sync initiated')
                            ->body("Service '{$record->name}' queued for synchronization")
                            ->success()
                            ->send();
                    })
                    ->visible(fn ($record) => $record->calcom_event_type_id),

                Tables\Actions\Action::make('view_in_calcom')
                    ->label('View in Cal.com')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('secondary')
                    ->url(fn ($record) => "https://app.cal.com/event-types/{$record->calcom_event_type_id}")
                    ->openUrlInNewTab()
                    ->visible(fn ($record) => $record->calcom_event_type_id),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('sync_selected')
                    ->label('Sync Selected')
                    ->icon('heroicon-o-arrow-path')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->action(function ($records) {
                        $count = 0;
                        foreach ($records as $record) {
                            if ($record->calcom_event_type_id) {
                                \App\Jobs\UpdateCalcomEventTypeJob::dispatch($record);
                                $record->update(['sync_status' => 'pending']);
                                $count++;
                            }
                        }

                        \Filament\Notifications\Notification::make()
                            ->title('Bulk sync initiated')
                            ->body("{$count} services queued for synchronization")
                            ->success()
                            ->send();
                    }),
            ])
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5)
            ->poll('300s');
    }
}