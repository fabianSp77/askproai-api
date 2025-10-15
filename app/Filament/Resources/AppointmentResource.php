<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasCachedNavigationBadge;
use App\Events\Appointments\AppointmentCancelled;
use App\Events\Appointments\AppointmentRescheduled;
use App\Filament\Resources\AppointmentResource\Pages;
use App\Filament\Resources\AppointmentResource\RelationManagers;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Staff;
use App\Models\Branch;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Get;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section as InfoSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Grid as InfoGrid;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Carbon;
use Filament\Notifications\Notification;

class AppointmentResource extends Resource
{
    use HasCachedNavigationBadge;

    protected static ?string $model = Appointment::class;
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationGroup = 'CRM';
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationLabel = 'Termine';
    protected static ?string $recordTitleAttribute = 'id';

    public static function getNavigationBadge(): ?string
    {
        // ✅ RESTORED with caching (2025-10-03) - Memory bugs fixed
        return static::getCachedBadge(function() {
            return static::getModel()::whereNotNull('starts_at')->count();
        });
    }

    public static function getNavigationBadgeColor(): ?string
    {
        // ✅ RESTORED with caching (2025-10-03)
        return static::getCachedBadgeColor(function() {
            $count = static::getModel()::whereNotNull('starts_at')->count();
            return $count > 50 ? 'danger' : ($count > 20 ? 'warning' : 'info');
        });
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // 🏢 KONTEXT Section
                Section::make('🏢 Kontext')
                    ->description(function ($context, $record) {
                        if ($context === 'edit' && $record) {
                            $company = $record->company->name ?? 'Unbekannt';
                            $branch = $record->branch->name ?? 'Unbekannt';
                            return "**{$company}** → {$branch}";
                        }
                        return 'Wo findet der Termin statt?';
                    })
                    ->schema([
                        // Company - hidden, auto-filled
                        Forms\Components\Hidden::make('company_id')
                            ->default(function ($context, $record) {
                                if ($context === 'edit' && $record) {
                                    return $record->company_id;
                                }
                                return auth()->user()->company_id ?? 1;
                            }),

                        // Branch - FIRST interactive field!
                        Forms\Components\Select::make('branch_id')
                            ->label('Filiale')
                            ->relationship('branch', 'name', function ($query, $context, $record) {
                                $companyId = ($context === 'edit' && $record)
                                    ? $record->company_id
                                    : (auth()->user()->company_id ?? 1);
                                return $query->where('company_id', $companyId);
                            })
                            ->searchable()
                            ->preload()
                            ->required()
                            ->reactive()
                            ->default(function ($context, $record) {
                                if ($context === 'edit' && $record) {
                                    return $record->branch_id;
                                }
                                $companyId = auth()->user()->company_id ?? 1;
                                $branches = \App\Models\Branch::where('company_id', $companyId)->get();
                                return $branches->count() === 1 ? $branches->first()->id : null;
                            })
                            ->helperText(fn ($context) =>
                                $context === 'create'
                                    ? '⚠️ Wählen Sie zuerst die Filiale aus'
                                    : null
                            ),
                    ])
                    ->collapsible()
                    ->collapsed(false)  // IMMER OFFEN
                    ->persistCollapsed(),  // User-Präferenz speichern

                // 👤 WER KOMMT? - Customer Section
                Section::make('👤 Wer kommt?')
                    ->description(function ($context, $record) {
                        if ($context === 'edit' && $record && $record->customer) {
                            $customer = $record->customer;
                            $apptCount = Appointment::where('customer_id', $customer->id)->count();
                            return "**{$customer->name}** ({$apptCount} Termine)";
                        }
                        return 'Kunde auswählen';
                    })
                    ->schema([
                        Forms\Components\Select::make('customer_id')
                            ->label('Kunde')
                            ->relationship('customer', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->required(),
                                Forms\Components\TextInput::make('phone')
                                    ->tel(),
                                Forms\Components\TextInput::make('email')
                                    ->email(),
                            ])
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $customer = Customer::find($state);
                                    if ($customer && $customer->preferred_branch_id) {
                                        $set('branch_id', $customer->preferred_branch_id);
                                    }
                                }
                            })
                            ->columnSpanFull(),

                        // Customer History - COMPACT VERSION
                        Forms\Components\Placeholder::make('customer_history_compact')
                            ->label('')
                            ->content(function (callable $get) {
                                $customerId = $get('customer_id');
                                if (!$customerId) return '';

                                $totalCount = Appointment::where('customer_id', $customerId)->count();
                                if ($totalCount === 0) {
                                    return '🆕 **Neukunde** - Keine bisherigen Termine';
                                }

                                $mostFrequent = Appointment::where('customer_id', $customerId)
                                    ->with('service:id,name')
                                    ->selectRaw('service_id, COUNT(*) as count')
                                    ->groupBy('service_id')
                                    ->orderBy('count', 'desc')
                                    ->first();

                                $preferredHour = Appointment::where('customer_id', $customerId)
                                    ->selectRaw('HOUR(starts_at) as hour')
                                    ->groupBy('hour')
                                    ->orderBy(\DB::raw('COUNT(*)'), 'desc')
                                    ->value('hour');

                                $lastAppt = Appointment::where('customer_id', $customerId)
                                    ->orderBy('starts_at', 'desc')
                                    ->first();

                                $statusIcon = $lastAppt ? match($lastAppt->status) {
                                    'completed' => '✅',
                                    'cancelled' => '❌',
                                    'no_show' => '👻',
                                    default => '📅'
                                } : '';

                                $lastDate = $lastAppt ? Carbon::parse($lastAppt->starts_at)->format('d.m.Y') : '';

                                return "📊 **{$totalCount} Termine** | " .
                                       "❤️ " . ($mostFrequent->service->name ?? 'N/A') . " | " .
                                       "🕐 {$preferredHour}:00 Uhr | " .
                                       "Letzter: {$statusIcon} {$lastDate}";
                            })
                            ->visible(fn (callable $get) => $get('customer_id') !== null)
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(false)  // IMMER OFFEN
                    ->persistCollapsed()
                    ->visible(fn ($context) => $context !== 'create'), // Hide in CREATE mode (handled by Booking Flow)

                // 💇 WAS WIRD GEMACHT? - Service & Staff Section
                Section::make('💇 Was wird gemacht?')
                    ->description(function ($context, $record) {
                        if ($context === 'edit' && $record && $record->service && $record->staff) {
                            $service = $record->service;
                            $staff = $record->staff;
                            $duration = $record->duration_minutes ?? 30;
                            return "**{$service->name}** ({$duration} Min) - {$staff->name}";
                        }
                        return 'Service und Mitarbeiter auswählen';
                    })
                    ->schema([
                        // DEPRECATED: Old service/staff dropdowns - now replaced by BookingFlow component
                        // Only shown in EDIT mode for reference
                        Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('service_id')
                                    ->label('Dienstleistung')
                                    ->relationship('service', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        if ($state) {
                                            $service = Service::find($state);
                                            if ($service) {
                                                $set('duration_minutes', $service->duration_minutes ?? 30);
                                                $set('price', $service->price);
                                            }
                                        }
                                    }),

                                // Staff - SMART FILTER: Branch + Service
                                Forms\Components\Select::make('staff_id')
                                    ->label('Mitarbeiter')
                                    ->relationship('staff', 'name', function ($query, callable $get) {
                                        $branchId = $get('branch_id');
                                        $serviceId = $get('service_id');

                                        // Filter by branch (direct foreign key)
                                        if ($branchId) {
                                            $query->where('branch_id', $branchId);
                                        }

                                        // Filter by service (pivot table)
                                        if ($serviceId) {
                                            $query->whereHas('services', function ($q) use ($serviceId) {
                                                $q->where('services.id', $serviceId)
                                                  ->where('service_staff.is_active', true)
                                                  ->where('service_staff.can_book', true);
                                            });
                                        }

                                        return $query;
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->helperText(function (callable $get) {
                                        $branchId = $get('branch_id');
                                        $serviceId = $get('service_id');

                                        if (!$branchId && !$serviceId) {
                                            return '⚠️ Bitte zuerst Filiale und Service wählen';
                                        }
                                        if (!$branchId) {
                                            return '⚠️ Bitte zuerst Filiale wählen';
                                        }
                                        if (!$serviceId) {
                                            return '⚠️ Bitte zuerst Service wählen';
                                        }

                                        return 'Nur Mitarbeiter die diesen Service in dieser Filiale anbieten';
                                    }),
                            ])
                            ->hidden(fn ($context) => $context === 'create'), // HIDE in create mode

                        // Service Info (Duration + Price)
                        Forms\Components\Placeholder::make('service_info')
                            ->label('')
                            ->content(function (callable $get) {
                                $serviceId = $get('service_id');
                                if (!$serviceId) return '';

                                $service = Service::find($serviceId);
                                if (!$service) return '';

                                $duration = $service->duration_minutes ?? 30;
                                $price = $service->price ? number_format($service->price, 2, ',', '.') : '0,00';

                                return "⏱️ **Dauer:** {$duration} Min | 💰 **Preis:** {$price} €";
                            })
                            ->visible(fn (callable $get, $context) => $get('service_id') !== null && $context !== 'create')
                            ->columnSpanFull(),

                    ])
                    ->collapsible()
                    ->collapsed(false)  // IMMER OFFEN
                    ->persistCollapsed()
                    ->visible(fn ($context) => $context !== 'create'), // Hide in CREATE mode (handled by Booking Flow)

                // ⏰ WANN? Section - Time Selection with Week Picker
                Section::make(fn ($context) => $context === 'create' ? '📅 Termin buchen' : '⏰ Wann?')
                    ->description(function ($context, $record) {
                        if ($context === 'edit' && $record) {
                            $start = Carbon::parse($record->starts_at);
                            $end = Carbon::parse($record->ends_at);
                            $statusLabel = match($record->status) {
                                'pending' => '⏳ Ausstehend',
                                'confirmed' => '✅ Bestätigt',
                                'in_progress' => '🔄 In Bearbeitung',
                                'completed' => '✨ Abgeschlossen',
                                'cancelled' => '❌ Storniert',
                                'no_show' => '👻 Nicht erschienen',
                                default => $record->status
                            };
                            return "**{$start->format('d.m.Y H:i')} - {$end->format('H:i')} Uhr** ({$statusLabel})";
                        }
                        return 'Wählen Sie Filiale, Kunde, Service, Mitarbeiter und Termin';
                    })
                    ->schema([
                        // NEW: V4 Professional Booking Flow (Service-First)
                        Forms\Components\ViewField::make('booking_flow')
                            ->label('')
                            ->view('livewire.appointment-booking-flow-wrapper', function (callable $get, $context, $record) {
                                $companyId = ($context === 'edit' && $record)
                                    ? $record->company_id
                                    : (auth()->user()->company_id ?? 1);

                                return [
                                    'companyId' => $companyId,
                                    'preselectedServiceId' => $get('service_id'),
                                    'preselectedSlot' => $get('starts_at'),
                                ];
                            })
                            ->reactive()
                            ->live()
                            ->columnSpanFull()
                            ->dehydrated(false)
                            ->extraAttributes(['class' => 'booking-flow-field'])
                            ->visible(fn ($context) => $context === 'create'), // Only in CREATE mode

                        // Hidden Fields: For BookingFlowWrapper to populate (CREATE mode only)
                        Forms\Components\TextInput::make('branch_id')
                            ->hidden()
                            ->visible(fn ($context) => $context === 'create')
                            ->dehydrated(),

                        Forms\Components\TextInput::make('customer_id')
                            ->hidden()
                            ->visible(fn ($context) => $context === 'create')
                            ->dehydrated(),

                        Forms\Components\TextInput::make('service_id')
                            ->hidden()
                            ->visible(fn ($context) => $context === 'create')
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $service = Service::find($state);
                                    if ($service) {
                                        $set('duration_minutes', $service->duration_minutes ?? 30);
                                        $set('price', $service->price);
                                    }
                                }
                            })
                            ->dehydrated(),

                        Forms\Components\TextInput::make('staff_id')
                            ->hidden()
                            ->visible(fn ($context) => $context === 'create')
                            ->dehydrated(),

                        // Hidden Field: starts_at (populated by Week Picker via Livewire)
                        Forms\Components\Hidden::make('starts_at')
                            ->required()
                            ->live()  // CRITICAL: Detects DOM changes immediately
                            ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                if ($state) {
                                    // Berechne automatisch ends_at
                                    $duration = $get('duration_minutes') ?? 30;
                                    $endsAt = Carbon::parse($state)->addMinutes($duration);
                                    $set('ends_at', $endsAt);
                                }
                            }),

                        // Fallback: Manual DateTimePicker (optional, nur wenn Week Picker nicht genutzt wird)
                        Grid::make(2)->schema([
                            Forms\Components\DateTimePicker::make('starts_at_manual')
                                ->label('⏰ Oder: Manuell Termin-Beginn wählen')
                                ->seconds(false)
                                ->minDate(now())
                                ->maxDate(now()->addWeeks(4))
                                ->native(false)
                                ->displayFormat('d.m.Y H:i')
                                ->reactive()
                                ->disabled(fn (callable $get) => !$get('service_id'))
                                ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                    if ($state) {
                                        // Copy to starts_at
                                        $set('starts_at', $state);
                                        // Berechne automatisch ends_at
                                        $duration = $get('duration_minutes') ?? 30;
                                        $endsAt = Carbon::parse($state)->addMinutes($duration);
                                        $set('ends_at', $endsAt);
                                    }
                                })
                                ->helperText(fn (callable $get) =>
                                    !$get('service_id')
                                        ? '⚠️ Bitte zuerst Service wählen'
                                        : 'Alternativ zu Wochenkalender: Datum/Uhrzeit manuell eingeben'
                                )
                                ->suffixAction(
                                    Forms\Components\Actions\Action::make('findNextSlot')
                                        ->label('Nächster freier Slot')
                                        ->icon('heroicon-m-sparkles')
                                        ->color('success')
                                        ->action(function (callable $get, callable $set) {
                                            $staffId = $get('staff_id');
                                            $duration = $get('duration_minutes') ?? 30;

                                            if (!$staffId) {
                                                return;
                                            }

                                            // Finde nächsten verfügbaren Slot
                                            $slots = self::findAvailableSlots($staffId, $duration, 1);

                                            if (!empty($slots)) {
                                                $nextSlot = $slots[0];
                                                $set('starts_at', $nextSlot);
                                                $set('ends_at', $nextSlot->copy()->addMinutes($duration));

                                                \Filament\Notifications\Notification::make()
                                                    ->success()
                                                    ->title('Slot gefunden!')
                                                    ->body('Nächster freier Termin: ' . $nextSlot->format('d.m.Y H:i') . ' Uhr')
                                                    ->send();
                                            } else {
                                                \Filament\Notifications\Notification::make()
                                                    ->warning()
                                                    ->title('Keine freien Slots')
                                                    ->body('In den nächsten 2 Wochen sind keine Termine frei.')
                                                    ->send();
                                            }
                                        })
                                        ->disabled(fn (callable $get) => !$get('staff_id'))
                                ),

                            Forms\Components\DateTimePicker::make('ends_at')
                                ->label('🏁 Termin-Ende')
                                ->seconds(false)
                                ->native(false)
                                ->displayFormat('d.m.Y H:i')
                                ->disabled()
                                ->dehydrated()
                                ->helperText('= Beginn + Dauer (automatisch berechnet)'),
                        ]),

                        // Dauer & Ende Info anzeigen
                        Grid::make(2)
                            ->schema([
                                // DAUER SICHTBAR
                                Forms\Components\TextInput::make('duration_minutes')
                                    ->label('Dauer')
                                    ->suffix('Min')
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated()
                                    ->default(30)
                                    ->helperText('⏱️ Automatisch aus Service'),

                                // ENDE INFO (berechnet)
                                Forms\Components\Placeholder::make('end_time_display')
                                    ->label('Ende')
                                    ->content(function (callable $get) {
                                        $startsAt = $get('starts_at');
                                        $duration = $get('duration_minutes') ?? 30;

                                        if (!$startsAt) {
                                            return '—';
                                        }

                                        $endsAt = Carbon::parse($startsAt)->addMinutes($duration);
                                        return '🕐 ' . $endsAt->format('H:i') . ' Uhr (= Beginn + Dauer)';
                                    }),
                            ])
                            ->visible(fn (callable $get) => $get('time_slot') && $get('time_slot') !== 'no_slots'),

                        // Status Field - Moved from Additional Information
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'pending' => '⏳ Ausstehend',
                                'confirmed' => '✅ Bestätigt',
                                'in_progress' => '🔄 In Bearbeitung',
                                'completed' => '✨ Abgeschlossen',
                                'cancelled' => '❌ Storniert',
                                'no_show' => '👻 Nicht erschienen',
                            ])
                            ->default('pending')
                            ->required()
                            ->reactive()
                            ->helperText(fn (callable $get, $context) =>
                                $context === 'edit'
                                    ? 'Status nach Termin aktualisieren'
                                    : 'Neue Termine sind standardmäßig "Ausstehend"'
                            )
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(false)  // IMMER OFFEN
                    ->persistCollapsed(),

                // Additional Information Section
                Section::make('Zusätzliche Informationen')
                    ->description('Erweiterte Einstellungen und Details')
                    ->icon('heroicon-o-cog')
                    ->schema([
                        // Internal notes and booking metadata
                        Forms\Components\RichEditor::make('notes')
                            ->label('Notizen')
                            ->toolbarButtons([
                                'bold',
                                'italic',
                                'bulletList',
                                'orderedList',
                            ])
                            ->columnSpanFull(),

                        Grid::make(3)
                            ->schema([
                                Forms\Components\Select::make('source')
                                    ->label('Buchungsquelle')
                                    ->options([
                                        'phone' => '📞 Telefon',
                                        'online' => '💻 Online',
                                        'walk_in' => '🚶 Walk-In',
                                        'app' => '📱 App',
                                        'ai_assistant' => '🤖 KI-Assistent',
                                    ])
                                    ->default('phone')
                                    ->required(),

                                Forms\Components\TextInput::make('price')
                                    ->label('Preis')
                                    ->numeric()
                                    ->prefix('€')
                                    ->step(0.01),

                                Forms\Components\Select::make('booking_type')
                                    ->label('Buchungstyp')
                                    ->options([
                                        'single' => 'Einzeltermin',
                                        'series' => 'Serie',
                                        'group' => 'Gruppe',
                                        'package' => 'Paket',
                                    ])
                                    ->default('single')
                                    ->required(),
                            ]),

                        // Reminder settings
                        Forms\Components\Toggle::make('send_reminder')
                            ->label('Erinnerung senden')
                            ->default(true)
                            ->reactive()
                            ->helperText('24 Stunden vor dem Termin'),

                        Forms\Components\Toggle::make('send_confirmation')
                            ->label('Bestätigung senden')
                            ->default(true)
                            ->helperText('Sofort nach der Buchung'),

                        // Package/Series fields (only shown when relevant)
                        Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('package_sessions_total')
                                    ->label('Paket Sitzungen Gesamt')
                                    ->numeric()
                                    ->visible(fn (Get $get) => $get('booking_type') === 'package'),

                                Forms\Components\TextInput::make('package_sessions_used')
                                    ->label('Paket Sitzungen Verbraucht')
                                    ->numeric()
                                    ->default(0)
                                    ->visible(fn (Get $get) => $get('booking_type') === 'package'),

                                Forms\Components\DatePicker::make('package_expires_at')
                                    ->label('Paket läuft ab')
                                    ->visible(fn (Get $get) => $get('booking_type') === 'package'),
                            ]),
                    ])
                    ->collapsed(),

                // Hidden technical fields (kept for data integrity)
                Forms\Components\Hidden::make('version')
                    ->default(0),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('starts_at', 'asc')
            ->columns([
                // Time slot column with smart formatting
                Tables\Columns\TextColumn::make('starts_at')
                    ->label('Zeit')
                    ->dateTime('H:i')
                    ->date('d.m.Y')
                    ->description(fn ($record) =>
                        Carbon::parse($record->starts_at)->format('D') . ' | ' .
                        Carbon::parse($record->starts_at)->diffForHumans()
                    )
                    ->sortable()
                    ->icon('heroicon-m-clock')
                    ->iconColor(fn ($record) =>
                        Carbon::parse($record->starts_at)->isPast() ? 'gray' : 'primary'
                    ),

                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Kunde')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-m-user')
                    ->description(fn ($record) => $record->customer?->phone)
                    ->url(fn ($record) => $record->customer
                        ? CustomerResource::getUrl('view', ['record' => $record->customer_id])
                        : null
                    ),

                Tables\Columns\TextColumn::make('service.name')
                    ->label('Service')
                    ->searchable()
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('staff.name')
                    ->label('Mitarbeiter')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('branch.name')
                    ->label('Filiale')
                    ->searchable()
                    ->toggleable()
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'confirmed',
                        'primary' => 'in_progress',
                        'info' => 'completed',
                        'danger' => 'cancelled',
                        'gray' => 'no_show',
                    ])
                    ->icon(fn (string $state): ?string => match($state) {
                        'pending' => 'heroicon-m-clock',
                        'confirmed' => 'heroicon-m-check-circle',
                        'in_progress' => 'heroicon-m-arrow-path',
                        'completed' => 'heroicon-m-sparkles',
                        'cancelled' => 'heroicon-m-x-circle',
                        'no_show' => 'heroicon-m-user-minus',
                        default => null,
                    })
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'pending' => 'Ausstehend',
                        'confirmed' => 'Bestätigt',
                        'in_progress' => 'In Bearbeitung',
                        'completed' => 'Abgeschlossen',
                        'cancelled' => 'Storniert',
                        'no_show' => 'Nicht erschienen',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('duration')
                    ->label('Dauer')
                    ->getStateUsing(fn ($record) =>
                        $record->starts_at && $record->ends_at
                            ? Carbon::parse($record->starts_at)->diffInMinutes($record->ends_at) . ' Min'
                            : '-'
                    )
                    ->badge()
                    ->color('gray')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('price')
                    ->label('Preis')
                    ->money('EUR')
                    ->sortable()
                    ->toggleable()
                    ->alignEnd(),

                Tables\Columns\IconColumn::make('reminder_24h_sent_at')
                    ->label('Erinnerung')
                    ->boolean()
                    ->trueIcon('heroicon-o-bell')
                    ->falseIcon('heroicon-o-bell-slash')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('source')
                    ->label('Quelle')
                    ->badge()
                    ->colors([
                        'primary' => 'phone',
                        'success' => 'online',
                        'warning' => 'walk_in',
                        'info' => 'app',
                        'danger' => 'ai_assistant',
                    ])
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'phone' => '📞 Telefon',
                        'online' => '💻 Online',
                        'walk_in' => '🚶 Walk-In',
                        'app' => '📱 App',
                        'ai_assistant' => '🤖 KI',
                        default => $state,
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // Quick filters for common views
                Tables\Filters\TernaryFilter::make('time_filter')
                    ->label('Zeitraum')
                    ->placeholder('Alle Termine')
                    ->trueLabel('Heute')
                    ->falseLabel('Diese Woche')
                    ->queries(
                        true: fn (Builder $query) => $query->whereDate('starts_at', today()),
                        false: fn (Builder $query) => $query->whereBetween('starts_at', [
                            now()->startOfWeek(),
                            now()->endOfWeek()
                        ]),
                        blank: fn (Builder $query) => $query,
                    ),

                SelectFilter::make('status')
                    ->label('Status')
                    ->multiple()
                    ->options([
                        'pending' => 'Ausstehend',
                        'confirmed' => 'Bestätigt',
                        'in_progress' => 'In Bearbeitung',
                        'completed' => 'Abgeschlossen',
                        'cancelled' => 'Storniert',
                        'no_show' => 'Nicht erschienen',
                    ]),

                SelectFilter::make('staff_id')
                    ->label('Mitarbeiter')
                    ->relationship('staff', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('service_id')
                    ->label('Service')
                    ->relationship('service', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('branch_id')
                    ->label('Filiale')
                    ->relationship('branch', 'name')
                    ->searchable()
                    ->preload(),

                Filter::make('upcoming')
                    ->label('Bevorstehend')
                    ->query(fn (Builder $query): Builder => $query->where('starts_at', '>=', now()))
                    ->default(),

                Filter::make('past')
                    ->label('Vergangen')
                    ->query(fn (Builder $query): Builder => $query->where('starts_at', '<', now())),
            ])
            ->filtersLayout(Tables\Enums\FiltersLayout::AboveContent)
            ->filtersFormColumns(4)
            ->actions([
                // Quick status update actions
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('confirm')
                        ->label('Bestätigen')
                        ->icon('heroicon-m-check-circle')
                        ->color('success')
                        ->visible(fn ($record) => $record->status === 'pending')
                        ->action(function ($record) {
                            $record->update(['status' => 'confirmed']);
                            Notification::make()
                                ->title('Termin bestätigt')
                                ->body("Termin mit {$record->customer->name} wurde bestätigt.")
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\Action::make('complete')
                        ->label('Abschließen')
                        ->icon('heroicon-m-sparkles')
                        ->color('success')
                        ->visible(fn ($record) => in_array($record->status, ['confirmed', 'in_progress']))
                        ->action(function ($record) {
                            $record->update(['status' => 'completed']);
                            Notification::make()
                                ->title('Termin abgeschlossen')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\Action::make('cancel')
                        ->label('Stornieren')
                        ->icon('heroicon-m-x-circle')
                        ->color('danger')
                        ->visible(fn ($record) => !in_array($record->status, ['completed', 'cancelled']))
                        ->requiresConfirmation()
                        ->modalHeading('Termin stornieren')
                        ->modalDescription('Sind Sie sicher, dass Sie diesen Termin stornieren möchten?')
                        ->action(function ($record) {
                            // Update status and set sync origin
                            $record->update([
                                'status' => 'cancelled',
                                'sync_origin' => 'admin',  // Mark as admin-initiated
                                'cancellation_reason' => 'Cancelled by admin',
                            ]);

                            // 🔄 Fire AppointmentCancelled event for Cal.com sync
                            event(new AppointmentCancelled(
                                appointment: $record,
                                reason: 'Cancelled by admin',
                                cancelledBy: 'admin'
                            ));

                            Notification::make()
                                ->title('Termin storniert')
                                ->warning()
                                ->send();
                        }),

                    Tables\Actions\Action::make('reschedule')
                        ->label('Verschieben')
                        ->icon('heroicon-m-calendar')
                        ->color('warning')
                        ->modalWidth('7xl') // Wide modal for week view
                        ->modalHeading('Termin verschieben - Wochenansicht')
                        ->modalSubmitActionLabel('Verschieben')
                        ->modalCancelActionLabel('Abbrechen')
                        ->form(function ($record) {
                            // VERSTÄRKTE Guard Clause: Check service_id UND starts_at UND service relation
                            if (!$record->service_id || !$record->starts_at || !$record->relationLoaded('service') || !$record->service) {
                                return [
                                    Forms\Components\Placeholder::make('error')
                                        ->label('')
                                        ->content('⚠️ Termin hat keinen Service zugeordnet oder unvollständige Daten. Bitte bearbeiten Sie den Termin.')
                                        ->columnSpanFull(),
                                ];
                            }

                            return [
                                // Service Info Display (NULL-SAFE)
                                Forms\Components\Placeholder::make('service_info')
                                    ->label('Service')
                                    ->content(function () use ($record) {
                                        $serviceName = $record->service?->name ?? 'Unbekannter Service';
                                        $serviceDuration = $record->service?->duration_minutes ?? 30;
                                        return "{$serviceName} ({$serviceDuration} min)";
                                    })
                                    ->columnSpanFull(),

                                // Week Picker Component (CLOSURE statt Array - wie Create Form)
                                Forms\Components\ViewField::make('week_picker')
                                    ->label('')
                                    ->view('livewire.appointment-week-picker-wrapper', function () use ($record) {
                                        return [
                                            'serviceId' => $record->service_id,
                                            'preselectedSlot' => $record->starts_at?->toIso8601String() ?? null,
                                        ];
                                    })
                                    ->reactive()  // ← FIX: Ensure proper rendering
                                    ->live()      // ← FIX: Update immediately
                                    ->columnSpanFull()
                                    ->dehydrated(false)
                                    ->extraAttributes(['class' => 'week-picker-field']),

                                // Hidden field to store selected datetime (populated by week picker)
                                Forms\Components\Hidden::make('starts_at')
                                    ->required(),
                            ];
                        })
                        ->action(function ($record, array $data) {
                            // Store old time before update
                            $oldStartTime = Carbon::parse($record->starts_at);
                            $newStartTime = Carbon::parse($data['starts_at']);

                            $duration = $oldStartTime->diffInMinutes($record->ends_at);

                            // Check for conflicts before updating
                            $conflicts = Appointment::where('staff_id', $record->staff_id)
                                ->where('id', '!=', $record->id)
                                ->where('status', '!=', 'cancelled')
                                ->where(function ($query) use ($data, $newStartTime, $duration) {
                                    $endsAt = $newStartTime->copy()->addMinutes($duration);
                                    $query->where(function ($q) use ($data) {
                                        $q->where('starts_at', '<=', $data['starts_at'])
                                          ->where('ends_at', '>', $data['starts_at']);
                                    })->orWhere(function ($q) use ($endsAt) {
                                        $q->where('starts_at', '<', $endsAt)
                                          ->where('ends_at', '>=', $endsAt);
                                    })->orWhere(function ($q) use ($data, $endsAt) {
                                        $q->where('starts_at', '>=', $data['starts_at'])
                                          ->where('ends_at', '<=', $endsAt);
                                    });
                                })
                                ->exists();

                            if ($conflicts) {
                                Notification::make()
                                    ->title('⚠️ Konflikt erkannt!')
                                    ->body('Der Mitarbeiter hat bereits einen Termin zu dieser Zeit.')
                                    ->warning()
                                    ->send();
                                return;
                            }

                            // Update appointment and set sync origin
                            $record->update([
                                'starts_at' => $data['starts_at'],
                                'ends_at' => $newStartTime->copy()->addMinutes($duration),
                                'sync_origin' => 'admin',  // Mark as admin-initiated
                            ]);

                            // 🔄 Fire AppointmentRescheduled event for Cal.com sync
                            event(new AppointmentRescheduled(
                                appointment: $record,
                                oldStartTime: $oldStartTime,
                                newStartTime: $newStartTime,
                                reason: 'Rescheduled by admin'
                            ));

                            Notification::make()
                                ->title('Termin verschoben')
                                ->body("Neuer Termin: " . $newStartTime->format('d.m.Y H:i'))
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\Action::make('sendReminder')
                        ->label('Erinnerung senden')
                        ->icon('heroicon-m-bell')
                        ->color('info')
                        ->action(function ($record) {
                            // TODO: Implement SMS/Email reminder
                            $record->update(['reminder_24h_sent_at' => now()]);
                            Notification::make()
                                ->title('Erinnerung gesendet')
                                ->body("Erinnerung wurde an {$record->customer->name} gesendet.")
                                ->success()
                                ->send();
                        }),
                ]),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('bulkConfirm')
                        ->label('Bestätigen')
                        ->icon('heroicon-m-check-circle')
                        ->color('success')
                        ->action(function ($records) {
                            $records->each->update(['status' => 'confirmed']);
                            Notification::make()
                                ->title('Termine bestätigt')
                                ->body(count($records) . ' Termine wurden bestätigt.')
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation(),

                    Tables\Actions\BulkAction::make('bulkCancel')
                        ->label('Stornieren')
                        ->icon('heroicon-m-x-circle')
                        ->color('danger')
                        ->action(function ($records) {
                            // Cancel each appointment and fire events
                            $records->each(function ($record) {
                                $record->update([
                                    'status' => 'cancelled',
                                    'sync_origin' => 'admin',
                                    'cancellation_reason' => 'Bulk cancelled by admin',
                                ]);

                                // 🔄 Fire AppointmentCancelled event for Cal.com sync
                                event(new AppointmentCancelled(
                                    appointment: $record,
                                    reason: 'Bulk cancelled by admin',
                                    cancelledBy: 'admin'
                                ));
                            });

                            Notification::make()
                                ->title('Termine storniert')
                                ->body(count($records) . ' Termine wurden storniert.')
                                ->warning()
                                ->send();
                        })
                        ->requiresConfirmation(),

                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Löschen'),
                ])
                ->label('Massenaktionen')
                ->icon('heroicon-o-squares-plus')
                ->color('primary'),
            ])
            ->poll('60s')
            ->deferLoading()
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->striped()
            ->defaultPaginationPageOption(25);
    }

    public static function getRelations(): array
    {
        return [
            // RelationManagers\NotesRelationManager::class,
        ];
    }

    public static function getWidgets(): array
    {
        return [
            \App\Filament\Resources\AppointmentResource\Widgets\AppointmentStats::class,
            \App\Filament\Resources\AppointmentResource\Widgets\UpcomingAppointments::class,
            \App\Filament\Resources\AppointmentResource\Widgets\AppointmentCalendar::class,
        ];
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // Hauptinformationen
                InfoSection::make('Terminübersicht')
                    ->schema([
                        InfoGrid::make(3)
                            ->schema([
                                TextEntry::make('id')
                                    ->label('Termin-ID')
                                    ->badge()
                                    ->color('gray'),

                                TextEntry::make('status')
                                    ->label('Status')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        'scheduled' => '📅 Geplant',
                                        'confirmed' => '✅ Bestätigt',
                                        'completed' => '✔️ Abgeschlossen',
                                        'cancelled' => '❌ Storniert',
                                        'no_show' => '🚫 Nicht erschienen',
                                        default => $state,
                                    })
                                    ->color(fn (string $state): string => match ($state) {
                                        'scheduled' => 'info',
                                        'confirmed' => 'success',
                                        'completed' => 'gray',
                                        'cancelled' => 'danger',
                                        'no_show' => 'warning',
                                        default => 'gray',
                                    }),

                                TextEntry::make('booking_type')
                                    ->label('Buchungsart')
                                    ->badge()
                                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                                        'online' => '🌐 Online',
                                        'phone' => '📞 Telefon',
                                        'walk-in' => '🚶 Walk-In',
                                        'recurring' => '🔄 Wiederkehrend',
                                        default => $state ?? 'Standard',
                                    }),
                            ]),

                        InfoGrid::make(2)
                            ->schema([
                                TextEntry::make('starts_at')
                                    ->label('Beginn')
                                    ->dateTime('d.m.Y H:i')
                                    ->icon('heroicon-o-calendar')
                                    ->size('lg'),

                                TextEntry::make('ends_at')
                                    ->label('Ende')
                                    ->dateTime('d.m.Y H:i')
                                    ->icon('heroicon-o-clock'),
                            ]),
                    ])
                    ->icon('heroicon-o-calendar-days')
                    ->collapsible(),

                // Teilnehmer
                InfoSection::make('Teilnehmer')
                    ->schema([
                        InfoGrid::make(2)
                            ->schema([
                                TextEntry::make('customer.name')
                                    ->label('Kunde')
                                    ->icon('heroicon-o-user')
                                    ->url(fn ($record) => $record->customer_id
                                        ? CustomerResource::getUrl('view', ['record' => $record->customer_id])
                                        : null)
                                    ->openUrlInNewTab()
                                    ->placeholder('Kein Kunde zugeordnet'),

                                TextEntry::make('staff.name')
                                    ->label('Mitarbeiter')
                                    ->icon('heroicon-o-user-circle')
                                    ->url(fn ($record) => $record->staff_id
                                        ? StaffResource::getUrl('view', ['record' => $record->staff_id])
                                        : null)
                                    ->openUrlInNewTab()
                                    ->placeholder('Kein Mitarbeiter zugeordnet'),
                            ]),

                        TextEntry::make('service.name')
                            ->label('Service')
                            ->icon('heroicon-o-briefcase')
                            ->url(fn ($record) => $record->service_id
                                ? ServiceResource::getUrl('view', ['record' => $record->service_id])
                                : null)
                            ->openUrlInNewTab()
                            ->placeholder('Kein Service zugeordnet'),

                        InfoGrid::make(2)
                            ->schema([
                                TextEntry::make('branch.name')
                                    ->label('Filiale')
                                    ->icon('heroicon-o-building-storefront')
                                    ->placeholder('Keine Filiale'),

                                TextEntry::make('company.name')
                                    ->label('Unternehmen')
                                    ->icon('heroicon-o-building-office')
                                    ->placeholder('Kein Unternehmen'),
                            ]),
                    ])
                    ->icon('heroicon-o-user-group')
                    ->collapsible(),

                // Service-Details
                InfoSection::make('Service & Preise')
                    ->schema([
                        InfoGrid::make(3)
                            ->schema([
                                TextEntry::make('service.duration_minutes')
                                    ->label('Dauer')
                                    ->formatStateUsing(fn ($state): string => $state ? "{$state} Min." : 'N/A')
                                    ->icon('heroicon-o-clock'),

                                TextEntry::make('price')
                                    ->label('Preis')
                                    ->money('EUR')
                                    ->icon('heroicon-o-currency-euro'),

                                TextEntry::make('travel_time_minutes')
                                    ->label('Anfahrtszeit')
                                    ->formatStateUsing(fn ($state): string => $state ? "{$state} Min." : 'Keine')
                                    ->icon('heroicon-o-truck'),
                            ]),

                        TextEntry::make('notes')
                            ->label('Notizen')
                            ->columnSpanFull()
                            ->placeholder('Keine Notizen vorhanden'),
                    ])
                    ->icon('heroicon-o-currency-euro')
                    ->collapsible()
                    ->collapsed(true),

                // Cal.com Integration
                InfoSection::make('Cal.com Integration')
                    ->schema([
                        InfoGrid::make(2)
                            ->schema([
                                TextEntry::make('calcom_booking_id')
                                    ->label('Cal.com Booking ID')
                                    ->badge()
                                    ->color('info')
                                    ->placeholder('Keine Cal.com ID'),

                                TextEntry::make('calcom_event_type_id')
                                    ->label('Event Type ID')
                                    ->badge()
                                    ->placeholder('Kein Event Type'),
                            ]),

                        TextEntry::make('source')
                            ->label('Quelle')
                            ->badge()
                            ->formatStateUsing(fn (?string $state): string => match ($state) {
                                'cal.com' => '📅 Cal.com',
                                'api' => '🔌 API',
                                'manual' => '✋ Manuell',
                                'phone' => '📞 Telefon',
                                default => $state ?? 'Unbekannt',
                            }),
                    ])
                    ->icon('heroicon-o-link')
                    ->collapsible()
                    ->collapsed(true)
                    ->visible(fn ($record): bool =>
                        !empty($record->calcom_booking_id) ||
                        !empty($record->calcom_event_type_id) ||
                        !empty($record->source)
                    ),

                // Serie & Pakete
                InfoSection::make('Serie & Pakete')
                    ->schema([
                        InfoGrid::make(2)
                            ->schema([
                                TextEntry::make('series_id')
                                    ->label('Serien-ID')
                                    ->badge()
                                    ->placeholder('Keine Serie'),

                                TextEntry::make('group_booking_id')
                                    ->label('Gruppenbuchung')
                                    ->badge()
                                    ->placeholder('Keine Gruppe'),
                            ]),

                        InfoGrid::make(3)
                            ->schema([
                                TextEntry::make('package_sessions_total')
                                    ->label('Paket Gesamt')
                                    ->badge()
                                    ->formatStateUsing(fn ($state): string => $state ? "{$state} Sitzungen" : 'Kein Paket'),

                                TextEntry::make('package_sessions_used')
                                    ->label('Verwendet')
                                    ->badge()
                                    ->formatStateUsing(fn ($state): string => $state ? "{$state} Sitzungen" : '0'),

                                TextEntry::make('package_expires_at')
                                    ->label('Paket läuft ab')
                                    ->dateTime('d.m.Y')
                                    ->placeholder('Kein Ablaufdatum'),
                            ]),

                        TextEntry::make('recurrence_rule')
                            ->label('Wiederholungsregel')
                            ->columnSpanFull()
                            ->placeholder('Keine Wiederholung'),
                    ])
                    ->icon('heroicon-o-arrow-path')
                    ->collapsible()
                    ->collapsed(true)
                    ->visible(fn ($record): bool =>
                        !empty($record->series_id) ||
                        !empty($record->group_booking_id) ||
                        !empty($record->package_sessions_total) ||
                        !empty($record->recurrence_rule)
                    ),

                // Erinnerungen & Metadaten
                InfoSection::make('Erinnerungen & System')
                    ->schema([
                        InfoGrid::make(2)
                            ->schema([
                                TextEntry::make('reminder_24h_sent_at')
                                    ->label('24h Erinnerung')
                                    ->dateTime('d.m.Y H:i')
                                    ->placeholder('Nicht gesendet')
                                    ->icon('heroicon-o-bell'),

                                TextEntry::make('created_at')
                                    ->label('Erstellt')
                                    ->dateTime('d.m.Y H:i')
                                    ->icon('heroicon-o-calendar'),
                            ]),

                        TextEntry::make('external_id')
                            ->label('Externe ID')
                            ->badge()
                            ->placeholder('Keine externe ID'),

                        TextEntry::make('metadata')
                            ->label('Metadaten')
                            ->formatStateUsing(function ($state) {
                                if (empty($state)) return 'Keine Metadaten';
                                if (is_array($state)) {
                                    return json_encode($state, JSON_UNESCAPED_UNICODE);
                                }
                                return (string)$state;
                            })
                            ->columnSpanFull(),
                    ])
                    ->icon('heroicon-o-information-circle')
                    ->collapsible()
                    ->collapsed(true),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAppointments::route('/'),
            'calendar' => Pages\Calendar::route('/calendar'),
            'create' => Pages\CreateAppointment::route('/create'),
            'view' => Pages\ViewAppointment::route('/{record}'),
            'edit' => Pages\EditAppointment::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'customer:id,name,email,phone',
                'service:id,name,price,duration_minutes',
                'staff:id,name',
                'branch:id,name',
                'company:id,name'
            ])
            ->withCasts([
                'starts_at' => 'datetime',
                'ends_at' => 'datetime',
            ]);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['customer.name', 'service.name', 'staff.name', 'notes'];
    }

    /**
     * Find available time slots for a staff member
     *
     * @param string $staffId Staff member ID (UUID)
     * @param int $duration Duration in minutes
     * @param int $count Number of slots to find
     * @return array Array of Carbon dates representing available slots
     */
    protected static function findAvailableSlots(string $staffId, int $duration, int $count = 5): array
    {
        $availableSlots = [];
        $currentDate = Carbon::now()->startOfHour();

        // If it's past 5 PM, start from tomorrow 9 AM
        if ($currentDate->hour >= 17) {
            $currentDate->addDay()->setTime(9, 0);
        } elseif ($currentDate->hour < 9) {
            $currentDate->setTime(9, 0);
        }

        $maxDays = 14; // Search up to 2 weeks ahead
        $daysSearched = 0;

        while (count($availableSlots) < $count && $daysSearched < $maxDays) {
            // Skip weekends (optional - remove these lines if staff works weekends)
            if ($currentDate->isWeekend()) {
                $currentDate->addDay()->setTime(9, 0);
                $daysSearched++;
                continue;
            }

            // Check slots from 9 AM to 5 PM (minus duration to fit)
            $dayStart = $currentDate->copy()->setTime(9, 0);
            $dayEnd = $currentDate->copy()->setTime(17, 0)->subMinutes($duration);
            $currentSlot = $dayStart->copy();

            while ($currentSlot <= $dayEnd) {
                // Check if this slot conflicts with existing appointments
                $hasConflict = Appointment::where('staff_id', $staffId)
                    ->where('status', '!=', 'cancelled')
                    ->where(function ($query) use ($currentSlot, $duration) {
                        $slotEnd = $currentSlot->copy()->addMinutes($duration);
                        $query->where(function ($q) use ($currentSlot) {
                            $q->where('starts_at', '<=', $currentSlot)
                              ->where('ends_at', '>', $currentSlot);
                        })->orWhere(function ($q) use ($slotEnd) {
                            $q->where('starts_at', '<', $slotEnd)
                              ->where('ends_at', '>=', $slotEnd);
                        })->orWhere(function ($q) use ($currentSlot, $slotEnd) {
                            $q->where('starts_at', '>=', $currentSlot)
                              ->where('ends_at', '<=', $slotEnd);
                        });
                    })
                    ->exists();

                if (!$hasConflict) {
                    $availableSlots[] = $currentSlot->copy();

                    if (count($availableSlots) >= $count) {
                        break 2; // Break out of both loops
                    }
                }

                // Move to next 15-minute slot
                $currentSlot->addMinutes(15);
            }

            // Move to next day at 9 AM
            $currentDate->addDay()->setTime(9, 0);
            $daysSearched++;
        }

        return $availableSlots;
    }
}