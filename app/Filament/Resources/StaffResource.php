<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StaffResource\Pages;
use App\Filament\Resources\StaffResource\RelationManagers;
use App\Models\Staff;
use App\Models\Company;
use App\Models\Branch;
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

class StaffResource extends Resource
{
    protected static ?string $model = Staff::class;
    protected static ?string $navigationIcon = 'heroicon-o-identification';
    protected static ?string $navigationGroup = 'Stammdaten';
    protected static ?int $navigationSort = 3;
    protected static ?string $navigationLabel = 'Personal';
    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationBadge(): ?string
    {
        return null; // EMERGENCY: Disabled to prevent memory exhaustion
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return null; // EMERGENCY: Disabled to prevent memory exhaustion
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Optimized to 4 logical tabs for staff management
                Tabs::make('Staff Details')
                    ->tabs([
                        // Tab 1: Personal Information
                        Tabs\Tab::make('Person')
                            ->icon('heroicon-m-user')
                            ->schema([
                                Section::make('Persönliche Daten')
                                    ->schema([
                                        Grid::make(2)->schema([
                                            Forms\Components\TextInput::make('name')
                                                ->label('Name')
                                                ->required()
                                                ->maxLength(255)
                                                ->placeholder('Max Mustermann'),

                                            Forms\Components\Select::make('branch_id')
                                                ->label('Stamm-Filiale')
                                                ->relationship('branch', 'name')
                                                ->required()
                                                ->searchable()
                                                ->preload(),
                                        ]),

                                        Grid::make(3)->schema([
                                            Forms\Components\TextInput::make('email')
                                                ->label('E-Mail')
                                                ->email()
                                                ->maxLength(255),

                                            Forms\Components\TextInput::make('phone')
                                                ->label('Telefon')
                                                ->tel()
                                                ->maxLength(255),

                                            Forms\Components\Select::make('experience_level')
                                                ->label('Erfahrungslevel')
                                                ->options([
                                                    1 => '🌱 Anfänger',
                                                    2 => '🌿 Junior',
                                                    3 => '🌳 Erfahren',
                                                    4 => '🏆 Senior',
                                                    5 => '👑 Expert',
                                                ])
                                                ->default(1)
                                                ->required()
                                                ->native(false),
                                        ]),

                                        Grid::make(2)->schema([
                                            Forms\Components\Toggle::make('is_active')
                                                ->label('Aktiv')
                                                ->default(true),

                                            Forms\Components\Toggle::make('is_bookable')
                                                ->label('Buchbar')
                                                ->default(true),
                                        ]),

                                        Forms\Components\Select::make('company_id')
                                            ->label('Unternehmen')
                                            ->relationship('company', 'name')
                                            ->searchable()
                                            ->preload(),
                                    ]),
                            ]),

                        // Tab 2: Skills & Qualifications
                        Tabs\Tab::make('Qualifikationen')
                            ->icon('heroicon-m-academic-cap')
                            ->schema([
                                Section::make('Fähigkeiten & Qualifikationen')
                                    ->schema([
                                        Grid::make(2)->schema([
                                            Forms\Components\TextInput::make('mobility_radius_km')
                                                ->label('Mobilitätsradius')
                                                ->suffix('km')
                                                ->numeric()
                                                ->placeholder('0 = nur Filiale'),

                                            Forms\Components\TextInput::make('average_rating')
                                                ->label('Durchschnittsbewertung')
                                                ->suffix('/ 5.0')
                                                ->numeric()
                                                ->step(0.1)
                                                ->disabled(),
                                        ]),

                                        Forms\Components\Textarea::make('skills')
                                            ->label('Fähigkeiten')
                                            ->rows(3)
                                            ->placeholder('JavaScript, Python, Projektmanagement...')
                                            ->helperText('Kommagetrennte Liste der Fähigkeiten'),

                                        Forms\Components\Textarea::make('languages')
                                            ->label('Sprachen')
                                            ->rows(2)
                                            ->placeholder('Deutsch (Muttersprache), Englisch (Fließend)...')
                                            ->helperText('Sprachen mit Kenntnisstand'),

                                        Forms\Components\Textarea::make('specializations')
                                            ->label('Spezialisierungen')
                                            ->rows(3)
                                            ->placeholder('Frontend-Entwicklung, API-Integration...')
                                            ->helperText('Besondere Fachbereiche'),

                                        Forms\Components\Textarea::make('certifications')
                                            ->label('Zertifikate')
                                            ->rows(3)
                                            ->placeholder('AWS Certified Developer, Scrum Master...')
                                            ->helperText('Professionelle Zertifikate'),
                                    ]),
                            ]),

                        // Tab 3: Schedule & Availability
                        Tabs\Tab::make('Arbeitszeit')
                            ->icon('heroicon-m-clock')
                            ->schema([
                                Section::make('Arbeitszeiten & Verfügbarkeit')
                                    ->schema([
                                        Forms\Components\Textarea::make('working_hours')
                                            ->label('Arbeitszeiten')
                                            ->rows(6)
                                            ->placeholder('Mo-Fr: 08:00-17:00\nSa: 09:00-14:00\nSo: Frei')
                                            ->helperText('Standardarbeitszeiten pro Wochentag'),

                                        Forms\Components\Textarea::make('notes')
                                            ->label('Notizen')
                                            ->rows(3)
                                            ->placeholder('Besondere Hinweise zu Verfügbarkeit, Präferenzen...')
                                            ->helperText('Interne Notizen zur Einsatzplanung'),

                                        Grid::make(2)->schema([
                                            Forms\Components\TextInput::make('home_branch_id')
                                                ->label('Home Branch ID')
                                                ->maxLength(36)
                                                ->placeholder('Wird automatisch gesetzt'),

                                            Forms\Components\TextInput::make('calcom_username')
                                                ->label('Cal.com Benutzername')
                                                ->maxLength(255),
                                        ]),
                                    ]),
                            ]),

                        // Tab 4: Integration & Calendar (Admin only)
                        Tabs\Tab::make('Integration')
                            ->icon('heroicon-m-calendar')
                            ->visible(fn () => auth()->user()?->hasRole('admin'))
                            ->schema([
                                Section::make('Kalender & Integration')
                                    ->schema([
                                        Grid::make(2)->schema([
                                            Forms\Components\TextInput::make('calcom_user_id')
                                                ->label('Cal.com User ID')
                                                ->maxLength(255),

                                            Forms\Components\TextInput::make('calcom_calendar_link')
                                                ->label('Cal.com Kalender Link')
                                                ->url()
                                                ->maxLength(255),
                                        ]),

                                        Grid::make(2)->schema([
                                            Forms\Components\TextInput::make('external_calendar_id')
                                                ->label('Externe Kalender ID')
                                                ->maxLength(255),

                                            Forms\Components\Select::make('calendar_provider')
                                                ->label('Kalender Anbieter')
                                                ->options([
                                                    'google' => 'Google Calendar',
                                                    'outlook' => 'Microsoft Outlook',
                                                    'calcom' => 'Cal.com',
                                                    'other' => 'Sonstige',
                                                ])
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
            // Performance: Eager load relationships and count aggregates
            ->modifyQueryUsing(fn (Builder $query) =>
                $query->with(['company', 'branch'])
                    ->withCount(['appointments' => fn ($q) => $q->where('status', 'confirmed')])
            )
            // Optimized to 9 essential columns with rich visual information
            ->columns([
                // Staff name with experience indicator
                Tables\Columns\TextColumn::make('name')
                    ->label('Mitarbeiter')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn ($record) =>
                        ($record->branch?->name ?: 'Keine Filiale') .
                        ($record->experience_level ? ' • Level ' . $record->experience_level : '')
                    )
                    ->icon(fn ($record) => match($record->experience_level) {
                        1 => 'heroicon-m-academic-cap',
                        2 => 'heroicon-m-user',
                        3 => 'heroicon-m-user-plus',
                        4 => 'heroicon-m-star',
                        5 => 'heroicon-m-trophy',
                        default => 'heroicon-m-user',
                    }),

                // Contact information
                Tables\Columns\TextColumn::make('contact')
                    ->label('Kontakt')
                    ->getStateUsing(fn ($record) =>
                        ($record->email ?: '') .
                        ($record->email && $record->phone ? ' • ' : '') .
                        ($record->phone ?: '')
                    )
                    ->searchable(['email', 'phone'])
                    
                    ->icon('heroicon-m-envelope'),

                // Experience level with visual coding
                Tables\Columns\TextColumn::make('experience_level')
                    ->label('Level')
                    ->badge()
                    ->colors([
                        'gray' => 1,
                        'info' => 2,
                        'warning' => 3,
                        'success' => 4,
                        'purple' => 5,
                    ])
                    ->formatStateUsing(fn ($state) => match($state) {
                        1 => '🌱 Anfänger',
                        2 => '🌿 Junior',
                        3 => '🌳 Erfahren',
                        4 => '🏆 Senior',
                        5 => '👑 Expert',
                        default => '❓ Unbekannt',
                    }),

                // Availability status
                Tables\Columns\TextColumn::make('availability_status')
                    ->label('Verfügbarkeit')
                    ->getStateUsing(fn ($record) =>
                        $record->is_active && $record->active && $record->is_bookable ? 'available' :
                        ($record->is_active && $record->active ? 'limited' : 'unavailable')
                    )
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'available' => 'success',
                        'limited' => 'warning',
                        'unavailable' => 'danger',
                    })
                    ->formatStateUsing(fn ($state) => match($state) {
                        'available' => '🟢 Verfügbar',
                        'limited' => '🟡 Eingeschränkt',
                        'unavailable' => '🔴 Nicht verfügbar',
                    }),

                // Skills preview
                Tables\Columns\TextColumn::make('skills_preview')
                    ->label('Fähigkeiten')
                    ->getStateUsing(fn ($record) =>
                        $record->skills ?
                        implode(' • ', array_slice(explode(',', $record->skills), 0, 3)) :
                        'Keine Angabe'
                    )
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->skills)
                    ->badge()
                    ->color('info'),

                // Performance metrics
                Tables\Columns\TextColumn::make('performance')
                    ->label('Performance')
                    ->getStateUsing(fn ($record) =>
                        '⭐ ' . number_format($record->average_rating ?: 0, 1) . ' • ' .
                        '📅 ' . $record->appointments_count . ' Termine'
                    )
                    ->badge()
                    ->color(fn ($record) =>
                        $record->average_rating >= 4.5 ? 'success' :
                        ($record->average_rating >= 3.5 ? 'warning' : 'gray')
                    ),

                // Mobility and reach
                Tables\Columns\TextColumn::make('mobility')
                    ->label('Mobilität')
                    ->getStateUsing(fn ($record) =>
                        $record->mobility_radius_km > 0 ?
                        '🚗 ' . $record->mobility_radius_km . 'km Radius' :
                        '🏢 Nur Filiale'
                    )
                    ->badge()
                    ->color(fn ($record) => $record->mobility_radius_km > 0 ? 'success' : 'gray')
                    ->toggleable(),

                // Calendar integration status
                Tables\Columns\IconColumn::make('calendar_integration')
                    ->label('Kalender')
                    ->getStateUsing(fn ($record) =>
                        $record->calcom_user_id || $record->external_calendar_id
                    )
                    ->boolean()
                    ->trueIcon('heroicon-o-calendar')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->tooltip(fn ($record) =>
                        'Cal.com: ' . ($record->calcom_user_id ? '✅' : '❌') .
                        ' • Extern: ' . ($record->external_calendar_id ? '✅' : '❌')
                    )
                    ->toggleable(isToggledHiddenByDefault: true),

                // Last activity (hidden by default)
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Aktualisiert')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            // Smart business filters for staff management
            ->filters([
                SelectFilter::make('branch_id')
                    ->label('Filiale')
                    ->relationship('branch', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('experience_level')
                    ->label('Erfahrungslevel')
                    ->options([
                        1 => '🌱 Anfänger',
                        2 => '🌿 Junior',
                        3 => '🌳 Erfahren',
                        4 => '🏆 Senior',
                        5 => '👑 Expert',
                    ]),

                Filter::make('available_now')
                    ->label('Aktuell verfügbar')
                    ->query(fn (Builder $query): Builder =>
                        $query->where('is_active', true)
                            ->where('is_bookable', true)
                    )
                    ->default(),

                Filter::make('mobile_staff')
                    ->label('Mobile Mitarbeiter')
                    ->query(fn (Builder $query): Builder =>
                        $query->where('mobility_radius_km', '>', 0)
                    ),

                Filter::make('high_rated')
                    ->label('Top Bewertung (≥4.0)')
                    ->query(fn (Builder $query): Builder =>
                        $query->where('average_rating', '>=', 4.0)
                    ),

                Filter::make('has_calendar')
                    ->label('Mit Kalender-Integration')
                    ->query(fn (Builder $query): Builder =>
                        $query->where(fn ($q) =>
                            $q->whereNotNull('calcom_user_id')
                              ->orWhereNotNull('external_calendar_id')
                        )
                    ),

                Filter::make('certified_staff')
                    ->label('Zertifiziert')
                    ->query(fn (Builder $query): Builder =>
                        $query->whereNotNull('certifications')
                            ->where('certifications', '!=', '')
                    ),

                SelectFilter::make('company_id')
                    ->label('Unternehmen')
                    ->relationship('company', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->filtersLayout(Tables\Enums\FiltersLayout::AboveContent)
            ->filtersFormColumns(3)
            // Quick actions for staff management
            ->actions([
                Tables\Actions\ActionGroup::make([
                    // Schedule appointment
                    Tables\Actions\Action::make('scheduleAppointment')
                        ->label('Termin planen')
                        ->icon('heroicon-o-calendar-days')
                        ->color('success')
                        ->url(fn ($record) => route('filament.admin.resources.appointments.create', [
                            'staff_id' => $record->id
                        ]))
                        ->visible(fn ($record) => $record->is_active && $record->is_bookable),

                    // Update skills
                    Tables\Actions\Action::make('updateSkills')
                        ->label('Qualifikationen')
                        ->icon('heroicon-m-academic-cap')
                        ->color('info')
                        ->form([
                            Forms\Components\Textarea::make('skills')
                                ->label('Fähigkeiten')
                                ->default(fn ($record) => $record->skills)
                                ->rows(3)
                                ->placeholder('JavaScript, Python, Projektmanagement...'),
                            Forms\Components\Textarea::make('certifications')
                                ->label('Zertifikate')
                                ->default(fn ($record) => $record->certifications)
                                ->rows(3),
                            Forms\Components\Select::make('experience_level')
                                ->label('Erfahrungslevel')
                                ->options([
                                    1 => 'Anfänger',
                                    2 => 'Junior',
                                    3 => 'Erfahren',
                                    4 => 'Senior',
                                    5 => 'Expert',
                                ])
                                ->default(fn ($record) => $record->experience_level),
                        ])
                        ->modalWidth('2xl')
                        ->modalHeading('Qualifikationen bearbeiten')
                        ->modalSubmitActionLabel('Speichern')
                        ->modalCancelActionLabel('Abbrechen')
                        ->action(function ($record, array $data) {
                            try {
                                $record->update($data);

                                Notification::make()
                                    ->title('Qualifikationen aktualisiert')
                                    ->body('Fähigkeiten und Zertifikate wurden aktualisiert.')
                                    ->success()
                                    ->send();
                            } catch (\Exception $e) {
                                \Log::error('Staff updateSkills error: ' . $e->getMessage());

                                Notification::make()
                                    ->title('Fehler beim Aktualisieren')
                                    ->body('Die Qualifikationen konnten nicht gespeichert werden.')
                                    ->danger()
                                    ->send();
                            }
                        }),

                    // Working hours management
                    Tables\Actions\Action::make('updateSchedule')
                        ->label('Arbeitszeiten')
                        ->icon('heroicon-m-clock')
                        ->color('warning')
                        ->form([
                            Forms\Components\Textarea::make('working_hours')
                                ->label('Arbeitszeiten')
                                ->default(fn ($record) => $record->working_hours)
                                ->rows(6)
                                ->placeholder('Mo-Fr: 08:00-17:00\nSa: 09:00-14:00\nSo: Frei'),
                            Forms\Components\Toggle::make('is_bookable')
                                ->label('Buchbar')
                                ->default(fn ($record) => $record->is_bookable),
                            Forms\Components\TextInput::make('mobility_radius_km')
                                ->label('Mobilitätsradius (km)')
                                ->numeric()
                                ->default(fn ($record) => $record->mobility_radius_km),
                        ])
                        ->modalWidth('2xl')
                        ->modalHeading('Arbeitszeiten bearbeiten')
                        ->modalSubmitActionLabel('Speichern')
                        ->modalCancelActionLabel('Abbrechen')
                        ->action(function ($record, array $data) {
                            try {
                                $record->update($data);

                                Notification::make()
                                    ->title('Arbeitszeiten aktualisiert')
                                    ->body('Zeitplan und Verfügbarkeit wurden geändert.')
                                    ->success()
                                    ->send();
                            } catch (\Exception $e) {
                                \Log::error('Staff updateSchedule error: ' . $e->getMessage());

                                Notification::make()
                                    ->title('Fehler beim Aktualisieren')
                                    ->body('Die Arbeitszeiten konnten nicht gespeichert werden.')
                                    ->danger()
                                    ->send();
                            }
                        }),

                    // Availability toggle
                    Tables\Actions\Action::make('toggleAvailability')
                        ->label('Verfügbarkeit')
                        ->icon('heroicon-m-power')
                        ->color(fn ($record) => $record->is_active ? 'danger' : 'success')
                        ->form([
                            Forms\Components\Toggle::make('is_active')
                                ->label('Mitarbeiter aktiv')
                                ->default(fn ($record) => $record->is_active),
                            Forms\Components\Toggle::make('is_bookable')
                                ->label('Buchbar')
                                ->default(fn ($record) => $record->is_bookable),
                            Forms\Components\Textarea::make('notes')
                                ->label('Notiz zur Änderung')
                                ->rows(2),
                        ])
                        ->modalWidth('xl')
                        ->modalHeading('Verfügbarkeit ändern')
                        ->modalSubmitActionLabel('Speichern')
                        ->modalCancelActionLabel('Abbrechen')
                        ->action(function ($record, array $data) {
                            try {
                                $record->update(array_filter($data, fn ($key) => $key !== 'notes', ARRAY_FILTER_USE_KEY));

                                if ($data['notes']) {
                                    // TODO: Log availability change with note
                                }

                                Notification::make()
                                    ->title('Verfügbarkeit geändert')
                                    ->body('Mitarbeiter-Verfügbarkeit wurde aktualisiert.')
                                    ->success()
                                    ->send();
                            } catch (\Exception $e) {
                                \Log::error('Staff toggleAvailability error: ' . $e->getMessage());

                                Notification::make()
                                    ->title('Fehler beim Aktualisieren')
                                    ->body('Die Verfügbarkeit konnte nicht geändert werden.')
                                    ->danger()
                                    ->send();
                            }
                        }),

                    // Branch transfer
                    Tables\Actions\Action::make('transferBranch')
                        ->label('Filiale wechseln')
                        ->icon('heroicon-m-building-storefront')
                        ->color('gray')
                        ->form([
                            Forms\Components\Select::make('branch_id')
                                ->label('Neue Filiale')
                                ->relationship('branch', 'name')
                                ->default(fn ($record) => $record->branch_id)
                                ->required()
                                ->searchable(),
                            Forms\Components\Textarea::make('transfer_reason')
                                ->label('Grund für Wechsel')
                                ->rows(2),
                        ])
                        ->modalWidth('xl')
                        ->modalHeading('Filiale wechseln')
                        ->modalSubmitActionLabel('Versetzung durchführen')
                        ->modalCancelActionLabel('Abbrechen')
                        ->action(function ($record, array $data) {
                            try {
                                $oldBranch = $record->branch?->name;
                                $record->update(['branch_id' => $data['branch_id']]);
                                $newBranch = $record->fresh()->branch?->name;

                                // TODO: Log branch transfer

                                Notification::make()
                                    ->title('Filiale gewechselt')
                                    ->body("Mitarbeiter von '{$oldBranch}' nach '{$newBranch}' versetzt.")
                                    ->success()
                                    ->send();
                            } catch (\Exception $e) {
                                \Log::error('Staff transferBranch error: ' . $e->getMessage());

                                Notification::make()
                                    ->title('Fehler beim Wechseln')
                                    ->body('Der Filialwechsel konnte nicht durchgeführt werden.')
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->requiresConfirmation(),

                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                ]),
            ])
            // Bulk operations for staff management
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('bulkAvailabilityUpdate')
                        ->label('Verfügbarkeit setzen')
                        ->icon('heroicon-m-power')
                        ->color('warning')
                        ->form([
                            Forms\Components\Toggle::make('is_active')
                                ->label('Mitarbeiter aktivieren'),
                            Forms\Components\Toggle::make('is_bookable')
                                ->label('Buchbar setzen'),
                            Forms\Components\Textarea::make('reason')
                                ->label('Grund für Änderung')
                                ->rows(2),
                        ])
                        ->modalWidth('xl')
                        ->modalHeading('Verfügbarkeit setzen (Massenbearbeitung)')
                        ->modalSubmitActionLabel('Aktualisieren')
                        ->modalCancelActionLabel('Abbrechen')
                        ->action(function ($records, array $data) {
                            try {
                                $updates = array_filter($data, fn ($key) => $key !== 'reason', ARRAY_FILTER_USE_KEY);
                                $records->each->update($updates);

                                Notification::make()
                                    ->title('Verfügbarkeit aktualisiert')
                                    ->body(count($records) . ' Mitarbeiter wurden aktualisiert.')
                                    ->success()
                                    ->send();
                            } catch (\Exception $e) {
                                \Log::error('Staff bulkAvailabilityUpdate error: ' . $e->getMessage());

                                Notification::make()
                                    ->title('Fehler bei Massenbearbeitung')
                                    ->body('Die Verfügbarkeit konnte nicht aktualisiert werden.')
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->requiresConfirmation(),

                    Tables\Actions\BulkAction::make('bulkBranchTransfer')
                        ->label('Filiale wechseln')
                        ->icon('heroicon-m-building-storefront')
                        ->color('info')
                        ->form([
                            Forms\Components\Select::make('branch_id')
                                ->label('Ziel-Filiale')
                                ->relationship('branch', 'name')
                                ->required()
                                ->searchable(),
                        ])
                        ->modalWidth('xl')
                        ->modalHeading('Filiale wechseln (Massenbearbeitung)')
                        ->modalSubmitActionLabel('Versetzung durchführen')
                        ->modalCancelActionLabel('Abbrechen')
                        ->action(function ($records, array $data) {
                            try {
                                $records->each->update($data);

                                Notification::make()
                                    ->title('Massen-Transfer durchgeführt')
                                    ->body(count($records) . ' Mitarbeiter wurden zur neuen Filiale versetzt.')
                                    ->success()
                                    ->send();
                            } catch (\Exception $e) {
                                \Log::error('Staff bulkBranchTransfer error: ' . $e->getMessage());

                                Notification::make()
                                    ->title('Fehler bei Massen-Transfer')
                                    ->body('Der Filialwechsel konnte nicht durchgeführt werden.')
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->requiresConfirmation(),

                    Tables\Actions\BulkAction::make('bulkExperienceUpdate')
                        ->label('Level anpassen')
                        ->icon('heroicon-m-arrow-trending-up')
                        ->color('success')
                        ->form([
                            Forms\Components\Select::make('experience_level')
                                ->label('Neues Erfahrungslevel')
                                ->options([
                                    1 => '🌱 Anfänger',
                                    2 => '🌿 Junior',
                                    3 => '🌳 Erfahren',
                                    4 => '🏆 Senior',
                                    5 => '👑 Expert',
                                ])
                                ->required(),
                        ])
                        ->modalWidth('lg')
                        ->modalHeading('Erfahrungslevel anpassen (Massenbearbeitung)')
                        ->modalSubmitActionLabel('Level aktualisieren')
                        ->modalCancelActionLabel('Abbrechen')
                        ->action(function ($records, array $data) {
                            try {
                                $records->each->update($data);

                                Notification::make()
                                    ->title('Erfahrungslevel aktualisiert')
                                    ->body(count($records) . ' Mitarbeiter haben ein neues Level erhalten.')
                                    ->success()
                                    ->send();
                            } catch (\Exception $e) {
                                \Log::error('Staff bulkExperienceUpdate error: ' . $e->getMessage());

                                Notification::make()
                                    ->title('Fehler bei Level-Anpassung')
                                    ->body('Das Erfahrungslevel konnte nicht aktualisiert werden.')
                                    ->danger()
                                    ->send();
                            }
                        }),

                    Tables\Actions\ExportBulkAction::make()
                        ->label('Exportieren'),

                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Löschen'),
                ])
                ->label('Massenaktionen')
                ->icon('heroicon-o-squares-plus')
                ->color('primary'),
            ])
            // Performance optimizations
            ->defaultPaginationPageOption(25)
            ->poll('60s')
            ->deferLoading()
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->striped();
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\AppointmentsRelationManager::class,
            RelationManagers\WorkingHoursRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStaff::route('/'),
            'create' => Pages\CreateStaff::route('/create'),
            'view' => Pages\ViewStaff::route('/{record}'),
            'edit' => Pages\EditStaff::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['company', 'branch'])
            ->withCount(['appointments' => fn ($q) => $q->where('status', 'confirmed')]);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'email', 'phone', 'skills', 'specializations'];
    }
}
