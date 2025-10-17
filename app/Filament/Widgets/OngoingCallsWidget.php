<?php

namespace App\Filament\Widgets;

use App\Models\Call;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class OngoingCallsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $heading = '📞 Laufende Anrufe - Echtzeit-Übersicht';

    public static function canView(): bool
    {
        return true;
    }

    /**
     * Poll every 10 seconds for real-time updates (balanced performance)
     */
    protected static ?string $pollingInterval = '10s';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Call::query()
                    // PERFORMANCE: Select only needed columns, exclude LONGTEXT fields (raw, analysis, details)
                    ->select([
                        'id', 'created_at', 'status', 'call_status',
                        'from_number', 'to_number', 'customer_id', 'customer_name',
                        'agent_id', 'company_id', 'direction', 'start_timestamp'
                    ])
                    // Exclude calls that are definitely ended based on either status field
                    ->whereNotIn('status', ['completed', 'ended', 'failed', 'analyzed', 'call_analyzed'])
                    ->whereNotIn('call_status', ['ended', 'completed', 'failed', 'analyzed'])
                    // Only include calls that have an active status in at least one field
                    // Note: Database uses 'in_progress' (underscore), not 'in-progress' (hyphen)
                    ->where(function (Builder $query) {
                        $query->whereIn('status', ['ongoing', 'in_progress', 'in-progress', 'active', 'ringing'])
                              ->orWhereIn('call_status', ['ongoing', 'in_progress', 'in-progress', 'active', 'ringing']);
                    })
                    // Add time filter to exclude stale calls (older than 2 hours)
                    ->where('created_at', '>=', now()->subHours(2))
                    // PERFORMANCE: Eager load with column selection to prevent N+1 queries
                    ->with([
                        'customer:id,name',
                        'agent:id,name',
                        'company:id,name'
                    ])
                    ->orderBy('created_at', 'desc')
            )
            ->columns([
                // Call ID removed - not needed for live overview

                Tables\Columns\TextColumn::make('from_number')
                    ->label('Von → Nach')
                    ->searchable()
                    ->formatStateUsing(fn ($state, $record) =>
                        ($state ?: 'Anonym') . ' → ' . ($record->to_number ?: 'Unbekannt')
                    )
                    ->description(fn ($record) => $record->direction === 'inbound' ? '📞 Eingehend' : '☎️ Ausgehend')
                    ->wrap(),

                Tables\Columns\TextColumn::make('customer_name')
                    ->label('Anrufer')
                    ->searchable()
                    ->default('Unbekannt')
                    ->formatStateUsing(function ($state, $record) {
                        // Priority: customer_name field > linked customer > fallback
                        if ($record->customer_name) {
                            return $record->customer_name;
                        } elseif ($record->customer?->name) {
                            return $record->customer->name;
                        } else {
                            return $record->from_number === 'anonymous' ? 'Anonymer Anrufer' : 'Neuer Anrufer';
                        }
                    })
                    ->weight('bold'),

                // Status column removed - redundant for live calls

                Tables\Columns\TextColumn::make('duration_live')
                    ->label('Dauer')
                    ->getStateUsing(function ($record) {
                        // Calculate duration from start to now for ongoing calls
                        $startTime = null;

                        // Use start_timestamp if available (it's a datetime, not milliseconds)
                        if ($record->start_timestamp) {
                            $startTime = is_string($record->start_timestamp)
                                ? Carbon::parse($record->start_timestamp)
                                : $record->start_timestamp;
                        } elseif ($record->created_at) {
                            $startTime = $record->created_at;
                        }

                        if ($startTime) {
                            $duration = $startTime->diffInSeconds(now());
                            // Format as mm:ss
                            $minutes = floor($duration / 60);
                            $seconds = $duration % 60;
                            return sprintf('%02d:%02d', $minutes, $seconds);
                        }

                        return '--:--';
                    })
                    ->badge()
                    ->color(function ($state) {
                        // Color based on duration
                        if (!$state || $state === '--:--') return 'gray';
                        $parts = explode(':', $state);
                        if (count($parts) == 2) {
                            $totalSeconds = (int)$parts[0] * 60 + (int)$parts[1];
                            if ($totalSeconds > 600) return 'danger';  // >10 min
                            if ($totalSeconds > 300) return 'warning'; // >5 min
                        }
                        return 'success';
                    })
                    ->size('lg')
                    ->extraAttributes(['class' => 'font-bold']),

                Tables\Columns\TextColumn::make('agent.name')
                    ->label('Agent')
                    ->searchable()
                    ->default('KI-Assistent')
                    ->toggleable(),

                // Direction integrated into phone number column

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Gestartet')
                    ->dateTime('H:i:s')
                    ->description(fn ($record) => $record->created_at?->diffForHumans())
                    ->sortable(),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Überwachen')
                    ->icon('heroicon-o-eye')
                    ->color('warning')
                    ->url(fn (Call $record): string => route('filament.admin.resources.calls.view', $record)),
            ])
            ->bulkActions([])
            ->emptyStateHeading('Keine laufenden Anrufe')
            ->emptyStateDescription('Anrufe werden hier angezeigt, sobald sie starten')
            ->emptyStateIcon('heroicon-o-phone')
            ->striped()
            ->defaultSort('created_at', 'desc');
    }

    public function getTableRecordsPerPageSelectOptions(): array
    {
        return [5, 10, 25];
    }
}