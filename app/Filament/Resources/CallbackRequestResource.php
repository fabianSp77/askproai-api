<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasCachedNavigationBadge;
use App\Filament\Resources\CallbackRequestResource\Pages;
use App\Filament\Resources\CallbackRequestResource\RelationManagers;
use App\Models\CallbackRequest;
use App\Models\Customer;
use App\Models\Staff;
use App\Models\Branch;
use App\Models\Service;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Database\Eloquent\Builder;
// ❌ REMOVED: SoftDeletingScope import - CallbackRequest doesn't use SoftDeletes
// use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\HtmlString;

class CallbackRequestResource extends Resource
{
    use HasCachedNavigationBadge;

    protected static ?string $model = CallbackRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-phone-arrow-down-left';

    protected static ?string $navigationGroup = 'CRM';

    protected static ?string $navigationLabel = 'Rückrufanfragen';

    protected static ?string $modelLabel = 'Rückrufanfrage';

    protected static ?string $pluralModelLabel = 'Rückrufanfragen';

    protected static ?int $navigationSort = 30;

    public static function getNavigationBadge(): ?string
    {
        // ✅ RESTORED with caching (2025-10-03) - Memory bugs fixed
        return static::getCachedBadge(function() {
            return static::getModel()::where('status', CallbackRequest::STATUS_PENDING)->count();
        });
    }

