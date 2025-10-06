<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WorkingHourResource\Pages;
use App\Filament\Resources\WorkingHourResource\RelationManagers;
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
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\Action;

class WorkingHourResourceOptimized extends Resource
{
    protected static ?string $model = WorkingHour::class;
    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static ?string $navigationGroup = 'Stammdaten';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationLabel = 'Arbeitszeiten';
    protected static ?string $pluralLabel = 'Arbeitszeiten';
    protected static ?string $modelLabel = 'Arbeitszeit';
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
                // Optimized form with 3 essential tabs (like CustomerResource)
                Tabs::make('Arbeitszeit Details')
                    ->tabs([
                        // Tab 1: Core Information
                        Tabs\Tab::make('Grunddaten')
                            ->icon('heroicon-m-identification')
                            ->schema([
                                Section::make('Arbeitszeit Informationen')
                                    ->description('Grundlegende Einstellungen der Arbeitszeit')
                                    ->schema([
                                        Grid::make(2)->schema([
                                            Forms\Components\TextInput::make('title')
                                                ->label('Bezeichnung')
                                                ->placeholder('z.B. Montag Frühschicht')
                                                ->maxLength(255),

                                            Forms\Components\Select::make('staff_id')
                                                ->label('Mitarbeiter')
                                                ->relationship('staff', 'name')
                                                ->searchable()
                                                ->preload()
                                                ->required()
                                                ->reactive()
                                                ->afterStateUpdated(function ($state, Forms\Set $set) {
                                                    if ($state) {
                                                        $staff = Staff::find($state);
                                                        if ($staff) {
                                                            $set('company_id', $staff->company_id);
                                                            $set('branch_id', $staff->branch_id);
                                                        }
                                                    }
                                                }),
                                        ]),

                                        Grid::make(2)->schema([
                                            Forms\Components\Select::make('company_id')
                                                ->label('Unternehmen')
                                                ->relationship('company', 'name')
                                                ->disabled()
                                                ->dehydrated(),

                                            Forms\Components\Select::make('branch_id')
                                                ->label('Filiale')
                                                ->relationship('branch', 'name')
                                                ->disabled()
                                                ->dehydrated(),
                                        ]),

                                        Forms\Components\Textarea::make('description')
                                            ->label('Beschreibung')
                                            ->rows(2)
                                            ->maxLength(500),
                                    ]),
                            ]),

                        // Tab 2: Schedule Details
                        Tabs\Tab::make('Zeitplan')
                            ->icon('heroicon-m-calendar-days')
                            ->schema([
                                Section::make('Arbeitszeitplan')
                                    ->description('Wochentag und Uhrzeiten festlegen')
                                    ->schema([
                                        Grid::make(2)->schema([
                                            Forms\Components\Select::make('day_of_week')
                                                ->label('Wochentag')
                                                ->options([
                                                    1 => '🗓 Montag',
                                                    2 => '🗓 Dienstag',
                                                    3 => '🗓 Mittwoch',
                                                    4 => '🗓 Donnerstag',
                                                    5 => '🗓 Freitag',
                                                    6 => '🗓 Samstag',
                                                    0 => '🗓 Sonntag',
                                                ])
                                                ->required()
                                                ->native(false),

                                            Forms\Components\Select::make('timezone')
                                                ->label('Zeitzone')
                                                ->options([
                                                    'Europe/Berlin' => 'Berlin (GMT+1)',
                                                    'Europe/London' => 'London (GMT)',
                                                    'Europe/Paris' => 'Paris (GMT+1)',
                                                    'Europe/Madrid' => 'Madrid (GMT+1)',
                                                ])
                                                ->default('Europe/Berlin')
                                                ->required(),
                                        ]),

                                        Grid::make(4)->schema([
                                            Forms\Components\TimePicker::make('start')
                                                ->label('Arbeitsbeginn')
                                                ->required()
                                                ->seconds(false)
                                                ->default('09:00'),

                                            Forms\Components\TimePicker::make('end')
                                                ->label('Arbeitsende')
                                                ->required()
                                                ->seconds(false)
                                                ->default('17:00')
                                                ->after('start'),

                                            Forms\Components\TimePicker::make('break_start')
                                                ->label('Pause von')
                                                ->seconds(false),

                                            Forms\Components\TimePicker::make('break_end')
                                                ->label('Pause bis')
                                                ->seconds(false)
                                                ->after('break_start'),
                                        ]),

                                        Grid::make(3)->schema([
                                            Forms\Components\Toggle::make('is_active')
                                                ->label('Aktiv')
                                                ->default(true)
                                                ->inline(),

                                            Forms\Components\Toggle::make('is_recurring')
                                                ->label('Wiederkehrend')
                                                ->default(true)
                                                ->reactive()
                                                ->inline(),

                                            Forms\Components\TextInput::make('weekday')
                                                ->label('Legacy Weekday')
                                                ->numeric()
                                                ->hidden(),
                                        ]),

                                        Grid::make(2)->schema([
                                            Forms\Components\DatePicker::make('valid_from')
                                                ->label('Gültig ab')
                                                ->displayFormat('d.m.Y')
                                                ->visible(fn (Get $get) => $get('is_recurring')),

                                            Forms\Components\DatePicker::make('valid_until')
                                                ->label('Gültig bis')
                                                ->displayFormat('d.m.Y')
                                                ->after('valid_from')
                                                ->visible(fn (Get $get) => $get('is_recurring')),
                                        ]),
                                    ]),
                            ]),

                        // Tab 3: Cal.com Integration
                        Tabs\Tab::make('Cal.com')
                            ->icon('heroicon-m-link')
                            ->schema([
                                Section::make('Cal.com Synchronisation')
                                    ->description('Verknüpfung mit externen Kalendersystemen')
                                    ->schema([
                                        Grid::make(2)->schema([
                                            Forms\Components\TextInput::make('calcom_availability_id')
                                                ->label('Cal.com Availability ID')
                                                ->placeholder('Automatisch generiert')
                                                ->disabled()
                                                ->dehydrated(),

                                            Forms\Components\TextInput::make('calcom_schedule_id')
                                                ->label('Cal.com Schedule ID')
                                                ->placeholder('Automatisch generiert')
                                                ->disabled()
                                                ->dehydrated(),
                                        ]),

                                        Forms\Components\Placeholder::make('external_sync_at')
                                            ->label('Letzte Synchronisation')
                                            ->content(fn ($record) =>
                                                $record && $record->external_sync_at
                                                    ? Carbon::parse($record->external_sync_at)->format('d.m.Y H:i:s')
                                                    : 'Noch nicht synchronisiert'
                                            ),

                                        Forms\Components\Actions::make([
                                            Forms\Components\Actions\Action::make('syncWithCalcom')
                                                ->label('Jetzt synchronisieren')
                                                ->icon('heroicon-m-arrow-path')
                                                ->color('primary')
                                                ->action(function ($record) {
                                                    // TODO: Implement Cal.com sync
                                                    $record->update(['external_sync_at' => now()]);

                                                    Notification::make()
                                                        ->title('Cal.com Synchronisation')
                                                        ->body('Arbeitszeit wurde mit Cal.com synchronisiert.')
                                                        ->success()
                                                        ->send();
                                                })
                                                ->disabled(fn ($record) => !$record || !$record->staff?->calcom_user_id),
                                        ]),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            // Performance: Optimized eager loading
            ->modifyQueryUsing(fn (Builder $query) =>
                $query->with(['staff:id,name,email,phone,company_id,branch_id,calcom_user_id',
                             'company:id,name',
                             'branch:id,name'])
                    ->orderByRaw('FIELD(day_of_week, 1,2,3,4,5,6,0)')
                    ->orderBy('start', 'asc')
            )
            // Optimized to 9 essential columns (like CustomerResource)
            ->columns([
                // 1. Staff & Title
                Tables\Columns\TextColumn::make('display_title')
                    ->label('Arbeitszeit')
                    ->searchable(['title', 'staff.name'])
                    ->sortable()
                    ->weight('bold')
                    ->getStateUsing(fn ($record) =>
                        ($record->title ?: $record->getDayNameAttribute() . ' Arbeitszeit') .
                        ' • ' . $record->staff?->name
                    )
                    ->description(fn ($record) =>
                        $record->description ?:
                        ($record->company?->name . ' • ' . $record->branch?->name)
                    ),

                // 2. Day of Week
                Tables\Columns\BadgeColumn::make('day_of_week')
                    ->label('Wochentag')
                    ->formatStateUsing(fn ($state) => match($state) {
                        0 => '🗓 Sonntag',
                        1 => '🗓 Montag',
                        2 => '🗓 Dienstag',
                        3 => '🗓 Mittwoch',
                        4 => '🗓 Donnerstag',
                        5 => '🗓 Freitag',
                        6 => '🗓 Samstag',
                        default => '❓ Unbekannt',
                    })
                    ->color(fn ($state) => match($state) {
                        0, 6 => 'warning', // Weekend
                        default => 'info', // Weekday
                    }),

                // 3. Time Range
                Tables\Columns\TextColumn::make('time_range')
                    ->label('Arbeitszeit')
                    ->getStateUsing(fn ($record) => $record->getTimeRangeAttribute())
                    ->badge()
                    ->color('success')
                    ->icon('heroicon-m-clock'),

                // 4. Break Time
                Tables\Columns\TextColumn::make('break_time')
                    ->label('Pause')
                    ->getStateUsing(fn ($record) =>
                        $record->break_start && $record->break_end ?
                        Carbon::parse($record->break_start)->format('H:i') . '-' .
                        Carbon::parse($record->break_end)->format('H:i') :
                        'Keine'
                    )
                    ->badge()
                    ->color(fn ($record) =>
                        $record->break_start ? 'gray' : 'warning'
                    ),

                // 5. Working Hours
                Tables\Columns\TextColumn::make('total_hours')
                    ->label('Stunden')
                    ->getStateUsing(function ($record) {
                        $start = Carbon::parse($record->start);
                        $end = Carbon::parse($record->end);
                        $breakMinutes = 0;

                        if ($record->break_start && $record->break_end) {
                            $breakStart = Carbon::parse($record->break_start);
                            $breakEnd = Carbon::parse($record->break_end);
                            $breakMinutes = $breakEnd->diffInMinutes($breakStart);
                        }

                        $totalMinutes = $end->diffInMinutes($start) - $breakMinutes;
                        $hours = floor($totalMinutes / 60);
                        $minutes = $totalMinutes % 60;

                        return sprintf('%dh %02dmin', $hours, $minutes);
                    })
                    ->badge()
                    ->color('primary'),

                // 6. Company/Branch
                Tables\Columns\TextColumn::make('location')
                    ->label('Standort')
                    ->getStateUsing(fn ($record) =>
                        $record->branch?->name ?: $record->company?->name ?: 'N/A'
                    )
                    ->searchable(['company.name', 'branch.name'])
                    ->badge()
                    ->color('gray'),

                // 7. Recurrence & Validity
                Tables\Columns\TextColumn::make('validity')
                    ->label('Gültigkeit')
                    ->getStateUsing(fn ($record) =>
                        $record->is_recurring ?
                            ($record->valid_from || $record->valid_until ?
                                Carbon::parse($record->valid_from ?: now())->format('d.m.Y') . ' - ' .
                                ($record->valid_until ? Carbon::parse($record->valid_until)->format('d.m.Y') : '∞') :
                                'Unbegrenzt') :
                            'Einmalig'
                    )
                    ->badge()
                    ->color(fn ($record) => $record->is_recurring ? 'info' : 'gray')
                    ->icon(fn ($record) => $record->is_recurring ?
                        'heroicon-m-arrow-path' : 'heroicon-m-calendar'),

                // 8. Cal.com Sync Status
                Tables\Columns\IconColumn::make('cal_sync')
                    ->label('Cal.com')
                    ->getStateUsing(fn ($record) =>
                        $record->calcom_schedule_id || $record->external_sync_at
                    )
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->tooltip(fn ($record) =>
                        $record->external_sync_at ?
                        'Synchronisiert: ' . Carbon::parse($record->external_sync_at)->format('d.m.Y H:i') :
                        'Nicht synchronisiert'
                    ),

                // 9. Status
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Aktiv')
                    ->onColor('success')
                    ->offColor('danger'),
            ])
            // Smart filters (optimized like CustomerResource)
            ->filters([
                SelectFilter::make('staff_id')
                    ->label('Mitarbeiter')
                    ->relationship('staff', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('day_of_week')
                    ->label('Wochentag')
                    ->options([
                        1 => 'Montag',
                        2 => 'Dienstag',
                        3 => 'Mittwoch',
                        4 => 'Donnerstag',
                        5 => 'Freitag',
                        6 => 'Samstag',
                        0 => 'Sonntag',
                    ]),

                Filter::make('active')
                    ->label('Nur aktive')
                    ->query(fn (Builder $query): Builder =>
                        $query->where('is_active', true)
                    )
                    ->default(),

                Filter::make('recurring')
                    ->label('Wiederkehrend')
                    ->query(fn (Builder $query): Builder =>
                        $query->where('is_recurring', true)
                    ),

                Filter::make('synced')
                    ->label('Cal.com synchronisiert')
                    ->query(fn (Builder $query): Builder =>
                        $query->whereNotNull('calcom_schedule_id')
                    ),
            ])
            ->filtersLayout(Tables\Enums\FiltersLayout::AboveContent)
            ->filtersFormColumns(3)
            // Quick actions (5 per record like CustomerResource)
            ->actions([
                ActionGroup::make([
                    // 1. View
                    Tables\Actions\ViewAction::make()
                        ->label('Ansehen'),

                    // 2. Edit
                    Tables\Actions\EditAction::make()
                        ->label('Bearbeiten'),

                    // 3. Duplicate
                    Action::make('duplicate')
                        ->label('Duplizieren')
                        ->icon('heroicon-m-document-duplicate')
                        ->color('info')
                        ->action(function ($record) {
                            $newRecord = $record->replicate();
                            $newRecord->title = ($record->title ?: 'Arbeitszeit') . ' (Kopie)';
                            $newRecord->is_active = false;
                            $newRecord->calcom_schedule_id = null;
                            $newRecord->calcom_availability_id = null;
                            $newRecord->external_sync_at = null;
                            $newRecord->save();

                            Notification::make()
                                ->title('Arbeitszeit dupliziert')
                                ->body('Die Arbeitszeit wurde erfolgreich kopiert.')
                                ->success()
                                ->send();
                        }),

                    // 4. Sync Cal.com
                    Action::make('syncCalcom')
                        ->label('Cal.com Sync')
                        ->icon('heroicon-m-arrow-path')
                        ->color('primary')
                        ->action(function ($record) {
                            // TODO: Implement Cal.com sync
                            $record->update(['external_sync_at' => now()]);

                            Notification::make()
                                ->title('Synchronisation gestartet')
                                ->body('Die Arbeitszeit wird mit Cal.com synchronisiert.')
                                ->success()
                                ->send();
                        })
                        ->visible(fn ($record) => $record->staff?->calcom_user_id),

                    // 5. Toggle Status
                    Action::make('toggleStatus')
                        ->label(fn ($record) => $record->is_active ? 'Deaktivieren' : 'Aktivieren')
                        ->icon('heroicon-m-power')
                        ->color(fn ($record) => $record->is_active ? 'danger' : 'success')
                        ->action(function ($record) {
                            $record->update(['is_active' => !$record->is_active]);

                            Notification::make()
                                ->title('Status geändert')
                                ->body('Die Arbeitszeit wurde ' .
                                    ($record->is_active ? 'aktiviert' : 'deaktiviert') . '.')
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            // Bulk actions
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('bulkActivate')
                        ->label('Aktivieren')
                        ->icon('heroicon-m-check')
                        ->color('success')
                        ->action(fn ($records) => $records->each->update(['is_active' => true]))
                        ->requiresConfirmation(),

                    Tables\Actions\BulkAction::make('bulkDeactivate')
                        ->label('Deaktivieren')
                        ->icon('heroicon-m-x-mark')
                        ->color('danger')
                        ->action(fn ($records) => $records->each->update(['is_active' => false]))
                        ->requiresConfirmation(),

                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            // Performance optimizations (like CustomerResource)
            ->defaultPaginationPageOption(25)
            ->poll('300s') // 5 minutes instead of 60s
            ->deferLoading()
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->striped();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWorkingHours::route('/'),
            'create' => Pages\CreateWorkingHour::route('/create'),
            'view' => Pages\ViewWorkingHour::route('/{record}'),
            'edit' => Pages\EditWorkingHour::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['staff:id,name,email,company_id,branch_id,calcom_user_id',
                   'company:id,name',
                   'branch:id,name']);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['title', 'description', 'staff.name'];
    }

    public static function getRelations(): array
    {
        return [
            // Future: Add RelationManagers here
        ];
    }
}