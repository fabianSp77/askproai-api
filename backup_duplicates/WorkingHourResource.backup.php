<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WorkingHourResource\Pages;
use App\Models\WorkingHour;
use App\Models\Staff;
use App\Models\Branch;
use App\Models\Company;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Filament\Notifications\Notification;

class WorkingHourResource extends Resource
{
    protected static ?string $model = WorkingHour::class;
    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static ?string $navigationGroup = 'Stammdaten';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationLabel = 'Arbeitszeiten';
    protected static ?string $recordTitleAttribute = 'title';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('is_active', true)->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $count = static::getModel()::where('is_active', true)->count();
        return $count > 100 ? 'success' : ($count > 50 ? 'warning' : 'info');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Complete working hours management with 3 logical tabs
                Tabs::make('Working Hours Details')
                    ->tabs([
                        // Tab 1: Basic Schedule Information
                        Tabs\Tab::make('Arbeitszeit')
                            ->icon('heroicon-m-clock')
                            ->schema([
                                Section::make('Grundinformationen')
                                    ->schema([
                                        Grid::make(2)->schema([
                                            Forms\Components\TextInput::make('title')
                                                ->label('Titel')
                                                ->required()
                                                ->maxLength(255)
                                                ->placeholder('Standard Arbeitszeit'),

                                            Forms\Components\Select::make('type')
                                                ->label('Typ')
                                                ->options([
                                                    'regular' => '📅 Regulär',
                                                    'overtime' => '⏰ Überstunden',
                                                    'holiday' => '🎉 Feiertag',
                                                    'vacation' => '🏖️ Urlaub',
                                                    'sick' => '🤒 Krank',
                                                    'break' => '☕ Pause',
                                                ])
                                                ->default('regular')
                                                ->required()
                                                ->native(false),
                                        ]),

                                        Grid::make(3)->schema([
                                            Forms\Components\Select::make('staff_id')
                                                ->label('Mitarbeiter')
                                                ->relationship('staff', 'name')
                                                ->searchable()
                                                ->preload(),

                                            Forms\Components\Select::make('branch_id')
                                                ->label('Filiale')
                                                ->relationship('branch', 'name')
                                                ->searchable()
                                                ->preload(),

                                            Forms\Components\Select::make('company_id')
                                                ->label('Unternehmen')
                                                ->relationship('company', 'name')
                                                ->searchable()
                                                ->preload(),
                                        ]),

                                        Grid::make(2)->schema([
                                            Forms\Components\Toggle::make('is_active')
                                                ->label('Aktiv')
                                                ->default(true),

                                            Forms\Components\Toggle::make('is_recurring')
                                                ->label('Wiederkehrend')
                                                ->default(false)
                                                ->reactive(),
                                        ]),
                                    ]),
                            ]),

                        // Tab 2: Time Details & Schedule
                        Tabs\Tab::make('Zeiten')
                            ->icon('heroicon-m-calendar-days')
                            ->schema([
                                Section::make('Zeitplanung')
                                    ->schema([
                                        Grid::make(2)->schema([
                                            Forms\Components\DatePicker::make('date')
                                                ->label('Datum')
                                                ->required()
                                                ->displayFormat('d.m.Y')
                                                ->visible(fn (Get $get) => !$get('is_recurring')),

                                            Forms\Components\Select::make('day_of_week')
                                                ->label('Wochentag')
                                                ->options([
                                                    1 => 'Montag',
                                                    2 => 'Dienstag',
                                                    3 => 'Mittwoch',
                                                    4 => 'Donnerstag',
                                                    5 => 'Freitag',
                                                    6 => 'Samstag',
                                                    0 => 'Sonntag',
                                                ])
                                                ->visible(fn (Get $get) => $get('is_recurring'))
                                                ->native(false),
                                        ]),

                                        Grid::make(4)->schema([
                                            Forms\Components\TimePicker::make('start_time')
                                                ->label('Startzeit')
                                                ->required()
                                                ->seconds(false)
                                                ->default('09:00'),

                                            Forms\Components\TimePicker::make('end_time')
                                                ->label('Endzeit')
                                                ->required()
                                                ->seconds(false)
                                                ->default('17:00'),

                                            Forms\Components\TimePicker::make('break_start')
                                                ->label('Pause Start')
                                                ->seconds(false)
                                                ->default('12:00'),

                                            Forms\Components\TimePicker::make('break_end')
                                                ->label('Pause Ende')
                                                ->seconds(false)
                                                ->default('13:00'),
                                        ]),

                                        Grid::make(3)->schema([
                                            Forms\Components\TextInput::make('total_hours')
                                                ->label('Gesamtstunden')
                                                ->numeric()
                                                ->step(0.25)
                                                ->suffix('h')
                                                ->disabled()
                                                ->dehydrated(false),

                                            Forms\Components\TextInput::make('break_minutes')
                                                ->label('Pausenzeit')
                                                ->numeric()
                                                ->suffix('min')
                                                ->default(60),

                                            Forms\Components\TextInput::make('overtime_hours')
                                                ->label('Überstunden')
                                                ->numeric()
                                                ->step(0.25)
                                                ->suffix('h')
                                                ->default(0),
                                        ]),

                                        Forms\Components\Textarea::make('notes')
                                            ->label('Notizen')
                                            ->rows(3)
                                            ->placeholder('Besondere Hinweise zu diesem Arbeitszeitplan...')
                                            ->maxLength(500),
                                    ]),
                            ]),

                        // Tab 3: Recurring Pattern & Rules (for recurring schedules)
                        Tabs\Tab::make('Wiederholung')
                            ->icon('heroicon-m-arrow-path')
                            ->visible(fn (Get $get) => $get('is_recurring'))
                            ->schema([
                                Section::make('Wiederholungsregeln')
                                    ->schema([
                                        Grid::make(2)->schema([
                                            Forms\Components\DatePicker::make('valid_from')
                                                ->label('Gültig ab')
                                                ->default(now())
                                                ->displayFormat('d.m.Y'),

                                            Forms\Components\DatePicker::make('valid_until')
                                                ->label('Gültig bis')
                                                ->displayFormat('d.m.Y')
                                                ->after('valid_from'),
                                        ]),

                                        Grid::make(2)->schema([
                                            Forms\Components\Select::make('recurrence_pattern')
                                                ->label('Wiederholungsmuster')
                                                ->options([
                                                    'weekly' => '📅 Wöchentlich',
                                                    'biweekly' => '📅 Alle 2 Wochen',
                                                    'monthly' => '📅 Monatlich',
                                                    'custom' => '⚙️ Benutzerdefiniert',
                                                ])
                                                ->default('weekly')
                                                ->native(false),

                                            Forms\Components\TextInput::make('recurrence_interval')
                                                ->label('Wiederholungsintervall')
                                                ->numeric()
                                                ->default(1)
                                                ->helperText('z.B. 2 für alle 2 Wochen'),
                                        ]),

                                        Forms\Components\CheckboxList::make('exception_dates')
                                            ->label('Ausnahmetage')
                                            ->options([
                                                '2024-12-24' => '24.12.2024 (Heiligabend)',
                                                '2024-12-25' => '25.12.2024 (1. Weihnachtstag)',
                                                '2024-12-26' => '26.12.2024 (2. Weihnachtstag)',
                                                '2024-12-31' => '31.12.2024 (Silvester)',
                                                '2025-01-01' => '01.01.2025 (Neujahr)',
                                            ])
                                            ->helperText('Tage an denen diese Arbeitszeit NICHT gilt'),

                                        Forms\Components\Toggle::make('auto_generate_timesheet')
                                            ->label('Zeiterfassung automatisch generieren')
                                            ->default(false)
                                            ->helperText('Erstellt automatisch Zeiterfassungseinträge basierend auf diesem Plan'),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            // Performance: Eager load relationships
            ->modifyQueryUsing(fn (Builder $query) =>
                $query->with(['staff', 'branch', 'company'])
                    ->orderBy('date', 'desc')
                    ->orderBy('start_time', 'asc')
            )
            // Optimized to 9 essential columns for working hours management
            ->columns([
                // Schedule title with type indicator
                Tables\Columns\TextColumn::make('title')
                    ->label('Arbeitszeit')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn ($record) =>
                        ($record->staff?->name ?: 'Alle Mitarbeiter') .
                        ($record->branch ? ' • ' . $record->branch->name : '')
                    )
                    ->icon(fn ($record) => match($record->type) {
                        'regular' => 'heroicon-m-clock',
                        'overtime' => 'heroicon-m-forward',
                        'holiday' => 'heroicon-m-gift',
                        'vacation' => 'heroicon-m-sun',
                        'sick' => 'heroicon-m-heart',
                        'break' => 'heroicon-m-pause',
                        default => 'heroicon-m-clock',
                    }),

                // Type with visual coding
                Tables\Columns\BadgeColumn::make('type')
                    ->label('Typ')
                    ->colors([
                        'success' => 'regular',
                        'warning' => 'overtime',
                        'info' => 'holiday',
                        'purple' => 'vacation',
                        'danger' => 'sick',
                        'gray' => 'break',
                    ])
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'regular' => '📅 Regulär',
                        'overtime' => '⏰ Überstunden',
                        'holiday' => '🎉 Feiertag',
                        'vacation' => '🏖️ Urlaub',
                        'sick' => '🤒 Krank',
                        'break' => '☕ Pause',
                        default => $state,
                    }),

                // Date/Day information
                Tables\Columns\TextColumn::make('schedule_info')
                    ->label('Zeitpunkt')
                    ->getStateUsing(fn ($record) =>
                        $record->is_recurring ?
                        $this->getDayName($record->day_of_week) . ' (wiederkehrend)' :
                        Carbon::parse($record->date)->format('d.m.Y (l)')
                    )
                    ->sortable(['date', 'day_of_week'])
                    ->searchable(['date'])
                    ->badge()
                    ->color(fn ($record) => $record->is_recurring ? 'info' : 'success'),

                // Time range with visual formatting
                Tables\Columns\TextColumn::make('time_range')
                    ->label('Arbeitszeit')
                    ->getStateUsing(fn ($record) =>
                        Carbon::parse($record->start_time)->format('H:i') . ' - ' .
                        Carbon::parse($record->end_time)->format('H:i')
                    )
                    ->description(fn ($record) =>
                        $record->break_start && $record->break_end ?
                        'Pause: ' . Carbon::parse($record->break_start)->format('H:i') . '-' . Carbon::parse($record->break_end)->format('H:i') :
                        'Ohne Pause'
                    )
                    ->badge()
                    ->color('warning'),

                // Total working hours
                Tables\Columns\TextColumn::make('calculated_hours')
                    ->label('Stunden')
                    ->getStateUsing(function ($record) {
                        $start = Carbon::parse($record->start_time);
                        $end = Carbon::parse($record->end_time);
                        $breakMinutes = $record->break_minutes ?: 0;

                        $totalMinutes = $end->diffInMinutes($start) - $breakMinutes;
                        $hours = floor($totalMinutes / 60);
                        $minutes = $totalMinutes % 60;

                        return $hours . 'h ' . $minutes . 'min';
                    })
                    ->badge()
                    ->color('success')
                    ->sortable(),

                // Staff assignment
                Tables\Columns\TextColumn::make('staff.name')
                    ->label('Mitarbeiter')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Alle Mitarbeiter')
                    ->icon('heroicon-m-user')
                    ->toggleable(),

                // Overtime indicator
                Tables\Columns\TextColumn::make('overtime_hours')
                    ->label('Überstunden')
                    ->formatStateUsing(fn ($state) => $state > 0 ? '+' . $state . 'h' : '-')
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'gray')
                    ->visible(fn () => auth()->user()?->hasRole(['admin', 'manager']))
                    ->toggleable(isToggledHiddenByDefault: true),

                // Recurrence status
                Tables\Columns\IconColumn::make('is_recurring')
                    ->label('Wiederkehrend')
                    ->boolean()
                    ->trueIcon('heroicon-o-arrow-path')
                    ->falseIcon('heroicon-o-calendar-days')
                    ->trueColor('info')
                    ->falseColor('gray')
                    ->tooltip(fn ($record) =>
                        $record->is_recurring ?
                        'Wiederkehrend: ' . ucfirst($record->recurrence_pattern ?: 'wöchentlich') :
                        'Einmaliger Termin'
                    ),

                // Status
                Tables\Columns\BadgeColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueLabel('✅ Aktiv')
                    ->falseLabel('⏸️ Inaktiv')
                    ->trueColor('success')
                    ->falseColor('danger'),
            ])
            // Smart business filters for working hours management
            ->filters([
                SelectFilter::make('type')
                    ->label('Typ')
                    ->multiple()
                    ->options([
                        'regular' => '📅 Regulär',
                        'overtime' => '⏰ Überstunden',
                        'holiday' => '🎉 Feiertag',
                        'vacation' => '🏖️ Urlaub',
                        'sick' => '🤒 Krank',
                        'break' => '☕ Pause',
                    ]),

                SelectFilter::make('staff_id')
                    ->label('Mitarbeiter')
                    ->relationship('staff', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('branch_id')
                    ->label('Filiale')
                    ->relationship('branch', 'name')
                    ->searchable()
                    ->preload(),

                Filter::make('active_schedules')
                    ->label('Aktive Arbeitszeiten')
                    ->query(fn (Builder $query): Builder =>
                        $query->where('is_active', true)
                    )
                    ->default(),

                Filter::make('recurring_only')
                    ->label('Nur wiederkehrende')
                    ->query(fn (Builder $query): Builder =>
                        $query->where('is_recurring', true)
                    ),

                Filter::make('today')
                    ->label('Heute')
                    ->query(fn (Builder $query): Builder =>
                        $query->where(fn ($q) =>
                            $q->where('date', today())
                              ->orWhere(fn ($sq) =>
                                  $sq->where('is_recurring', true)
                                     ->where('day_of_week', today()->dayOfWeek)
                              )
                        )
                    ),

                Filter::make('this_week')
                    ->label('Diese Woche')
                    ->query(fn (Builder $query): Builder =>
                        $query->where(fn ($q) =>
                            $q->whereBetween('date', [now()->startOfWeek(), now()->endOfWeek()])
                              ->orWhere('is_recurring', true)
                        )
                    ),

                Filter::make('overtime')
                    ->label('Mit Überstunden')
                    ->query(fn (Builder $query): Builder =>
                        $query->where('overtime_hours', '>', 0)
                    ),
            ])
            ->filtersLayout(Tables\Enums\FiltersLayout\AboveContent)
            ->filtersFormColumns(3)
            // Quick actions for working hours management
            ->actions([
                Tables\Actions\ActionGroup::make([
                    // Copy schedule
                    Tables\Actions\Action::make('copySchedule')
                        ->label('Zeitplan kopieren')
                        ->icon('heroicon-m-document-duplicate')
                        ->color('info')
                        ->form([
                            Forms\Components\DatePicker::make('target_date')
                                ->label('Zieldatum')
                                ->required()
                                ->displayFormat('d.m.Y'),
                            Forms\Components\Select::make('target_staff_id')
                                ->label('Ziel-Mitarbeiter')
                                ->relationship('staff', 'name')
                                ->searchable(),
                        ])
                        ->action(function ($record, array $data) {
                            $copy = $record->replicate();
                            $copy->date = $data['target_date'];
                            $copy->staff_id = $data['target_staff_id'] ?? $record->staff_id;
                            $copy->is_recurring = false;
                            $copy->title = $record->title . ' (Kopie)';
                            $copy->save();

                            Notification::make()
                                ->title('Zeitplan kopiert')
                                ->body('Arbeitszeit wurde für ' . Carbon::parse($data['target_date'])->format('d.m.Y') . ' kopiert.')
                                ->success()
                                ->send();
                        }),

                    // Adjust times
                    Tables\Actions\Action::make('adjustTimes')
                        ->label('Zeiten anpassen')
                        ->icon('heroicon-m-clock')
                        ->color('warning')
                        ->form([
                            Forms\Components\TimePicker::make('start_time')
                                ->label('Startzeit')
                                ->default(fn ($record) => $record->start_time)
                                ->required(),
                            Forms\Components\TimePicker::make('end_time')
                                ->label('Endzeit')
                                ->default(fn ($record) => $record->end_time)
                                ->required(),
                            Forms\Components\TextInput::make('break_minutes')
                                ->label('Pausenzeit (min)')
                                ->numeric()
                                ->default(fn ($record) => $record->break_minutes),
                            Forms\Components\TextInput::make('overtime_hours')
                                ->label('Überstunden')
                                ->numeric()
                                ->step(0.25)
                                ->default(fn ($record) => $record->overtime_hours),
                        ])
                        ->action(function ($record, array $data) {
                            $record->update($data);

                            Notification::make()
                                ->title('Zeiten angepasst')
                                ->body('Arbeitszeiten wurden erfolgreich geändert.')
                                ->success()
                                ->send();
                        }),

                    // Generate timesheet
                    Tables\Actions\Action::make('generateTimesheet')
                        ->label('Zeiterfassung erstellen')
                        ->icon('heroicon-m-document-plus')
                        ->color('success')
                        ->action(function ($record) {
                            // TODO: Generate timesheet entry based on working hours

                            Notification::make()
                                ->title('Zeiterfassung erstellt')
                                ->body('Zeiterfassungseintrag wurde basierend auf dem Arbeitsplan erstellt.')
                                ->success()
                                ->send();
                        })
                        ->visible(fn ($record) => $record->type === 'regular'),

                    // Toggle status
                    Tables\Actions\Action::make('toggleStatus')
                        ->label('Status umschalten')
                        ->icon('heroicon-m-power')
                        ->color(fn ($record) => $record->is_active ? 'danger' : 'success')
                        ->action(function ($record) {
                            $newStatus = !$record->is_active;
                            $record->update(['is_active' => $newStatus]);

                            Notification::make()
                                ->title('Status geändert')
                                ->body('Arbeitszeit ist jetzt ' . ($newStatus ? 'aktiv' : 'inaktiv'))
                                ->success()
                                ->send();
                        }),

                    // Create recurring series
                    Tables\Actions\Action::make('createRecurringSeries')
                        ->label('Terminserie erstellen')
                        ->icon('heroicon-m-arrow-path')
                        ->color('purple')
                        ->form([
                            Forms\Components\DatePicker::make('start_date')
                                ->label('Startdatum')
                                ->required()
                                ->default(today()),
                            Forms\Components\DatePicker::make('end_date')
                                ->label('Enddatum')
                                ->required()
                                ->default(today()->addMonths(3)),
                            Forms\Components\Select::make('recurrence')
                                ->label('Wiederholung')
                                ->options([
                                    'daily' => 'Täglich',
                                    'weekly' => 'Wöchentlich',
                                    'biweekly' => 'Alle 2 Wochen',
                                    'monthly' => 'Monatlich',
                                ])
                                ->default('weekly')
                                ->required(),
                        ])
                        ->action(function ($record, array $data) {
                            // TODO: Generate recurring working hour entries

                            Notification::make()
                                ->title('Terminserie erstellt')
                                ->body('Wiederkehrende Arbeitszeiten wurden generiert.')
                                ->success()
                                ->send();
                        })
                        ->visible(fn ($record) => !$record->is_recurring),

                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                ]),
            ])
            // Bulk operations for working hours management
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('bulkTimeAdjustment')
                        ->label('Zeiten anpassen')
                        ->icon('heroicon-m-clock')
                        ->color('warning')
                        ->form([
                            Forms\Components\Select::make('adjustment_type')
                                ->label('Anpassungstyp')
                                ->options([
                                    'shift_start' => 'Startzeit verschieben',
                                    'shift_end' => 'Endzeit verschieben',
                                    'extend' => 'Arbeitszeit verlängern',
                                    'reduce' => 'Arbeitszeit verkürzen',
                                ])
                                ->required(),
                            Forms\Components\TextInput::make('adjustment_minutes')
                                ->label('Minuten (±)')
                                ->numeric()
                                ->required()
                                ->helperText('Positive Werte = später/länger, Negative Werte = früher/kürzer'),
                        ])
                        ->action(function ($records, array $data) {
                            foreach ($records as $record) {
                                $startTime = Carbon::parse($record->start_time);
                                $endTime = Carbon::parse($record->end_time);

                                match($data['adjustment_type']) {
                                    'shift_start' => $record->update(['start_time' => $startTime->addMinutes($data['adjustment_minutes'])]),
                                    'shift_end' => $record->update(['end_time' => $endTime->addMinutes($data['adjustment_minutes'])]),
                                    'extend' => $record->update(['end_time' => $endTime->addMinutes($data['adjustment_minutes'])]),
                                    'reduce' => $record->update(['end_time' => $endTime->subMinutes($data['adjustment_minutes'])]),
                                };
                            }

                            Notification::make()
                                ->title('Zeiten angepasst')
                                ->body(count($records) . ' Arbeitszeiten wurden aktualisiert.')
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation(),

                    Tables\Actions\BulkAction::make('bulkStatusUpdate')
                        ->label('Status setzen')
                        ->icon('heroicon-m-power')
                        ->color('info')
                        ->form([
                            Forms\Components\Toggle::make('is_active')
                                ->label('Arbeitszeiten aktivieren'),
                        ])
                        ->action(function ($records, array $data) {
                            $records->each->update($data);

                            Notification::make()
                                ->title('Status aktualisiert')
                                ->body(count($records) . ' Arbeitszeiten wurden ' . ($data['is_active'] ? 'aktiviert' : 'deaktiviert') . '.')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\BulkAction::make('bulkTypeChange')
                        ->label('Typ ändern')
                        ->icon('heroicon-m-tag')
                        ->color('purple')
                        ->form([
                            Forms\Components\Select::make('type')
                                ->label('Neuer Typ')
                                ->options([
                                    'regular' => '📅 Regulär',
                                    'overtime' => '⏰ Überstunden',
                                    'holiday' => '🎉 Feiertag',
                                    'vacation' => '🏖️ Urlaub',
                                    'sick' => '🤒 Krank',
                                    'break' => '☕ Pause',
                                ])
                                ->required(),
                        ])
                        ->action(function ($records, array $data) {
                            $records->each->update($data);

                            Notification::make()
                                ->title('Typ geändert')
                                ->body(count($records) . ' Arbeitszeiten wurden umkategorisiert.')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\ExportBulkAction::make()
                        ->label('Exportieren'),

                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            // Performance optimizations
            ->defaultPaginationPageOption(25)
            ->poll('60s')
            ->deferLoading()
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->striped();
    }

    protected function getDayName($dayOfWeek): string
    {
        return match($dayOfWeek) {
            0 => 'Sonntag',
            1 => 'Montag',
            2 => 'Dienstag',
            3 => 'Mittwoch',
            4 => 'Donnerstag',
            5 => 'Freitag',
            6 => 'Samstag',
            default => 'Unbekannt',
        };
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWorkingHours::route('/'),
            'create' => Pages\CreateWorkingHour::route('/create'),
            'edit' => Pages\EditWorkingHour::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['staff', 'branch', 'company']);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['title', 'type', 'notes'];
    }
}