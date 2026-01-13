<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServiceGatewayExchangeLogResource\Pages;
use App\Models\ServiceGatewayExchangeLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * ServiceGatewayExchangeLogResource
 *
 * Admin UI for viewing Service Gateway Exchange Logs.
 * Provides accountability and observability for all external API calls.
 *
 * Features:
 * - Read-only (no create/edit/delete)
 * - Redacted data only (No-Leak Guarantee)
 * - Retry chain visualization
 * - Status monitoring
 *
 * @package App\Filament\Resources
 */
class ServiceGatewayExchangeLogResource extends Resource
{
    protected static ?string $model = ServiceGatewayExchangeLog::class;
    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';
    protected static ?string $navigationGroup = 'Service Gateway';
    protected static ?string $navigationLabel = 'Exchange Logs';
    protected static ?string $modelLabel = 'Exchange Log';
    protected static ?string $pluralModelLabel = 'Exchange Logs';
    protected static ?int $navigationSort = 16;

    /**
     * Only show in navigation when Service Gateway is enabled.
     */
    public static function shouldRegisterNavigation(): bool
    {
        return config('gateway.mode_enabled', false);
    }

    /**
     * Read-only resource - no creation allowed.
     */
    public static function canCreate(): bool
    {
        return false;
    }

    /**
     * Form definition (for completeness, but never used).
     */
    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    /**
     * Table definition for list view.
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('event_id')
                    ->label('Event ID')
                    ->limit(12)
                    ->tooltip(fn ($record) => $record->event_id)
                    ->copyable()
                    ->copyMessage('Event ID kopiert')
                    ->fontFamily('mono')
                    ->toggleable(),

                Tables\Columns\BadgeColumn::make('direction')
                    ->label('Richtung')
                    ->colors([
                        'primary' => 'outbound',
                        'success' => 'inbound',
                    ])
                    ->icons([
                        'heroicon-o-arrow-up-right' => 'outbound',
                        'heroicon-o-arrow-down-left' => 'inbound',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'outbound' => 'Ausgehend',
                        'inbound' => 'Eingehend',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('endpoint')
                    ->label('Endpoint')
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->endpoint)
                    ->searchable(),

                Tables\Columns\TextColumn::make('http_method')
                    ->label('Methode')
                    ->badge()
                    ->color('gray')
                    ->fontFamily('mono'),

                Tables\Columns\TextColumn::make('overall_status')
                    ->label('Status')
                    ->badge()
                    ->getStateUsing(fn ($record) => match ($record->getOverallStatus()) {
                        'success' => $record->status_code ? "OK ({$record->status_code})" : 'OK',
                        'http_error' => "HTTP {$record->status_code}",
                        'semantic_error' => 'Semantischer Fehler',
                        'exception' => 'Exception',
                        default => 'Unbekannt',
                    })
                    ->color(fn ($record) => match ($record->getOverallStatus()) {
                        'success' => 'success',
                        'http_error' => 'danger',
                        'semantic_error' => 'warning',
                        'exception' => 'danger',
                        default => 'gray',
                    })
                    ->icon(fn ($record) => match ($record->getOverallStatus()) {
                        'success' => 'heroicon-o-check-circle',
                        'http_error' => 'heroicon-o-x-circle',
                        'semantic_error' => 'heroicon-o-exclamation-triangle',
                        'exception' => 'heroicon-o-bolt',
                        default => 'heroicon-o-question-mark-circle',
                    })
                    ->tooltip(fn ($record) => $record->hasSemanticError()
                        ? $record->getSemanticErrorMessage()
                        : ($record->error_message ?? null)
                    )
                    ->sortable(query: fn ($query, $direction) =>
                        $query->orderBy('status_code', $direction)
                    ),

                Tables\Columns\TextColumn::make('formatted_duration')
                    ->label('Dauer')
                    ->sortable(query: fn ($query, $direction) =>
                        $query->orderBy('duration_ms', $direction)
                    ),

                Tables\Columns\TextColumn::make('attempt_no')
                    ->label('Versuch')
                    ->formatStateUsing(fn ($record) =>
                        "{$record->attempt_no}/{$record->max_attempts}"
                    )
                    ->color(fn ($record) => $record->isRetry() ? 'warning' : null)
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt')
                    ->dateTime('d.m.Y H:i:s')
                    ->sortable()
                    ->description(fn ($record) => $record->created_at?->diffForHumans()),

                // Error columns - visible when errors exist
                Tables\Columns\TextColumn::make('error_class')
                    ->label('Fehlerklasse')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->limit(30)
                    ->color('danger'),

                Tables\Columns\TextColumn::make('error_message')
                    ->label('Fehlermeldung')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->error_message)
                    ->color(fn ($record) => $record->hasSemanticError() ? 'warning' : 'danger')
                    ->placeholder('—')
                    ->wrap(),

                Tables\Columns\TextColumn::make('correlation_id')
                    ->label('Correlation ID')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->fontFamily('mono')
                    ->limit(12)
                    ->copyable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('serviceCase.subject')
                    ->label('Ticket')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->limit(30),

                Tables\Columns\TextColumn::make('company.name')
                    ->label('Company')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('direction')
                    ->label('Richtung')
                    ->options([
                        'outbound' => 'Ausgehend',
                        'inbound' => 'Eingehend',
                    ]),

                Tables\Filters\TernaryFilter::make('status')
                    ->label('Status')
                    ->placeholder('Alle')
                    ->trueLabel('Erfolgreich')
                    ->falseLabel('Fehlgeschlagen')
                    ->queries(
                        true: fn ($query) => $query->successful(),
                        false: fn ($query) => $query->failed(),
                    ),

                Tables\Filters\SelectFilter::make('status_type')
                    ->label('Status-Typ')
                    ->options([
                        'success' => '✅ Erfolgreich',
                        'semantic_error' => '⚠️ Semantische Fehler',
                        'http_error' => '❌ HTTP Fehler',
                        'exception' => '💥 Exceptions',
                    ])
                    ->query(function ($query, array $data) {
                        return match ($data['value'] ?? null) {
                            'success' => $query->whereNull('error_class')
                                ->where(fn ($q) => $q->whereNull('status_code')->orWhere('status_code', '<', 400)),
                            'semantic_error' => $query->where('error_class', 'like', 'SemanticError:%'),
                            'http_error' => $query->where('status_code', '>=', 400)
                                ->where(fn ($q) => $q->whereNull('error_class')->orWhere('error_class', 'not like', 'SemanticError:%')),
                            'exception' => $query->whereNotNull('error_class')
                                ->where('error_class', 'not like', 'SemanticError:%'),
                            default => $query,
                        };
                    }),

                Tables\Filters\SelectFilter::make('http_method')
                    ->label('HTTP Methode')
                    ->options([
                        'GET' => 'GET',
                        'POST' => 'POST',
                        'PUT' => 'PUT',
                        'PATCH' => 'PATCH',
                        'DELETE' => 'DELETE',
                    ]),

                Tables\Filters\Filter::make('status_code_range')
                    ->label('Status-Code Bereich')
                    ->form([
                        Forms\Components\Select::make('status_range')
                            ->label('Bereich')
                            ->options([
                                '2xx' => '2xx (Erfolg)',
                                '3xx' => '3xx (Redirect)',
                                '4xx' => '4xx (Client Error)',
                                '5xx' => '5xx (Server Error)',
                            ]),
                    ])
                    ->query(function ($query, array $data) {
                        return match ($data['status_range'] ?? null) {
                            '2xx' => $query->whereBetween('status_code', [200, 299]),
                            '3xx' => $query->whereBetween('status_code', [300, 399]),
                            '4xx' => $query->whereBetween('status_code', [400, 499]),
                            '5xx' => $query->whereBetween('status_code', [500, 599]),
                            default => $query,
                        };
                    }),

                Tables\Filters\Filter::make('has_errors')
                    ->label('Nur Fehler')
                    ->query(fn ($query) => $query->whereNotNull('error_class')),

                Tables\Filters\Filter::make('created_at')
                    ->label('Zeitraum')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('Von'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Bis'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'] ?? null,
                                fn ($query, $date) => $query->whereDate('created_at', '>=', $date)
                            )
                            ->when($data['until'] ?? null,
                                fn ($query, $date) => $query->whereDate('created_at', '<=', $date)
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([])
            ->poll('30s');
    }

    /**
     * Infolist definition for detail view.
     */
    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Exchange Details')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('event_id')
                                    ->label('Event ID')
                                    ->fontFamily('mono')
                                    ->copyable(),

