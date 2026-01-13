<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServiceGatewayExchangeLogResource\Pages;
use App\Models\ServiceGatewayExchangeLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\IconPosition;
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
 * UI/UX Design:
 * - Color-coded status badges for instant recognition
 * - Semantic error differentiation (warning vs danger)
 * - Clear visual hierarchy with primary/secondary information
 * - Helpful tooltips with actionable microcopy
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
     *
     * Visual Hierarchy:
     * 1. Status (primary) - Immediate attention indicator
     * 2. Endpoint + Method - What was called
     * 3. Duration + Attempts - Performance metrics
     * 4. Timestamp - When it happened
     * 5. Error details - Supporting context (toggleable)
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // === PRIMARY: Status Indicator (First Column for Immediate Recognition) ===
                Tables\Columns\TextColumn::make('overall_status')
                    ->label('Status')
                    ->badge()
                    ->size('lg')
                    ->getStateUsing(fn ($record) => match ($record->getOverallStatus()) {
                        'success' => 'Erfolgreich',
                        'http_error' => "HTTP {$record->status_code}",
                        'semantic_error' => 'Semantisch',
                        'exception' => 'Exception',
                        default => 'Unbekannt',
                    })
                    ->color(fn ($record) => match ($record->getOverallStatus()) {
                        'success' => 'success',
                        'http_error' => 'danger',
                        'semantic_error' => 'warning',
                        'exception' => 'gray',
                        default => 'gray',
                    })
                    ->icon(fn ($record) => match ($record->getOverallStatus()) {
                        'success' => 'heroicon-m-check-circle',
                        'http_error' => 'heroicon-m-x-circle',
                        'semantic_error' => 'heroicon-m-exclamation-triangle',
                        'exception' => 'heroicon-m-bolt',
                        default => 'heroicon-m-question-mark-circle',
                    })
                    ->tooltip(fn ($record) => match ($record->getOverallStatus()) {
                        'success' => 'Anfrage erfolgreich verarbeitet',
                        'http_error' => "Server antwortete mit HTTP {$record->status_code}. " . self::getHttpErrorHint($record->status_code),
                        'semantic_error' => 'HTTP 200 erhalten, aber Fehler im Response-Body erkannt: ' . ($record->getSemanticErrorMessage() ?? 'Details in Ansicht'),
                        'exception' => 'Verbindungsfehler oder Timeout: ' . ($record->error_class ?? 'Details in Ansicht'),
                        default => 'Status unbekannt',
                    })
                    ->sortable(query: fn ($query, $direction) =>
                        $query->orderBy('status_code', $direction)
                    ),

                // === SECONDARY: Request Identity ===
                Tables\Columns\TextColumn::make('http_method')
                    ->label('Methode')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'GET' => 'info',
                        'POST' => 'success',
                        'PUT', 'PATCH' => 'warning',
                        'DELETE' => 'danger',
                        default => 'gray',
                    })
                    ->tooltip(fn (string $state): string => match ($state) {
                        'GET' => 'Daten abrufen',
                        'POST' => 'Daten erstellen',
                        'PUT' => 'Daten vollstaendig ersetzen',
                        'PATCH' => 'Daten teilweise aktualisieren',
                        'DELETE' => 'Daten loeschen',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('endpoint')
                    ->label('Endpoint')
                    ->weight(FontWeight::Medium)
                    ->limit(45)
                    ->tooltip(fn ($record) => $record->endpoint)
                    ->searchable()
                    ->description(fn ($record) => $record->direction === 'outbound' ? 'Ausgehend' : 'Eingehend'),

                // === METRICS: Performance Indicators ===
                Tables\Columns\TextColumn::make('formatted_duration')
                    ->label('Dauer')
                    ->icon('heroicon-m-clock')
                    ->iconPosition(IconPosition::Before)
                    ->color(fn ($record) => match (true) {
                        $record->duration_ms === null => 'gray',
                        $record->duration_ms < 500 => 'success',
                        $record->duration_ms < 2000 => 'warning',
                        default => 'danger',
                    })
                    ->tooltip(fn ($record) => match (true) {
                        $record->duration_ms === null => 'Dauer nicht gemessen',
                        $record->duration_ms < 500 => 'Schnelle Antwort (< 500ms)',
                        $record->duration_ms < 2000 => 'Normale Antwort (< 2s)',
                        default => 'Langsame Antwort - moeglicherweise Optimierung noetig',
                    })
                    ->sortable(query: fn ($query, $direction) =>
                        $query->orderBy('duration_ms', $direction)
                    ),

                Tables\Columns\TextColumn::make('attempt_no')
                    ->label('Versuch')
                    ->badge()
                    ->formatStateUsing(fn ($record) =>
                        "{$record->attempt_no}/{$record->max_attempts}"
                    )
                    ->color(fn ($record) => match (true) {
                        $record->attempt_no === 1 => 'gray',
                        $record->attempt_no < $record->max_attempts => 'warning',
                        default => 'danger',
                    })
                    ->icon(fn ($record) => $record->isRetry() ? 'heroicon-m-arrow-path' : null)
                    ->tooltip(fn ($record) => match (true) {
                        $record->attempt_no === 1 => 'Erster Versuch',
                        $record->attempt_no < $record->max_attempts => "Wiederholungsversuch {$record->attempt_no} von {$record->max_attempts}",
                        default => "Letzter Versuch ({$record->attempt_no}/{$record->max_attempts}) - keine weiteren Retries",
                    })
                    ->sortable(),

                // === TEMPORAL: Timestamp ===
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Zeitpunkt')
                    ->dateTime('d.m.Y H:i:s')
                    ->sortable()
                    ->description(fn ($record) => $record->created_at?->diffForHumans())
                    ->tooltip(fn ($record) => $record->created_at?->format('l, d. F Y - H:i:s T')),

                // === CONTEXT: Error Details (Toggleable) ===
                Tables\Columns\TextColumn::make('error_message')
                    ->label('Fehlermeldung')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->error_message)
                    ->color(fn ($record) => match ($record->getOverallStatus()) {
                        'semantic_error' => 'warning',
                        'exception' => 'gray',
                        default => 'danger',
                    })
                    ->icon(fn ($record) => $record->error_message ? 'heroicon-m-chat-bubble-bottom-center-text' : null)
                    ->placeholder('Kein Fehler')
                    ->wrap(),

                // === IDENTIFIERS: Technical Reference (Hidden by Default) ===
                Tables\Columns\TextColumn::make('event_id')
                    ->label('Event ID')
                    ->limit(12)
                    ->tooltip(fn ($record) => "Vollstaendige ID: {$record->event_id}\nKlicken zum Kopieren")
                    ->copyable()
                    ->copyMessage('Event ID in Zwischenablage kopiert')
                    ->fontFamily('mono')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('error_class')
                    ->label('Fehlerklasse')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->limit(35)
                    ->fontFamily('mono')
                    ->color(fn ($record) => $record->hasSemanticError() ? 'warning' : 'danger')
                    ->tooltip(fn ($record) => match (true) {
                        $record->hasSemanticError() => 'Semantischer Fehler: HTTP war erfolgreich, aber Inhalt zeigt Fehler',
                        $record->error_class !== null => "Exception-Typ: {$record->error_class}",
                        default => null,
                    }),

                Tables\Columns\TextColumn::make('correlation_id')
                    ->label('Correlation ID')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->fontFamily('mono')
                    ->limit(12)
                    ->copyable()
                    ->copyMessage('Correlation ID kopiert')
                    ->searchable()
                    ->tooltip('Verknuepft zusammengehoerige Anfragen'),

                Tables\Columns\TextColumn::make('serviceCase.subject')
                    ->label('Ticket')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->limit(30)
                    ->icon('heroicon-m-ticket'),

                Tables\Columns\TextColumn::make('company.name')
                    ->label('Unternehmen')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->icon('heroicon-m-building-office'),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->filters([
                // === Quick Filters (Most Used) ===
                Tables\Filters\TernaryFilter::make('status')
                    ->label('Ergebnis')
                    ->placeholder('Alle anzeigen')
                    ->trueLabel('Nur Erfolge')
                    ->falseLabel('Nur Fehler')
                    ->queries(
                        true: fn ($query) => $query->successful(),
                        false: fn ($query) => $query->failed(),
                    )
                    ->native(false),

                Tables\Filters\SelectFilter::make('status_type')
                    ->label('Fehlertyp')
                    ->options([
                        'success' => 'Erfolgreich',
                        'semantic_error' => 'Semantische Fehler',
                        'http_error' => 'HTTP Fehler (4xx/5xx)',
                        'exception' => 'Exceptions/Timeouts',
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
                    })
                    ->native(false)
                    ->indicator('Typ'),

                // === Request Filters ===
                Tables\Filters\SelectFilter::make('direction')
                    ->label('Richtung')
                    ->options([
                        'outbound' => 'Ausgehend (zu externem Service)',
                        'inbound' => 'Eingehend (von externem Service)',
                    ])
                    ->native(false)
                    ->indicator('Richtung'),

                Tables\Filters\SelectFilter::make('http_method')
                    ->label('HTTP Methode')
                    ->options([
                        'GET' => 'GET (Abrufen)',
                        'POST' => 'POST (Erstellen)',
                        'PUT' => 'PUT (Ersetzen)',
                        'PATCH' => 'PATCH (Aktualisieren)',
                        'DELETE' => 'DELETE (Loeschen)',
                    ])
                    ->native(false)
                    ->indicator('Methode'),

                Tables\Filters\Filter::make('status_code_range')
                    ->label('HTTP Status-Bereich')
                    ->form([
                        Forms\Components\Select::make('status_range')
                            ->label('Status-Code')
                            ->placeholder('Alle Codes')
                            ->options([
                                '2xx' => '2xx - Erfolg',
                                '3xx' => '3xx - Weiterleitung',
                                '4xx' => '4xx - Client-Fehler',
                                '5xx' => '5xx - Server-Fehler',
                            ])
                            ->native(false),
                    ])
                    ->query(function ($query, array $data) {
                        return match ($data['status_range'] ?? null) {
                            '2xx' => $query->whereBetween('status_code', [200, 299]),
                            '3xx' => $query->whereBetween('status_code', [300, 399]),
                            '4xx' => $query->whereBetween('status_code', [400, 499]),
                            '5xx' => $query->whereBetween('status_code', [500, 599]),
                            default => $query,
                        };
                    })
                    ->indicateUsing(function (array $data): ?string {
                        return $data['status_range'] ?? null ? "HTTP {$data['status_range']}" : null;
                    }),

                // === Time Filter ===
                Tables\Filters\Filter::make('created_at')
                    ->label('Zeitraum')
                    ->form([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DatePicker::make('from')
                                    ->label('Von')
                                    ->placeholder('Startdatum')
                                    ->native(false),
                                Forms\Components\DatePicker::make('until')
                                    ->label('Bis')
                                    ->placeholder('Enddatum')
                                    ->native(false),
                            ]),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'] ?? null,
                                fn ($query, $date) => $query->whereDate('created_at', '>=', $date)
                            )
                            ->when($data['until'] ?? null,
                                fn ($query, $date) => $query->whereDate('created_at', '<=', $date)
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['from'] ?? null) {
                            $indicators[] = 'Ab ' . \Carbon\Carbon::parse($data['from'])->format('d.m.Y');
                        }
                        if ($data['until'] ?? null) {
                            $indicators[] = 'Bis ' . \Carbon\Carbon::parse($data['until'])->format('d.m.Y');
                        }
                        return $indicators;
                    }),

                // === Quick Error Filter ===
                Tables\Filters\Filter::make('has_errors')
                    ->label('Nur Fehler anzeigen')
                    ->toggle()
                    ->query(fn ($query) => $query->whereNotNull('error_class')),
            ])
            ->filtersFormColumns(2)
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Details')
                    ->icon('heroicon-m-eye')
                    ->tooltip('Vollstaendige Details anzeigen'),
            ])
            ->bulkActions([])
            ->poll('30s')
            ->emptyStateHeading('Keine Exchange Logs vorhanden')
            ->emptyStateDescription('Es wurden noch keine API-Aufrufe protokolliert.')
            ->emptyStateIcon('heroicon-o-arrow-path');
    }

    /**
     * Get helpful hint text for HTTP error codes.
     */
    private static function getHttpErrorHint(?int $statusCode): string
    {
        return match (true) {
            $statusCode === null => '',
            $statusCode === 400 => 'Ungueltige Anfrage - Daten pruefen',
            $statusCode === 401 => 'Nicht autorisiert - API-Schluessel pruefen',
            $statusCode === 403 => 'Zugriff verweigert - Berechtigungen pruefen',
            $statusCode === 404 => 'Ressource nicht gefunden - URL pruefen',
            $statusCode === 408 => 'Timeout - Server antwortet nicht',
            $statusCode === 429 => 'Rate Limit erreicht - Anfragen reduzieren',
            $statusCode >= 400 && $statusCode < 500 => 'Client-Fehler - Anfrage pruefen',
            $statusCode === 500 => 'Interner Serverfehler - Externer Service pruefen',
            $statusCode === 502 => 'Bad Gateway - Netzwerk pruefen',
            $statusCode === 503 => 'Service nicht verfuegbar - Spaeter erneut versuchen',
            $statusCode === 504 => 'Gateway Timeout - Netzwerk pruefen',
            $statusCode >= 500 => 'Server-Fehler - Externer Service hat Problem',
            default => '',
        };
    }

    /**
     * Infolist definition for detail view.
     *
     * Layout Strategy:
     * 1. Status Banner (top) - Immediate context on success/failure
     * 2. Request Overview - What was called
     * 3. Status Details - Outcome and metrics
     * 4. Payloads - Request/Response data (collapsible)
     * 5. References - Correlation IDs (collapsed by default)
     */
    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // === STATUS BANNER (Prominent Alert for Errors) ===
                Infolists\Components\Section::make()
                    ->schema([
                        // Success Banner
                        Infolists\Components\TextEntry::make('success_banner')
                            ->hiddenLabel()
                            ->getStateUsing(fn () => 'Anfrage erfolgreich verarbeitet')
                            ->visible(fn ($record) => $record->getOverallStatus() === 'success')
                            ->columnSpanFull()
                            ->icon('heroicon-m-check-circle')
                            ->iconColor('success')
                            ->weight(FontWeight::SemiBold)
                            ->extraAttributes([
                                'class' => 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-200 p-4 rounded-xl',
                            ]),

                        // HTTP Error Banner
                        Infolists\Components\TextEntry::make('http_error_banner')
                            ->hiddenLabel()
                            ->getStateUsing(fn ($record) => "HTTP Fehler {$record->status_code}: " . self::getHttpErrorHint($record->status_code))
                            ->visible(fn ($record) => $record->getOverallStatus() === 'http_error')
                            ->columnSpanFull()
                            ->icon('heroicon-m-x-circle')
                            ->iconColor('danger')
                            ->weight(FontWeight::SemiBold)
                            ->extraAttributes([
                                'class' => 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-200 p-4 rounded-xl',
                            ]),

                        // Semantic Error Banner
                        Infolists\Components\TextEntry::make('semantic_error_banner')
                            ->hiddenLabel()
                            ->getStateUsing(fn ($record) => 'Semantischer Fehler: HTTP war erfolgreich (200), aber der Response-Body enthaelt einen Fehler. ' . ($record->getSemanticErrorMessage() ?? 'Details siehe unten.'))
                            ->visible(fn ($record) => $record->hasSemanticError())
                            ->columnSpanFull()
                            ->icon('heroicon-m-exclamation-triangle')
                            ->iconColor('warning')
                            ->weight(FontWeight::SemiBold)
                            ->extraAttributes([
                                'class' => 'bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 text-amber-800 dark:text-amber-200 p-4 rounded-xl',
                            ]),

                        // Exception Banner
                        Infolists\Components\TextEntry::make('exception_banner')
                            ->hiddenLabel()
                            ->getStateUsing(fn ($record) => 'Verbindungsfehler: ' . ($record->error_class ?? 'Unbekannter Fehler') . ' - ' . ($record->error_message ?? 'Keine Details verfuegbar'))
                            ->visible(fn ($record) => $record->getOverallStatus() === 'exception')
                            ->columnSpanFull()
                            ->icon('heroicon-m-bolt')
                            ->iconColor('gray')
                            ->weight(FontWeight::SemiBold)
                            ->extraAttributes([
                                'class' => 'bg-gray-100 dark:bg-gray-800 border border-gray-300 dark:border-gray-600 text-gray-800 dark:text-gray-200 p-4 rounded-xl',
                            ]),
                    ])
                    ->extraAttributes(['class' => 'mb-2']),

                // === REQUEST OVERVIEW ===
                Infolists\Components\Section::make('Anfrage-Details')
                    ->description('Informationen zur API-Anfrage')
                    ->icon('heroicon-m-arrow-up-right')
                    ->schema([
                        Infolists\Components\Grid::make(4)
                            ->schema([
                                Infolists\Components\TextEntry::make('http_method')
                                    ->label('Methode')
                                    ->badge()
                                    ->size('lg')
                                    ->color(fn (string $state): string => match ($state) {
                                        'GET' => 'info',
                                        'POST' => 'success',
                                        'PUT', 'PATCH' => 'warning',
                                        'DELETE' => 'danger',
                                        default => 'gray',
                                    }),

                                Infolists\Components\TextEntry::make('direction')
                                    ->label('Richtung')
                                    ->badge()
                                    ->icon(fn (string $state): string => match ($state) {
                                        'outbound' => 'heroicon-m-arrow-up-right',
                                        'inbound' => 'heroicon-m-arrow-down-left',
                                        default => 'heroicon-m-arrows-right-left',
                                    })
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

                                Infolists\Components\TextEntry::make('created_at')
                                    ->label('Gestartet')
                                    ->icon('heroicon-m-clock')
                                    ->dateTime('d.m.Y H:i:s')
                                    ->helperText(fn ($record) => $record->created_at?->diffForHumans()),

                                Infolists\Components\TextEntry::make('formatted_duration')
                                    ->label('Dauer')
                                    ->icon('heroicon-m-bolt')
                                    ->iconColor(fn ($record) => match (true) {
                                        $record->duration_ms === null => 'gray',
                                        $record->duration_ms < 500 => 'success',
                                        $record->duration_ms < 2000 => 'warning',
                                        default => 'danger',
                                    }),
                            ]),

                        Infolists\Components\TextEntry::make('endpoint')
                            ->label('Endpoint URL')
                            ->fontFamily('mono')
                            ->copyable()
                            ->copyMessage('URL kopiert')
                            ->columnSpanFull()
                            ->extraAttributes([
                                'class' => 'bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-gray-100 p-3 rounded-lg break-all border border-gray-200 dark:border-gray-700',
                            ]),

                        Infolists\Components\TextEntry::make('event_id')
                            ->label('Event ID')
                            ->fontFamily('mono')
                            ->copyable()
                            ->copyMessage('Event ID kopiert')
                            ->helperText('Eindeutige Kennung dieser Anfrage'),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(false),

                // === STATUS & METRICS ===
                Infolists\Components\Section::make('Status & Metriken')
                    ->description('Ergebnis und Leistungsdaten')
                    ->icon('heroicon-m-chart-bar')
                    ->schema([
                        Infolists\Components\Grid::make(4)
                            ->schema([
                                Infolists\Components\TextEntry::make('status_code')
                                    ->label('HTTP Status')
                                    ->badge()
                                    ->size('lg')
                                    ->color(fn ($record): string => $record->status_color ?? 'gray')
                                    ->placeholder('N/A'),

                                Infolists\Components\TextEntry::make('overall_status_display')
                                    ->label('Gesamtergebnis')
                                    ->badge()
                                    ->size('lg')
                                    ->getStateUsing(fn ($record) => match ($record->getOverallStatus()) {
                                        'success' => 'Erfolgreich',
                                        'http_error' => 'HTTP Fehler',
                                        'semantic_error' => 'Semantischer Fehler',
                                        'exception' => 'Exception',
                                        default => 'Unbekannt',
                                    })
                                    ->icon(fn ($record) => match ($record->getOverallStatus()) {
                                        'success' => 'heroicon-m-check-circle',
                                        'http_error' => 'heroicon-m-x-circle',
                                        'semantic_error' => 'heroicon-m-exclamation-triangle',
                                        'exception' => 'heroicon-m-bolt',
                                        default => 'heroicon-m-question-mark-circle',
                                    })
                                    ->color(fn ($record) => match ($record->getOverallStatus()) {
                                        'success' => 'success',
                                        'http_error' => 'danger',
                                        'semantic_error' => 'warning',
                                        'exception' => 'gray',
                                        default => 'gray',
                                    }),

                                Infolists\Components\TextEntry::make('attempt_info')
                                    ->label('Versuch')
                                    ->badge()
                                    ->getStateUsing(fn ($record) => "{$record->attempt_no} / {$record->max_attempts}")
                                    ->icon(fn ($record) => $record->isRetry() ? 'heroicon-m-arrow-path' : 'heroicon-m-play')
                                    ->color(fn ($record) => match (true) {
                                        $record->attempt_no === 1 => 'gray',
                                        $record->attempt_no < $record->max_attempts => 'warning',
                                        default => 'danger',
                                    })
                                    ->helperText(fn ($record) => $record->isRetry() ? 'Wiederholungsversuch' : 'Erster Versuch'),

                                Infolists\Components\IconEntry::make('can_retry')
                                    ->label('Retry moeglich')
                                    ->boolean()
                                    ->trueIcon('heroicon-m-arrow-path')
                                    ->falseIcon('heroicon-m-x-mark')
                                    ->trueColor('success')
                                    ->falseColor('gray')
                                    ->getStateUsing(fn ($record) => $record->canRetry()),
                            ]),

                        // Error Details (only visible when there's an error)
                        Infolists\Components\Fieldset::make('Fehlerdetails')
                            ->schema([
                                Infolists\Components\TextEntry::make('error_class')
                                    ->label('Fehlertyp')
                                    ->fontFamily('mono')
                                    ->badge()
                                    ->color(fn ($record) => $record->hasSemanticError() ? 'warning' : 'danger'),

                                Infolists\Components\TextEntry::make('error_message')
                                    ->label('Fehlermeldung')
                                    ->columnSpanFull()
                                    ->prose()
                                    ->extraAttributes([
                                        'class' => 'bg-red-50 dark:bg-red-900/10 p-3 rounded-lg text-red-800 dark:text-red-200',
                                    ]),
                            ])
                            ->visible(fn ($record) => $record->error_class !== null || $record->error_message !== null)
                            ->columns(1),
                    ])
                    ->collapsible()
                    ->collapsed(false),

                // === REQUEST PAYLOAD ===
                Infolists\Components\Section::make('Request-Body')
                    ->description('Gesendete Daten (sensible Felder maskiert)')
                    ->icon('heroicon-m-arrow-up-on-square')
                    ->schema([
                        Infolists\Components\TextEntry::make('request_json')
                            ->hiddenLabel()
                            ->columnSpanFull()
                            ->getStateUsing(function ($record) {
                                $data = $record->request_body_redacted;
                                if (!$data) return 'Keine Request-Daten vorhanden';
                                return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                            })
                            ->copyable()
                            ->copyMessage('Request-Body kopiert')
                            ->fontFamily('mono')
                            ->extraAttributes([
                                'class' => 'whitespace-pre-wrap text-xs bg-slate-50 dark:bg-slate-900 text-slate-800 dark:text-slate-200 p-4 rounded-xl overflow-x-auto max-h-[500px] overflow-y-auto border border-slate-200 dark:border-slate-700',
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(false),

                // === RESPONSE PAYLOAD ===
                Infolists\Components\Section::make('Response-Body')
                    ->description('Empfangene Daten (sensible Felder maskiert)')
                    ->icon('heroicon-m-arrow-down-on-square')
                    ->schema([
                        Infolists\Components\TextEntry::make('response_json')
                            ->hiddenLabel()
                            ->columnSpanFull()
                            ->getStateUsing(function ($record) {
                                $data = $record->response_body_redacted;
                                if (!$data) return 'Keine Response-Daten vorhanden';
                                return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                            })
                            ->copyable()
                            ->copyMessage('Response-Body kopiert')
                            ->fontFamily('mono')
                            ->extraAttributes([
                                'class' => 'whitespace-pre-wrap text-xs bg-slate-50 dark:bg-slate-900 text-slate-800 dark:text-slate-200 p-4 rounded-xl overflow-x-auto max-h-[500px] overflow-y-auto border border-slate-200 dark:border-slate-700',
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(false)
                    ->visible(fn ($record) => $record->response_body_redacted !== null),

                // === HEADERS ===
                Infolists\Components\Section::make('HTTP Headers')
                    ->description('Request-Header (sensible Felder maskiert)')
                    ->icon('heroicon-m-document-text')
                    ->schema([
                        Infolists\Components\TextEntry::make('headers_json')
                            ->hiddenLabel()
                            ->columnSpanFull()
                            ->getStateUsing(function ($record) {
                                $data = $record->headers_redacted;
                                if (!$data) return 'Keine Header-Daten vorhanden';
                                return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                            })
                            ->copyable()
                            ->copyMessage('Headers kopiert')
                            ->fontFamily('mono')
                            ->extraAttributes([
                                'class' => 'whitespace-pre-wrap text-xs bg-slate-50 dark:bg-slate-900 text-slate-800 dark:text-slate-200 p-4 rounded-xl overflow-x-auto border border-slate-200 dark:border-slate-700',
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(false)
                    ->visible(fn ($record) => $record->headers_redacted !== null),

                // === CORRELATION & REFERENCES ===
                Infolists\Components\Section::make('Verknuepfungen & Referenzen')
                    ->description('IDs zur Nachverfolgung zusammengehoeriger Anfragen')
                    ->icon('heroicon-m-link')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('correlation_id')
                                    ->label('Correlation ID')
                                    ->fontFamily('mono')
                                    ->copyable()
                                    ->copyMessage('Correlation ID kopiert')
                                    ->placeholder('Keine Correlation ID')
                                    ->helperText('Verknuepft zusammengehoerige Anfragen'),

                                Infolists\Components\TextEntry::make('parent_event_id')
                                    ->label('Parent Event ID')
                                    ->fontFamily('mono')
                                    ->copyable()
                                    ->placeholder('Keine Parent-Referenz')
                                    ->visible(fn ($record) => $record->parent_event_id !== null)
                                    ->helperText('Urspruengliche Anfrage (bei Retries)'),
                            ]),

                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('call_id')
                                    ->label('Call ID')
                                    ->icon('heroicon-m-phone')
                                    ->placeholder('N/A'),

                                Infolists\Components\TextEntry::make('service_case_id')
                                    ->label('Service Case ID')
                                    ->icon('heroicon-m-ticket')
                                    ->placeholder('N/A'),

                                Infolists\Components\TextEntry::make('company.name')
                                    ->label('Unternehmen')
                                    ->icon('heroicon-m-building-office')
                                    ->placeholder('N/A'),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(false),
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