    public static function getNavigationBadgeColor(): ?string
    {
        // ✅ RESTORED with caching (2025-10-03)
        return static::getCachedBadgeColor(function() {
            $count = static::getModel()::where('status', CallbackRequest::STATUS_PENDING)->count();
            return $count > 10 ? 'danger' : ($count > 5 ? 'warning' : 'info');
        });
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Tabs')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Kontaktdaten')
                            ->icon('heroicon-o-user')
                            ->schema([
                                Forms\Components\Section::make()
                                    ->schema([
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\Select::make('customer_id')
                                                    ->label('Kunde')
                                                    ->relationship('customer', 'name')
                                                    ->searchable(['name', 'email', 'phone_number'])
                                                    ->preload()
                                                    ->nullable()
                                                    ->reactive()
                                                    ->afterStateUpdated(function ($state, callable $set) {
                                                        if ($state) {
                                                            $customer = Customer::find($state);
                                                            if ($customer) {
                                                                $set('phone_number', $customer->phone_number);
                                                                $set('customer_name', $customer->name);
                                                            }
                                                        }
                                                    })
                                                    ->createOptionForm([
                                                        Forms\Components\TextInput::make('name')
                                                            ->label('Name')
                                                            ->required()
                                                            ->maxLength(255),
                                                        Forms\Components\TextInput::make('phone_number')
                                                            ->label('Telefonnummer')
                                                            ->tel()
                                                            ->required()
                                                            ->maxLength(255),
                                                        Forms\Components\TextInput::make('email')
                                                            ->label('E-Mail')
                                                            ->email()
                                                            ->maxLength(255),
                                                    ])
                                                    ->helperText('Wählen Sie einen bestehenden Kunden oder erstellen Sie einen neuen'),

                                                Forms\Components\Select::make('branch_id')
                                                    ->label('Filiale')
                                                    ->relationship('branch', 'name')
                                                    ->required()
                                                    ->searchable()
                                                    ->preload()
                                                    ->helperText('Filiale für den Rückruf'),
                                            ]),

                                        Forms\Components\Grid::make(3)
                                            ->schema([
                                                Forms\Components\TextInput::make('phone_number')
                                                    ->label('Telefonnummer')
                                                    ->tel()
                                                    ->required()
                                                    ->maxLength(255)
                                                    ->prefixIcon('heroicon-o-phone')
                                                    ->helperText('Primäre Kontaktnummer')
                                                    ->columnSpan(1),

                                                Forms\Components\TextInput::make('customer_name')
                                                    ->label('Kundenname')
                                                    ->required()
                                                    ->maxLength(255)
                                                    ->prefixIcon('heroicon-o-user')
                                                    ->helperText('Name des Kunden für den Rückruf')
                                                    ->columnSpan(1),

                                                // ✅ Phase 4: Email field for callback confirmation
                                                Forms\Components\TextInput::make('customer_email')
                                                    ->label('E-Mail')
                                                    ->email()
                                                    ->maxLength(255)
                                                    ->prefixIcon('heroicon-o-envelope')
                                                    ->helperText('Optional: Für Terminbestätigungen per E-Mail')
                                                    ->columnSpan(1),
                                            ]),
                                    ]),
                            ]),

                        Forms\Components\Tabs\Tab::make('Details')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Forms\Components\Section::make()
                                    ->schema([
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\Select::make('service_id')
                                                    ->label('Service')
                                                    ->relationship('service', 'name')
                                                    ->searchable()
                                                    ->preload()
                                                    ->nullable()
                                                    ->helperText('Gewünschter Service (optional)'),

                                                Forms\Components\Select::make('priority')
                                                    ->label('Priorität')
                                                    ->options([
                                                        CallbackRequest::PRIORITY_NORMAL => 'Normal',
                                                        CallbackRequest::PRIORITY_HIGH => 'Hoch',
                                                        CallbackRequest::PRIORITY_URGENT => 'Dringend',
                                                    ])
                                                    ->default(CallbackRequest::PRIORITY_NORMAL)
                                                    ->required()
                                                    ->native(false)
                                                    ->helperText('Priorität der Anfrage'),
                                            ]),

                                        Forms\Components\KeyValue::make('preferred_time_window')
                                            ->label('Bevorzugtes Zeitfenster')
                                            ->keyLabel('Tag')
                                            ->valueLabel('Zeitraum')
                                            ->addActionLabel('Zeitfenster hinzufügen')
                                            ->reorderable()
                                            ->helperText('Bevorzugte Zeiten für den Rückruf (z.B. Montag: 09:00-12:00)')
                                            ->columnSpanFull(),

                                        Forms\Components\Textarea::make('notes')
                                            ->label('Notizen')
                                            ->rows(4)
                                            ->maxLength(65535)
                                            ->helperText('Zusätzliche Informationen zur Anfrage')
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        Forms\Components\Tabs\Tab::make('Zuweisung')
                            ->icon('heroicon-o-user-group')
                            ->schema([
                                Forms\Components\Section::make()
                                    ->schema([
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\Select::make('staff_id')
                                                    ->label('Bevorzugter Mitarbeiter')
                                                    ->relationship('staff', 'name')
                                                    ->searchable()
                                                    ->preload()
                                                    ->nullable()
                                                    ->helperText('Bevorzugter Mitarbeiter für den Rückruf (optional)'),

                                                Forms\Components\Select::make('assigned_to')
                                                    ->label('Zugewiesen an')
                                                    ->relationship('assignedTo', 'name')
                                                    ->searchable()
                                                    ->preload()
                                                    ->nullable()
                                                    ->helperText('Aktuell zugewiesener Mitarbeiter'),
                                            ]),

                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\Select::make('status')
                                                    ->label('Status')
                                                    ->options([
                                                        CallbackRequest::STATUS_PENDING => 'Ausstehend',
                                                        CallbackRequest::STATUS_ASSIGNED => 'Zugewiesen',
                                                        CallbackRequest::STATUS_CONTACTED => 'Kontaktiert',
                                                        CallbackRequest::STATUS_COMPLETED => 'Abgeschlossen',
                                                        CallbackRequest::STATUS_EXPIRED => 'Abgelaufen',
                                                        CallbackRequest::STATUS_CANCELLED => 'Abgebrochen',
                                                    ])
                                                    ->default(CallbackRequest::STATUS_PENDING)
                                                    ->required()
                                                    ->native(false)
                                                    ->helperText('Aktueller Status der Anfrage'),

                                                Forms\Components\DateTimePicker::make('expires_at')
                                                    ->label('Läuft ab am')
                                                    ->nullable()
                                                    ->native(false)
                                                    ->helperText('Ablaufzeitpunkt der Anfrage')
                                                    ->displayFormat('d.m.Y H:i'),
                                            ]),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull()
                    ->persistTabInQueryString(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // ✅ PHASE 2: Urgency Indicator (Visual Priority at a Glance)
                Tables\Columns\ViewColumn::make('urgency_indicator')
                    ->label('')
                    ->view('filament.tables.columns.callback-urgency')
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        // Sort by urgency: overdue+urgent > overdue > urgent > high > normal
                        return $query
                            ->orderByRaw('
                                CASE
                                    WHEN expires_at < NOW() AND status NOT IN (?, ?, ?) AND priority = ? THEN 0
                                    WHEN expires_at < NOW() AND status NOT IN (?, ?, ?) THEN 1
                                    WHEN priority = ? THEN 2
                                    WHEN priority = ? THEN 3
                                    ELSE 4
                                END ' . $direction,
                                [
                                    CallbackRequest::STATUS_COMPLETED,
                                    CallbackRequest::STATUS_EXPIRED,
                                    CallbackRequest::STATUS_CANCELLED,
                                    CallbackRequest::PRIORITY_URGENT,
                                    CallbackRequest::STATUS_COMPLETED,
                                    CallbackRequest::STATUS_EXPIRED,
                                    CallbackRequest::STATUS_CANCELLED,
                                    CallbackRequest::PRIORITY_URGENT,
                                    CallbackRequest::PRIORITY_HIGH,
                                ]
                            );
                    })
                    ->alignCenter()
                    ->width('60px'),

                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->badge()
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('customer_name')
                    ->label('Kunde')
                    ->weight('bold')
                    ->description(fn (CallbackRequest $record): string =>
                        implode(' • ', array_filter([
                            $record->phone_number,
                            $record->customer_email,
                            $record->branch?->name,
                            $record->service?->name,
                        ]))
                    )
                    ->icon('heroicon-o-user')
                    ->searchable(['customer_name', 'phone_number', 'customer_email'])
                    ->sortable()
                    ->wrap(),

                // ✅ Phase 4: Email column for callback confirmation
                Tables\Columns\TextColumn::make('customer_email')
                    ->label('E-Mail')
                    ->icon('heroicon-o-envelope')
                    ->copyable()
                    ->placeholder('—')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->visibleFrom('md'),

                // ✅ PHASE 2: Inline Quick Status Change (1-Click statt 5+ Clicks)
                Tables\Columns\SelectColumn::make('status')
                    ->label('Status')
                    ->options([
                        CallbackRequest::STATUS_PENDING => 'Ausstehend',
                        CallbackRequest::STATUS_ASSIGNED => 'Zugewiesen',
                        CallbackRequest::STATUS_CONTACTED => 'Kontaktiert',
                        CallbackRequest::STATUS_COMPLETED => 'Abgeschlossen',
                        CallbackRequest::STATUS_CANCELLED => 'Abgebrochen',
                    ])
                    ->selectablePlaceholder(false)
                    ->beforeStateUpdated(function (CallbackRequest $record, $state) {
                        // Auto-set timestamps based on status change
                        if ($state === CallbackRequest::STATUS_CONTACTED && !$record->contacted_at) {
                            $record->contacted_at = now();
                        }
                        if ($state === CallbackRequest::STATUS_COMPLETED && !$record->completed_at) {
                            $record->completed_at = now();
                        }
                    })
                    ->afterStateUpdated(function (CallbackRequest $record, $state) {
                        $statusLabels = [
                            CallbackRequest::STATUS_PENDING => 'Ausstehend',
                            CallbackRequest::STATUS_ASSIGNED => 'Zugewiesen',
                            CallbackRequest::STATUS_CONTACTED => 'Kontaktiert',
                            CallbackRequest::STATUS_COMPLETED => 'Abgeschlossen',
                            CallbackRequest::STATUS_CANCELLED => 'Abgebrochen',
                        ];

                        \Filament\Notifications\Notification::make()
                            ->title('Status geändert')
                            ->success()
                            ->body("Status auf \"{$statusLabels[$state]}\" gesetzt")
                            ->send();
                    })
                    ->sortable()
                    ->searchable(),

                // ✅ PHASE 2: Inline Quick Priority Change
                Tables\Columns\SelectColumn::make('priority')
                    ->label('Priorität')
                    ->options([
                        CallbackRequest::PRIORITY_NORMAL => 'Normal',
                        CallbackRequest::PRIORITY_HIGH => 'Hoch',
                        CallbackRequest::PRIORITY_URGENT => 'Dringend',
                    ])
                    ->selectablePlaceholder(false)
                    ->afterStateUpdated(function (CallbackRequest $record, $state) {
                        $priorityLabels = [
                            CallbackRequest::PRIORITY_NORMAL => 'Normal',
                            CallbackRequest::PRIORITY_HIGH => 'Hoch',
                            CallbackRequest::PRIORITY_URGENT => 'Dringend',
                        ];

                        \Filament\Notifications\Notification::make()
                            ->title('Priorität geändert')
                            ->success()
                            ->body("Priorität auf \"{$priorityLabels[$state]}\" gesetzt")
                            ->send();
                    })
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('branch.name')
                    ->label('Filiale')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->visibleFrom('md'), // Hide on mobile (already shown in customer_name description)

                Tables\Columns\TextColumn::make('service.name')
                    ->label('Service')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->visibleFrom('md') // Hide on mobile (already shown in customer_name description)
                    ->default('—'),

                // ✅ PHASE 2: Inline Quick Assignment (1-Click statt 6-9 Clicks)
                Tables\Columns\SelectColumn::make('assigned_to')
                    ->label('Zugewiesen an')
                    ->options(fn () => \App\Models\Staff::query()
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->toArray()
                    )
                    ->searchable()
                    ->selectablePlaceholder(false)
                    ->placeholder('Nicht zugewiesen')
                    ->beforeStateUpdated(function (CallbackRequest $record, $state) {
                        // Auto-set status to assigned when staff is assigned
                        if ($state && $record->status === CallbackRequest::STATUS_PENDING) {
                            $record->status = CallbackRequest::STATUS_ASSIGNED;
                            $record->assigned_at = now();
                        }
                    })
                    ->afterStateUpdated(function (CallbackRequest $record, $state) {
                        // Show success notification
                        if ($state) {
                            $staffName = \App\Models\Staff::find($state)?->name;
                            \Filament\Notifications\Notification::make()
                                ->title('Zugewiesen')
                                ->success()
                                ->body("Callback wurde an {$staffName} zugewiesen")
                                ->send();
                        }
                    })
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('expires_at')
                    ->label('Läuft ab')
                    ->dateTime('d.m.Y H:i')
                    ->description(fn (CallbackRequest $record): ?string =>
                        $record->expires_at ? $record->expires_at->diffForHumans() : null
                    )
                    ->sortable()
                    ->toggleable()
                    ->icon(fn (CallbackRequest $record): string =>
                        $record->is_overdue ? 'heroicon-o-exclamation-triangle' : 'heroicon-o-clock'
                    )
                    ->color(fn (CallbackRequest $record): string =>
                        $record->is_overdue ? 'danger' : 'gray'
                    ),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt am')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('escalations_count')
                    ->label('Eskalationen')
                    ->counts('escalations')
                    ->badge()
                    ->color('danger')
                    ->toggleable()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->multiple()
                    ->options([
                        CallbackRequest::STATUS_PENDING => 'Ausstehend',
                        CallbackRequest::STATUS_ASSIGNED => 'Zugewiesen',
                        CallbackRequest::STATUS_CONTACTED => 'Kontaktiert',
                        CallbackRequest::STATUS_COMPLETED => 'Abgeschlossen',
                        CallbackRequest::STATUS_EXPIRED => 'Abgelaufen',
                        CallbackRequest::STATUS_CANCELLED => 'Abgebrochen',
                    ]),

                Tables\Filters\SelectFilter::make('priority')
                    ->label('Priorität')
                    ->multiple()
                    ->options([
                        CallbackRequest::PRIORITY_NORMAL => 'Normal',
                        CallbackRequest::PRIORITY_HIGH => 'Hoch',
                        CallbackRequest::PRIORITY_URGENT => 'Dringend',
                    ]),

                Tables\Filters\SelectFilter::make('branch_id')
                    ->label('Filiale')
                    ->relationship('branch', 'name')
                    ->searchable()
                    ->preload(),

                // ✅ Phase 4: Email filter
                Tables\Filters\TernaryFilter::make('has_email')
                    ->label('Mit E-Mail')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('customer_email'),
                        false: fn (Builder $query) => $query->whereNull('customer_email'),
                    )
                    ->placeholder('Alle anzeigen')
                    ->trueLabel('Nur mit E-Mail')
                    ->falseLabel('Ohne E-Mail'),

                Tables\Filters\TernaryFilter::make('overdue')
                    ->label('Überfällig')
                    ->queries(
                        true: fn (Builder $query) => $query->overdue(),
                        false: fn (Builder $query) => $query->where(function (Builder $query) {
                            $query->where('expires_at', '>=', now())
                                ->orWhereNull('expires_at')
                                ->orWhereIn('status', [
                                    CallbackRequest::STATUS_COMPLETED,
                                    CallbackRequest::STATUS_EXPIRED,
                                    CallbackRequest::STATUS_CANCELLED
                                ]);
                        }),
                    )
                    ->placeholder('Alle anzeigen'),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Erstellt von')
                            ->native(false),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Erstellt bis')
                            ->native(false),
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
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['created_from'] ?? null) {
                            $indicators[] = Tables\Filters\Indicator::make('Erstellt ab ' . \Carbon\Carbon::parse($data['created_from'])->format('d.m.Y'))
                                ->removeField('created_from');
                        }

                        if ($data['created_until'] ?? null) {
                            $indicators[] = Tables\Filters\Indicator::make('Erstellt bis ' . \Carbon\Carbon::parse($data['created_until'])->format('d.m.Y'))
                                ->removeField('created_until');
                        }

                        return $indicators;
                    }),

                // ❌ REMOVED: TrashedFilter - CallbackRequest model doesn't use SoftDeletes
                // Tables\Filters\TrashedFilter::make(),
            ], layout: Tables\Enums\FiltersLayout::AboveContent)
            ->filtersFormColumns(3)
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('assign')
                        ->label('Zuweisen')
                        ->icon('heroicon-o-user-plus')
                        ->color('info')
                        ->visible(fn (CallbackRequest $record): bool => !$record->assigned_to)
                        ->form([
                            Forms\Components\Select::make('staff_id')
                                ->label('Mitarbeiter')
                                ->options(Staff::pluck('name', 'id'))
                                ->searchable()
                                ->required()
                                ->helperText('Wählen Sie den Mitarbeiter für die Bearbeitung'),
                        ])
                        ->action(function (CallbackRequest $record, array $data): void {
                            $staff = Staff::find($data['staff_id']);
                            if ($staff) {
                                $record->assign($staff);
                            }
                        })
                        ->successNotificationTitle('Erfolgreich zugewiesen')
                        ->requiresConfirmation(),

                    Tables\Actions\Action::make('autoAssign')
                        ->label('Auto-Zuweisen')
                        ->icon('heroicon-o-sparkles')
                        ->color('warning')
                        ->visible(fn (CallbackRequest $record): bool => !$record->assigned_to)
                        ->form([
                            Forms\Components\Select::make('strategy')
                                ->label('Zuweisung-Strategie')
                                ->options([
                                    'round_robin' => 'Round-Robin (Gleichmäßige Verteilung)',
                                    'load_based' => 'Lastbasiert (Wenigste aktive Callbacks)',
                                ])
                                ->default('load_based')
                                ->required()
                                ->helperText('Wählen Sie die Strategie für die automatische Zuweisung'),
                        ])
                        ->action(function (CallbackRequest $record, array $data): void {
                            $service = app(\App\Services\Callbacks\CallbackAssignmentService::class);
                            $staff = $service->autoAssign($record, $data['strategy']);

                            if (!$staff) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Keine verfügbaren Mitarbeiter')
                                    ->danger()
                                    ->body('Es konnten keine geeigneten Mitarbeiter für die automatische Zuweisung gefunden werden.')
                                    ->send();
                            }
                        })
                        ->successNotificationTitle('Automatisch zugewiesen')
                        ->successNotification(fn ($record) =>
                            $record->assignedTo
                                ? \Filament\Notifications\Notification::make()
                                    ->title('Automatisch zugewiesen')
                                    ->success()
                                    ->body("Callback wurde an {$record->assignedTo->name} zugewiesen")
                                : null
                        ),

                    Tables\Actions\Action::make('markContacted')
                        ->label('Als kontaktiert markieren')
                        ->icon('heroicon-o-check-circle')
                        ->color('primary')
                        ->visible(fn (CallbackRequest $record): bool =>
                            $record->status === CallbackRequest::STATUS_ASSIGNED
                        )
                        ->action(fn (CallbackRequest $record) => $record->markContacted())
                        ->successNotificationTitle('Als kontaktiert markiert')
                        ->requiresConfirmation(),

                    Tables\Actions\Action::make('markCompleted')
                        ->label('Als abgeschlossen markieren')
                        ->icon('heroicon-o-check-badge')
                        ->color('success')
                        ->visible(fn (CallbackRequest $record): bool =>
                            $record->status === CallbackRequest::STATUS_CONTACTED
                        )
                        ->form([
                            Forms\Components\Textarea::make('notes')
                                ->label('Abschlussnotizen')
                                ->rows(3)
                                ->helperText('Zusätzliche Informationen zum Abschluss'),
                        ])
                        ->action(function (CallbackRequest $record, array $data): void {
                            if (!empty($data['notes'])) {
                                $record->notes = ($record->notes ? $record->notes . "\n\n" : '') .
                                    '**Abschluss:** ' . $data['notes'];
                            }
                            $record->markCompleted();
                        })
                        ->successNotificationTitle('Als abgeschlossen markiert')
                        ->requiresConfirmation(),

                    Tables\Actions\Action::make('escalate')
                        ->label('Eskalieren')
                        ->icon('heroicon-o-exclamation-triangle')
                        ->color('danger')
                        ->visible(fn (CallbackRequest $record): bool =>
                            $record->status !== CallbackRequest::STATUS_COMPLETED
                        )
                        ->form([
                            Forms\Components\Select::make('reason')
                                ->label('Eskalationsgrund')
                                ->options([
                                    'no_response' => 'Keine Antwort',
                                    'technical_issue' => 'Technisches Problem',
                                    'customer_complaint' => 'Kundenbeschwerde',
                                    'urgent_request' => 'Dringende Anfrage',
                                    'complex_case' => 'Komplexer Fall',
                                    'other' => 'Sonstiges',
                                ])
                                ->required()
                                ->native(false),
                            Forms\Components\Textarea::make('details')
                                ->label('Details')
                                ->rows(3)
                                ->helperText('Zusätzliche Informationen zur Eskalation'),
                        ])
                        ->action(function (CallbackRequest $record, array $data): void {
                            $reason = $data['reason'];
                            if (!empty($data['details'])) {
                                $reason .= ': ' . $data['details'];
                            }
                            $record->escalate($reason);
                        })
                        ->successNotificationTitle('Erfolgreich eskaliert')
                        ->requiresConfirmation(),

                    // ✅ PHASE 3: Link to Appointment System
                    Tables\Actions\Action::make('createAppointment')
                        ->label('Termin erstellen')
                        ->icon('heroicon-o-calendar-days')
                        ->color('success')
                        ->visible(fn (CallbackRequest $record): bool =>
                            $record->status === CallbackRequest::STATUS_CONTACTED ||
                            $record->status === CallbackRequest::STATUS_COMPLETED
                        )
                        ->url(fn (CallbackRequest $record): string =>
                            AppointmentResource::getUrl('create', [
                                'callback_id' => $record->id,
                                'customer_id' => $record->customer_id,
                                'customer_name' => $record->customer_name,
                                'phone_number' => $record->phone_number,
                                'branch_id' => $record->branch_id,
                                'service_id' => $record->service_id,
                                'staff_id' => $record->staff_id ?? $record->assigned_to,
                            ])
                        )
                        ->openUrlInNewTab()
                        ->tooltip('Erstellt einen Termin mit den Callback-Daten'),

                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('bulkAssign')
                        ->label('Zuweisen')
                        ->icon('heroicon-o-user-plus')
                        ->color('info')
                        ->form([
                            Forms\Components\Select::make('staff_id')
                                ->label('Mitarbeiter')
                                ->options(Staff::pluck('name', 'id'))
                                ->searchable()
                                ->required()
                                ->helperText('Wählen Sie den Mitarbeiter für alle ausgewählten Anfragen'),
                        ])
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data): void {
                            $staff = Staff::find($data['staff_id']);
                            if ($staff) {
                                foreach ($records as $record) {
                                    $record->assign($staff);
                                }
                            }
                        })
                        ->successNotificationTitle('Erfolgreich zugewiesen')
                        ->deselectRecordsAfterCompletion()
                        ->requiresConfirmation(),

                    Tables\Actions\BulkAction::make('bulkAutoAssign')
                        ->label('Auto-Zuweisen (Alle)')
                        ->icon('heroicon-o-sparkles')
                        ->color('warning')
                        ->form([
                            Forms\Components\Select::make('strategy')
                                ->label('Zuweisung-Strategie')
                                ->options([
                                    'round_robin' => 'Round-Robin (Gleichmäßige Verteilung)',
                                    'load_based' => 'Lastbasiert (Wenigste aktive Callbacks)',
                                ])
                                ->default('load_based')
                                ->required()
                                ->helperText('Automatische Zuweisung aller ausgewählten Anfragen'),
                        ])
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data): void {
                            $service = app(\App\Services\Callbacks\CallbackAssignmentService::class);
                            $assigned = 0;
                            $failed = 0;

                            foreach ($records as $record) {
                                if (!$record->assigned_to) {
                                    $staff = $service->autoAssign($record, $data['strategy']);
                                    if ($staff) {
                                        $assigned++;
                                    } else {
                                        $failed++;
                                    }
                                }
                            }

                            if ($assigned > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Auto-Zuweisung abgeschlossen')
                                    ->success()
                                    ->body("$assigned Callback(s) erfolgreich zugewiesen" . ($failed > 0 ? ", $failed fehlgeschlagen" : ''))
                                    ->send();
                            }

                            if ($failed > 0 && $assigned === 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Auto-Zuweisung fehlgeschlagen')
                                    ->danger()
                                    ->body("Keine geeigneten Mitarbeiter gefunden für $failed Callback(s)")
                                    ->send();
                            }
                        })
                        ->deselectRecordsAfterCompletion(),

                    // ✅ PHASE 3: Callback Batching Workflow
                    Tables\Actions\BulkAction::make('bulkBatchCall')
                        ->label('Batch-Call starten')
                        ->icon('heroicon-o-phone')
                        ->color('info')
                        ->form([
                            Forms\Components\Placeholder::make('info')
                                ->label('Batch-Call Modus')
                                ->content(fn (\Illuminate\Database\Eloquent\Collection $records) =>
                                    "Sie sind dabei, **{$records->count()} Callback(s)** im Batch-Modus zu bearbeiten.\n\n" .
                                    "**Workflow:**\n" .
                                    "1. Alle ausgewählten Callbacks werden als 'kontaktiert' markiert\n" .
                                    "2. Sie können danach einzeln als 'abgeschlossen' oder 'abgebrochen' markieren\n" .
                                    "3. Notizen können Sie direkt in der Detail-Ansicht hinzufügen"
                                ),
                            Forms\Components\Select::make('batch_outcome')
                                ->label('Standard-Ergebnis nach Anruf')
                                ->options([
                                    'contacted_only' => 'Nur als kontaktiert markieren',
                                    'completed' => 'Direkt als abgeschlossen markieren',
                                ])
                                ->default('contacted_only')
                                ->required()
                                ->helperText('Wählen Sie das Standard-Ergebnis für alle Anrufe'),
                        ])
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data): void {
                            $contacted = 0;
                            $completed = 0;
                            $failed = 0;

                            foreach ($records as $record) {
                                try {
                                    if ($data['batch_outcome'] === 'completed') {
                                        $record->markCompleted();
                                        $completed++;
                                    } else {
                                        $record->markContacted();
                                        $contacted++;
                                    }
                                } catch (\Exception $e) {
                                    \Illuminate\Support\Facades\Log::error('[BatchCall] Failed to process callback', [
                                        'callback_id' => $record->id,
                                        'error' => $e->getMessage(),
                                    ]);
                                    $failed++;
                                }
                            }

                            $message = [];
                            if ($contacted > 0) {
                                $message[] = "$contacted kontaktiert";
                            }
                            if ($completed > 0) {
                                $message[] = "$completed abgeschlossen";
                            }
                            if ($failed > 0) {
                                $message[] = "$failed fehlgeschlagen";
                            }

                            \Filament\Notifications\Notification::make()
                                ->title('Batch-Call abgeschlossen')
                                ->success()
                                ->body(implode(', ', $message))
                                ->send();
                        })
                        ->successNotificationTitle('Batch-Call erfolgreich')
                        ->deselectRecordsAfterCompletion()
                        ->requiresConfirmation()
                        ->modalHeading('Batch-Call Modus starten')
                        ->modalDescription('Bearbeiten Sie mehrere Callbacks in einem durchgehenden Workflow')
                        ->modalSubmitActionLabel('Batch starten'),

                    Tables\Actions\BulkAction::make('bulkContact')
                        ->label('Als kontaktiert markieren')
                        ->icon('heroicon-o-phone-arrow-up-right')
                        ->color('warning')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records): void {
                            $contacted = 0;
                            $failed = 0;

                            foreach ($records as $record) {
                                try {
                                    $record->markContacted();
                                    $contacted++;
                                } catch (\Exception $e) {
                                    \Illuminate\Support\Facades\Log::error('[BulkContact] Failed to mark callback', [
                                        'callback_id' => $record->id,
                                        'error' => $e->getMessage(),
                                    ]);
                                    $failed++;
                                }
                            }

                            if ($contacted > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Als kontaktiert markiert')
                                    ->success()
                                    ->body("$contacted Callback(s) erfolgreich kontaktiert" . ($failed > 0 ? ", $failed fehlgeschlagen" : ''))
                                    ->send();
                            }
                        })
                        ->successNotificationTitle('Als kontaktiert markiert')
                        ->deselectRecordsAfterCompletion()
                        ->requiresConfirmation(),

                    Tables\Actions\BulkAction::make('bulkComplete')
                        ->label('Als abgeschlossen markieren')
                        ->icon('heroicon-o-check-badge')
                        ->color('success')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records): void {
                            foreach ($records as $record) {
                                $record->markCompleted();
                            }
                        })
                        ->successNotificationTitle('Als abgeschlossen markiert')
                        ->deselectRecordsAfterCompletion()
                        ->requiresConfirmation(),

                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation(),

                    // ❌ REMOVED: Force delete and restore - CallbackRequest doesn't use SoftDeletes
                    // Tables\Actions\ForceDeleteBulkAction::make()
                    //     ->requiresConfirmation(),
                    //
                    // Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->modifyQueryUsing(fn (Builder $query) =>
                $query->with(['customer', 'branch', 'service', 'assignedTo'])
                    ->withCount('escalations')
            )
            ->recordUrl(fn (CallbackRequest $record): string =>
                CallbackRequestResource::getUrl('view', ['record' => $record])
            );
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Hauptinformationen')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('id')
                                    ->label('ID')
                                    ->badge(),

                                Infolists\Components\TextEntry::make('status')
                                    ->label('Status')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        CallbackRequest::STATUS_PENDING => 'Ausstehend',
                                        CallbackRequest::STATUS_ASSIGNED => 'Zugewiesen',
                                        CallbackRequest::STATUS_CONTACTED => 'Kontaktiert',
                                        CallbackRequest::STATUS_COMPLETED => 'Abgeschlossen',
                                        CallbackRequest::STATUS_EXPIRED => 'Abgelaufen',
                                        CallbackRequest::STATUS_CANCELLED => 'Abgebrochen',
                                        default => $state,
                                    })
                                    ->color(fn (string $state): string => match ($state) {
                                        CallbackRequest::STATUS_PENDING => 'warning',
                                        CallbackRequest::STATUS_ASSIGNED => 'info',
                                        CallbackRequest::STATUS_CONTACTED => 'primary',
                                        CallbackRequest::STATUS_COMPLETED => 'success',
                                        CallbackRequest::STATUS_EXPIRED => 'danger',
                                        CallbackRequest::STATUS_CANCELLED => 'gray',
                                        default => 'gray',
                                    }),

                                Infolists\Components\TextEntry::make('priority')
                                    ->label('Priorität')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        CallbackRequest::PRIORITY_NORMAL => 'Normal',
                                        CallbackRequest::PRIORITY_HIGH => 'Hoch',
                                        CallbackRequest::PRIORITY_URGENT => 'Dringend',
                                        default => $state,
                                    })
                                    ->color(fn (string $state): string => match ($state) {
                                        CallbackRequest::PRIORITY_NORMAL => 'gray',
                                        CallbackRequest::PRIORITY_HIGH => 'warning',
                                        CallbackRequest::PRIORITY_URGENT => 'danger',
                                        default => 'gray',
                                    }),
                            ]),

                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('customer_name')
                                    ->label('Kundenname')
                                    ->icon('heroicon-o-user')
                                    ->weight('bold'),

                                Infolists\Components\TextEntry::make('phone_number')
                                    ->label('Telefonnummer')
                                    ->icon('heroicon-o-phone')
                                    ->copyable(),

                                // ✅ Phase 4: Email field for callback confirmation
                                Infolists\Components\TextEntry::make('customer_email')
                                    ->label('E-Mail (Callback)')
                                    ->icon('heroicon-o-envelope')
                                    ->copyable()
                                    ->placeholder('Nicht angegeben')
                                    ->helperText('Für Terminbestätigungen'),

                                Infolists\Components\TextEntry::make('customer.email')
                                    ->label('E-Mail (Kunde)')
                                    ->icon('heroicon-o-envelope')
                                    ->copyable()
                                    ->placeholder('—')
                                    ->helperText('Aus Kundenprofil'),

                                Infolists\Components\TextEntry::make('branch.name')
                                    ->label('Filiale')
                                    ->icon('heroicon-o-building-office'),

                                Infolists\Components\TextEntry::make('service.name')
                                    ->label('Service')
                                    ->icon('heroicon-o-wrench-screwdriver')
                                    ->placeholder('—'),

                                Infolists\Components\TextEntry::make('staff.name')
                                    ->label('Bevorzugter Mitarbeiter')
                                    ->icon('heroicon-o-user')
                                    ->placeholder('—'),
                            ]),

                        Infolists\Components\TextEntry::make('preferred_time_window')
                            ->label('Bevorzugtes Zeitfenster')
                            ->formatStateUsing(function ($state): string {
                                if (!$state || !is_array($state)) {
                                    return '—';
                                }
                                return collect($state)
                                    ->map(fn ($time, $day) => "$day: $time")
                                    ->join(', ');
                            })
                            ->columnSpanFull(),

                        Infolists\Components\TextEntry::make('notes')
                            ->label('Notizen')
                            ->markdown()
                            ->placeholder('Keine Notizen')
                            ->columnSpanFull(),
                    ]),

                Infolists\Components\Section::make('Bearbeitung')
                    ->icon('heroicon-o-cog')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('assignedTo.name')
                                    ->label('Zugewiesen an')
                                    ->icon('heroicon-o-user')
                                    ->placeholder('Nicht zugewiesen'),

                                Infolists\Components\TextEntry::make('contacted_at')
                                    ->label('Kontaktiert am')
                                    ->dateTime('d.m.Y H:i')
                                    ->placeholder('Noch nicht kontaktiert'),

                                Infolists\Components\TextEntry::make('completed_at')
                                    ->label('Abgeschlossen am')
                                    ->dateTime('d.m.Y H:i')
                                    ->placeholder('Noch nicht abgeschlossen'),
                            ]),
                    ]),

                Infolists\Components\Section::make('Zeitplanung')
                    ->icon('heroicon-o-clock')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('created_at')
                                    ->label('Erstellt am')
                                    ->dateTime('d.m.Y H:i')
                                    ->helperText(fn (CallbackRequest $record): string =>
                                        $record->created_at->diffForHumans()
                                    ),

                                Infolists\Components\TextEntry::make('expires_at')
                                    ->label('Läuft ab am')
                                    ->dateTime('d.m.Y H:i')
                                    ->helperText(fn (CallbackRequest $record): ?string =>
                                        $record->expires_at ? $record->expires_at->diffForHumans() : null
                                    )
                                    ->placeholder('Kein Ablaufdatum')
                                    ->color(fn (CallbackRequest $record): string =>
                                        $record->is_overdue ? 'danger' : 'gray'
                                    ),

                                Infolists\Components\TextEntry::make('is_overdue')
                                    ->label('Überfällig')
                                    ->formatStateUsing(fn (bool $state): string => $state ? 'Ja' : 'Nein')
                                    ->badge()
                                    ->color(fn (bool $state): string => $state ? 'danger' : 'success')
                                    ->icon(fn (bool $state): string => $state ? 'heroicon-o-exclamation-triangle' : 'heroicon-o-check-circle'),
                            ]),
                    ]),

                Infolists\Components\Section::make('Eskalationen')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('escalations')
                            ->label('')
                            ->schema([
                                Infolists\Components\Grid::make(3)
                                    ->schema([
                                        Infolists\Components\TextEntry::make('escalation_reason')
                                            ->label('Grund'),

                                        Infolists\Components\TextEntry::make('escalatedFrom.name')
                                            ->label('Von')
                                            ->placeholder('—'),

                                        Infolists\Components\TextEntry::make('escalatedTo.name')
                                            ->label('An')
                                            ->placeholder('—'),

                                        Infolists\Components\TextEntry::make('escalated_at')
                                            ->label('Eskaliert am')
                                            ->dateTime('d.m.Y H:i')
                                            ->helperText(fn ($record): string =>
                                                $record->escalated_at?->diffForHumans() ?? ''
                                            ),
                                    ]),
                            ])
                            ->placeholder('Keine Eskalationen')
                            ->columnSpanFull(),
                    ])
                    ->visible(fn (CallbackRequest $record): bool => $record->escalations()->exists()),
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
            'index' => Pages\ListCallbackRequests::route('/'),
            'create' => Pages\CreateCallbackRequest::route('/create'),
            'view' => Pages\ViewCallbackRequest::route('/{record}'),
            'edit' => Pages\EditCallbackRequest::route('/{record}/edit'),
        ];
    }

    // ❌ REMOVED: getEloquentQuery override - CallbackRequest doesn't use SoftDeletes
    // No need to remove SoftDeletingScope since the model doesn't use it
    // public static function getEloquentQuery(): Builder
    // {
    //     return parent::getEloquentQuery()
    //         ->withoutGlobalScopes([
    //             SoftDeletingScope::class,
    //         ]);
    // }

    public static function getRecordTitle($record): ?string
    {
        return $record->customer_name;
    }
}
