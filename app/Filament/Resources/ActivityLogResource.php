<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityLogResource\Pages;
use App\Models\ActivityLog;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\Split;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ActivityLogResource extends Resource
{
    protected static ?string $model = ActivityLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Aktivitätsprotokoll';

    protected static ?string $modelLabel = 'Aktivität';

    protected static ?string $pluralModelLabel = 'Aktivitäten';

    protected static ?string $navigationGroup = 'System';

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'description';

    public static function getNavigationBadge(): ?string
    {
        return null; // EMERGENCY: Disabled to prevent memory exhaustion
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return null; // EMERGENCY: Disabled to prevent memory exhaustion
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return null; // EMERGENCY: Disabled to prevent memory exhaustion
    }

    public static function canCreate(): bool
    {
        return false; // Activity logs are system-generated
    }

    public static function canEdit(Model $record): bool
    {
        return false; // Activity logs are immutable
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()->hasRole('super-admin') && $record->created_at < now()->subDays(30);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Activity logs are read-only
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Zeitpunkt')
                    ->dateTime('d.m.Y H:i:s')
                    ->sortable()
                    ->searchable()
                    ->icon('heroicon-m-clock')
                    ->iconColor('gray')
                    ->description(fn (ActivityLog $record) => $record->created_at->diffForHumans()),

                Tables\Columns\TextColumn::make('severity')
                    ->badge()
                    ->label('Schweregrad')
                    ->formatStateUsing(fn (ActivityLog $record) => match($record->severity) {
                        ActivityLog::SEVERITY_DEBUG => '🔍 Debug',
                        ActivityLog::SEVERITY_INFO => 'ℹ️ Info',
                        ActivityLog::SEVERITY_NOTICE => '📋 Hinweis',
                        ActivityLog::SEVERITY_WARNING => '⚠️ Warnung',
                        ActivityLog::SEVERITY_ERROR => '❌ Fehler',
                        ActivityLog::SEVERITY_CRITICAL => '🔴 Kritisch',
                        ActivityLog::SEVERITY_ALERT => '🚨 Alarm',
                        ActivityLog::SEVERITY_EMERGENCY => '🆘 Notfall',
                        default => '❓ Unbekannt'
                    })
                    ->colors([
                        'gray' => ActivityLog::SEVERITY_DEBUG,
                        'info' => ActivityLog::SEVERITY_INFO,
                        'primary' => ActivityLog::SEVERITY_NOTICE,
                        'warning' => ActivityLog::SEVERITY_WARNING,
                        'danger' => ActivityLog::SEVERITY_ERROR,
                        'danger' => ActivityLog::SEVERITY_CRITICAL,
                        'danger' => ActivityLog::SEVERITY_ALERT,
                        'danger' => ActivityLog::SEVERITY_EMERGENCY,
                    ])
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->label('Typ')
                    ->formatStateUsing(fn (ActivityLog $record) => $record->type_label)
                    ->colors([
                        'primary' => ActivityLog::TYPE_AUTH,
                        'success' => ActivityLog::TYPE_DATA,
                        'warning' => ActivityLog::TYPE_SYSTEM,
                        'danger' => ActivityLog::TYPE_ERROR,
                        'info' => ActivityLog::TYPE_API,
                        'secondary' => ActivityLog::TYPE_USER,
                        'gray' => ActivityLog::TYPE_AUDIT,
                        'purple' => ActivityLog::TYPE_PERFORMANCE,
                        'yellow' => ActivityLog::TYPE_SECURITY,
                        'green' => ActivityLog::TYPE_BUSINESS,
                        'blue' => ActivityLog::TYPE_INTEGRATION,
                        'orange' => ActivityLog::TYPE_NOTIFICATION,
                    ])
                    ->searchable(),

                Tables\Columns\TextColumn::make('event')
                    ->badge()
                    ->label('Ereignis')
                    ->formatStateUsing(fn (ActivityLog $record) => $record->event_label)
                    ->color(fn (ActivityLog $record) => $record->severity_color)
                    ->icon(fn (ActivityLog $record) => match($record->severity) {
                        ActivityLog::SEVERITY_CRITICAL,
                        ActivityLog::SEVERITY_EMERGENCY,
                        ActivityLog::SEVERITY_ALERT => 'heroicon-m-exclamation-triangle',
                        ActivityLog::SEVERITY_ERROR => 'heroicon-m-exclamation-circle',
                        ActivityLog::SEVERITY_WARNING => 'heroicon-m-exclamation-triangle',
                        ActivityLog::SEVERITY_NOTICE => 'heroicon-m-information-circle',
                        ActivityLog::SEVERITY_INFO => 'heroicon-m-check-circle',
                        default => 'heroicon-m-minus-circle',
                    })
                    ->searchable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Benutzer')
                    ->searchable()
                    ->sortable()
                    ->default('System')
                    ->formatStateUsing(function (ActivityLog $record) {
                        if (!$record->user) {
                            return new HtmlString('<span class="text-gray-500">🤖 System</span>');
                        }

                        $avatar = 'https://ui-avatars.com/api/?name=' . urlencode($record->user->name) . '&color=7F9CF5&background=EBF4FF&size=32';
                        return new HtmlString(
                            '<div class="flex items-center gap-2">
                                <img src="' . $avatar . '" class="w-6 h-6 rounded-full">
                                <span>' . e($record->user->name) . '</span>
                            </div>'
                        );
                    }),

                Tables\Columns\TextColumn::make('subject_display')
                    ->label('Betreff')
                    ->formatStateUsing(function (ActivityLog $record) {
                        if (!$record->subject_type) {
                            return new HtmlString('<span class="text-gray-500">-</span>');
                        }
                        $model = class_basename($record->subject_type);
                        $id = $record->subject_id;
                        $icon = match($model) {
                            'User' => '👤',
                            'Company' => '🏢',
                            'Tenant' => '🏠',
                            'Call' => '📞',
                            'Agent' => '🤖',
                            default => '📄'
                        };
                        return new HtmlString("$icon $model <span class='text-gray-500'>#$id</span>");
                    })
                    ->searchable(['subject_type', 'subject_id'])
                    ->toggleable(),

                Tables\Columns\TextColumn::make('description')
                    ->label('Beschreibung')
                    ->searchable()
                    ->limit(60)
                    ->tooltip(fn (ActivityLog $record) => $record->description)
                    ->formatStateUsing(function (ActivityLog $record) {
                        $desc = e(substr($record->description, 0, 60));
                        if (strlen($record->description) > 60) {
                            $desc .= '...';
                        }

                        // Add context indicators
                        if ($record->context) {
                            $context = is_array($record->context) ? $record->context : json_decode($record->context, true);
                            if (isset($context['module'])) {
                                $desc = "<span class='text-gray-500'>[{$context['module']}]</span> " . $desc;
                            }
                        }

                        return new HtmlString($desc);
                    }),

                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP-Adresse')
                    ->searchable()
                    
                    ->icon('heroicon-m-globe-alt')
                    ->formatStateUsing(function ($state) {
                        if (!$state) return '-';
                        // Add location indicator for known IPs
                        $location = match(true) {
                            str_starts_with($state, '192.168.') => '🏠 Lokal',
                            str_starts_with($state, '10.') => '🏢 Intern',
                            $state === '127.0.0.1' => '💻 Localhost',
                            default => '🌍 Extern'
                        };
                        return "$state ($location)";
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('method')
                    ->badge()
                    ->label('Methode')
                    ->colors([
                        'success' => 'GET',
                        'warning' => 'POST',
                        'danger' => 'DELETE',
                        'info' => 'PUT',
                        'secondary' => 'PATCH',
                        'gray' => 'HEAD',
                        'primary' => 'OPTIONS',
                    ])
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('url')
                    ->label('URL')
                    ->limit(40)
                    ->tooltip(fn (ActivityLog $record) => $record->url)
                    
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('status_code')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ?: '-')
                    ->color(fn ($state) => match(true) {
                        !$state => 'gray',
                        $state >= 200 && $state < 300 => 'success',
                        $state >= 300 && $state < 400 => 'info',
                        $state >= 400 && $state < 500 => 'warning',
                        $state >= 500 => 'danger',
                        default => 'gray',
                    })
                    ->icon(fn ($state) => match(true) {
                        !$state => null,
                        $state >= 200 && $state < 300 => 'heroicon-m-check-circle',
                        $state >= 300 && $state < 400 => 'heroicon-m-arrow-right-circle',
                        $state >= 400 && $state < 500 => 'heroicon-m-exclamation-triangle',
                        $state >= 500 => 'heroicon-m-x-circle',
                        default => null,
                    })
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('response_time')
                    ->label('Antwortzeit')
                    ->formatStateUsing(fn ($state) => $state ? $state . ' ms' : '-')
                    ->badge()
                    ->color(fn ($state) => match(true) {
                        !$state => 'gray',
                        $state < 100 => 'success',
                        $state < 500 => 'warning',
                        $state < 1000 => 'orange',
                        default => 'danger',
                    })
                    ->icon(fn ($state) => match(true) {
                        !$state => null,
                        $state < 100 => 'heroicon-m-bolt',
                        $state < 500 => 'heroicon-m-clock',
                        default => 'heroicon-m-exclamation-triangle',
                    })
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('tags')
                    ->label('Tags')
                    ->badge()
                    ->separator(',')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('is_read')
                    ->label('Gelesen')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-clock')
                    ->trueColor('success')
                    ->falseColor('warning')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('is_archived')
                    ->label('Archiviert')
                    ->boolean()
                    ->trueIcon('heroicon-o-archive-box')
                    ->falseIcon('heroicon-o-inbox')
                    ->trueColor('gray')
                    ->falseColor('primary')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('properties')
                    ->label('Details')
                    ->icon(fn (ActivityLog $record) => $record->properties && count($record->properties) > 0
                        ? 'heroicon-o-document-text'
                        : 'heroicon-o-minus')
                    ->color(fn (ActivityLog $record) => $record->properties && count($record->properties) > 0
                        ? 'success'
                        : 'gray')
                    ->tooltip(fn (ActivityLog $record) => $record->properties
                        ? count($record->properties) . ' Eigenschaften'
                        : 'Keine Details')
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('severity')
                    ->label('Schweregrad')
                    ->multiple()
                    ->options([
                        ActivityLog::SEVERITY_DEBUG => '🔍 Debug',
                        ActivityLog::SEVERITY_INFO => 'ℹ️ Info',
                        ActivityLog::SEVERITY_NOTICE => '📋 Hinweis',
                        ActivityLog::SEVERITY_WARNING => '⚠️ Warnung',
                        ActivityLog::SEVERITY_ERROR => '❌ Fehler',
                        ActivityLog::SEVERITY_CRITICAL => '🔴 Kritisch',
                        ActivityLog::SEVERITY_ALERT => '🚨 Alarm',
                        ActivityLog::SEVERITY_EMERGENCY => '🆘 Notfall',
                    ]),

                Tables\Filters\SelectFilter::make('type')
                    ->label('Typ')
                    ->multiple()
                    ->options([
                        ActivityLog::TYPE_AUTH => '🔐 Authentifizierung',
                        ActivityLog::TYPE_USER => '👤 Benutzer',
                        ActivityLog::TYPE_SYSTEM => '⚙️ System',
                        ActivityLog::TYPE_DATA => '📊 Daten',
                        ActivityLog::TYPE_API => '🔌 API',
                        ActivityLog::TYPE_ERROR => '❌ Fehler',
                        ActivityLog::TYPE_SECURITY => '🛡️ Sicherheit',
                        ActivityLog::TYPE_AUDIT => '📋 Audit',
                        ActivityLog::TYPE_PERFORMANCE => '⚡ Performance',
                        ActivityLog::TYPE_BUSINESS => '💼 Business',
                        ActivityLog::TYPE_INTEGRATION => '🔗 Integration',
                        ActivityLog::TYPE_NOTIFICATION => '🔔 Benachrichtigung',
                    ]),

                Tables\Filters\SelectFilter::make('event')
                    ->label('Ereignis')
                    ->multiple()
                    ->options([
                        ActivityLog::EVENT_LOGIN => 'Anmeldung',
                        ActivityLog::EVENT_LOGOUT => 'Abmeldung',
                        ActivityLog::EVENT_FAILED_LOGIN => 'Fehlgeschlagene Anmeldung',
                        ActivityLog::EVENT_CREATED => 'Erstellt',
                        ActivityLog::EVENT_UPDATED => 'Aktualisiert',
                        ActivityLog::EVENT_DELETED => 'Gelöscht',
                        ActivityLog::EVENT_VIEWED => 'Angesehen',
                        ActivityLog::EVENT_ERROR => 'Fehler',
                        ActivityLog::EVENT_PERMISSION_DENIED => 'Zugriff verweigert',
                        ActivityLog::EVENT_API_CALL => 'API-Aufruf',
                        ActivityLog::EVENT_EXPORT => 'Export',
                        ActivityLog::EVENT_IMPORT => 'Import',
                        ActivityLog::EVENT_SYNC => 'Synchronisation',
                    ]),

                Tables\Filters\SelectFilter::make('user_id')
                    ->label('Benutzer')
                    ->searchable()
                    ->preload()
                    ->options(User::pluck('name', 'id')),

                Tables\Filters\Filter::make('critical_only')
                    ->label('Nur kritische')
                    ->query(fn (Builder $query): Builder => $query->whereIn('severity', [
                        ActivityLog::SEVERITY_CRITICAL,
                        ActivityLog::SEVERITY_EMERGENCY,
                        ActivityLog::SEVERITY_ALERT,
                        ActivityLog::SEVERITY_ERROR,
                    ]))
                    ->toggle()
                    ->default(false),

                Tables\Filters\Filter::make('unread')
                    ->label('Ungelesen')
                    ->query(fn (Builder $query): Builder => $query->where('is_read', false))
                    ->toggle(),

                Tables\Filters\Filter::make('archived')
                    ->label('Archiviert')
                    ->query(fn (Builder $query): Builder => $query->where('is_archived', true))
                    ->toggle(),

                Tables\Filters\Filter::make('today')
                    ->label('Heute')
                    ->query(fn (Builder $query): Builder => $query->whereDate('created_at', today()))
                    ->toggle(),

                Tables\Filters\Filter::make('this_week')
                    ->label('Diese Woche')
                    ->query(fn (Builder $query): Builder => $query->whereBetween('created_at', [
                        now()->startOfWeek(),
                        now()->endOfWeek()
                    ]))
                    ->toggle(),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Von')
                            ->default(now()->subDays(7)),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Bis')
                            ->default(now()),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),

                Tables\Filters\Filter::make('performance')
                    ->label('Performance-Probleme')
                    ->query(fn (Builder $query): Builder => $query->where('response_time', '>', 1000))
                    ->toggle(),

                Tables\Filters\Filter::make('has_properties')
                    ->label('Mit Details')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('properties')
                        ->where('properties', '!=', '[]'))
                    ->toggle(),
            ])
            ->filtersLayout(Tables\Enums\FiltersLayout::AboveContentCollapsible)
            ->persistFiltersInSession()
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->iconButton()
                    ->tooltip('Details anzeigen'),

                Tables\Actions\Action::make('mark_read')
                    ->label('Als gelesen markieren')
                    ->icon('heroicon-m-check')
                    ->tooltip('Als gelesen markieren')
                    ->color('success')
                    ->action(function (ActivityLog $record) {
                        $record->markAsRead();
                        Notification::make()
                            ->title('Markiert')
                            ->body('Der Eintrag wurde als gelesen markiert.')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (ActivityLog $record) => !$record->is_read),

                Tables\Actions\Action::make('archive')
                    ->label('Archivieren')
                    ->icon('heroicon-m-archive-box')
                    ->tooltip('Archivieren')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(function (ActivityLog $record) {
                        $record->archive();
                        Notification::make()
                            ->title('Archiviert')
                            ->body('Der Eintrag wurde archiviert.')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (ActivityLog $record) => !$record->is_archived),

                Tables\Actions\DeleteAction::make()
                    ->iconButton()
                    ->tooltip('Löschen')
                    ->visible(fn (ActivityLog $record) =>
                        auth()->user()->hasRole('super-admin') &&
                        $record->created_at < now()->subDays(30)
                    ),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('mark_read')
                        ->label('Als gelesen markieren')
                        ->icon('heroicon-m-check')
                        ->color('success')
                        ->action(function (Collection $records) {
                            $records->each->markAsRead();
                            Notification::make()
                                ->title('Markiert')
                                ->body(count($records) . ' Einträge wurden als gelesen markiert.')
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('archive')
                        ->label('Archivieren')
                        ->icon('heroicon-m-archive-box')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            $records->each->archive();
                            Notification::make()
                                ->title('Archiviert')
                                ->body(count($records) . ' Einträge wurden archiviert.')
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('export_csv')
                        ->label('Als CSV exportieren')
                        ->icon('heroicon-m-arrow-down-tray')
                        ->color('success')
                        ->action(function (Collection $records) {
                            $csv = "ID,Zeitpunkt,Schweregrad,Typ,Ereignis,Benutzer,Beschreibung,IP-Adresse,Status,Antwortzeit\n";

                            foreach ($records as $record) {
                                $csv .= sprintf(
                                    "%s,%s,%s,%s,%s,%s,%s,%s,%s,%s\n",
                                    $record->id,
                                    $record->created_at->format('Y-m-d H:i:s'),
                                    $record->severity,
                                    $record->type,
                                    $record->event,
                                    $record->user?->name ?? 'System',
                                    str_replace([',', "\n"], [';', ' '], $record->description),
                                    $record->ip_address ?: '-',
                                    $record->status_code ?: '-',
                                    $record->response_time ? $record->response_time . 'ms' : '-'
                                );
                            }

                            return response()->streamDownload(function () use ($csv) {
                                echo $csv;
                            }, 'activity-log-' . now()->format('Y-m-d-His') . '.csv');
                        })
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('export_json')
                        ->label('Als JSON exportieren')
                        ->icon('heroicon-m-code-bracket')
                        ->color('primary')
                        ->action(function (Collection $records) {
                            $data = $records->map(function ($record) {
                                return [
                                    'id' => $record->id,
                                    'timestamp' => $record->created_at->toIso8601String(),
                                    'severity' => $record->severity,
                                    'type' => $record->type,
                                    'event' => $record->event,
                                    'user' => $record->user?->name,
                                    'user_id' => $record->user_id,
                                    'description' => $record->description,
                                    'ip_address' => $record->ip_address,
                                    'method' => $record->method,
                                    'url' => $record->url,
                                    'status_code' => $record->status_code,
                                    'response_time' => $record->response_time,
                                    'properties' => $record->properties,
                                    'context' => $record->context,
                                    'tags' => $record->tags,
                                ];
                            })->toArray();

                            $json = json_encode($data, JSON_PRETTY_PRINT);

                            return response()->streamDownload(function () use ($json) {
                                echo $json;
                            }, 'activity-log-' . now()->format('Y-m-d-His') . '.json');
                        })
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Protokolle löschen')
                        ->modalDescription('Sind Sie sicher, dass Sie die ausgewählten Protokolle löschen möchten?')
                        ->visible(fn () => auth()->user()->hasRole('super-admin')),
                ])
            ])
            ->headerActions([
                Tables\Actions\Action::make('statistics')
                    ->label('Statistiken')
                    ->icon('heroicon-m-chart-bar')
                    ->color('info')
                    ->modalHeading('Aktivitätsstatistiken')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Schließen')
                    ->modalContent(function () {
                        $stats = [
                            'today' => ActivityLog::whereDate('created_at', today())->count(),
                            'week' => ActivityLog::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
                            'month' => ActivityLog::whereMonth('created_at', now()->month)->count(),
                            'critical' => ActivityLog::where('severity', ActivityLog::SEVERITY_CRITICAL)->whereDate('created_at', today())->count(),
                            'errors' => ActivityLog::where('severity', ActivityLog::SEVERITY_ERROR)->whereDate('created_at', today())->count(),
                            'avg_response' => round(ActivityLog::whereNotNull('response_time')->whereDate('created_at', today())->avg('response_time') ?? 0, 2),
                            'slow_requests' => ActivityLog::where('response_time', '>', 1000)->whereDate('created_at', today())->count(),
                        ];

                        return view('filament.resources.activity-log.statistics', compact('stats'));
                    }),

                Tables\Actions\Action::make('cleanup')
                    ->label('Bereinigen')
                    ->icon('heroicon-m-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Alte Einträge bereinigen')
                    ->modalDescription('Alle Einträge älter als 90 Tage werden gelöscht.')
                    ->action(function () {
                        $deleted = ActivityLog::where('created_at', '<', now()->subDays(90))->delete();

                        Notification::make()
                            ->title('Bereinigung abgeschlossen')
                            ->body("$deleted Einträge wurden gelöscht.")
                            ->success()
                            ->send();
                    })
                    ->visible(fn () => auth()->user()->hasRole('super-admin')),
            ])
            ->poll('30s')
            ->striped()
            ->paginated([25, 50, 100, 200])
            ->defaultPaginationPageOption(50)
            ->emptyStateHeading('Keine Aktivitäten vorhanden')
            ->emptyStateDescription('Systemaktivitäten werden hier angezeigt')
            ->emptyStateIcon('heroicon-o-clipboard-document-list');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('🔍 Aktivitätsübersicht')
                    ->description('Detaillierte Informationen zu diesem Ereignis')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        Split::make([
                            Section::make([
                                TextEntry::make('severity')
                                    ->label('Schweregrad')
                                    ->formatStateUsing(fn (ActivityLog $record) => match($record->severity) {
                                        ActivityLog::SEVERITY_DEBUG => '🔍 Debug',
                                        ActivityLog::SEVERITY_INFO => 'ℹ️ Info',
                                        ActivityLog::SEVERITY_NOTICE => '📋 Hinweis',
                                        ActivityLog::SEVERITY_WARNING => '⚠️ Warnung',
                                        ActivityLog::SEVERITY_ERROR => '❌ Fehler',
                                        ActivityLog::SEVERITY_CRITICAL => '🔴 Kritisch',
                                        ActivityLog::SEVERITY_ALERT => '🚨 Alarm',
                                        ActivityLog::SEVERITY_EMERGENCY => '🆘 Notfall',
                                        default => '❓ Unbekannt'
                                    })
                                    ->badge()
                                    ->color(fn (ActivityLog $record) => match($record->severity) {
                                        ActivityLog::SEVERITY_DEBUG => 'gray',
                                        ActivityLog::SEVERITY_INFO => 'info',
                                        ActivityLog::SEVERITY_NOTICE => 'primary',
                                        ActivityLog::SEVERITY_WARNING => 'warning',
                                        ActivityLog::SEVERITY_ERROR,
                                        ActivityLog::SEVERITY_CRITICAL,
                                        ActivityLog::SEVERITY_ALERT,
                                        ActivityLog::SEVERITY_EMERGENCY => 'danger',
                                        default => 'secondary',
                                    }),

                                TextEntry::make('type')
                                    ->label('Typ')
                                    ->formatStateUsing(fn (ActivityLog $record) => $record->type_label)
                                    ->badge()
                                    ->color(fn (ActivityLog $record) => match($record->type) {
                                        ActivityLog::TYPE_AUTH => 'primary',
                                        ActivityLog::TYPE_DATA => 'success',
                                        ActivityLog::TYPE_SYSTEM => 'warning',
                                        ActivityLog::TYPE_ERROR => 'danger',
                                        ActivityLog::TYPE_API => 'info',
                                        ActivityLog::TYPE_USER => 'secondary',
                                        default => 'gray',
                                    }),

                                TextEntry::make('event')
                                    ->label('Ereignis')
                                    ->formatStateUsing(fn (ActivityLog $record) => $record->event_label)
                                    ->badge()
                                    ->color(fn (ActivityLog $record) => $record->severity_color),

                                TextEntry::make('created_at')
                                    ->label('Zeitpunkt')
                                    ->dateTime('d.m.Y H:i:s')
                                    ->description(fn (ActivityLog $record) => $record->created_at->diffForHumans()),

                                TextEntry::make('user.name')
                                    ->label('Benutzer')
                                    ->default('System')
                                    ->description(fn (ActivityLog $record) => $record->user?->email),
                            ])->grow(false),

                            Section::make([
                                TextEntry::make('ip_address')
                                    ->label('IP-Adresse')
                                    
                                    ->default('-')
                                    ->formatStateUsing(function ($state) {
                                        if (!$state) return '-';
                                        $location = match(true) {
                                            str_starts_with($state, '192.168.') => 'Lokal',
                                            str_starts_with($state, '10.') => 'Intern',
                                            $state === '127.0.0.1' => 'Localhost',
                                            default => 'Extern'
                                        };
                                        return "$state ($location)";
                                    }),

                                TextEntry::make('method')
                                    ->label('HTTP Methode')
                                    ->badge()
                                    ->color(fn ($state) => match($state) {
                                        'GET' => 'success',
                                        'POST' => 'warning',
                                        'DELETE' => 'danger',
                                        'PUT' => 'info',
                                        'PATCH' => 'secondary',
                                        default => 'gray',
                                    })
                                    ->default('-'),

                                TextEntry::make('status_code')
                                    ->label('Statuscode')
                                    ->badge()
                                    ->color(fn ($state) => match(true) {
                                        !$state => 'gray',
                                        $state >= 200 && $state < 300 => 'success',
                                        $state >= 300 && $state < 400 => 'info',
                                        $state >= 400 && $state < 500 => 'warning',
                                        $state >= 500 => 'danger',
                                        default => 'gray',
                                    })
                                    ->default('-'),

                                TextEntry::make('response_time')
                                    ->label('Antwortzeit')
                                    ->formatStateUsing(fn ($state) => $state ? $state . ' ms' : '-')
                                    ->badge()
                                    ->color(fn ($state) => match(true) {
                                        !$state => 'gray',
                                        $state < 100 => 'success',
                                        $state < 500 => 'warning',
                                        $state < 1000 => 'orange',
                                        default => 'danger',
                                    })
                                    ->description(fn ($state) => match(true) {
                                        !$state => null,
                                        $state < 100 => 'Sehr schnell',
                                        $state < 500 => 'Akzeptabel',
                                        $state < 1000 => 'Langsam',
                                        default => 'Zu langsam',
                                    }),

                                TextEntry::make('memory_usage')
                                    ->label('Speichernutzung')
                                    ->formatStateUsing(fn ($state) => $state ? round($state / 1024 / 1024, 2) . ' MB' : '-')
                                    ->badge()
                                    ->color(fn ($state) => match(true) {
                                        !$state => 'gray',
                                        $state < 50 * 1024 * 1024 => 'success',
                                        $state < 100 * 1024 * 1024 => 'warning',
                                        default => 'danger',
                                    }),
                            ]),
                        ])->from('md'),
                    ]),

                Section::make('📝 Beschreibung')
                    ->schema([
                        TextEntry::make('description')
                            ->label('')
                            ->columnSpanFull()
                            ->prose()
                            ->markdown(),
                    ])
                    ->collapsible(),

                Section::make('🎯 Betreff & Kontext')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('subject_type')
                                    ->label('Betreff-Modell')
                                    ->formatStateUsing(fn ($state) => $state ? class_basename($state) : '-'),

                                TextEntry::make('subject_id')
                                    ->label('Betreff-ID')
                                    ->default('-')
                                    ,

                                TextEntry::make('causer_type')
                                    ->label('Verursacher-Typ')
                                    ->formatStateUsing(fn ($state) => $state ? class_basename($state) : '-'),

                                TextEntry::make('causer_id')
                                    ->label('Verursacher-ID')
                                    ->default('-')
                                    ,
                            ]),

                        TextEntry::make('batch_uuid')
                            ->label('Batch UUID')
                            
                            ->default('-')
                            ->columnSpanFull(),
                    ])
                    ->collapsed(),

                Section::make('🌐 Request-Informationen')
                    ->schema([
                        TextEntry::make('url')
                            ->label('URL')
                            
                            ->columnSpanFull()
                            ->default('-')
                            ->limit(100),

                        TextEntry::make('user_agent')
                            ->label('User Agent')
                            ->columnSpanFull()
                            ->default('-')
                            ->formatStateUsing(function ($state) {
                                if (!$state) return '-';

                                // Parse browser and OS
                                $browser = match(true) {
                                    str_contains($state, 'Chrome') => '🌐 Chrome',
                                    str_contains($state, 'Firefox') => '🦊 Firefox',
                                    str_contains($state, 'Safari') => '🧭 Safari',
                                    str_contains($state, 'Edge') => '🔷 Edge',
                                    default => '🌐 Unbekannt'
                                };

                                $os = match(true) {
                                    str_contains($state, 'Windows') => '🪟 Windows',
                                    str_contains($state, 'Mac') => '🍎 macOS',
                                    str_contains($state, 'Linux') => '🐧 Linux',
                                    str_contains($state, 'Android') => '🤖 Android',
                                    str_contains($state, 'iOS') => '📱 iOS',
                                    default => '💻 Unbekannt'
                                };

                                return "$browser | $os\n" . substr($state, 0, 100) . '...';
                            }),

                        TextEntry::make('referer')
                            ->label('Referer')
                            
                            ->columnSpanFull()
                            ->default('-'),
                    ])
                    ->collapsed(),

                Section::make('📊 Eigenschaften & Metadaten')
                    ->schema([
                        KeyValueEntry::make('properties')
                            ->label('Eigenschaften')
                            ->columnSpanFull()
                            ->default([]),

                        KeyValueEntry::make('context')
                            ->label('Kontext')
                            ->columnSpanFull()
                            ->default([]),

                        TextEntry::make('tags')
                            ->label('Tags')
                            ->badge()
                            ->separator(',')
                            ->default([]),

                        Grid::make(2)
                            ->schema([
                                TextEntry::make('is_read')
                                    ->label('Gelesen')
                                    ->badge()
                                    ->formatStateUsing(fn ($state) => $state ? '✅ Ja' : '❌ Nein')
                                    ->color(fn ($state) => $state ? 'success' : 'warning'),

                                TextEntry::make('is_archived')
                                    ->label('Archiviert')
                                    ->badge()
                                    ->formatStateUsing(fn ($state) => $state ? '📦 Ja' : '📬 Nein')
                                    ->color(fn ($state) => $state ? 'gray' : 'primary'),

                                TextEntry::make('read_at')
                                    ->label('Gelesen am')
                                    ->dateTime('d.m.Y H:i:s')
                                    ->default('-'),

                                TextEntry::make('archived_at')
                                    ->label('Archiviert am')
                                    ->dateTime('d.m.Y H:i:s')
                                    ->default('-'),
                            ]),
                    ])
                    ->collapsed(),

                Section::make('🔄 Änderungen')
                    ->schema([
                        KeyValueEntry::make('old_values')
                            ->label('Alte Werte')
                            ->columnSpanFull()
                            ->default([]),

                        KeyValueEntry::make('new_values')
                            ->label('Neue Werte')
                            ->columnSpanFull()
                            ->default([]),
                    ])
                    ->collapsed()
                    ->visible(fn (ActivityLog $record) =>
                        $record->old_values || $record->new_values
                    ),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivityLogs::route('/'),
            'view' => Pages\ViewActivityLog::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['user', 'subject', 'causer']);
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return substr($record->description, 0, 50);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['description', 'event', 'type', 'ip_address', 'url'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Typ' => $record->type_label,
            'Benutzer' => $record->user?->name ?? 'System',
            'Zeitpunkt' => $record->created_at->format('d.m.Y H:i'),
        ];
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with(['user']);
    }

    public static function getWidgets(): array
    {
        return [
            // Add statistics widget if needed
        ];
    }
}