                                Infolists\Components\TextEntry::make('direction')
                                    ->label('Richtung')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'outbound' => 'primary',
                                        'inbound' => 'success',
                                        default => 'gray',
                                    })
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        'outbound' => 'Ausgehend',
                                        'inbound' => 'Eingehend',
                                        default => $state,
                                    }),

                                Infolists\Components\TextEntry::make('http_method')
                                    ->label('HTTP Methode')
                                    ->badge(),
                            ]),

                        Infolists\Components\TextEntry::make('endpoint')
                            ->label('Endpoint')
                            ->fontFamily('mono')
                            ->columnSpanFull(),

                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('created_at')
                                    ->label('Erstellt')
                                    ->dateTime('d.m.Y H:i:s'),

                                Infolists\Components\TextEntry::make('completed_at')
                                    ->label('Abgeschlossen')
                                    ->dateTime('d.m.Y H:i:s')
                                    ->placeholder('-'),

                                Infolists\Components\TextEntry::make('formatted_duration')
                                    ->label('Dauer'),
                            ]),
                    ]),

                Infolists\Components\Section::make('Status & Metriken')
                    ->schema([
                        // Semantic Error Warning Banner
                        Infolists\Components\TextEntry::make('semantic_error_warning')
                            ->label('')
                            ->getStateUsing(fn ($record) => $record->hasSemanticError()
                                ? '⚠️ Semantischer Fehler erkannt: HTTP-Status war erfolgreich, aber der Response-Body enthält einen Fehler. ' . ($record->getSemanticErrorMessage() ?? '')
                                : null
                            )
                            ->visible(fn ($record) => $record->hasSemanticError())
                            ->columnSpanFull()
                            ->extraAttributes(['class' => 'bg-yellow-100 dark:bg-yellow-900/30 border border-yellow-400 dark:border-yellow-600 text-yellow-800 dark:text-yellow-200 p-3 rounded-lg font-medium']),

                        Infolists\Components\Grid::make(4)
                            ->schema([
                                Infolists\Components\TextEntry::make('status_code')
                                    ->label('HTTP Status')
                                    ->badge()
                                    ->color(fn ($record): string => $record->status_color),

                                Infolists\Components\TextEntry::make('overall_status_display')
                                    ->label('Gesamtstatus')
                                    ->badge()
                                    ->getStateUsing(fn ($record) => match ($record->getOverallStatus()) {
                                        'success' => '✅ Erfolgreich',
                                        'http_error' => '❌ HTTP Fehler',
                                        'semantic_error' => '⚠️ Semantischer Fehler',
                                        'exception' => '💥 Exception',
                                        default => '❓ Unbekannt',
                                    })
                                    ->color(fn ($record) => match ($record->getOverallStatus()) {
                                        'success' => 'success',
                                        'http_error' => 'danger',
                                        'semantic_error' => 'warning',
                                        'exception' => 'danger',
                                        default => 'gray',
                                    }),

                                Infolists\Components\TextEntry::make('attempt_no')
                                    ->label('Versuch')
                                    ->formatStateUsing(fn ($record) =>
                                        "{$record->attempt_no} von {$record->max_attempts}"
                                    ),

                                Infolists\Components\IconEntry::make('can_retry')
                                    ->label('Retry moeglich')
                                    ->boolean()
                                    ->getStateUsing(fn ($record) => $record->canRetry()),
                            ]),

                        Infolists\Components\TextEntry::make('error_class')
                            ->label('Fehlerklasse')
                            ->placeholder('-')
                            ->color(fn ($record) => $record->hasSemanticError() ? 'warning' : 'danger')
                            ->visible(fn ($record) => $record->error_class !== null),

                        Infolists\Components\TextEntry::make('error_message')
                            ->label('Fehlermeldung')
                            ->placeholder('-')
                            ->columnSpanFull()
                            ->color(fn ($record) => $record->hasSemanticError() ? 'warning' : 'danger')
                            ->visible(fn ($record) => $record->error_message !== null),
                    ]),

                Infolists\Components\Section::make('Request (Redacted)')
                    ->schema([
                        Infolists\Components\TextEntry::make('request_json')
                            ->label('')
                            ->columnSpanFull()
                            ->getStateUsing(function ($record) {
                                $data = $record->request_body_redacted;
                                if (!$data) return '-';
                                return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                            })
                            ->copyable()
                            ->fontFamily('mono')
                            ->extraAttributes(['class' => 'whitespace-pre-wrap text-xs bg-gray-50 dark:bg-gray-800 p-4 rounded-lg overflow-x-auto max-h-96 overflow-y-auto']),
                    ])
                    ->collapsible()
                    ->collapsed(false),

                Infolists\Components\Section::make('Response (Redacted)')
                    ->schema([
                        Infolists\Components\TextEntry::make('response_json')
                            ->label('')
                            ->columnSpanFull()
                            ->getStateUsing(function ($record) {
                                $data = $record->response_body_redacted;
                                if (!$data) return '-';
                                return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                            })
                            ->copyable()
                            ->fontFamily('mono')
                            ->extraAttributes(['class' => 'whitespace-pre-wrap text-xs bg-gray-50 dark:bg-gray-800 p-4 rounded-lg overflow-x-auto max-h-96 overflow-y-auto']),
                    ])
                    ->collapsible()
                    ->collapsed(false)
                    ->visible(fn ($record) => $record->response_body_redacted !== null),

                Infolists\Components\Section::make('Headers (Redacted)')
                    ->schema([
                        Infolists\Components\TextEntry::make('headers_json')
                            ->label('')
                            ->columnSpanFull()
                            ->getStateUsing(function ($record) {
                                $data = $record->headers_redacted;
                                if (!$data) return '-';
                                return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                            })
                            ->copyable()
                            ->fontFamily('mono')
                            ->extraAttributes(['class' => 'whitespace-pre-wrap text-xs bg-gray-50 dark:bg-gray-800 p-4 rounded-lg overflow-x-auto']),
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->visible(fn ($record) => $record->headers_redacted !== null),

                Infolists\Components\Section::make('Correlation & Referenzen')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('correlation_id')
                                    ->label('Correlation ID')
                                    ->fontFamily('mono')
                                    ->copyable()
                                    ->placeholder('-'),

                                Infolists\Components\TextEntry::make('parent_event_id')
                                    ->label('Parent Event ID')
                                    ->fontFamily('mono')
                                    ->placeholder('-')
                                    ->visible(fn ($record) => $record->parent_event_id !== null),
                            ]),

                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('call_id')
                                    ->label('Call ID')
                                    ->placeholder('-'),

                                Infolists\Components\TextEntry::make('service_case_id')
                                    ->label('Service Case ID')
                                    ->placeholder('-'),

                                Infolists\Components\TextEntry::make('company.name')
                                    ->label('Company')
                                    ->placeholder('-'),
                            ]),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServiceGatewayExchangeLogs::route('/'),
            'view' => Pages\ViewServiceGatewayExchangeLog::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $failedCount = static::getModel()::failed()
            ->where('created_at', '>=', now()->subHours(24))
            ->count();

        return $failedCount > 0 ? (string) $failedCount : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    /**
     * Eager load relationships for performance.
     */
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->with(['serviceCase:id,subject', 'company:id,name', 'call:id,retell_call_id']);
    }
}
