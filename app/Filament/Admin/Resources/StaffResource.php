<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\StaffResource\Pages;
use App\Filament\Admin\Resources\StaffResource\RelationManagers;
use App\Filament\Admin\Traits\HasConsistentNavigation;
use App\Models\Staff;
use App\Models\Service;
use App\Models\Branch;
use App\Filament\Components\StatusBadge;
use App\Filament\Components\ActionButton;
use App\Filament\Components\DateRangePicker;
use App\Filament\Components\SearchableSelect;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Support\Enums\IconPosition;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Support\HtmlString;

class StaffResource extends EnhancedResourceSimple
{

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        
        // Super admin can view all
        if ($user->hasRole('super_admin')) {
            return true;
        }
        
        // Check specific permission or if user belongs to a company
        return $user->can('view_any_staff') || $user->company_id !== null;
    }
    
    public static function canView($record): bool
    {
        $user = auth()->user();
        
        // Super admin can view all
        if ($user->hasRole('super_admin')) {
            return true;
        }
        
        // Check specific permission
        if ($user->can('view_staff')) {
            return true;
        }
        
        // Users can view staff from their own company
        return $user->company_id === $record->company_id;
    }
    
    public static function canEdit($record): bool
    {
        $user = auth()->user();
        
        // Super admin can edit all
        if ($user->hasRole('super_admin')) {
            return true;
        }
        
        // Check specific permission
        if ($user->can('update_staff')) {
            return true;
        }
        
        // Company admins can edit staff from their own company
        return $user->company_id === $record->company_id && 
               ($user->hasRole('company_admin') || $user->hasRole('branch_manager'));
    }
    
    public static function canCreate(): bool
    {
        $user = auth()->user();
        
        // Super admin can create
        if ($user->hasRole('super_admin')) {
            return true;
        }
        
        // Check specific permission
        if ($user->can('create_staff')) {
            return true;
        }
        
        // Company admins and branch managers can create staff
        return $user->company_id !== null && 
               ($user->hasRole('company_admin') || $user->hasRole('branch_manager'));
    }

    use HasConsistentNavigation;
    protected static ?string $model = Staff::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'Mitarbeiter';
    protected static ?string $navigationGroup = 'Täglicher Betrieb';
    protected static ?int $navigationSort = 25;
    protected static ?string $modelLabel = 'Mitarbeiter';
    protected static ?string $pluralModelLabel = 'Mitarbeiter';
    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Mitarbeiter Details')
                    ->tabs([
                        Tabs\Tab::make('Stammdaten')
                            ->icon('heroicon-o-user')
                            ->schema([
                                Section::make('Grundinformationen')
                                    ->description('Basisdaten des Mitarbeiters')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                SearchableSelect::company('company_id')
                                                    ->required()
                                                    ->reactive()
                                                    ->helperText('Wählen Sie das Unternehmen aus, zu dem dieser Mitarbeiter gehört.')
                                                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                                                        $set('home_branch_id', null);
                                                        $set('branches', []);
                                                        $set('services', []);
                                                    }),

                                                Forms\Components\Select::make('home_branch_id')
                                                    ->relationship(
                                                        name: 'homeBranch',
                                                        titleAttribute: 'name',
                                                        modifyQueryUsing: fn (Builder $query, Forms\Get $get) => 
                                                            $get('company_id') ? $query->where('company_id', $get('company_id')) : $query->where(DB::raw('1'), '=', DB::raw('0'))
                                                    )
                                                    ->label('Stammfiliale')
                                                    ->required()
                                                    ->searchable()
                                                    ->preload()
                                                    ->reactive()
                                                    ->disabled(fn (Forms\Get $get) => !$get('company_id'))
                                                    ->helperText('Die Hauptfiliale, in der der Mitarbeiter primär arbeitet. Diese Filiale wird für die Standard-Terminbuchungen verwendet.'),
                                            ]),

                                        Grid::make(3)
                                            ->schema([
                                                Forms\Components\TextInput::make('name')
                                                    ->required()
                                                    ->maxLength(255)
                                                    ->label('Name')
                                                    ->prefixIcon('heroicon-o-user')
                                                    ->helperText('Vollständiger Name des Mitarbeiters (Vor- und Nachname)'),

                                                Forms\Components\TextInput::make('email')
                                                    ->email()
                                                    ->maxLength(255)
                                                    ->label('E-Mail')
                                                    ->prefixIcon('heroicon-o-envelope')
                                                    ->helperText('E-Mail-Adresse für Terminbestätigungen und Benachrichtigungen'),

                                                Forms\Components\TextInput::make('phone')
                                                    ->tel()
                                                    ->maxLength(255)
                                                    ->label('Telefon')
                                                    ->prefixIcon('heroicon-o-phone')
                                                    ->helperText('Telefonnummer für direkte Kundenkontakte (Format: +49123456789)'),
                                            ]),

                                        Forms\Components\Textarea::make('notes')
                                            ->label('Notizen')
                                            ->rows(3)
                                            ->columnSpanFull()
                                            ->helperText('Interne Notizen zum Mitarbeiter (z.B. Spezialisierungen, Arbeitszeiten, besondere Fähigkeiten)'),

                                        Forms\Components\Toggle::make('active')
                                            ->label('Aktiv')
                                            ->default(true)
                                            ->inline(false)
                                            ->helperText('Deaktivierte Mitarbeiter können keine neuen Termine annehmen'),
                                    ])
                                    ->columns(1),
                            ]),

                        Tabs\Tab::make('Filialen & Services')
                            ->icon('heroicon-o-building-office')
                            ->schema([
                                Section::make('Filialen')
                                    ->description('Wählen Sie die Filialen, in denen der Mitarbeiter arbeitet')
                                    ->schema([
                                        Forms\Components\CheckboxList::make('branches')
                                            ->relationship(
                                                name: 'branches',
                                                titleAttribute: 'name',
                                                modifyQueryUsing: fn (Builder $query, Forms\Get $get) =>
                                                    $get('company_id') ? $query->where('company_id', $get('company_id')) : $query->where(DB::raw('1'), '=', DB::raw('0'))
                                            )
                                            ->label('Arbeitet in Filialen')
                                            ->columns(2)
                                            ->searchable()
                                            ->bulkToggleable()
                                            ->disabled(fn (Forms\Get $get) => !$get('company_id'))
                                            ->helperText('Der Mitarbeiter kann in mehreren Filialen arbeiten. Die Stammfiliale ist automatisch ausgewählt.')
                                            ->hint('Info')
                                            ->hintIcon('heroicon-o-information-circle')
                                            ->hintIconTooltip('Wählen Sie alle Filialen aus, in denen dieser Mitarbeiter Termine wahrnehmen kann.'),
                                    ]),

                                Section::make('Dienstleistungen')
                                    ->description('Wählen Sie die Dienstleistungen, die dieser Mitarbeiter anbietet')
                                    ->schema([
                                        Forms\Components\CheckboxList::make('services')
                                            ->relationship(
                                                name: 'services',
                                                titleAttribute: 'name',
                                                modifyQueryUsing: fn (Builder $query, Forms\Get $get) => 
                                                    $get('company_id') ? $query->where('company_id', $get('company_id')) : $query->where(DB::raw('1'), '=', DB::raw('0'))
                                            )
                                            ->label('Bietet Services an')
                                            ->columns(2)
                                            ->searchable()
                                            ->bulkToggleable()
                                            ->disabled(fn (Forms\Get $get) => !$get('company_id'))
->getOptionLabelFromRecordUsing(fn (Service $record) => 
    "{$record->name}" . 
    ($record->duration ? " ({$record->duration} Min.)" : "") . 
    ($record->price ? " - {$record->price}€" : "")
)

                                            ->helperText('Wählen Sie alle Dienstleistungen aus, die dieser Mitarbeiter durchführen kann.')
                                            ->hint('Wichtig')
                                            ->hintIcon('heroicon-o-exclamation-triangle')
                                            ->hintIconTooltip('Der Mitarbeiter kann nur Termine für die hier ausgewählten Services annehmen.'),
                                    ]),
                            ]),

                        Tabs\Tab::make('Arbeitszeiten')
                            ->icon('heroicon-o-clock')
                            ->schema([
                                Section::make('Arbeitszeiten')
                                    ->description('Definieren Sie die regulären Arbeitszeiten des Mitarbeiters')
                                    ->schema([
                                        Forms\Components\Placeholder::make('working_hours_info')
                                            ->content('Die Arbeitszeiten können nach dem Erstellen des Mitarbeiters im Detail konfiguriert werden.')
                                            ->helperText('Arbeitszeiten definieren, wann der Mitarbeiter für Termine verfügbar ist. Sie können für jeden Wochentag individuelle Zeiten festlegen.'),
                                    ]),
                            ]),

                        Tabs\Tab::make('Kalender')
                            ->icon('heroicon-o-calendar')
                            ->schema([
                                Section::make('Kalender-Integration')
                                    ->description('Verbinden Sie externe Kalendersysteme')
                                    ->schema([
                                        Forms\Components\TextInput::make('external_calendar_id')
                                            ->label('Externe Kalender-ID')
                                            ->maxLength(255)
                                            ->helperText('Die ID des Mitarbeiters im externen Kalendersystem')
                                            ->hint('Wo finde ich die ID?')
                                            ->hintIcon('heroicon-o-question-mark-circle')
                                            ->hintIconTooltip('Cal.com: Einstellungen > Entwickler > Webhook-Endpunkt. Google Calendar: Kalender-Einstellungen > Kalender-ID. Die ID sieht meist so aus: user_123456 oder email@domain.com'),

                                        Forms\Components\Select::make('calendar_provider')
                                            ->label('Kalender-Anbieter')
                                            ->options([
                                                'calcom' => 'Cal.com',
                                                'google' => 'Google Calendar',
                                                'outlook' => 'Microsoft Outlook',
                                                'other' => 'Andere',
                                            ])
                                            ->helperText('Wählen Sie den Kalender-Anbieter für die Integration')
                                            ->hint('Dokumentation')
                                            ->hintIcon('heroicon-o-book-open')
                                            ->hintIconTooltip('Cal.com: https://cal.com/docs/api-reference | Google: https://developers.google.com/calendar/api'),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        $table = parent::enhanceTable($table);
        
        return $table
            ->modifyQueryUsing(fn ($query) => $query
                ->with(['company', 'homeBranch'])
                ->withCount(['appointments', 'appointments as upcoming_appointments_count' => function ($query) {
                    $query->where('starts_at', '>=', now())->whereIn('status', ['confirmed', 'pending']);
                }]))
            ->columns([
                Split::make([
                    Tables\Columns\ImageColumn::make('avatar')
                        ->defaultImageUrl(url('/images/default-avatar.png'))
                        ->circular()
                        ->grow(false),
                    
                    Stack::make([
                        Tables\Columns\TextColumn::make('name')
                            ->searchable()
                            ->sortable()
                            ->weight('bold')
                            ->icon('heroicon-o-user'),
Tables\Columns\TextColumn::make('email')
                            ->searchable()
                            ->icon('heroicon-o-envelope')
                            ->iconPosition(IconPosition::Before)
                            ->color('gray'),
                    ]),
                ]),

                Tables\Columns\TextColumn::make('company.name')
                    ->label('Unternehmen')
                    ->badge()
                    ->searchable()
                    ->sortable()
                    ->getStateUsing(fn ($record) => $record?->company?->name ?? '-'),

                Tables\Columns\TextColumn::make('homeBranch.name')
                    ->label('Stammfiliale')
                    ->badge()
                    ->color('success')
                    ->searchable()
                    ->sortable()
                    ->getStateUsing(fn ($record) => $record?->homeBranch?->name ?? '-'),

                Tables\Columns\TextColumn::make('branches_count')
                    ->label('Filialen')
                    ->counts('branches')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('services_count')
                    ->label('Services')
                    ->counts('services')
                    ->badge()
                    ->color('warning'),

                StatusBadge::activityStatus('active'),
                
                Tables\Columns\TextColumn::make('appointments_count')
                    ->label('Termine gesamt')
                    ->badge()
                    ->color('success')
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('upcoming_appointments_count')
                    ->label('Anstehend')
                    ->badge()
                    ->color('warning')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('external_calendar_id')
                    ->label('Kalender-ID')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->copyable()
                    ->copyMessage('Kalender-ID kopiert')
                    ->copyMessageDuration(1500),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('company_id')
                    ->relationship('company', 'name')
                    ->label('Unternehmen')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('home_branch_id')
                    ->relationship('homeBranch', 'name')
                    ->label('Stammfiliale')
                    ->searchable()
                    ->multiple(),

                Tables\Filters\TernaryFilter::make('active')
                    ->label('Status')
                    ->placeholder('Alle Mitarbeiter')
                    ->trueLabel('Nur aktive')
                    ->falseLabel('Nur inaktive'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('availability')
                        ->label('Verfügbarkeit')
                        ->icon('heroicon-o-calendar')
                        ->color('info')
                        ->url(fn ($record) => static::getUrl('availability', ['record' => $record])),
                        
                    Tables\Actions\Action::make('performance')
                        ->label('Leistung')
                        ->icon('heroicon-o-chart-bar')
                        ->color('success')
                        ->modalHeading(fn ($record) => 'Leistungsübersicht: ' . $record->name)
                        ->modalContent(fn ($record) => view('filament.staff.performance-modal', ['staff' => $record]))
                        ->modalWidth('5xl')
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Schließen'),
                        
                    Tables\Actions\Action::make('schedule')
                        ->label('Zeitplan')
                        ->icon('heroicon-o-clock')
                        ->color('warning')
                        ->url(fn ($record) => "/admin/working-hours?tableFilters[staff][value]={$record->id}"),
                        
                    ActionButton::sendEmail(),
                    ActionButton::call(),
                    
                    Tables\Actions\DeleteAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Mitarbeiter löschen')
                        ->modalDescription('Sind Sie sicher, dass Sie diesen Mitarbeiter löschen möchten? Diese Aktion kann nicht rückgängig gemacht werden.')
                        ->modalSubmitActionLabel('Ja, löschen'),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Mitarbeiter löschen')
                        ->modalDescription('Sind Sie sicher, dass Sie die ausgewählten Mitarbeiter löschen möchten?')
                        ->modalSubmitActionLabel('Ja, alle löschen'),
                ]),
            ])
            ->defaultSort('name', 'asc')
            ->poll('60s')
            ->recordUrl(
                fn ($record): string => static::getUrl('view', ['record' => $record])
            )
            ->headerActions([
                Tables\Actions\Action::make('workload_overview')
                    ->label('Auslastungsübersicht')
                    ->icon('heroicon-o-presentation-chart-bar')
                    ->modalHeading('Team-Auslastung')
                    ->modalContent(view('filament.staff.workload-overview'))
                    ->modalWidth('7xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Schließen'),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\WorkingHoursRelationManager::class,
            RelationManagers\AppointmentsRelationManager::class,
            RelationManagers\EventTypesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStaff::route('/'),
            'create' => Pages\CreateStaff::route('/create'),
            'view' => Pages\ViewStaff::route('/{record}'),
            'edit' => Pages\EditStaff::route('/{record}/edit'),
            'availability' => Pages\StaffAvailability::route('/{record}/availability'),
        ];
    }
    
    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Mitarbeiterdaten')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('name')
                                    ->label('Name')
                                    ->icon('heroicon-m-user'),
                                    
                                Infolists\Components\TextEntry::make('email')
                                    ->label('E-Mail')
                                    ->icon('heroicon-m-envelope')
                                    ->copyable(),
                                    
                                Infolists\Components\TextEntry::make('phone')
                                    ->label('Telefon')
                                    ->icon('heroicon-m-phone')
                                    ->copyable(),
                                    
                                Infolists\Components\TextEntry::make('company.name')
                                    ->label('Unternehmen')
                                    ->badge()
                                    ->color('primary'),
                                    
                                Infolists\Components\TextEntry::make('homeBranch.name')
                                    ->label('Stammfiliale')
                                    ->badge()
                                    ->color('success'),
                                    
                                Infolists\Components\IconEntry::make('active')
                                    ->label('Status')
                                    ->boolean()
                                    ->trueIcon('heroicon-o-check-circle')
                                    ->falseIcon('heroicon-o-x-circle')
                                    ->trueColor('success')
                                    ->falseColor('danger'),
                            ]),
                    ]),
                    
                Infolists\Components\Section::make('Leistungsmetriken')
                    ->schema([
                        Infolists\Components\Grid::make(4)
                            ->schema([
                                Infolists\Components\TextEntry::make('total_appointments')
                                    ->label('Termine gesamt')
                                    ->state(fn ($record) => $record->appointments()->count())
                                    ->badge()
                                    ->color('info'),
                                    
                                Infolists\Components\TextEntry::make('completed_appointments')
                                    ->label('Abgeschlossen')
                                    ->state(fn ($record) => $record->appointments()->where('status', 'completed')->count())
                                    ->badge()
                                    ->color('success'),
                                    
                                Infolists\Components\TextEntry::make('cancelled_appointments')
                                    ->label('Abgesagt')
                                    ->state(fn ($record) => $record->appointments()->where('status', 'cancelled')->count())
                                    ->badge()
                                    ->color('danger'),
                                    
                                Infolists\Components\TextEntry::make('completion_rate')
                                    ->label('Abschlussrate')
                                    ->state(function ($record) {
                                        $total = $record->appointments()->count();
                                        if ($total === 0) return '0%';
                                        $completed = $record->appointments()->where('status', 'completed')->count();
                                        return round(($completed / $total) * 100) . '%';
                                    })
                                    ->badge()
                                    ->color(fn ($state) => match(true) {
                                        str_replace('%', '', $state) >= 80 => 'success',
                                        str_replace('%', '', $state) >= 60 => 'warning',
                                        default => 'danger'
                                    }),
                            ]),
                    ]),
                    
                Infolists\Components\Section::make('Zuweisung')
                    ->schema([
                        Infolists\Components\TextEntry::make('branches')
                            ->label('Arbeitet in Filialen')
                            ->listWithLineBreaks()
                            ->bulleted()
                            ->state(fn ($record) => $record->branches->pluck('name')),
                            
                        Infolists\Components\TextEntry::make('services')
                            ->label('Bietet Services an')
                            ->listWithLineBreaks()
                            ->bulleted()
                            ->state(fn ($record) => $record->services->map(fn ($service) => 
                                $service->name . ($service->duration ? ' (' . $service->duration . ' Min.)' : '')
                            )),
                    ])
                    ->columns(2),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['company', 'homeBranch', 'branches', 'services'])
            ->withCount(['branches', 'services']);
    }

    public static function getGlobalSearchResultDetails($record): array
    {
        return [
            'Unternehmen' => $record->company?->name,
            'Filiale' => $record->homeBranch?->name,
            'Status' => $record->active ? 'Aktiv' : 'Inaktiv',
        ];
    }
    
    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'email', 'phone', 'company.name', 'homeBranch.name'];
    }
    
    protected static function getExportColumns(): array
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'email' => 'E-Mail',
            'phone' => 'Telefon',
            'company.name' => 'Unternehmen',
            'homeBranch.name' => 'Stammfiliale',
            'active' => 'Aktiv',
            'appointments_count' => 'Termine gesamt',
            'created_at' => 'Erstellt am',
        ];
    }
}
