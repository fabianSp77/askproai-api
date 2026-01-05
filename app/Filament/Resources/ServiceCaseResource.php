<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServiceCaseResource\Pages;
use App\Models\ServiceCase;
use App\Models\ServiceCaseCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ServiceCaseResource extends Resource
{
    protected static ?string $model = ServiceCase::class;
    protected static ?string $navigationIcon = 'heroicon-o-ticket';
    protected static ?string $navigationGroup = 'Service Gateway';
    protected static ?string $navigationLabel = 'Service Cases';
    protected static ?string $modelLabel = 'Service Case';
    protected static ?string $pluralModelLabel = 'Service Cases';
    protected static ?int $navigationSort = 10;

    /**
     * Only show in navigation when Service Gateway is enabled.
     * @see config/gateway.php 'mode_enabled'
     */
    public static function shouldRegisterNavigation(): bool
    {
        return config('gateway.mode_enabled', false);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Case Details')
                    ->schema([
                        Forms\Components\TextInput::make('subject')
                            ->label('Betreff')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('description')
                            ->label('Beschreibung')
                            ->required()
                            ->rows(4)
                            ->columnSpanFull(),
                        Forms\Components\Select::make('category_id')
                            ->label('Kategorie')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\Textarea::make('description')
                                    ->rows(3),
                                Forms\Components\TextInput::make('sla_response_hours')
                                    ->label('SLA Response (Stunden)')
                                    ->numeric()
                                    ->default(4),
                                Forms\Components\TextInput::make('sla_resolution_hours')
                                    ->label('SLA Resolution (Stunden)')
                                    ->numeric()
                                    ->default(24),
                            ]),
                        Forms\Components\Select::make('case_type')
                            ->label('Typ')
                            ->options([
                                ServiceCase::TYPE_INCIDENT => 'Störung',
                                ServiceCase::TYPE_REQUEST => 'Anfrage',
                                ServiceCase::TYPE_INQUIRY => 'Anliegen',
                            ])
                            ->required()
                            ->default(ServiceCase::TYPE_INQUIRY),
                        Forms\Components\Select::make('call_id')
                            ->label('Zugehöriger Anruf')
                            ->relationship('call', 'id')
                            ->searchable()
                            ->preload()
                            ->disabled(fn ($record) => $record && $record->call_id),
                        Forms\Components\Select::make('customer_id')
                            ->label('Kunde')
                            ->relationship('customer', 'name')
                            ->searchable()
                            ->preload(),
                    ])->columns(2),

                Forms\Components\Section::make('Priority & Status')
                    ->schema([
                        Forms\Components\Select::make('priority')
                            ->label('Priorität')
                            ->options([
                                ServiceCase::PRIORITY_LOW => 'Niedrig',
                                ServiceCase::PRIORITY_NORMAL => 'Normal',
                                ServiceCase::PRIORITY_HIGH => 'Hoch',
                                ServiceCase::PRIORITY_CRITICAL => 'Kritisch',
                            ])
                            ->required()
                            ->default(ServiceCase::PRIORITY_NORMAL),
                        Forms\Components\Select::make('urgency')
                            ->label('Dringlichkeit')
                            ->options([
                                ServiceCase::PRIORITY_LOW => 'Niedrig',
                                ServiceCase::PRIORITY_NORMAL => 'Normal',
                                ServiceCase::PRIORITY_HIGH => 'Hoch',
                                ServiceCase::PRIORITY_CRITICAL => 'Kritisch',
                            ])
                            ->required()
                            ->default(ServiceCase::PRIORITY_NORMAL),
                        Forms\Components\Select::make('impact')
                            ->label('Auswirkung')
                            ->options([
                                ServiceCase::PRIORITY_LOW => 'Niedrig',
                                ServiceCase::PRIORITY_NORMAL => 'Normal',
                                ServiceCase::PRIORITY_HIGH => 'Hoch',
                                ServiceCase::PRIORITY_CRITICAL => 'Kritisch',
                            ])
                            ->required()
                            ->default(ServiceCase::PRIORITY_NORMAL),
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                ServiceCase::STATUS_NEW => 'Neu',
                                ServiceCase::STATUS_OPEN => 'Offen',
                                ServiceCase::STATUS_PENDING => 'Wartend',
                                ServiceCase::STATUS_RESOLVED => 'Gelöst',
                                ServiceCase::STATUS_CLOSED => 'Geschlossen',
                            ])
                            ->required()
                            ->default(ServiceCase::STATUS_NEW),
                        Forms\Components\Select::make('assigned_to')
                            ->label('Zugewiesen an (Person)')
                            ->relationship('assignedTo', 'name')
                            ->searchable()
                            ->preload()
                            ->helperText('Individuelle Zuweisung'),
                        Forms\Components\Select::make('assigned_group_id')
                            ->label('Zugewiesen an (Gruppe)')
                            ->relationship('assignedGroup', 'name')
                            ->searchable()
                            ->preload()
                            ->helperText('Team-basierte Zuweisung'),
                        Forms\Components\TextInput::make('external_reference')
                            ->label('Externe Referenz')
                            ->maxLength(255),
                    ])->columns(3),

                Forms\Components\Section::make('SLA & Output')
                    ->schema([
                        Forms\Components\DateTimePicker::make('sla_response_due_at')
                            ->label('SLA Response Deadline')
                            ->disabled(),
                        Forms\Components\DateTimePicker::make('sla_resolution_due_at')
                            ->label('SLA Resolution Deadline')
                            ->disabled(),
                        Forms\Components\Select::make('output_status')
                            ->label('Output Status')
                            ->options([
                                ServiceCase::OUTPUT_PENDING => 'Ausstehend',
                                ServiceCase::OUTPUT_SENT => 'Gesendet',
                                ServiceCase::OUTPUT_FAILED => 'Fehlgeschlagen',
                            ])
                            ->disabled(),
                        Forms\Components\DateTimePicker::make('output_sent_at')
                            ->label('Output gesendet am')
                            ->disabled(),
                        Forms\Components\Textarea::make('output_error')
                            ->label('Output Fehler')
                            ->rows(2)
                            ->disabled()
                            ->columnSpanFull(),
                    ])->columns(2)
                    ->collapsible()
                    ->collapsed()
                    ->visibleOn('view'),  // Hide in Edit/Create - SLA info already in ViewServiceCase sidebar

                Forms\Components\Section::make('AI Metadata')
                    ->schema([
                        Forms\Components\KeyValue::make('structured_data')
                            ->label('Strukturierte Daten')
                            ->columnSpanFull(),
                        Forms\Components\KeyValue::make('ai_metadata')
                            ->label('KI Metadaten')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->visibleOn('view'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // 🎫 Phase 2: ServiceNow-Style 3-Zeilen Summary (Primäransicht)
                Tables\Columns\ViewColumn::make('summary')
                    ->label('Ticket')
                    ->view('filament.columns.service-case-list-row')
                    ->searchable(query: function ($query, $search) {
                        return $query->where(function ($q) use ($search) {
                            $q->where('id', 'like', "%{$search}%")
                              ->orWhere('subject', 'like', "%{$search}%")
                              ->orWhere('ai_metadata->customer_name', 'like', "%{$search}%")
                              ->orWhere('ai_metadata->customer_phone', 'like', "%{$search}%");
                        });
                    }),

                // === Detail-Spalten (standardmäßig ausgeblendet, für erweiterte Ansicht) ===
                Tables\Columns\TextColumn::make('formatted_id')
                    ->label('Ticket-ID')
                    ->sortable(query: fn ($query, $direction) => $query->orderBy('id', $direction))
                    ->searchable(query: fn ($query, $search) => $query->where('id', 'like', "%{$search}%"))
                    ->copyable()
                    ->copyMessage('Ticket-ID kopiert')
                    ->weight('bold')
                    ->color('primary')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('subject')
                    ->label('Betreff')
                    ->searchable()
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->subject)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\BadgeColumn::make('case_type')
                    ->label('Typ')
                    ->colors([
                        'danger' => ServiceCase::TYPE_INCIDENT,
                        'warning' => ServiceCase::TYPE_REQUEST,
                        'info' => ServiceCase::TYPE_INQUIRY,
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        ServiceCase::TYPE_INCIDENT => 'Störung',
                        ServiceCase::TYPE_REQUEST => 'Anfrage',
                        ServiceCase::TYPE_INQUIRY => 'Anliegen',
                        default => $state,
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\BadgeColumn::make('priority')
                    ->label('Priorität')
                    ->colors([
                        'gray' => ServiceCase::PRIORITY_LOW,
                        'primary' => ServiceCase::PRIORITY_NORMAL,
                        'warning' => ServiceCase::PRIORITY_HIGH,
                        'danger' => ServiceCase::PRIORITY_CRITICAL,
                    ])
                    ->icons([
                        'heroicon-o-arrow-down' => ServiceCase::PRIORITY_LOW,
                        'heroicon-o-minus' => ServiceCase::PRIORITY_NORMAL,
                        'heroicon-o-arrow-up' => ServiceCase::PRIORITY_HIGH,
                        'heroicon-o-fire' => ServiceCase::PRIORITY_CRITICAL,
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        ServiceCase::PRIORITY_LOW => 'Niedrig',
                        ServiceCase::PRIORITY_NORMAL => 'Normal',
                        ServiceCase::PRIORITY_HIGH => 'Hoch',
                        ServiceCase::PRIORITY_CRITICAL => 'Kritisch',
                        default => $state,
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'gray' => ServiceCase::STATUS_NEW,
                        'info' => ServiceCase::STATUS_OPEN,
                        'warning' => ServiceCase::STATUS_PENDING,
                        'success' => ServiceCase::STATUS_RESOLVED,
                        'primary' => ServiceCase::STATUS_CLOSED,
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        ServiceCase::STATUS_NEW => 'Neu',
                        ServiceCase::STATUS_OPEN => 'Offen',
                        ServiceCase::STATUS_PENDING => 'Wartend',
                        ServiceCase::STATUS_RESOLVED => 'Gelöst',
                        ServiceCase::STATUS_CLOSED => 'Geschlossen',
                        default => $state,
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Kategorie')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                // Caller from ai_metadata (Voice AI captured data)
                Tables\Columns\TextColumn::make('ai_metadata.customer_name')
                    ->label('Anrufer')
                    ->placeholder('—')
                    ->searchable(query: function ($query, $search) {
                        return $query->where('ai_metadata->customer_name', 'like', "%{$search}%");
                    })
                    ->toggleable(),
                Tables\Columns\TextColumn::make('ai_metadata.customer_phone')
                    ->label('Telefon')
                    ->placeholder('—')
                    ->copyable()
                    ->copyMessage('Telefonnummer kopiert')
                    ->searchable(query: function ($query, $search) {
                        return $query->where('ai_metadata->customer_phone', 'like', "%{$search}%");
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('ai_metadata.others_affected')
                    ->label('Mehrere betroffen')
                    ->boolean()
                    ->trueIcon('heroicon-o-user-group')
                    ->falseIcon('heroicon-o-user')
                    ->trueColor('danger')
                    ->falseColor('gray')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('CRM Kunde')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('assignedTo.name')
                    ->label('Zugewiesen (Person)')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('assignedGroup.name')
                    ->label('Zugewiesen (Gruppe)')
                    ->icon('heroicon-o-user-group')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('sla_status')
                    ->label('SLA')
                    ->state(function (ServiceCase $record): ?string {
                        if ($record->isResolutionOverdue()) {
                            return 'overdue';
                        }
                        if ($record->isResponseOverdue()) {
                            return 'warning';
                        }
                        return 'ok';
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'overdue' => 'heroicon-o-exclamation-circle',
                        'warning' => 'heroicon-o-exclamation-triangle',
                        'ok' => 'heroicon-o-check-circle',
                        default => 'heroicon-o-clock',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'overdue' => 'danger',
                        'warning' => 'warning',
                        'ok' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\BadgeColumn::make('output_status')
                    ->label('Output')
                    ->colors([
                        'warning' => ServiceCase::OUTPUT_PENDING,
                        'success' => ServiceCase::OUTPUT_SENT,
                        'danger' => ServiceCase::OUTPUT_FAILED,
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        ServiceCase::OUTPUT_PENDING => 'Ausstehend',
                        ServiceCase::OUTPUT_SENT => 'Gesendet',
                        ServiceCase::OUTPUT_FAILED => 'Fehler',
                        default => $state,
                    })
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            // ServiceNow-Style Row Highlighting für visuelle Hierarchie
            ->recordClasses(function (ServiceCase $record): ?string {
                // SLA überfällig = höchste Priorität (rot)
                if ($record->isResolutionOverdue() || $record->isResponseOverdue()) {
                    return 'bg-red-50 dark:bg-red-950/20 border-l-4 border-l-red-500';
                }
                // Kritisch/Hoch + Offen = Aufmerksamkeit erforderlich (amber)
                if (in_array($record->priority, [ServiceCase::PRIORITY_CRITICAL, ServiceCase::PRIORITY_HIGH]) && $record->isOpen()) {
                    return 'bg-amber-50 dark:bg-amber-950/20 border-l-4 border-l-amber-500';
                }
                // Output fehlgeschlagen = Problem (rose)
                if ($record->output_status === ServiceCase::OUTPUT_FAILED) {
                    return 'bg-rose-50 dark:bg-rose-950/20 border-l-4 border-l-rose-500';
                }
                return null;
            })
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        ServiceCase::STATUS_NEW => 'Neu',
                        ServiceCase::STATUS_OPEN => 'Offen',
                        ServiceCase::STATUS_PENDING => 'Wartend',
                        ServiceCase::STATUS_RESOLVED => 'Gelöst',
                        ServiceCase::STATUS_CLOSED => 'Geschlossen',
                    ])
                    ->multiple(),
                Tables\Filters\SelectFilter::make('priority')
                    ->label('Priorität')
                    ->options([
                        ServiceCase::PRIORITY_LOW => 'Niedrig',
                        ServiceCase::PRIORITY_NORMAL => 'Normal',
                        ServiceCase::PRIORITY_HIGH => 'Hoch',
                        ServiceCase::PRIORITY_CRITICAL => 'Kritisch',
                    ])
                    ->multiple(),
                Tables\Filters\SelectFilter::make('case_type')
                    ->label('Typ')
                    ->options([
                        ServiceCase::TYPE_INCIDENT => 'Störung',
                        ServiceCase::TYPE_REQUEST => 'Anfrage',
                        ServiceCase::TYPE_INQUIRY => 'Anliegen',
                    ])
                    ->multiple(),
                Tables\Filters\SelectFilter::make('category_id')
                    ->label('Kategorie')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('assigned_group_id')
                    ->label('Zuweisungsgruppe')
                    ->relationship('assignedGroup', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('assigned_to')
                    ->label('Zugewiesen an (Person)')
                    ->relationship('assignedTo', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\Filter::make('open')
                    ->label('Nur offene Cases')
                    ->query(fn (Builder $query): Builder => $query->open())
                    ->toggle(),
                // FIX: Only filter cases that have SLA dates set (exclude pass-through companies with NULL SLA)
                Tables\Filters\Filter::make('overdue')
                    ->label('SLA überschritten')
                    ->query(fn (Builder $query): Builder => $query->where(function ($q) {
                        $q->where(function ($q2) {
                            $q2->whereNotNull('sla_resolution_due_at')
                               ->where('sla_resolution_due_at', '<', now());
                        })->orWhere(function ($q2) {
                            $q2->whereNotNull('sla_response_due_at')
                               ->where('sla_response_due_at', '<', now());
                        });
                    }))
                    ->toggle(),
                Tables\Filters\Filter::make('output_failed')
                    ->label('Output fehlgeschlagen')
                    ->query(fn (Builder $query): Builder => $query->where('output_status', ServiceCase::OUTPUT_FAILED))
                    ->toggle(),

                // 📅 Phase 4: Zeitraum-Filter (ServiceNow-Style)
                Tables\Filters\Filter::make('created_at')
                    ->label('Erstellungszeitraum')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Von')
                            ->native(false)
                            ->displayFormat('d.m.Y')
                            ->placeholder('TT.MM.JJJJ'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Bis')
                            ->native(false)
                            ->displayFormat('d.m.Y')
                            ->placeholder('TT.MM.JJJJ'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['created_from'] ?? null) {
                            $indicators['created_from'] = 'Ab: ' . \Carbon\Carbon::parse($data['created_from'])->format('d.m.Y');
                        }
                        if ($data['created_until'] ?? null) {
                            $indicators['created_until'] = 'Bis: ' . \Carbon\Carbon::parse($data['created_until'])->format('d.m.Y');
                        }
                        return $indicators;
                    }),

                // 🔄 Phase 4: Enrichment-Status Filter (für Troubleshooting)
                Tables\Filters\SelectFilter::make('enrichment_status')
                    ->label('Enrichment-Status')
                    ->options([
                        'pending' => '⏳ Ausstehend',
                        'enriched' => '✅ Angereichert',
                        'failed' => '❌ Fehlgeschlagen',
                        'skipped' => '⏭️ Übersprungen',
                    ])
                    ->placeholder('Alle'),

                // 📤 Phase 4: Output-Status Filter (erweitert)
                Tables\Filters\SelectFilter::make('output_status')
                    ->label('Output-Status')
                    ->options([
                        ServiceCase::OUTPUT_PENDING => '⏳ Ausstehend',
                        ServiceCase::OUTPUT_SENT => '✅ Gesendet',
                        ServiceCase::OUTPUT_FAILED => '❌ Fehlgeschlagen',
                    ])
                    ->placeholder('Alle'),
            ])
            ->actions([
                // 🎯 Phase 3: Quick Action - Mir zuweisen (ServiceNow-Style)
                Tables\Actions\Action::make('assign_to_me')
                    ->icon('heroicon-o-user-plus')
                    ->iconButton()
                    ->tooltip('Mir zuweisen')
                    ->color('primary')
                    ->visible(fn (ServiceCase $record): bool =>
                        $record->assigned_to !== \Illuminate\Support\Facades\Auth::user()?->staff?->id
                        && \Illuminate\Support\Facades\Auth::user()?->staff !== null
                        && $record->isOpen()
                    )
                    ->action(function (ServiceCase $record) {
                        $staffId = \Illuminate\Support\Facades\Auth::user()->staff->id;
                        $record->update(['assigned_to' => $staffId]);
                        \Filament\Notifications\Notification::make()
                            ->title('Ticket zugewiesen')
                            ->body('Das Ticket wurde Ihnen zugewiesen.')
                            ->success()
                            ->send();
                    }),

                // 🚨 Phase 3: Quick Action - Priorität ändern
                Tables\Actions\Action::make('change_priority')
                    ->icon('heroicon-o-arrow-trending-up')
                    ->iconButton()
                    ->tooltip('Priorität ändern')
                    ->color('warning')
                    ->visible(fn (ServiceCase $record): bool => $record->isOpen())
                    ->form([
                        Forms\Components\Select::make('priority')
                            ->label('Neue Priorität')
                            ->options([
                                ServiceCase::PRIORITY_LOW => '↓ Niedrig',
                                ServiceCase::PRIORITY_NORMAL => '– Normal',
                                ServiceCase::PRIORITY_HIGH => '↑ Hoch',
                                ServiceCase::PRIORITY_CRITICAL => '🔥 Kritisch',
                            ])
                            ->default(fn (ServiceCase $record) => $record->priority)
                            ->required(),
                    ])
                    ->action(function (ServiceCase $record, array $data) {
                        $oldPriority = $record->priority;
                        $record->update(['priority' => $data['priority']]);
                        \Filament\Notifications\Notification::make()
                            ->title('Priorität geändert')
                            ->body("Von {$oldPriority} auf {$data['priority']}")
                            ->success()
                            ->send();
                    }),

                // ✅ Phase 3: Quick Action - Status ändern
                Tables\Actions\Action::make('change_status')
                    ->icon('heroicon-o-arrow-path')
                    ->iconButton()
                    ->tooltip('Status ändern')
                    ->color('info')
                    ->form([
                        Forms\Components\Select::make('status')
                            ->label('Neuer Status')
                            ->options([
                                ServiceCase::STATUS_NEW => 'Neu',
                                ServiceCase::STATUS_OPEN => 'Offen',
                                ServiceCase::STATUS_PENDING => 'Wartend',
                                ServiceCase::STATUS_RESOLVED => 'Gelöst',
                                ServiceCase::STATUS_CLOSED => 'Geschlossen',
                            ])
                            ->default(fn (ServiceCase $record) => $record->status)
                            ->required(),
                        Forms\Components\Textarea::make('resolution_notes')
                            ->label('Lösungsnotiz')
                            ->placeholder('Optional: Beschreiben Sie die Lösung...')
                            ->visible(fn (callable $get) => in_array($get('status'), [ServiceCase::STATUS_RESOLVED, ServiceCase::STATUS_CLOSED]))
                            ->maxLength(1000),
                    ])
                    ->action(function (ServiceCase $record, array $data) {
                        $updateData = ['status' => $data['status']];
                        if (!empty($data['resolution_notes'])) {
                            $updateData['resolution_notes'] = $data['resolution_notes'];
                            $updateData['resolved_at'] = now();
                        }
                        $record->update($updateData);
                        \Filament\Notifications\Notification::make()
                            ->title('Status geändert')
                            ->body("Status auf '{$data['status']}' gesetzt.")
                            ->success()
                            ->send();
                    }),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('resend_output')
                    ->label('Output erneut senden')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (ServiceCase $record) => $record->output_status === ServiceCase::OUTPUT_FAILED)
                    ->action(function (ServiceCase $record) {
                        $record->update(['output_status' => ServiceCase::OUTPUT_PENDING]);
                        // Trigger resend via event or job
                        \Filament\Notifications\Notification::make()
                            ->title('Output wird erneut gesendet')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('mark_resolved')
                        ->label('Als gelöst markieren')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each->update(['status' => ServiceCase::STATUS_RESOLVED])),
                    Tables\Actions\BulkAction::make('assign')
                        ->label('Zuweisen')
                        ->icon('heroicon-o-user-plus')
                        ->form([
                            Forms\Components\Select::make('assigned_to')
                                ->label('Zuweisen an')
                                ->relationship('assignedTo', 'name')
                                ->searchable()
                                ->preload()
                                ->required(),
                        ])
                        ->action(function ($records, array $data) {
                            $records->each->update(['assigned_to' => $data['assigned_to']]);
                        }),
                    Tables\Actions\BulkAction::make('export_csv')
                        ->label('Als CSV exportieren')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('gray')
                        ->action(function (Collection $records) {
                            // CSV Header
                            $csv = "Ticket-ID,Betreff,Typ,Status,Priorität,Kategorie,Anrufer,Telefon,Zugewiesen an,Gruppe,Erstellt,SLA Response,SLA Resolution\n";

                            foreach ($records as $record) {
                                // Safe field extraction with fallbacks
                                $callerName = $record->ai_metadata['customer_name'] ?? '';
                                $callerPhone = $record->ai_metadata['customer_phone'] ?? '';

                                $csv .= sprintf(
                                    "%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s\n",
                                    $record->formatted_id,
                                    '"' . str_replace('"', '""', $record->subject ?? '') . '"',
                                    $record->case_type ?? '',
                                    $record->status ?? '',
                                    $record->priority ?? '',
                                    '"' . str_replace('"', '""', $record->category?->name ?? '') . '"',
                                    '"' . str_replace('"', '""', $callerName) . '"',
                                    $callerPhone,
                                    '"' . str_replace('"', '""', $record->assignedTo?->name ?? '') . '"',
                                    '"' . str_replace('"', '""', $record->assignedGroup?->name ?? '') . '"',
                                    $record->created_at?->format('Y-m-d H:i') ?? '',
                                    $record->sla_response_due_at?->format('Y-m-d H:i') ?? '',
                                    $record->sla_resolution_due_at?->format('Y-m-d H:i') ?? ''
                                );
                            }

                            return response()->streamDownload(function () use ($csv) {
                                echo $csv;
                            }, 'service-cases-' . now()->format('Y-m-d-His') . '.csv', [
                                'Content-Type' => 'text/csv; charset=utf-8',
                            ]);
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Case Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('formatted_id')
                            ->label('Ticket-ID')
                            ->badge()
                            ->color('primary')
                            ->copyable()
                            ->copyMessage('Ticket-ID kopiert'),
                        Infolists\Components\TextEntry::make('subject')
                            ->label('Betreff')
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('description')
                            ->label('Beschreibung')
                            ->columnSpanFull()
                            ->markdown(),
                        Infolists\Components\TextEntry::make('case_type')
                            ->label('Typ')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                ServiceCase::TYPE_INCIDENT => 'danger',
                                ServiceCase::TYPE_REQUEST => 'warning',
                                ServiceCase::TYPE_INQUIRY => 'info',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                ServiceCase::TYPE_INCIDENT => 'Störung',
                                ServiceCase::TYPE_REQUEST => 'Anfrage',
                                ServiceCase::TYPE_INQUIRY => 'Anliegen',
                                default => $state,
                            }),
                        Infolists\Components\TextEntry::make('category.name')
                            ->label('Kategorie'),
                        Infolists\Components\TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                ServiceCase::STATUS_NEW => 'gray',
                                ServiceCase::STATUS_OPEN => 'info',
                                ServiceCase::STATUS_PENDING => 'warning',
                                ServiceCase::STATUS_RESOLVED => 'success',
                                ServiceCase::STATUS_CLOSED => 'primary',
                                default => 'gray',
                            }),
                        Infolists\Components\TextEntry::make('priority')
                            ->label('Priorität')
                            ->badge(),
                        Infolists\Components\TextEntry::make('urgency')
                            ->label('Dringlichkeit')
                            ->badge(),
                        Infolists\Components\TextEntry::make('impact')
                            ->label('Auswirkung')
                            ->badge(),
                    ])->columns(3),
                // ========================================
                // CALLER INFORMATION (from Voice AI)
                // ========================================
                Infolists\Components\Section::make('Anrufer-Informationen')
                    ->description('Vom Voice-AI erfasste Kundendaten')
                    ->icon('heroicon-o-phone')
                    ->schema([
                        Infolists\Components\TextEntry::make('ai_metadata.customer_name')
                            ->label('Name')
                            ->placeholder('Nicht angegeben')
                            ->icon('heroicon-o-user'),
                        Infolists\Components\TextEntry::make('ai_metadata.customer_phone')
                            ->label('Telefon')
                            ->placeholder('Nicht angegeben')
                            ->icon('heroicon-o-phone')
                            ->copyable()
                            ->copyMessage('Telefonnummer kopiert'),
                        Infolists\Components\TextEntry::make('ai_metadata.customer_location')
                            ->label('Standort/Büro')
                            ->placeholder('Nicht angegeben')
                            ->icon('heroicon-o-map-pin'),
                        Infolists\Components\IconEntry::make('ai_metadata.others_affected')
                            ->label('Mehrere Personen betroffen')
                            ->boolean()
                            ->trueIcon('heroicon-o-user-group')
                            ->falseIcon('heroicon-o-user')
                            ->trueColor('danger')
                            ->falseColor('success'),
                        Infolists\Components\TextEntry::make('ai_metadata.retell_call_id')
                            ->label('Retell Call ID')
                            ->placeholder('—')
                            ->badge()
                            ->color('gray')
                            ->copyable(),
                        Infolists\Components\TextEntry::make('ai_metadata.finalized_at')
                            ->label('Erfasst am')
                            ->placeholder('—')
                            ->dateTime('d.m.Y H:i:s'),
                    ])->columns(3)
                    ->collapsible(),

                Infolists\Components\Section::make('Assignment & References')
                    ->schema([
                        Infolists\Components\TextEntry::make('call.id')
                            ->label('Anruf ID')
                            ->url(fn ($record) => $record->call_id ? route('filament.admin.resources.calls.view', $record->call_id) : null),
                        Infolists\Components\TextEntry::make('customer.name')
                            ->label('CRM Kunde')
                            ->url(fn ($record) => $record->customer_id ? route('filament.admin.resources.customers.edit', $record->customer_id) : null),
                        Infolists\Components\TextEntry::make('assignedTo.name')
                            ->label('Zugewiesen an'),
                        Infolists\Components\TextEntry::make('external_reference')
                            ->label('Externe Referenz'),
                    ])->columns(2),
                // NOTE: SLA, Output Status, and Timestamps sections are now rendered
                // in the custom ServiceNow-style ViewServiceCase page with enhanced UI.
                // See: app/Filament/Resources/ServiceCaseResource/Pages/ViewServiceCase.php
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
            'index' => Pages\ListServiceCases::route('/'),
            'create' => Pages\CreateServiceCase::route('/create'),
            'view' => Pages\ViewServiceCase::route('/{record}'),
            'edit' => Pages\EditServiceCase::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::open()->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $count = static::getModel()::open()->count();
        if ($count > 10) return 'danger';
        if ($count > 5) return 'warning';
        return 'success';
    }
}
