<?php

namespace App\Filament\Resources\ServiceOutputConfigurationResource\RelationManagers;

use App\Models\ServiceGatewayExchangeLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Illuminate\Contracts\View\View;

/**
 * DeliveryLogsRelationManager
 *
 * Displays webhook delivery history for a ServiceOutputConfiguration.
 * Implements Stripe/ServiceNow-level monitoring with:
 * - Real-time delivery status
 * - Success rate statistics
 * - Request/Response payload inspection
 * - Test vs Real delivery filtering
 *
 * @package App\Filament\Resources\ServiceOutputConfigurationResource\RelationManagers
 */
class DeliveryLogsRelationManager extends RelationManager
{
    protected static string $relationship = 'exchangeLogs';

    protected static ?string $title = 'Delivery Historie';

    protected static ?string $icon = 'heroicon-o-paper-airplane';

    /**
     * Auto-refresh every 30 seconds for live monitoring.
     */
    protected static bool $isLazy = false;

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('event_id')
            ->defaultSort('created_at', 'desc')
            ->poll('30s')
            ->heading(fn () => $this->getTableHeading())
            ->description(fn () => $this->getTableDescription())
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Zeit')
                    ->dateTime('d.m.Y H:i:s')
                    ->description(fn (ServiceGatewayExchangeLog $record): string =>
                        $record->created_at->diffForHumans()
                    )
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status_code')
                    ->label('Status')
                    ->formatStateUsing(fn (?int $state, ServiceGatewayExchangeLog $record): string =>
                        $state ? (string) $state : ($record->error_class ? 'Error' : 'Pending')
                    )
                    ->color(fn (?int $state, ServiceGatewayExchangeLog $record): string =>
                        $this->getStatusColor($state, $record)
                    )
                    ->icon(fn (?int $state, ServiceGatewayExchangeLog $record): ?string =>
                        $this->getStatusIcon($state, $record)
                    ),

                Tables\Columns\TextColumn::make('formatted_duration')
                    ->label('Dauer')
                    ->alignEnd(),

                Tables\Columns\BadgeColumn::make('is_test')
                    ->label('Typ')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Test' : 'Echt')
                    ->color(fn (bool $state): string => $state ? 'info' : 'success')
                    ->icon(fn (bool $state): string => $state ? 'heroicon-o-beaker' : 'heroicon-o-paper-airplane'),

                Tables\Columns\TextColumn::make('serviceCase.subject')
                    ->label('Ticket')
                    ->limit(30)
                    ->placeholder('[Test Webhook]')
                    ->description(fn (ServiceGatewayExchangeLog $record): ?string =>
                        $record->service_case_id
                            ? 'TKT-' . $record->serviceCase?->created_at?->format('Y') . '-' . str_pad((string) $record->service_case_id, 5, '0', STR_PAD_LEFT)
                            : null
                    )
                    ->toggleable(),

                Tables\Columns\TextColumn::make('error_message')
                    ->label('Fehler')
                    ->limit(40)
                    ->placeholder('-')
                    ->color('danger')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_test')
                    ->label('Typ')
                    ->placeholder('Alle')
                    ->trueLabel('Nur Tests')
                    ->falseLabel('Nur Echte'),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'success' => 'Erfolgreich',
                        'failed' => 'Fehlgeschlagen',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'success' => $query->successful(),
                            'failed' => $query->failed(),
                            default => $query,
                        };
                    }),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('Von'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Bis'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn (Builder $q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['until'], fn (Builder $q, $date) => $q->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->headerActions([
                // Test Webhook button could be added here if needed
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Details')
                    ->icon('heroicon-o-eye')
                    ->modalHeading(fn (ServiceGatewayExchangeLog $record): string =>
                        'Delivery Details - ' . $record->event_id
                    )
                    ->modalContent(fn (ServiceGatewayExchangeLog $record): View =>
                        view('filament.relation-managers.exchange-log-detail', ['log' => $record])
                    )
                    ->modalWidth('4xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Schließen'),
            ])
            ->bulkActions([])
            ->emptyStateHeading('Keine Zustellungen')
            ->emptyStateDescription('Es wurden noch keine Webhooks an diese Konfiguration gesendet.')
            ->emptyStateIcon('heroicon-o-paper-airplane');
    }

    /**
     * Get table heading with stats.
     */
    protected function getTableHeading(): string
    {
        $stats = $this->getDeliveryStats();
        $total = $stats['total'] ?? 0;

        if ($total === 0) {
            return 'Delivery Historie';
        }

        return "Delivery Historie ({$total} Zustellungen)";
    }

    /**
     * Get table description with success rate.
     */
    protected function getTableDescription(): ?string
    {
        $stats = $this->getDeliveryStats();

        if (($stats['total'] ?? 0) === 0) {
            return null;
        }

        $parts = [];

        // Success rate
        $successRate = $stats['success_rate'] ?? 0;
        $emoji = $successRate >= 95 ? '✅' : ($successRate >= 80 ? '⚠️' : '❌');
        $parts[] = "{$emoji} {$successRate}% Erfolgsrate";

        // Average duration
        if (isset($stats['avg_duration_ms']) && $stats['avg_duration_ms'] > 0) {
            $avgMs = round($stats['avg_duration_ms']);
            $parts[] = "Ø {$avgMs}ms";
        }

        // Test count
        if (isset($stats['test_count']) && $stats['test_count'] > 0) {
            $parts[] = "{$stats['test_count']} Tests";
        }

        return implode(' | ', $parts);
    }

    /**
     * Get delivery statistics for the current configuration.
     */
    protected function getDeliveryStats(): array
    {
        $configId = $this->getOwnerRecord()->id;

        $stats = ServiceGatewayExchangeLog::query()
            ->where('output_configuration_id', $configId)
            ->where('created_at', '>=', now()->subDays(7))
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN (status_code IS NULL OR status_code >= 400 OR error_class IS NOT NULL) THEN 0 ELSE 1 END) as successful,
                AVG(duration_ms) as avg_duration_ms,
                SUM(CASE WHEN is_test = true THEN 1 ELSE 0 END) as test_count
            ')
            ->first();

        $total = $stats->total ?? 0;
        $successful = $stats->successful ?? 0;

        return [
            'total' => $total,
            'successful' => $successful,
            'failed' => $total - $successful,
            'success_rate' => $total > 0 ? round(($successful / $total) * 100, 1) : 0,
            'avg_duration_ms' => $stats->avg_duration_ms ?? null,
            'test_count' => $stats->test_count ?? 0,
        ];
    }

    /**
     * Get status badge color based on HTTP status code.
     */
    protected function getStatusColor(?int $statusCode, ServiceGatewayExchangeLog $record): string
    {
        if ($record->error_class) {
            return 'danger';
        }

        if ($statusCode === null) {
            return 'gray';
        }

        if ($statusCode >= 200 && $statusCode < 300) {
            return 'success';
        }

        if ($statusCode >= 300 && $statusCode < 400) {
            return 'warning';
        }

        return 'danger';
    }

    /**
     * Get status icon based on HTTP status code.
     */
    protected function getStatusIcon(?int $statusCode, ServiceGatewayExchangeLog $record): ?string
    {
        if ($record->error_class) {
            return 'heroicon-o-x-circle';
        }

        if ($statusCode === null) {
            return 'heroicon-o-clock';
        }

        if ($statusCode >= 200 && $statusCode < 300) {
            return 'heroicon-o-check-circle';
        }

        return 'heroicon-o-exclamation-circle';
    }
}
