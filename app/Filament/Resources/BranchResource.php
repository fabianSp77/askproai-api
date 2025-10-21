<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BranchResource\Pages;
use App\Filament\Resources\BranchResource\RelationManagers;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Staff;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use App\Models\PolicyConfiguration;
use Illuminate\Support\HtmlString;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Filament\Notifications\Notification;

class BranchResource extends Resource
{
    protected static ?string $model = Branch::class;
    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';
    protected static ?string $navigationGroup = 'Stammdaten';
    protected static ?int $navigationSort = 5;
    protected static ?string $navigationLabel = 'Filialen';
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
                // Optimized to 3 logical tabs for branch management
                Tabs::make('Branch Details')
                    ->tabs([
                        // Tab 1: Essential Branch Information
                        Tabs\Tab::make('Filiale')
                            ->icon('heroicon-m-building-storefront')
                            ->schema([
                                Section::make('Grundinformationen')
                                    ->schema([
                                        Grid::make(2)->schema([
                                            Forms\Components\Select::make('company_id')
                                                ->label('Unternehmen')
                                                ->relationship('company', 'name')
                                                ->required()
                                                ->searchable()
                                                ->preload(),

                                            Forms\Components\TextInput::make('name')
                                                ->label('Filialname')
                                                ->required()
                                                ->maxLength(255)
                                                ->placeholder('Filiale Musterstadt'),
                                        ]),

                                        Grid::make(3)->schema([
                                            Forms\Components\TextInput::make('slug')
                                                ->label('URL Slug')
                                                ->maxLength(255)
                                                ->placeholder('musterstadt'),

                                            Forms\Components\Toggle::make('is_active')
                                                ->label('Aktiv')
                                                ->default(true),

                                            Forms\Components\Toggle::make('accepts_walkins')
                                                ->label('Walk-Ins akzeptiert')
                                                ->default(true),
                                        ]),

                                        Grid::make(2)->schema([
                                            Forms\Components\TextInput::make('phone_number')
                                                ->label('Telefon')
                                                ->tel()
                                                ->maxLength(255),

                                            Forms\Components\TextInput::make('notification_email')
                                                ->label('Benachrichtigungs-E-Mail')
                                                ->email()
                                                ->maxLength(255),
                                        ]),

                                        Forms\Components\TextInput::make('address')
                                            ->label('Adresse')
                                            ->maxLength(255),

                                        Grid::make(3)->schema([
                                            Forms\Components\TextInput::make('city')
                                                ->label('Stadt')
                                                ->maxLength(255),

                                            Forms\Components\TextInput::make('postal_code')
                                                ->label('PLZ')
                                                ->maxLength(10),

                                            Forms\Components\TextInput::make('country')
                                                ->label('Land')
                                                ->default('Deutschland')
                                                ->maxLength(255),
                                        ]),

                                        Forms\Components\TextInput::make('website')
                                            ->label('Website')
                                            ->url()
                                            ->maxLength(255),
                                    ]),
                            ]),

                        // Tab 2: Operations & Services
                        Tabs\Tab::make('Betrieb')
                            ->icon('heroicon-m-clock')
                            ->schema([
                                Section::make('Betriebsinformationen')
                                    ->schema([
                                        Grid::make(2)->schema([
                                            Forms\Components\TextInput::make('service_radius_km')
                                                ->label('Service-Radius (km)')
                                                ->numeric()
                                                ->suffix('km')
                                                ->default(0),

                                            Forms\Components\Select::make('calendar_mode')
                                                ->label('Kalender-Vererbung')
                                                ->options([
                                                    'inherit' => '📋 Von Unternehmen übernehmen',
                                                    'override' => '✏️ Eigene Einstellung',
                                                ])
                                                ->default('inherit')
                                                ->required()
                                                ->native(false)
                                                ->helperText('Bestimmt ob Filiale eigene Kalendereinstellungen nutzt'),
                                        ]),

                                        Grid::make(2)->schema([
                                            Forms\Components\Toggle::make('parking_available')
                                                ->label('Parkplätze verfügbar')
                                                ->default(false),

                                            Forms\Components\Toggle::make('active')
                                                ->label('Betriebsbereit')
                                                ->default(true),
                                        ]),

                                        Forms\Components\Textarea::make('business_hours')
                                            ->label('Öffnungszeiten')
                                            ->rows(4)
                                            ->placeholder('Mo-Fr: 08:00-18:00\nSa: 09:00-16:00\nSo: Geschlossen'),

                                        Forms\Components\Textarea::make('features')
                                            ->label('Besonderheiten')
                                            ->rows(3)
                                            ->placeholder('WLAN, Klimaanlage, Barrierefreiheit...'),

                                        Forms\Components\Textarea::make('public_transport_access')
                                            ->label('ÖPNV Anbindung')
                                            ->rows(2)
                                            ->placeholder('U-Bahn Linie 3, Bus 42...'),
                                    ]),
                            ]),

                        // Tab 3: Policies
                        Tabs\Tab::make('Richtlinien')
                            ->icon('heroicon-m-shield-check')
                            ->schema([
                                static::getPolicySection('cancellation', 'Stornierungsrichtlinie'),
                                static::getPolicySection('reschedule', 'Umbuchungsrichtlinie'),
                                static::getPolicySection('recurring', 'Wiederholungsrichtlinie'),
                            ]),

                        // Tab 4: Integration & Settings (Admin only)
                        Tabs\Tab::make('Integration')
                            ->icon('heroicon-m-cog')
                            ->visible(fn () => auth()->user() && (auth()->user()->hasRole('admin') || auth()->user()->hasRole('Admin') || auth()->user()->hasRole('super_admin') || auth()->user()->hasRole('Super Admin')))
                            ->schema([
                                Section::make('Integration & Einstellungen')
                                    ->schema([
                                        Grid::make(2)->schema([
                                            Forms\Components\TextInput::make('calcom_event_type_id')
                                                ->label('Cal.com Event Type ID')
                                                ->maxLength(255)
                                                ->helperText('Event Type ID von Cal.com (von Company-Einstellungen)'),

                                            Forms\Components\Select::make('calendar_mode')
                                                ->label('Kalender-Vererbung')
                                                ->options([
                                                    'inherit' => '📋 Von Unternehmen übernehmen',
                                                    'override' => '✏️ Eigene Einstellung',
                                                ])
                                                ->default('inherit')
                                                ->helperText('Nutzt Einstellungen vom Unternehmen oder eigene'),
                                        ]),

                                        Forms\Components\Placeholder::make('integration_info')
                                            ->label('Integration Status')
                                            ->content(function (?Branch $record) {
                                                if (!$record) return 'Neue Filiale - noch keine Integrationen';

                                                $company = $record->company;
                                                $info = [];

                                                if ($company?->retell_api_key) {
                                                    $info[] = '✅ Retell (über Unternehmen)';
                                                }
                                                if ($company?->calcom_api_key) {
                                                    $info[] = '✅ Cal.com API (über Unternehmen)';
                                                }
                                                if ($record->calcom_event_type_id) {
                                                    $info[] = '✅ Cal.com Event Type konfiguriert';
                                                }

                                                return empty($info)
                                                    ? '⚠️ Keine Integrationen konfiguriert'
                                                    : implode(' • ', $info);
                                            }),

                                        Grid::make(2)->schema([
                                            Forms\Components\Toggle::make('include_transcript_in_summary')
                                                ->label('Transkript in Summary')
                                                ->helperText('Überschreibt Unternehmenseinstellung'),

                                            Forms\Components\Toggle::make('include_csv_export')
                                                ->label('CSV Export einschließen')
                                                ->helperText('Überschreibt Unternehmenseinstellung'),
                                        ]),

                                        Forms\Components\TextInput::make('uuid')
                                            ->label('UUID')
                                            ->disabled()
                                            ->dehydrated(false),
                                    ]),
                            ]),

                        // Tab 5: Retell Agent Configuration
                        Tabs\Tab::make('Retell Agent')
                            ->icon('heroicon-m-microphone')
                            ->visible(fn () => auth()->user() && (auth()->user()->hasRole('admin') || auth()->user()->hasRole('Admin') || auth()->user()->hasRole('super_admin') || auth()->user()->hasRole('Super Admin')))
                            ->schema([
                                Section::make('Retell AI Agent Konfiguration')
                                    ->description('Verwalten Sie das Prompt und die Funktionen für den Retell AI Agenten dieser Filiale')
                                    ->schema([
                                        Grid::make(1)->schema([
                                            Forms\Components\Select::make('retell_template')
                                                ->label('Template auswählen')
                                                ->options([
                                                    'dynamic-service-selection-v127' => '🎯 Dynamic Service Selection (V127)',
                                                    'basic-appointment-booking' => '📅 Basic Appointment Booking',
                                                    'information-only' => 'ℹ️ Information Only',
                                                ])
                                                ->placeholder('Wählen Sie ein Template...')
                                                ->helperText('Das Template wird als Basis für die Konfiguration verwendet')
                                                ->dehydrated(false),
                                        ]),

                                        Forms\Components\Placeholder::make('retell_info')
                                            ->label('Agent Status')
                                            ->content(function (?Branch $record) {
                                                if (!$record) {
                                                    return view('filament.components.retell-no-branch');
                                                }

                                                $activePrompt = $record->retellAgentPrompts()
                                                    ->where('is_active', true)
                                                    ->first();

                                                if (!$activePrompt) {
                                                    return view('filament.components.retell-no-config');
                                                }

                                                return view('filament.components.retell-agent-info', [
                                                    'prompt' => $activePrompt,
                                                ]);
                                            }),

                                        Forms\Components\Actions::make([
                                            Forms\Components\Actions\Action::make('edit_prompt')
                                                ->label('Prompt bearbeiten')
                                                ->url(fn (?Branch $record) => $record
                                                    ? route('filament.admin.resources.branches.retell-agent.edit', $record)
                                                    : null)
                                                ->openUrlInNewTab()
                                                ->icon('heroicon-m-pencil-square'),

                                            Forms\Components\Actions\Action::make('deploy_from_template')
                                                ->label('Aus Template deployen')
                                                ->action(function (Branch $record, Forms\Get $get) {
                                                    $template = $get('retell_template');
                                                    if (!$template) {
                                                        Notification::make()
                                                            ->title('Template erforderlich')
                                                            ->body('Bitte wählen Sie ein Template aus')
                                                            ->danger()
                                                            ->send();
                                                        return;
                                                    }

                                                    try {
                                                        $service = new \App\Services\Retell\RetellAgentManagementService();
                                                        $templateService = new \App\Services\Retell\RetellPromptTemplateService();

                                                        $promptVersion = $templateService->applyTemplateToBranch(
                                                            $record->id,
                                                            $template
                                                        );

                                                        $result = $service->deployPromptVersion($promptVersion, auth()->user());

                                                        if ($result['success']) {
                                                            Notification::make()
                                                                ->title('Erfolgreich deployed')
                                                                ->body('Agent-Version ' . $result['retell_version'] . ' ist jetzt aktiv')
                                                                ->success()
                                                                ->send();
                                                        } else {
                                                            Notification::make()
                                                                ->title('Deployment fehlgeschlagen')
                                                                ->body(implode(', ', $result['errors'] ?? []))
                                                                ->danger()
                                                                ->send();
                                                        }
                                                    } catch (\Exception $e) {
                                                        Notification::make()
                                                            ->title('Fehler')
                                                            ->body($e->getMessage())
                                                            ->danger()
                                                            ->send();
                                                    }
                                                })
                                                ->icon('heroicon-m-rocket-launch'),
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
                $query->with(['company'])
                    ->withCount(['staff' => fn ($q) => $q->where('is_active', true)])
            )
            // Optimized to 9 essential columns with rich visual information
            ->columns([
                // Branch name with company context
                Tables\Columns\TextColumn::make('name')
                    ->label('Filiale')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn ($record) =>
                        $record->company?->name .
                        ($record->city ? ' • ' . $record->city : '')
                    )
                    ->icon('heroicon-m-building-storefront'),

                // Location information
                Tables\Columns\TextColumn::make('location')
                    ->label('Standort')
                    ->getStateUsing(fn ($record) =>
                        trim(
                            ($record->address ? $record->address . ', ' : '') .
                            ($record->postal_code ? $record->postal_code . ' ' : '') .
                            ($record->city ?: '')
                        )
                    )
                    ->searchable(['address', 'city', 'postal_code'])
                    
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->address . ', ' . $record->postal_code . ' ' . $record->city),

                // Contact information
                Tables\Columns\TextColumn::make('contact')
                    ->label('Kontakt')
                    ->getStateUsing(fn ($record) =>
                        ($record->phone_number ?: '') .
                        ($record->phone_number && $record->notification_email ? ' • ' : '') .
                        ($record->notification_email ?: '')
                    )
                    ->searchable(['phone_number', 'notification_email'])
                    
                    ->icon('heroicon-m-phone'),

                // Operational status with visual indicators
                Tables\Columns\TextColumn::make('operational_status')
                    ->badge()
                    ->label('Betrieb')
                    ->getStateUsing(fn ($record) =>
                        $record->is_active && $record->active ? 'operational' :
                        ($record->is_active ? 'limited' : 'closed')
                    )
                    ->colors([
                        'success' => 'operational',
                        'warning' => 'limited',
                        'danger' => 'closed',
                    ])
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'operational' => '🟢 Betriebsbereit',
                        'limited' => '🟡 Eingeschränkt',
                        'closed' => '🔴 Geschlossen',
                        default => $state,
                    }),

                // Service capabilities
                Tables\Columns\TextColumn::make('capabilities')
                    ->label('Services')
                    ->getStateUsing(fn ($record) =>
                        ($record->accepts_walkins ? '🚶 Walk-In' : '') .
                        ($record->accepts_walkins && $record->parking_available ? ' • ' : '') .
                        ($record->parking_available ? '🅿️ Parking' : '') .
                        ($record->service_radius_km > 0 ? ' • 📍 ' . $record->service_radius_km . 'km' : '')
                    )
                    ->badge()
                    ->color('info'),

                // Staff count with quick link
                Tables\Columns\TextColumn::make('staff_count')
                    ->label('Personal')
                    ->badge()
                    ->formatStateUsing(fn ($state) => '👥 ' . $state . ' Mitarbeiter')
                    ->color(fn ($state) => $state > 5 ? 'success' : ($state > 0 ? 'warning' : 'danger'))
                    ->sortable(),

                // Calendar mode
                Tables\Columns\TextColumn::make('calendar_mode')
                    ->label('Kalender')
                    ->badge()
                    ->colors([
                        'info' => 'individual',
                        'success' => 'shared',
                        'warning' => 'hybrid',
                    ])
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'individual' => '👤 Individuell',
                        'shared' => '👥 Gemeinsam',
                        'hybrid' => '🔄 Hybrid',
                        default => $state,
                    })
                    ->toggleable(),

                // Integration status
                Tables\Columns\IconColumn::make('integration_status')
                    ->label('Integration')
                    ->getStateUsing(fn ($record) =>
                        $record->calcom_event_type_id && $record->company?->retell_api_key
                    )
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->tooltip(fn ($record) =>
                        'Cal.com: ' . ($record->calcom_event_type_id ? '✅' : '❌') .
                        ' • Retell: ' . ($record->company?->retell_api_key ? '✅' : '❌')
                    )
                    ->toggleable(isToggledHiddenByDefault: true),

                // Last updated (hidden by default)
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Aktualisiert')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            // Smart business filters for branch management
            ->filters([
                SelectFilter::make('company_id')
                    ->label('Unternehmen')
                    ->relationship('company', 'name')
                    ->searchable()
                    ->preload(),

                Filter::make('operational')
                    ->label('Betriebsbereit')
                    ->query(fn (Builder $query): Builder =>
                        $query->where('is_active', true)->where('active', true)
                    )
                    ->default(),

                Filter::make('accepts_walkins')
                    ->label('Walk-Ins möglich')
                    ->query(fn (Builder $query): Builder =>
                        $query->where('accepts_walkins', true)
                    ),

                Filter::make('has_parking')
                    ->label('Mit Parkplatz')
                    ->query(fn (Builder $query): Builder =>
                        $query->where('parking_available', true)
                    ),

                SelectFilter::make('calendar_mode')
                    ->label('Kalender Modus')
                    ->options([
                        'individual' => 'Individuell',
                        'shared' => 'Gemeinsam',
                        'hybrid' => 'Hybrid',
                    ]),

                Filter::make('has_staff')
                    ->label('Mit Personal')
                    ->query(fn (Builder $query): Builder =>
                        $query->has('staff')
                    ),

                SelectFilter::make('city')
                    ->label('Stadt')
                    ->options(fn () => Branch::distinct()->pluck('city', 'city')->filter())
                    ->searchable(),
            ])
            ->filtersLayout(Tables\Enums\FiltersLayout::AboveContent)
            ->filtersFormColumns(3)
            // Quick actions for branch management
            ->actions([
                Tables\Actions\ActionGroup::make([
                    // Staff management
                    Tables\Actions\Action::make('manageStaff')
                        ->label('Personal verwalten')
                        ->icon('heroicon-m-user-group')
                        ->color('info')
                        ->url(fn ($record) => route('filament.admin.resources.staff.index', [
                            'tableFilters' => ['branch_id' => ['value' => $record->id]]
                        ])),

                    // Quick appointment booking
                    Tables\Actions\Action::make('bookAppointment')
                        ->label('Termin buchen')
                        ->icon('heroicon-m-calendar')
                        ->color('success')
                        ->url(fn ($record) => route('filament.admin.resources.appointments.create', [
                            'branch_id' => $record->id
                        ])),

                    // Operating hours update
                    Tables\Actions\Action::make('updateHours')
                        ->label('Öffnungszeiten')
                        ->icon('heroicon-m-clock')
                        ->color('warning')
                        ->form([
                            Forms\Components\Textarea::make('business_hours')
                                ->label('Öffnungszeiten')
                                ->default(fn ($record) => $record->business_hours)
                                ->rows(6)
                                ->placeholder('Mo-Fr: 08:00-18:00\nSa: 09:00-16:00\nSo: Geschlossen'),
                            Forms\Components\Toggle::make('is_active')
                                ->label('Filiale aktiv')
                                ->default(fn ($record) => $record->is_active),
                            Forms\Components\Toggle::make('accepts_walkins')
                                ->label('Walk-Ins akzeptiert')
                                ->default(fn ($record) => $record->accepts_walkins),
                        ])
                        ->action(function ($record, array $data) {
                            $record->update($data);

                            Notification::make()
                                ->title('Öffnungszeiten aktualisiert')
                                ->body('Die Betriebsinformationen wurden erfolgreich geändert.')
                                ->success()
                                ->send();
                        }),

                    // Integration status check
                    Tables\Actions\Action::make('checkIntegration')
                        ->label('Integration prüfen')
                        ->icon('heroicon-m-cog')
                        ->color('gray')
                        ->action(function ($record) {
                            $integrations = [];
                            if ($record->calcom_event_type_id) $integrations[] = 'Cal.com';
                            if ($record->company?->retell_api_key) $integrations[] = 'Retell';

                            $message = empty($integrations)
                                ? 'Keine Integrationen konfiguriert.'
                                : 'Aktive Integrationen: ' . implode(', ', $integrations);

                            Notification::make()
                                ->title('Integration Status')
                                ->body($message)
                                ->info()
                                ->send();
                        }),

                    // Toggle operational status
                    Tables\Actions\Action::make('toggleStatus')
                        ->label('Status umschalten')
                        ->icon('heroicon-m-power')
                        ->color(fn ($record) => $record->is_active ? 'danger' : 'success')
                        ->action(function ($record) {
                            $newStatus = !$record->is_active;
                            $record->update(['is_active' => $newStatus]);

                            Notification::make()
                                ->title('Status geändert')
                                ->body('Filiale ist jetzt ' . ($newStatus ? 'aktiv' : 'inaktiv'))
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation(),

                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                ]),
            ])
            // Bulk operations for branch management
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('bulkStatusUpdate')
                        ->label('Status aktualisieren')
                        ->icon('heroicon-m-power')
                        ->color('warning')
                        ->form([
                            Forms\Components\Toggle::make('is_active')
                                ->label('Filiale aktiv'),
                            Forms\Components\Toggle::make('accepts_walkins')
                                ->label('Walk-Ins akzeptiert'),
                            Forms\Components\Toggle::make('parking_available')
                                ->label('Parkplätze verfügbar'),
                        ])
                        ->action(function ($records, array $data) {
                            $updates = array_filter($data, fn ($value) => $value !== null);
                            $records->each->update($updates);

                            Notification::make()
                                ->title('Massen-Update durchgeführt')
                                ->body(count($records) . ' Filialen wurden aktualisiert.')
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation(),

                    Tables\Actions\BulkAction::make('bulkHoursUpdate')
                        ->label('Öffnungszeiten setzen')
                        ->icon('heroicon-m-clock')
                        ->color('info')
                        ->form([
                            Forms\Components\Textarea::make('business_hours')
                                ->label('Standard Öffnungszeiten')
                                ->required()
                                ->rows(4)
                                ->placeholder('Mo-Fr: 08:00-18:00\nSa: 09:00-16:00\nSo: Geschlossen'),
                        ])
                        ->action(function ($records, array $data) {
                            $records->each->update($data);

                            Notification::make()
                                ->title('Öffnungszeiten aktualisiert')
                                ->body(count($records) . ' Filialen haben neue Öffnungszeiten.')
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation(),

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

    public static function getRelations(): array
    {
        return [
            RelationManagers\ServicesRelationManager::class,
            RelationManagers\StaffRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBranches::route('/'),
            'create' => Pages\CreateBranch::route('/create'),
            'view' => Pages\ViewBranch::route('/{record}'),
            'edit' => Pages\EditBranch::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['company'])
            ->withCount(['staff' => fn ($q) => $q->where('is_active', true)]);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'city', 'address', 'phone_number', 'notification_email'];
    }

    /**
     * Generate policy configuration section for form
     */
    protected static function getPolicySection(string $policyType, string $label): Section
    {
        return Section::make($label)
            ->icon(match($policyType) {
                'cancellation' => 'heroicon-m-x-circle',
                'reschedule' => 'heroicon-m-arrow-path',
                'recurring' => 'heroicon-m-arrow-path-rounded-square',
                default => 'heroicon-m-shield-check',
            })
            ->description('Konfigurieren Sie die Richtlinien für ' . strtolower($label))
            ->schema([
                Forms\Components\Toggle::make("override_{$policyType}")
                    ->label('Überschreiben')
                    ->helperText('Aktivieren Sie diese Option, um eigene Richtlinien festzulegen')
                    ->reactive()
                    ->afterStateUpdated(function (Set $set, $state, $record) use ($policyType) {
                        if (!$state && $record) {
                            // Remove policy configuration when override is disabled
                            PolicyConfiguration::where('configurable_type', Branch::class)
                                ->where('configurable_id', $record->id)
                                ->where('policy_type', $policyType)
                                ->delete();
                        }
                    })
                    ->dehydrated(false)
                    ->default(function ($record) use ($policyType) {
                        if (!$record) return false;
                        return PolicyConfiguration::where('configurable_type', Branch::class)
                            ->where('configurable_id', $record->id)
                            ->where('policy_type', $policyType)
                            ->exists();
                    }),

                Forms\Components\KeyValue::make("policy_config_{$policyType}")
                    ->label('Konfiguration')
                    ->keyLabel('Schlüssel')
                    ->valueLabel('Wert')
                    ->addActionLabel('Eigenschaft hinzufügen')
                    ->reorderable()
                    ->visible(fn (Get $get) => $get("override_{$policyType}") === true)
                    ->default(function ($record) use ($policyType) {
                        if (!$record) return [];
                        $policy = PolicyConfiguration::where('configurable_type', Branch::class)
                            ->where('configurable_id', $record->id)
                            ->where('policy_type', $policyType)
                            ->first();
                        return $policy?->config ?? [];
                    })
                    ->dehydrated(false),

                Forms\Components\Placeholder::make("inherited_{$policyType}")
                    ->label('Geerbt von')
                    ->content(function ($record) use ($policyType) {
                        if (!$record) {
                            return new HtmlString('<span class="text-gray-500">Neue Filiale - noch keine Vererbung</span>');
                        }

                        $hasOverride = PolicyConfiguration::where('configurable_type', Branch::class)
                            ->where('configurable_id', $record->id)
                            ->where('policy_type', $policyType)
                            ->exists();

                        if ($hasOverride) {
                            return new HtmlString('<span class="text-primary-600 font-medium">Eigene Konfiguration aktiv</span>');
                        }

                        // Get inherited config from company
                        $inheritedConfig = static::getInheritedPolicyConfig($record, $policyType);
                        if ($inheritedConfig) {
                            $configDisplay = json_encode($inheritedConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                            return new HtmlString(
                                '<div class="text-sm">
                                    <p class="text-gray-700 font-medium mb-2">Geerbt von Unternehmen</p>
                                    <pre class="bg-gray-100 p-2 rounded text-xs overflow-auto max-h-32">' .
                                    htmlspecialchars($configDisplay) .
                                    '</pre>
                                </div>'
                            );
                        }

                        return new HtmlString('<span class="text-gray-500">Systemstandardwerte (keine Konfiguration beim Unternehmen)</span>');
                    })
                    ->visible(fn (Get $get) => $get("override_{$policyType}") !== true),

                Forms\Components\Placeholder::make("hierarchy_info_{$policyType}")
                    ->label('Hierarchie')
                    ->content(new HtmlString(
                        '<div class="text-sm text-gray-600">
                            <p class="font-medium mb-1">Vererbungsreihenfolge:</p>
                            <ol class="list-decimal list-inside space-y-1">
                                <li>Unternehmen</li>
                                <li><strong>Filiale</strong> (aktuelle Ebene)</li>
                                <li>Dienstleistung (erbt von Filiale)</li>
                            </ol>
                        </div>'
                    ))
                    ->columnSpanFull(),
            ])
            ->collapsible()
            ->collapsed();
    }

    /**
     * Get inherited policy configuration for a record
     */
    protected static function getInheritedPolicyConfig($record, string $policyType): ?array
    {
        if (!$record || !$record->company_id) return null;

        // Check if company has a policy configuration
        $companyPolicy = PolicyConfiguration::where('configurable_type', Company::class)
            ->where('configurable_id', $record->company_id)
            ->where('policy_type', $policyType)
            ->first();

        return $companyPolicy?->config;
    }

    /**
     * Save policy configuration for a record
     */
    protected static function savePolicyConfiguration($record, array $data): void
    {
        foreach (['cancellation', 'reschedule', 'recurring'] as $policyType) {
            $overrideKey = "override_{$policyType}";
            $configKey = "policy_config_{$policyType}";

            if (isset($data[$overrideKey]) && $data[$overrideKey]) {
                // Create or update policy configuration
                $config = $data[$configKey] ?? [];

                // Get parent policy ID if exists
                $parentPolicy = PolicyConfiguration::where('configurable_type', Company::class)
                    ->where('configurable_id', $record->company_id)
                    ->where('policy_type', $policyType)
                    ->first();

                PolicyConfiguration::updateOrCreate(
                    [
                        'configurable_type' => Branch::class,
                        'configurable_id' => $record->id,
                        'policy_type' => $policyType,
                    ],
                    [
                        'config' => $config,
                        'is_override' => $parentPolicy ? true : false,
                        'overrides_id' => $parentPolicy?->id,
                    ]
                );
            } else {
                // Remove policy configuration if override is disabled
                PolicyConfiguration::where('configurable_type', Branch::class)
                    ->where('configurable_id', $record->id)
                    ->where('policy_type', $policyType)
                    ->delete();
            }
        }
    }
}