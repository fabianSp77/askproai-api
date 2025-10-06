<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasCachedNavigationBadge;
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
                // Main appointment details in a prominent section
                Section::make('Termindetails')
                    ->description('Hauptinformationen zum Termin')
                    ->icon('heroicon-o-calendar')
                    ->schema([
                        Grid::make(2)
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
                                            if ($customer) {
                                                // Auto-fill branch if customer has preferred branch
                                                if ($customer->preferred_branch_id) {
                                                    $set('branch_id', $customer->preferred_branch_id);
                                                }
                                            }
                                        }
                                    }),

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
                                                // Auto-calculate end time based on service duration
                                                $set('duration_minutes', $service->duration_minutes ?? 30);
                                                // Auto-set price
                                                $set('price', $service->price);
                                            }
                                        }
                                    }),
                            ]),

                        Grid::make(3)
                            ->schema([
                                Forms\Components\DateTimePicker::make('starts_at')
                                    ->label('Beginn')
                                    ->required()
                                    ->native(false)
                                    ->seconds(false)
                                    ->minutesStep(15)
                                    ->displayFormat('d.m.Y H:i')
                                    ->minDate(fn ($context) => $context === 'create' ? now() : null)
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                        if ($state && $get('duration_minutes')) {
                                            $set('ends_at', Carbon::parse($state)->addMinutes($get('duration_minutes')));
                                        }
                                    }),

                                Forms\Components\DateTimePicker::make('ends_at')
                                    ->label('Ende')
                                    ->required()
                                    ->native(false)
                                    ->seconds(false)
                                    ->minutesStep(15)
                                    ->displayFormat('d.m.Y H:i')
                                    ->after('starts_at'),

                                Forms\Components\Hidden::make('duration_minutes')
                                    ->default(30),
                            ]),

                        Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('staff_id')
                                    ->label('Mitarbeiter')
                                    ->relationship('staff', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required(),

                                Forms\Components\Select::make('branch_id')
                                    ->label('Filiale')
                                    ->relationship('branch', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                            ]),

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
                            ->reactive(),

                        Forms\Components\RichEditor::make('notes')
                            ->label('Notizen')
                            ->toolbarButtons([
                                'bold',
                                'italic',
                                'bulletList',
                                'orderedList',
                            ])
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

                // Additional Information Section
                Section::make('Zusätzliche Informationen')
                    ->description('Erweiterte Einstellungen und Details')
                    ->icon('heroicon-o-cog')
                    ->schema([
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

                // Hidden technical fields (kept for data integrity but not shown)
                Forms\Components\Hidden::make('company_id')
                    ->default(fn () => auth()->user()->company_id ?? 1),
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
                            $record->update(['status' => 'cancelled']);
                            Notification::make()
                                ->title('Termin storniert')
                                ->warning()
                                ->send();
                        }),

                    Tables\Actions\Action::make('reschedule')
                        ->label('Verschieben')
                        ->icon('heroicon-m-calendar')
                        ->color('warning')
                        ->form([
                            Forms\Components\DateTimePicker::make('starts_at')
                                ->label('Neuer Starttermin')
                                ->required()
                                ->native(false)
                                ->seconds(false)
                                ->minutesStep(15)
                                ->minDate(now()),
                        ])
                        ->action(function ($record, array $data) {
                            $duration = Carbon::parse($record->starts_at)->diffInMinutes($record->ends_at);
                            $record->update([
                                'starts_at' => $data['starts_at'],
                                'ends_at' => Carbon::parse($data['starts_at'])->addMinutes($duration),
                            ]);
                            Notification::make()
                                ->title('Termin verschoben')
                                ->body("Neuer Termin: " . Carbon::parse($data['starts_at'])->format('d.m.Y H:i'))
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
                            $records->each->update(['status' => 'cancelled']);
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
                'service:id,name,price,duration',
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
}