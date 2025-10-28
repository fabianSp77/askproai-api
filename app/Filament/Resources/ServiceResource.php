<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServiceResource\Pages;
use App\Filament\Resources\ServiceResource\RelationManagers;
use App\Models\Service;
use App\Models\Company;
use App\Services\ServiceMatcher;
use App\Support\TooltipBuilder;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use App\Models\PolicyConfiguration;
use Illuminate\Support\HtmlString;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Notifications\Notification;
use Filament\Tables\Filters\Filter;
use App\Services\CalcomService;
use Illuminate\Support\Str;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section as InfoSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\Grid as InfoGrid;
use Filament\Infolists\Components\Split;
use Filament\Infolists\Components\Fieldset;
use App\Filament\Concerns\HasCachedNavigationBadge;

class ServiceResource extends Resource
{
    use HasCachedNavigationBadge;
    protected static ?string $model = Service::class;
    protected static ?string $navigationIcon = 'heroicon-o-briefcase';
    protected static ?string $navigationGroup = 'Stammdaten';
    protected static ?string $navigationLabel = 'Dienstleistungen';
    protected static ?int $navigationSort = 3;

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
                Forms\Components\Section::make('Service-Informationen')
                    ->schema([
                        Forms\Components\Select::make('company_id')
                            ->label(__('services.company'))
                            ->options(Company::pluck('name', 'id'))
                            ->required()
                            ->searchable()
                            ->reactive()
                            ->afterStateUpdated(fn ($state, callable $set) =>
                                $set('branch_id', null)
                            ),

                        Forms\Components\Select::make('branch_id')
                            ->label(__('services.branch'))
                            ->options(function (callable $get) {
                                $companyId = $get('company_id');
                                if (!$companyId) {
                                    return [];
                                }
                                return \App\Models\Branch::where('company_id', $companyId)
                                    ->pluck('name', 'id');
                            })
                            ->searchable()
                            ->visible(fn (callable $get) => $get('company_id')),

                        Forms\Components\TextInput::make('name')
                            ->label('Name (von cal.com)')
                            ->required()
                            ->maxLength(255)
                            ->disabled(fn ($record) => $record && $record->calcom_event_type_id)
                            ->helperText(fn ($record) => $record && $record->calcom_event_type_id ? 'Wird automatisch von cal.com synchronisiert' : 'Manuell eingebbarer Name'),

                        Forms\Components\TextInput::make('display_name')
                            ->label('Anzeigename (optional)')
                            ->maxLength(255)
                            ->placeholder('Leer lassen für cal.com Namen')
                            ->helperText('Optionaler Name für die Plattform-Anzeige'),

                        Forms\Components\Select::make('category')
                            ->options([
                                'consultation' => 'Consultation',
                                'treatment' => 'Treatment',
                                'diagnostic' => 'Diagnostic',
                                'therapy' => 'Therapy',
                                'training' => 'Training',
                                'other' => 'Other',
                            ])
                            ->required(),

                        Forms\Components\Textarea::make('description')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Service-Einstellungen')
                    ->schema([
                        Forms\Components\TextInput::make('duration_minutes')
                            ->numeric()
                            ->default(30)
                            ->required()
                            ->suffix('minutes'),

                        Forms\Components\TextInput::make('buffer_time_minutes')
                            ->numeric()
                            ->default(0)
                            ->suffix('minutes'),

                        Forms\Components\TextInput::make('price')
                            ->numeric()
                            ->prefix('€')
                            ->step(0.01),

                        Forms\Components\TextInput::make('max_bookings_per_day')
                            ->numeric()
                            ->default(10),

                        Forms\Components\Toggle::make('is_active')
                            ->default(true),

                        Forms\Components\Toggle::make('is_online')
                            ->label(__('services.online_booking'))
                            ->default(false),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Komposite Dienstleistung')
                    ->description('Konfigurieren Sie Dienstleistungen mit mehreren Segmenten und Lücken')
                    ->schema([
                        Forms\Components\Toggle::make('composite')
                            ->label('Komposite Dienstleistung aktivieren')
                            ->helperText('Erstellt automatisch Cal.com Event Types für jedes Segment beim Speichern')
                            ->reactive()
                            ->afterStateUpdated(function (Set $set, $state) {
                                if (!$state) {
                                    $set('segments', []);
                                    $set('pause_bookable_policy', null);
                                }
                            }),

                        Forms\Components\Placeholder::make('calcom_sync_info')
                            ->label('ℹ️ Cal.com Synchronisation (automatisch)')
                            ->content(new \Illuminate\Support\HtmlString('
                                <div style="background-color: #eff6ff; padding: 1rem; border-radius: 0.5rem; border: 1px solid #bfdbfe; margin-bottom: 1rem;">
                                    <p style="font-weight: 600; color: #1e40af; margin-bottom: 0.75rem;">Was passiert beim Speichern?</p>

                                    <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                                        <div style="display: flex; gap: 0.5rem;">
                                            <span style="color: #2563eb; font-weight: bold; flex-shrink: 0;">1.</span>
                                            <div>
                                                <p style="font-weight: 500; color: #1e40af; margin-bottom: 0.25rem;">Event Types erstellen</p>
                                                <p style="color: #1e40af; font-size: 0.875rem; margin-bottom: 0.25rem;">Für jedes Segment wird ein Cal.com Event Type erstellt</p>
                                                <p style="color: #3b82f6; font-size: 0.875rem;">Beispiel: "Herrenhaarschnitt: Waschen (1 von 3) - Friseur 1"</p>
                                            </div>
                                        </div>

                                        <div style="display: flex; gap: 0.5rem;">
                                            <span style="color: #2563eb; font-weight: bold; flex-shrink: 0;">2.</span>
                                            <div>
                                                <p style="font-weight: 500; color: #1e40af; margin-bottom: 0.25rem;">Hosts zuweisen</p>
                                                <p style="color: #1e40af; font-size: 0.875rem; margin-bottom: 0.25rem;">Alle Team-Mitarbeiter werden automatisch zugewiesen</p>
                                                <p style="color: #3b82f6; font-size: 0.875rem;">Event Types sind sofort buchbar</p>
                                            </div>
                                        </div>

                                        <div style="display: flex; gap: 0.5rem;">
                                            <span style="color: #2563eb; font-weight: bold; flex-shrink: 0;">3.</span>
                                            <div>
                                                <p style="font-weight: 500; color: #1e40af; margin-bottom: 0.25rem;">Daten synchronisieren</p>
                                                <p style="color: #1e40af; font-size: 0.875rem;">Service wird mit Cal.com Event Type IDs verknüpft</p>
                                            </div>
                                        </div>
                                    </div>

                                    <div style="padding-top: 0.75rem; margin-top: 0.75rem; border-top: 1px solid #93c5fd;">
                                        <p style="font-size: 0.875rem; color: #2563eb; font-weight: 500; margin-bottom: 0.25rem;">📍 Wo Sie die Event Types finden:</p>
                                        <p style="font-size: 0.875rem; color: #1e40af;">Cal.com Dashboard → Event Types → Filter: <span style="font-family: monospace; background-color: #dbeafe; padding: 0.125rem 0.25rem; border-radius: 0.25rem;">Hidden</span></p>
                                    </div>
                                </div>
                            '))
                            ->columnSpanFull()
                            ->visible(fn (Get $get): bool => (bool) $get('composite')),

                        Forms\Components\Select::make('composite_template')
                            ->label('Service-Template verwenden')
                            ->placeholder('Vorlage auswählen...')
                            ->options([
                                'hairdresser_premium' => '🎨 Friseur Premium (2h 40min mit Pausen)',
                                'hairdresser_express' => '✂️ Friseur Express (90min ohne Pausen)',
                                'spa_wellness' => '💆 Spa Wellness (3h mit Pausen)',
                                'medical_treatment' => '⚕️ Medizinische Behandlung (2h mit Nachsorge)',
                                'beauty_complete' => '💅 Beauty Komplett (4h mit mehreren Pausen)',
                            ])
                            ->visible(fn (Get $get): bool => (bool) $get('composite'))
                            ->reactive()
                            ->afterStateUpdated(function (Set $set, $state) {
                                if (!$state) return;

                                switch ($state) {
                                    case 'hairdresser_premium':
                                        $set('name', 'Premium Friseur Komplettpaket');
                                        $set('description', 'Komplette Haarbehandlung mit Waschen, Schneiden, Färben und Styling');
                                        $set('duration_minutes', 160);
                                        $set('segments', [
                                            [
                                                'key' => 'A',
                                                'name' => 'Waschen & Vorbereitung',
                                                'duration' => 30,
                                                'gap_after' => 20,
                                                'preferSameStaff' => true
                                            ],
                                            [
                                                'key' => 'B',
                                                'name' => 'Schneiden/Styling',
                                                'duration' => 60,
                                                'gap_after' => 20,
                                                'preferSameStaff' => true
                                            ],
                                            [
                                                'key' => 'C',
                                                'name' => 'Finishing & Final Styling',
                                                'duration' => 30,
                                                'gap_after' => 0,
                                                'preferSameStaff' => true
                                            ]
                                        ]);
                                        $set('pause_bookable_policy', 'never');
                                        break;

                                    case 'hairdresser_express':
                                        $set('name', 'Express Haarschnitt');
                                        $set('description', 'Schneller professioneller Haarschnitt ohne Extras');
                                        $set('duration_minutes', 90);
                                        $set('segments', [
                                            [
                                                'key' => 'A',
                                                'name' => 'Waschen & Schneiden',
                                                'duration' => 45,
                                                'gap_after' => 0,
                                                'preferSameStaff' => true
                                            ],
                                            [
                                                'key' => 'B',
                                                'name' => 'Styling & Finishing',
                                                'duration' => 45,
                                                'gap_after' => 0,
                                                'preferSameStaff' => true
                                            ]
                                        ]);
                                        $set('pause_bookable_policy', 'never');
                                        break;

                                    case 'spa_wellness':
                                        $set('name', 'Spa Wellness Deluxe');
                                        $set('description', 'Entspannende Wellness-Behandlung mit mehreren Stationen');
                                        $set('duration_minutes', 210);
                                        $set('segments', [
                                            [
                                                'key' => 'A',
                                                'name' => 'Empfang & Vorbereitung',
                                                'duration' => 15,
                                                'gap_after' => 10,
                                                'preferSameStaff' => false
                                            ],
                                            [
                                                'key' => 'B',
                                                'name' => 'Massage',
                                                'duration' => 60,
                                                'gap_after' => 30,
                                                'preferSameStaff' => false
                                            ],
                                            [
                                                'key' => 'C',
                                                'name' => 'Gesichtsbehandlung',
                                                'duration' => 45,
                                                'gap_after' => 15,
                                                'preferSameStaff' => false
                                            ],
                                            [
                                                'key' => 'D',
                                                'name' => 'Abschluss & Ruhezeit',
                                                'duration' => 30,
                                                'gap_after' => 0,
                                                'preferSameStaff' => false
                                            ]
                                        ]);
                                        $set('pause_bookable_policy', 'never');
                                        break;
                                }

                                // Reset template selection after applying
                                $set('composite_template', null);
                            })
                            ->helperText('Wählen Sie eine Vorlage für schnelle Konfiguration'),

                        Forms\Components\Repeater::make('segments')
                            ->label('Service-Segmente')
                            ->schema([
                                Forms\Components\TextInput::make('key')
                                    ->label('Segment-Schlüssel')
                                    ->disabled()
                                    ->dehydrated()
                                    ->default(function (Get $get) {
                                        $segments = $get('../../segments') ?? [];
                                        $keys = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'];
                                        foreach ($keys as $key) {
                                            $exists = false;
                                            foreach ($segments as $segment) {
                                                if (($segment['key'] ?? '') === $key) {
                                                    $exists = true;
                                                    break;
                                                }
                                            }
                                            if (!$exists) {
                                                return $key;
                                            }
                                        }
                                        return 'K'; // Fallback if somehow we have more than 10 segments
                                    })
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('name')
                                    ->label(__('services.segment_name'))
                                    ->required()
                                    ->maxLength(100)
                                    ->placeholder(__('services.placeholders.segment_name'))
                                    ->distinct() // Ensure unique segment names
                                    ->validationMessages([
                                        'distinct' => 'Segment names must be unique',
                                        'required' => 'Segment name is required',
                                        'max' => 'Segment name must not exceed 100 characters',
                                    ])
                                    ->columnSpan(2),

                                Forms\Components\TextInput::make('duration')
                                    ->label(__('services.segment_duration'))
                                    ->numeric()
                                    ->required()
                                    ->minValue(5)
                                    ->maxValue(480)
                                    ->default(30)
                                    ->rules(['integer', 'min:5', 'max:480'])
                                    ->validationMessages([
                                        'min' => 'Duration must be at least 5 minutes',
                                        'max' => 'Duration cannot exceed 8 hours (480 minutes)',
                                        'required' => 'Duration is required for each segment',
                                    ])
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('gap_after')
                                    ->label(__('services.gap_after'))
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(240)
                                    ->default(0)
                                    ->rules(['integer', 'min:0', 'max:240'])
                                    ->helperText(__('services.gap_helper'))
                                    ->validationMessages([
                                        'min' => 'Gap cannot be negative',
                                        'max' => 'Gap cannot exceed 4 hours (240 minutes)',
                                    ])
                                    ->columnSpan(1),
                            ])
                            ->columns(5)
                            ->visible(fn (Get $get): bool => (bool) $get('composite'))
                            ->defaultItems(0)
                            ->minItems(2) // Composite services need at least 2 segments
                            ->maxItems(10) // Maximum 10 segments
                            ->addActionLabel('Add Segment')
                            ->reorderable()
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string =>
                                isset($state['key'], $state['name'])
                                    ? "{$state['key']}: {$state['name']}"
                                    : null
                            )
                            ->helperText(new \Illuminate\Support\HtmlString(
                                __('services.segments_helper') .
                                '<br><span class="text-primary-600 dark:text-primary-400 font-medium">→ Jedes Segment wird als eigener Cal.com Event Type erstellt (Hidden)</span>'
                            ))
                            ->validationMessages([
                                'min' => 'Composite services require at least 2 segments',
                                'max' => 'Maximum 10 segments allowed per service',
                            ]),

                        Forms\Components\Select::make('pause_bookable_policy')
                            ->label(__('services.gap_policy'))
                            ->options([
                                'free' => 'Staff can be booked during gaps',
                                'blocked' => 'Staff unavailable during gaps',
                                'flexible' => 'Depends on availability',
                            ])
                            ->visible(fn (Get $get): bool => (bool) $get('composite'))
                            ->helperText(__('services.gap_policy_helper')),

                        Forms\Components\Placeholder::make('total_duration_info')
                            ->label(__('services.total_duration'))
                            ->visible(fn (Get $get): bool => (bool) $get('composite'))
                            ->content(function (Get $get): \Illuminate\Support\HtmlString {
                                $segments = $get('segments') ?? [];
                                if (empty($segments)) {
                                    return new \Illuminate\Support\HtmlString('<span class="text-gray-500">No segments configured</span>');
                                }

                                $totalActive = 0;
                                $totalGaps = 0;

                                foreach ($segments as $index => $segment) {
                                    $totalActive += (int)($segment['duration'] ?? 0);
                                    if ($index < count($segments) - 1) {
                                        $totalGaps += (int)($segment['gap_after'] ?? 0);
                                    }
                                }

                                $total = $totalActive + $totalGaps;

                                if ($total === 0) {
                                    return new \Illuminate\Support\HtmlString('<span class="text-gray-500">No duration configured</span>');
                                }

                                // Validation warnings
                                $warnings = [];
                                if ($total > 480) {
                                    $warnings[] = '<span class="text-danger-600">⚠️ Total duration exceeds 8 hours</span>';
                                }
                                if ($totalGaps > $totalActive) {
                                    $warnings[] = '<span class="text-warning-600">⚠️ Gap time exceeds active service time</span>';
                                }
                                if (count($segments) < 2 && count($segments) > 0) {
                                    $warnings[] = '<span class="text-danger-600">⚠️ Minimum 2 segments required</span>';
                                }

                                $output = sprintf(
                                    '<div class="space-y-2">
                                        <div class="font-semibold">
                                            Active: <span class="text-primary-600">%d min</span> |
                                            Gaps: <span class="text-gray-600">%d min</span> |
                                            Total: <span class="%s">%d min (%s)</span>
                                        </div>
                                        %s
                                    </div>',
                                    $totalActive,
                                    $totalGaps,
                                    $total > 480 ? 'text-danger-600 font-bold' : 'text-success-600',
                                    $total,
                                    $total >= 60 ? round($total / 60, 1) . ' hours' : $total . ' minutes',
                                    !empty($warnings) ? '<div class="mt-2">' . implode('<br>', $warnings) . '</div>' : ''
                                );

                                return new \Illuminate\Support\HtmlString($output);
                            }),
                    ])
                    ->collapsible()
                    ->collapsed(),

                Forms\Components\Section::make('📅 Cal.com Mitarbeiter (Automatisch)')
                    ->description('Mitarbeiter aus Cal.com - Automatisch abgerufen und mit lokalen Mitarbeitern verbunden')
                    ->schema([
                        Forms\Components\ViewField::make('calcom_hosts_info')
                            ->view('components.calcom-hosts-display-form')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(),

                // Legacy staff section (hidden but kept for backward compatibility)
                Forms\Components\Section::make('Mitarbeiterzuweisung (Legacy)')
                    ->description('⚠️ Diese Funktion ist veraltet. Bitte verwenden Sie stattdessen die Cal.com Mitarbeiter-Section oben.')
                    ->schema([
                        Forms\Components\Repeater::make('staff')
                            ->relationship()
                            ->schema([
                                Forms\Components\Select::make('id')
                                    ->label(__('services.staff_member'))
                                    ->options(function () {
                                        // NOTE: Temporarily allowing cross-company staff assignment
                                        // because staff can work for multiple companies/branches
                                        // TODO: Implement many-to-many staff_company table for proper multi-tenant staff
                                        return \App\Models\Staff::query()
                                            ->where('is_active', true)
                                            ->orderBy('name')
                                            ->pluck('name', 'id');
                                    })
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->columnSpan(2),

                                Forms\Components\Toggle::make('pivot.is_primary')
                                    ->label(__('services.primary_staff'))
                                    ->helperText(__('services.primary_helper'))
                                    ->columnSpan(1),

                                Forms\Components\Toggle::make('pivot.can_book')
                                    ->label(__('services.can_book'))
                                    ->default(true)
                                    ->columnSpan(1),

                                Forms\Components\Select::make('pivot.allowed_segments')
                                    ->label(__('services.allowed_segments'))
                                    ->multiple()
                                    ->options(function (Get $get) {
                                        try {
                                            $isComposite = $get('../../composite');
                                            if (!$isComposite) {
                                                return [];
                                            }

                                            $segments = $get('../../segments') ?? [];
                                            $options = [];

                                            if (is_array($segments)) {
                                                foreach ($segments as $segment) {
                                                    if (isset($segment['key']) && isset($segment['name'])) {
                                                        $options[$segment['key']] = "{$segment['key']}: {$segment['name']}";
                                                    }
                                                }
                                            }
                                            return $options;
                                        } catch (\Exception $e) {
                                            // Silently fail - field will be hidden anyway
                                            return [];
                                        }
                                    })
                                    ->visible(function (Get $get): bool {
                                        try {
                                            return (bool) $get('../../composite');
                                        } catch (\Exception $e) {
                                            return false;
                                        }
                                    })
                                    ->helperText(__('services.segments_helper'))
                                    ->columnSpan(2),

                                Forms\Components\Select::make('pivot.skill_level')
                                    ->label(__('services.skill_level'))
                                    ->options([
                                        'junior' => 'Junior',
                                        'regular' => 'Regular',
                                        'senior' => 'Senior',
                                        'expert' => 'Expert',
                                    ])
                                    ->default('regular')
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('pivot.weight')
                                    ->label(__('services.weight'))
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(9.99)
                                    ->step(0.01)
                                    ->default(1.0)
                                    ->helperText(__('services.weight_helper'))
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('pivot.custom_duration_minutes')
                                    ->label(__('services.custom_duration'))
                                    ->numeric()
                                    ->minValue(5)
                                    ->maxValue(480)
                                    ->placeholder(__('services.custom_duration_placeholder'))
                                    ->helperText(__('services.custom_duration_helper'))
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('pivot.custom_price')
                                    ->label('Benutzerdefinierter Preis (€)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->step(0.01)
                                    ->placeholder('Standard verwenden')
                                    ->helperText('Standardpreis überschreiben')
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('pivot.commission_rate')
                                    ->label('Provision (%)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->step(0.01)
                                    ->placeholder('0')
                                    ->columnSpan(1),

                                Forms\Components\Textarea::make('pivot.specialization_notes')
                                    ->label('Notizen')
                                    ->rows(2)
                                    ->columnSpan(3),
                            ])
                            ->columns(3)
                            ->defaultItems(0)
                            ->addActionLabel('Mitarbeiter hinzufügen')
                            ->reorderable()
                            ->collapsible()
                            ->itemLabel(function (array $state): ?string {
                                // Safe itemLabel that doesn't cause 500 errors
                                if (empty($state['id']) || !isset($state['id'])) {
                                    return 'Neuer Mitarbeiter';
                                }

                                // Return just the ID with safe string
                                return "Staff ID: {$state['id']}";
                            }),
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->visible(true), // Show because Cal.com sync may not be configured yet

                Forms\Components\Section::make('Cal.com Integration')
                    ->schema([
                        Forms\Components\TextInput::make('calcom_event_type_id')
                            ->label('Cal.com Ereignistyp-ID')
                            ->disabled()
                            ->placeholder('Nicht synchronisiert'),

                        Forms\Components\Placeholder::make('sync_status')
                            ->label('Synchronisierungsstatus')
                            ->content(function ($record) {
                                if (!$record) return 'New service';
                                if ($record->calcom_event_type_id) {
                                    return '✅ Synced with Cal.com';
                                }
                                return '⚠️ Nicht synchronisiert';
                            }),
                    ])
                    ->collapsible(),

                Forms\Components\Section::make('Zuweisungsinformationen')
                    ->schema([
                        Forms\Components\Placeholder::make('assignment_status')
                            ->label('Zuweisungsstatus')
                            ->content(function ($record) {
                                if (!$record) return 'New service';
                                if (!$record->company_id) return '❌ Not assigned';
                                return $record->formatted_assignment_status;
                            }),

                        Forms\Components\Placeholder::make('assignment_confidence')
                            ->label('Konfidenz')
                            ->content(fn ($record) => $record && $record->assignment_confidence
                                ? "{$record->assignment_confidence}%"
                                : 'N/A'),

                        Forms\Components\Textarea::make('assignment_notes')
                            ->label('Zuweisungsnotizen')
                            ->rows(3)
                            ->disabled()
                            ->columnSpanFull(),

                        Forms\Components\Placeholder::make('assignment_date')
                            ->label('Zugewiesen am')
                            ->content(fn ($record) => $record && $record->assignment_date
                                ? $record->assignment_date->format('d.m.Y H:i')
                                : 'N/A'),

                        Forms\Components\Placeholder::make('assigned_by')
                            ->label('Zugewiesen von')
                            ->content(fn ($record) => $record && $record->assignedBy
                                ? $record->assignedBy->name
                                : 'N/A'),
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->visible(fn ($record) => $record && $record->assignment_method),

                Forms\Components\Section::make('Richtlinien')
                    ->icon('heroicon-m-shield-check')
                    ->description('Konfigurieren Sie die Richtlinien für diese Dienstleistung')
                    ->schema([
                        static::getPolicySection('cancellation', 'Stornierungsrichtlinie'),
                        static::getPolicySection('reschedule', 'Umbuchungsrichtlinie'),
                        static::getPolicySection('recurring', 'Wiederholungsrichtlinie'),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) =>
                $query->with([
                    'company:id,name',
                    'branch:id,name',
                    'assignedBy:id,name',
                    'staff' => fn ($q) => $q->select('staff.id', 'staff.name')
                        ->wherePivot('is_active', true)
                        ->orderByPivot('is_primary', 'desc')
                ])
                ->withCount([
                    'appointments as total_appointments',
                    'appointments as upcoming_appointments' => fn ($q) =>
                        $q->where('starts_at', '>=', now()),
                    'appointments as completed_appointments' => fn ($q) =>
                        $q->where('status', 'completed'),
                    'appointments as cancelled_appointments' => fn ($q) =>
                        $q->where('status', 'cancelled'),
                    'staff as staff_count'
                ])
            )
            ->columns([
                Tables\Columns\TextColumn::make('company.name')
                    ->label('Unternehmen')
                    ->sortable()
                    ->searchable()
                    ->weight('bold')
                    ->color(fn ($record) => $record->assignment_method ? 'primary' : 'danger')
                    ->description(function ($record) {
                        $parts = [];

                        // Show assignment method
                        if ($record->assignment_method) {
                            $parts[] = "Method: {$record->assignment_method}";
                        } else {
                            $parts[] = 'Not assigned';
                        }

                        // Show Team ID if exists
                        if ($record->company?->calcom_team_id) {
                            $parts[] = "Team ID: {$record->company->calcom_team_id}";
                        }

                        return implode(' | ', $parts);
                    })
                    ->tooltip(function ($record) {
                        if (!$record->company) return null;

                        $parts = [
                            "ID: {$record->company_id}",
                        ];

                        if ($record->company->calcom_team_id) {
                            $parts[] = "Cal.com Team: {$record->company->calcom_team_id}";
                        }

                        // Check mapping consistency
                        if ($record->calcom_event_type_id) {
                            $mapping = \Illuminate\Support\Facades\DB::table('calcom_event_mappings')
                                ->where('calcom_event_type_id', $record->calcom_event_type_id)
                                ->first();

                            if ($mapping && $mapping->calcom_team_id != $record->company->calcom_team_id) {
                                $parts[] = "⚠️ WARNUNG: Team ID Mismatch!";
                                $parts[] = "Service Event Type Team: {$mapping->calcom_team_id}";
                                $parts[] = "Company Team: {$record->company->calcom_team_id}";
                            }
                        }

                        return implode("\n", $parts);
                    }),

                Tables\Columns\TextColumn::make('display_name')
                    ->label('Dienstleistung')
                    ->getStateUsing(fn ($record) => $record->display_name ?? $record->name)
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->lineClamp(2)
                    ->tooltip(function ($record) {
                        $builder = TooltipBuilder::make();

                        // Section 1: IDs
                        $idContent = $builder->keyValue('Service ID', $record->id, true);
                        if ($record->calcom_event_type_id) {
                            $idContent .= '<br>' . $builder->keyValue('Cal.com Event Type', $record->calcom_event_type_id, true);
                        }
                        $builder->section('🆔 Identifiers', $idContent);

                        // Section 2: Pausen (only if composite service)
                        if ($record->composite && !empty($record->segments)) {
                            $segments = is_string($record->segments)
                                ? json_decode($record->segments, true)
                                : $record->segments;

                            if (is_array($segments)) {
                                $pauseItems = [];
                                foreach ($segments as $index => $segment) {
                                    $gap = (int)($segment['gap_after'] ?? 0);
                                    if ($gap > 0 && $index < count($segments) - 1) {
                                        $segmentName = $segment['name'] ?? "Schritt " . ($index + 1);
                                        $pauseItems[] = "{$segmentName}: <strong>+{$gap} min</strong>";
                                    }
                                }

                                if (!empty($pauseItems)) {
                                    $builder->section('⏱️ Pausen (Einwirkzeiten)', $builder->list($pauseItems));
                                }
                            }
                        }

                        // Section 3: Availability Policy
                        $policy = $record->availability_gap_policy ?? 'blocked';
                        $policyBadge = match($policy) {
                            'free' => $builder->badge('FREI buchbar', 'success'),
                            'flexible' => $builder->badge('FLEXIBEL buchbar', 'warning'),
                            'blocked' => $builder->badge('RESERVIERT', 'error'),
                            default => $builder->badge('Unbekannt', 'gray'),
                        };

                        $policyExplanation = match($policy) {
                            'free' => 'Andere Services können während Einwirkzeit gebucht werden',
                            'flexible' => 'Zeitfenster flexibel, abhängig von Auslastung',
                            'blocked' => 'Zeitfenster während Einwirkzeit blockiert',
                            default => 'Keine Verfügbarkeitspolicy definiert',
                        };

                        $builder->section('📅 Verfügbarkeit während Einwirkzeit',
                            $policyBadge . '<div class="text-xs text-gray-500 dark:text-gray-400 mt-1">' . $policyExplanation . '</div>');

                        return $builder->build();
                    })
                    ->description(fn ($record) =>
                        ($record->calcom_name && $record->display_name ?
                            "Cal.com: " . Str::limit($record->calcom_name, 40, '...') : '') .
                        ($record->category ? " | {$record->category}" : ''))
                    ->extraAttributes([
                        'style' => 'max-width: 300px; word-wrap: break-word;'
                    ]),

                Tables\Columns\TextColumn::make('assignment_confidence')
                    ->label('Konfidenz')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? "{$state}%" : '-')
                    ->colors([
                        'success' => fn ($state) => $state >= 80,
                        'warning' => fn ($state) => $state >= 60 && $state < 80,
                        'danger' => fn ($state) => $state > 0 && $state < 60,
                    ]),

                Tables\Columns\TextColumn::make('sync_status')
                    ->label('Synchronisierungsstatus')
                    ->badge()
                    ->formatStateUsing(function (string $state, $record) {
                        if ($state === 'synced' && $record->last_calcom_sync) {
                            return "✓ Sync (" . $record->last_calcom_sync->diffForHumans() . ")";
                        }
                        return match($state) {
                            'synced' => '✓ Synchronisiert',
                            'pending' => 'Ausstehend',
                            'error' => 'Fehler',
                            'never' => 'Nie synchronisiert',
                            default => $state,
                        };
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'synced' => 'success',
                        'pending' => 'warning',
                        'error' => 'danger',
                        'never' => 'gray',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): ?string => match($state) {
                        'synced' => 'heroicon-o-check-circle',
                        'pending' => 'heroicon-o-clock',
                        'error' => 'heroicon-o-x-circle',
                        'never' => 'heroicon-o-minus-circle',
                        default => null,
                    })
                    ->tooltip(function ($record): ?string {
                        $builder = TooltipBuilder::make();

                        // Event Type ID
                        if ($record->calcom_event_type_id) {
                            $eventContent = $builder->keyValue('Event Type ID', $record->calcom_event_type_id, true);
                            $builder->section('🆔 Cal.com Verbindung', $eventContent);
                        }

                        // Last Sync Info
                        if ($record->last_calcom_sync) {
                            $syncContent = $builder->keyValue('Letzter Sync', $record->last_calcom_sync->format('d.m.Y H:i'));
                            $syncContent .= '<br><span class="text-xs text-gray-500 dark:text-gray-400">' . $record->last_calcom_sync->diffForHumans() . '</span>';
                            $builder->section('🔄 Synchronisation', $syncContent);
                        }

                        // Error Info
                        if ($record->sync_error) {
                            $errorContent = '<div class="text-sm text-red-600 dark:text-red-400">' . htmlspecialchars(Str::limit($record->sync_error, 150)) . '</div>';
                            $builder->section('⚠️ Fehler', $errorContent);
                        }

                        // No sync info
                        if (!$record->calcom_event_type_id && !$record->last_calcom_sync) {
                            return TooltipBuilder::simple('Keine Cal.com Synchronisation konfiguriert', '📅');
                        }

                        return $builder->build();
                    })
                    ->searchable(query: function ($query, $search) {
                        return $query->where('calcom_event_type_id', 'like', "%{$search}%");
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('formatted_sync_status')
                    ->label('Letzte Synchronisation')
                    ->getStateUsing(fn ($record) => $record->formatted_sync_status)
                    ->description(fn ($record) =>
                        $record->sync_error ?
                        Str::limit($record->sync_error, 50) : null
                    )
                    ->color(fn ($state) =>
                        str_contains($state, '✅') ? 'success' :
                        (str_contains($state, '❌') ? 'danger' : 'warning')
                    ),

                Tables\Columns\TextColumn::make('duration_minutes')
                    ->label('Dauer')
                    ->formatStateUsing(function ($state, $record) {
                        if ($record->composite && !empty($record->segments)) {
                            $totalActive = 0;
                            $totalGaps = 0;
                            $segments = is_string($record->segments)
                                ? json_decode($record->segments, true)
                                : $record->segments;

                            if (is_array($segments)) {
                                foreach ($segments as $index => $segment) {
                                    $totalActive += (int)($segment['duration'] ?? 0);
                                    if ($index < count($segments) - 1) {
                                        $totalGaps += (int)($segment['gap_after'] ?? 0);
                                    }
                                }
                                $total = $totalActive + $totalGaps;
                                return "{$total} min (📋 {$totalActive}+{$totalGaps})";
                            }
                        }
                        return $state . ' min';
                    })
                    ->sortable()
                    ->alignCenter()
                    ->tooltip(function ($record) {
                        $builder = TooltipBuilder::make();

                        $activeDuration = 0;
                        $gapDuration = 0;

                        // Calculate durations for composite services
                        if ($record->composite && !empty($record->segments)) {
                            $segments = is_string($record->segments)
                                ? json_decode($record->segments, true)
                                : $record->segments;

                            if (is_array($segments)) {
                                foreach ($segments as $index => $segment) {
                                    $activeDuration += (int)($segment['duration'] ?? 0);
                                    if ($index < count($segments) - 1) {
                                        $gapDuration += (int)($segment['gap_after'] ?? 0);
                                    }
                                }
                            }
                        } else {
                            $activeDuration = $record->duration_minutes ?? 0;
                            $gapDuration = $record->gap_duration ?? 0;
                        }

                        $totalDuration = $activeDuration + $gapDuration;

                        if ($totalDuration === 0) {
                            return TooltipBuilder::simple('Keine Dauer festgelegt', '⏱️');
                        }

                        // Calculate percentages
                        $activePercent = $totalDuration > 0 ? round(($activeDuration / $totalDuration) * 100) : 0;
                        $gapPercent = $totalDuration > 0 ? round(($gapDuration / $totalDuration) * 100) : 0;

                        // Build breakdown
                        $breakdown = sprintf(
                            '<div class="space-y-3">
                                <div>
                                    <div class="flex justify-between mb-1">
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">⚡ Aktive Behandlung</span>
                                        <span class="text-sm font-bold text-gray-900 dark:text-gray-100">%d min</span>
                                    </div>
                                    %s
                                </div>
                                <div>
                                    <div class="flex justify-between mb-1">
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">💤 Einwirkzeit</span>
                                        <span class="text-sm font-bold text-gray-900 dark:text-gray-100">%d min</span>
                                    </div>
                                    %s
                                </div>
                                <div class="pt-2 border-t border-gray-200 dark:border-gray-700">
                                    <div class="flex justify-between">
                                        <span class="text-sm font-semibold text-gray-800 dark:text-gray-200">⏱️ Gesamtzeit</span>
                                        <span class="text-sm font-bold text-gray-900 dark:text-gray-100">%d min</span>
                                    </div>
                                </div>
                            </div>',
                            $activeDuration,
                            $builder->progressBar($activePercent, 'success'),
                            $gapDuration,
                            $builder->progressBar($gapPercent, 'warning'),
                            $totalDuration
                        );

                        $builder->section('🔢 Gesamtdauer Breakdown', $breakdown);

                        if ($gapDuration > 0) {
                            $builder->section('ℹ️ Info',
                                '<div class="text-xs text-gray-600 dark:text-gray-400">Einwirkzeit = Wartezeit zwischen Behandlungsschritten</div>');
                        }

                        return $builder->build();
                    }),

                Tables\Columns\IconColumn::make('composite')
                    ->label('Komposit')
                    ->boolean()
                    ->trueIcon('heroicon-o-squares-2x2')
                    ->falseIcon('heroicon-o-stop')
                    ->trueColor('info')
                    ->sortable(),

                Tables\Columns\TextColumn::make('pricing')
                    ->label('Preis')
                    ->getStateUsing(function ($record) {
                        $price = number_format($record->price, 2) . ' €';

                        if ($record->duration_minutes > 0) {
                            $hourlyRate = number_format($record->price / ($record->duration_minutes / 60), 2);
                            $price .= " ({$hourlyRate} €/h)";
                        }

                        if ($record->deposit_required) {
                            $price .= " 💰";
                        }

                        return $price;
                    })
                    ->description(fn ($record) =>
                        $record->deposit_required
                            ? "Anzahlung: " . number_format($record->deposit_amount, 2) . " €"
                            : null
                    )
                    ->tooltip(function ($record) {
                        $builder = TooltipBuilder::make();

                        // Section 1: Price Information
                        $priceContent = $builder->keyValue('Grundpreis', number_format($record->price, 2) . ' €');

                        if ($record->duration_minutes > 0) {
                            $hourlyRate = number_format($record->price / ($record->duration_minutes / 60), 2);
                            $priceContent .= '<br>' . $builder->keyValue('Stundensatz', $hourlyRate . ' €/h');
                        }

                        if ($record->deposit_required) {
                            $priceContent .= '<br>' . $builder->keyValue('Anzahlung', number_format($record->deposit_amount, 2) . ' €', false);
                            $priceContent .= '<br>' . $builder->badge('Anzahlung erforderlich', 'warning');
                        }

                        $builder->section('💰 Preis & Konditionen', $priceContent);

                        return $builder->build();
                    })
                    ->sortable(query: function ($query, $direction) {
                        $query->orderBy('price', $direction);
                    }),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_online')
                    ->label('Online')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('appointment_stats')
                    ->label('Termine & Umsatz')
                    ->getStateUsing(function ($record) {
                        $count = $record->appointments()->count();
                        // Calculate revenue: completed appointments * service price
                        $completedCount = $record->appointments()
                            ->where('status', 'completed')
                            ->count();
                        $revenue = $completedCount * ($record->price ?? 0);

                        return "{$count} Termine • " . number_format($revenue, 0) . " €";
                    })
                    ->badge()
                    ->color(function ($record) {
                        $count = $record->appointments()->count();
                        return $count > 10 ? 'success' : ($count > 0 ? 'info' : 'gray');
                    })
                    ->description(function ($record) {
                        $recent = $record->appointments()
                            ->where('created_at', '>=', now()->subDays(30))
                            ->count();

                        return $recent > 0 ? "📈 {$recent} neue (30 Tage)" : null;
                    })
                    ->tooltip(function ($record) {
                        $builder = TooltipBuilder::make();

                        // Get statistics
                        $total = $record->appointments()->count();
                        $completed = $record->appointments()->where('status', 'completed')->count();
                        $cancelled = $record->appointments()->where('status', 'cancelled')->count();
                        $scheduled = $record->appointments()->where('status', 'scheduled')->count();
                        $revenue = $completed * ($record->price ?? 0);

                        // Stats list
                        $stats = $builder->keyValue('Gesamt', $total);
                        $stats .= '<br>' . $builder->keyValue('Abgeschlossen', $completed) . ' ' . $builder->badge($completed, 'success');
                        $stats .= '<br>' . $builder->keyValue('Geplant', $scheduled) . ' ' . $builder->badge($scheduled, 'info');
                        if ($cancelled > 0) {
                            $stats .= '<br>' . $builder->keyValue('Storniert', $cancelled) . ' ' . $builder->badge($cancelled, 'error');
                        }

                        $builder->section('📊 Termine', $stats);

                        // Revenue section
                        $revenueContent = $builder->keyValue('Gesamtumsatz', number_format($revenue, 2) . ' €', true);
                        if ($completed > 0) {
                            $avgRevenue = $revenue / $completed;
                            $revenueContent .= '<br>' . $builder->keyValue('Ø pro Termin', number_format($avgRevenue, 2) . ' €', true);
                        }
                        $builder->section('💰 Umsatz', $revenueContent);

                        return $builder->build();
                    })
                    ->sortable(query: function ($query, $direction) {
                        $query->withCount('appointments')
                            ->orderBy('appointments_count', $direction);
                    })
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('staff_assignment')
                    ->label('Mitarbeiter')
                    ->getStateUsing(function ($record) {
                        $staff = $record->allowedStaff;

                        if ($staff->isEmpty()) {
                            return '👥 Keine zugewiesen';
                        }

                        $count = $staff->count();
                        if ($count > 3) {
                            $first = $staff->take(3)->pluck('name')->join(', ');
                            return "{$first} (+" . ($count - 3) . " weitere)";
                        }

                        return $staff->pluck('name')->join(', ');
                    })
                    ->badge()
                    ->color(fn ($record) =>
                        $record->allowedStaff->isEmpty() ? 'gray' : 'success'
                    )
                    ->tooltip(function ($record) {
                        $staff = $record->allowedStaff;

                        if ($staff->isEmpty()) {
                            return TooltipBuilder::simple('Keine Mitarbeiter für diesen Service zugewiesen', '👥');
                        }

                        $builder = TooltipBuilder::make();
                        $staffList = '';

                        foreach ($staff as $member) {
                            $badges = [];

                            // PRIMARY badge (green)
                            if ($member->pivot->is_primary) {
                                $badges[] = $builder->badge('⭐ PRIMARY', 'success');
                            }

                            // Bookable status (blue/gray)
                            if ($member->pivot->can_book) {
                                $badges[] = $builder->badge('✓ Buchbar', 'info');
                            } else {
                                $badges[] = $builder->badge('Nicht buchbar', 'gray');
                            }

                            // Build staff member card
                            $staffList .= sprintf(
                                '<div class="flex flex-col gap-1.5 py-2 border-b border-gray-200 dark:border-gray-700 last:border-0">
                                    <div class="font-medium text-gray-900 dark:text-gray-100">%s</div>
                                    <div class="flex flex-wrap items-center gap-1.5">%s</div>
                                    %s
                                </div>',
                                htmlspecialchars($member->name),
                                implode(' ', $badges),
                                $member->calcom_user_id
                                    ? '<div class="text-xs text-gray-500 dark:text-gray-400 font-mono">Cal.com ID: ' . htmlspecialchars($member->calcom_user_id) . '</div>'
                                    : ''
                            );
                        }

                        $builder->section('👥 Zugewiesene Mitarbeiter (' . $staff->count() . ')', $staffList);
                        return $builder->build();
                    })
                    ->sortable(query: function ($query, $direction) {
                        $query->withCount('allowedStaff')
                            ->orderBy('allowed_staff_count', $direction);
                    })
                    ->searchable(query: function ($query, string $search) {
                        $query->whereHas('allowedStaff', function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%");
                        });
                    }),
            ])
            ->defaultSort('company.name')
            ->filters([
                Filter::make('advanced_search')
                    ->form([
                        Forms\Components\TextInput::make('search')
                            ->placeholder('Intelligente Suche: versuchen Sie "Beratung", "50 Min", "€100", etc.')
                            ->helperText('Durchsucht Name, Beschreibung, Kategorie, Preis, Dauer mit unscharfer Übereinstimmung'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['search'])) {
                            return $query;
                        }

                        $search = strtolower($data['search']);
                        $searchWildcard = "%{$search}%";

                        return $query->where(function ($q) use ($search, $searchWildcard) {
                            // Text-based searches
                            $q->whereRaw('LOWER(name) LIKE ?', [$searchWildcard])
                              ->orWhereRaw('LOWER(description) LIKE ?', [$searchWildcard])
                              ->orWhereRaw('LOWER(category) LIKE ?', [$searchWildcard])
                              ->orWhereRaw('SOUNDEX(name) = SOUNDEX(?)', [$search]);

                            // Price search (with tolerance)
                            if (preg_match('/(\d+(?:\.\d+)?)\s*(?:€|euro|eur)?/i', $search, $matches)) {
                                $price = floatval($matches[1]);
                                $q->orWhereBetween('price', [$price - 10, $price + 10]);
                            }

                            // Duration search (with tolerance)
                            if (preg_match('/(\d+)\s*(?:min|minute|minutes)?/i', $search, $matches)) {
                                $duration = intval($matches[1]);
                                $q->orWhereBetween('duration_minutes', [$duration - 10, $duration + 10]);
                            }

                            // Company/branch search
                            $q->orWhereHas('company', fn($cq) =>
                                $cq->whereRaw('LOWER(name) LIKE ?', [$searchWildcard])
                            )
                            ->orWhereHas('branch', fn($bq) =>
                                $bq->whereRaw('LOWER(name) LIKE ?', [$searchWildcard])
                            );
                        });
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (empty($data['search'])) {
                            return null;
                        }
                        return 'Intelligente Suche: ' . $data['search'];
                    }),

                SelectFilter::make('company')
                    ->label('Nach Unternehmen filtern')
                    ->relationship('company', 'name')
                    ->searchable()
                    ->preload()
                    ->indicator('Company'),

                SelectFilter::make('sync_status')
                    ->label('Synchronisierungsstatus')
                    ->options([
                        'synced' => 'Mit Cal.com synchronisiert',
                        'not_synced' => 'Nicht synchronisiert',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if ($data['value'] === 'synced') {
                            $query->whereNotNull('calcom_event_type_id');
                        } elseif ($data['value'] === 'not_synced') {
                            $query->whereNull('calcom_event_type_id');
                        }
                    }),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Aktivstatus'),

                Tables\Filters\TernaryFilter::make('is_online')
                    ->label('Online-Buchung'),

                SelectFilter::make('category')
                    ->options([
                        'consultation' => 'Beratung',
                        'treatment' => 'Behandlung',
                        'diagnostic' => 'Diagnostik',
                        'therapy' => 'Therapie',
                        'training' => 'Training',
                        'other' => 'Sonstiges',
                    ]),

                SelectFilter::make('assignment_method')
                    ->label('Zuweisungsmethode')
                    ->options([
                        'manual' => 'Manuell',
                        'auto' => 'Automatisch',
                        'suggested' => 'Vorgeschlagen',
                        'import' => 'Importiert',
                    ]),

                SelectFilter::make('assignment_confidence')
                    ->label('Konfidenzniveau')
                    ->options([
                        'high' => 'Hoch (≥80%)',
                        'medium' => 'Mittel (60-79%)',
                        'low' => 'Low (<60%)',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if ($data['value'] === 'high') {
                            $query->where('assignment_confidence', '>=', 80);
                        } elseif ($data['value'] === 'medium') {
                            $query->whereBetween('assignment_confidence', [60, 79]);
                        } elseif ($data['value'] === 'low') {
                            $query->where('assignment_confidence', '<', 60);
                        }
                    }),
            ])
            ->filtersLayout(Tables\Enums\FiltersLayout::AboveContent)
            ->filtersFormColumns(5)
            ->persistFiltersInSession()
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->iconButton(),

                Tables\Actions\EditAction::make()
                    ->iconButton(),

                Action::make('sync')
                    ->label('Synchronisieren')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading(__('services.modals.sync_heading'))
                    ->modalDescription('This will sync this service with Cal.com. Automatic retry will be attempted on failure.')
                    ->action(function ($record) {
                        $maxRetries = 3;
                        $retryDelay = 2; // seconds
                        $lastError = null;
                        $attempt = 0;

                        while ($attempt < $maxRetries) {
                            $attempt++;

                            try {
                                // Update sync status to pending
                                $record->update([
                                    'sync_status' => 'pending',
                                    'sync_attempts' => $attempt,
                                    'last_sync_attempt' => now(),
                                ]);

                                $calcomService = new CalcomService();
                                $response = $calcomService->createEventType($record);

                                if ($response->successful()) {
                                    $data = $response->json();
                                    if (isset($data['eventType']['id'])) {
                                        $record->update([
                                            'calcom_event_type_id' => $data['eventType']['id'],
                                            'sync_status' => 'synced',
                                            'sync_error' => null,
                                            'last_sync_success' => now(),
                                            'sync_attempts' => $attempt,
                                        ]);

                                        Notification::make()
                                            ->title('Dienstleistung mit Cal.com synchronisiert')
                                            ->body("Event Type ID: {$data['eventType']['id']} (Attempt {$attempt}/{$maxRetries})")
                                            ->success()
                                            ->send();

                                        return; // Success - exit the retry loop
                                    } else {
                                        $lastError = 'Invalid response structure from Cal.com';
                                    }
                                } else {
                                    $statusCode = $response->status();
                                    $errorBody = $response->body();

                                    // Check if error is retryable
                                    if (in_array($statusCode, [408, 429, 500, 502, 503, 504])) {
                                        $lastError = "Cal.com API error {$statusCode}: {$errorBody}";

                                        if ($attempt < $maxRetries) {
                                            // Wait before retrying (exponential backoff)
                                            sleep($retryDelay * $attempt);
                                            continue;
                                        }
                                    } else {
                                        // Non-retryable error
                                        $lastError = "Cal.com API error {$statusCode}: {$errorBody}";
                                        break;
                                    }
                                }
                            } catch (\Exception $e) {
                                $lastError = $e->getMessage();

                                // Check if it's a network error (retryable)
                                if (str_contains($lastError, 'cURL') || str_contains($lastError, 'timeout') || str_contains($lastError, 'connection')) {
                                    if ($attempt < $maxRetries) {
                                        sleep($retryDelay * $attempt);
                                        continue;
                                    }
                                }
                            }
                        }

                        // All retries failed
                        $record->update([
                            'sync_status' => 'error',
                            'sync_error' => $lastError,
                            'sync_attempts' => $attempt,
                        ]);

                        Notification::make()
                            ->title('Synchronisierung fehlgeschlagen nach ' . $attempt . ' Versuchen')
                            ->body(Str::limit($lastError, 100))
                            ->danger()
                            ->persistent()
                            ->send();
                    })
                    ->visible(fn ($record) => !$record->calcom_event_type_id),

                Action::make('refresh_from_calcom')
                    ->label('Von Cal.com aktualisieren')
                    ->icon('heroicon-o-arrow-path-rounded-square')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('Service von Cal.com aktualisieren')
                    ->modalDescription('Dies wird die Dienstleistungsdaten von Cal.com abrufen und aktualisieren.')
                    ->action(function ($record) {
                        try {
                            if (!$record->calcom_event_type_id) {
                                Notification::make()
                                    ->title('Fehler: Keine Cal.com Event Type ID')
                                    ->danger()
                                    ->send();
                                return;
                            }

                            $apiKey = config('services.calcom.api_key');
                            $response = \Illuminate\Support\Facades\Http::get(
                                "https://api.cal.com/v1/event-types/{$record->calcom_event_type_id}",
                                ['apiKey' => $apiKey]
                            );

                            if (!$response->successful()) {
                                throw new \Exception("Cal.com API returned {$response->status()}: {$response->body()}");
                            }

                            $data = $response->json();
                            $eventType = $data['event_type'] ?? [];

                            if (empty($eventType)) {
                                throw new \Exception('Invalid response from Cal.com');
                            }

                            // Update service with Cal.com data
                            $record->update([
                                'name' => $eventType['title'] ?? $record->name,
                                'calcom_name' => $eventType['title'] ?? $record->calcom_name,
                                'duration_minutes' => $eventType['length'] ?? $record->duration_minutes,
                                'description' => $eventType['description'] ?? $record->description,
                                'slug' => $eventType['slug'] ?? $record->slug,
                                'schedule_id' => $eventType['scheduleId'] ?? $record->schedule_id,
                                'requires_confirmation' => $eventType['requiresConfirmation'] ?? false,
                                'booking_link' => $eventType['link'] ?? $record->booking_link,
                                'booking_fields_json' => json_encode($eventType['bookingFields'] ?? []),
                                'locations_json' => json_encode($eventType['locations'] ?? []),
                                'metadata_json' => json_encode($eventType['metadata'] ?? []),
                                'last_calcom_sync' => now(),
                                'sync_status' => 'synced',
                                'sync_error' => null,
                            ]);

                            Notification::make()
                                ->title('Service erfolgreich aktualisiert')
                                ->body("'{$eventType['title']}' - {$eventType['length']} Minuten")
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Fehler beim Aktualisieren')
                                ->body($e->getMessage())
                                ->danger()
                                ->persistent()
                                ->send();

                            $record->update([
                                'sync_status' => 'error',
                                'sync_error' => $e->getMessage(),
                            ]);
                        }
                    })
                    ->visible(fn ($record) => $record->calcom_event_type_id),

                Action::make('unsync')
                    ->label('Synchronisierung aufheben')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update([
                            'calcom_event_type_id' => null,
                        ]);

                        Notification::make()
                            ->title('Dienstleistung nicht mehr synchronisiert')
                            ->warning()
                            ->send();
                    })
                    ->visible(fn ($record) => $record->calcom_event_type_id),

                Action::make('assign_company')
                    ->label('Zuweisen')
                    ->icon('heroicon-o-link')
                    ->color('info')
                    ->form([
                        Forms\Components\Select::make('company_id')
                            ->label('Unternehmen auswählen')
                            ->options(Company::pluck('name', 'id'))
                            ->searchable()
                            ->required()
                            ->helperText(function ($record) {
                                $matcher = app(ServiceMatcher::class);
                                $suggestions = $matcher->suggestCompanies($record);
                                if ($suggestions->isNotEmpty()) {
                                    $top = $suggestions->first();
                                    return "Suggestion: {$top['company']->name} ({$top['confidence']}%)";
                                }
                                return null;
                            }),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'company_id' => $data['company_id'],
                            'assignment_method' => 'manual',
                            'assignment_confidence' => null,
                            'assignment_notes' => 'Manually assigned via ServiceResource',
                            'assignment_date' => now(),
                            'assigned_by' => auth()->id(),
                        ]);

                        Notification::make()
                            ->title('Dienstleistung zugewiesen')
                            ->body('Dienstleistung wurde zugewiesen an ' . Company::find($data['company_id'])->name)
                            ->success()
                            ->send();
                    }),

                Action::make('auto_assign')
                    ->label('Automatisch')
                    ->icon('heroicon-o-cpu-chip')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(function ($record) {
                        $matcher = app(ServiceMatcher::class);
                        $suggestions = $matcher->suggestCompanies($record);
                        return $suggestions->isNotEmpty() && $suggestions->first()['confidence'] >= 70;
                    })
                    ->action(function ($record) {
                        $matcher = app(ServiceMatcher::class);
                        $company = $matcher->autoAssign($record, 70);

                        if ($company) {
                            Notification::make()
                                ->title('Automatisch zugewiesen')
                                ->body("Zugewiesen an {$company->name} ({$record->assignment_confidence}%)")
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Automatische Zuweisung fehlgeschlagen')
                                ->body('Konfidenz zu niedrig')
                                ->warning()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Löschen'),

                    Tables\Actions\BulkAction::make('bulk_sync')
                        ->label('Ausgewählte synchronisieren')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Bulk Sync with Cal.com')
                        ->modalDescription('This will sync all selected services with Cal.com. Each service will have automatic retry on failure.')
                        ->action(function ($records) {
                            $synced = 0;
                            $failed = 0;
                            $skipped = 0;
                            $maxRetries = 3;
                            $retryDelay = 2; // seconds
                            $calcomService = new CalcomService();

                            foreach ($records as $record) {
                                if ($record->calcom_event_type_id) {
                                    $skipped++;
                                    continue;
                                }

                                $attempt = 0;
                                $success = false;

                                while ($attempt < $maxRetries && !$success) {
                                    $attempt++;

                                    try {
                                        // Update sync status
                                        $record->update([
                                            'sync_status' => 'pending',
                                            'sync_attempts' => $attempt,
                                            'last_sync_attempt' => now(),
                                        ]);

                                        $response = $calcomService->createEventType($record);

                                        if ($response->successful()) {
                                            $data = $response->json();
                                            if (isset($data['eventType']['id'])) {
                                                $record->update([
                                                    'calcom_event_type_id' => $data['eventType']['id'],
                                                    'sync_status' => 'synced',
                                                    'sync_error' => null,
                                                    'last_sync_success' => now(),
                                                ]);
                                                $synced++;
                                                $success = true;
                                            }
                                        } else {
                                            // Check if retryable error
                                            if (in_array($response->status(), [408, 429, 500, 502, 503, 504])) {
                                                if ($attempt < $maxRetries) {
                                                    sleep($retryDelay * $attempt);
                                                    continue;
                                                }
                                            }
                                        }
                                    } catch (\Exception $e) {
                                        // Network errors - retry if possible
                                        if ($attempt < $maxRetries &&
                                            (str_contains($e->getMessage(), 'cURL') ||
                                             str_contains($e->getMessage(), 'timeout') ||
                                             str_contains($e->getMessage(), 'connection'))) {
                                            sleep($retryDelay * $attempt);
                                            continue;
                                        }
                                    }
                                }

                                if (!$success) {
                                    $record->update([
                                        'sync_status' => 'error',
                                        'sync_error' => 'Failed after ' . $attempt . ' attempts',
                                    ]);
                                    $failed++;
                                }
                            }

                            // Build notification message
                            $messages = [];
                            if ($synced > 0) $messages[] = "{$synced} synced";
                            if ($failed > 0) $messages[] = "{$failed} failed";
                            if ($skipped > 0) $messages[] = "{$skipped} already synced";

                            $title = $synced > 0 ? 'Bulk sync completed' : 'Bulk sync failed';

                            $notification = Notification::make()
                                ->title($title)
                                ->body(implode(', ', $messages))
                                ->persistent();

                            // Set notification type based on results
                            if ($synced > 0) {
                                $notification->success();
                            } elseif ($failed > 0) {
                                $notification->danger();
                            } else {
                                $notification->warning();
                            }

                            $notification->send();
                        }),

                    Tables\Actions\BulkAction::make('bulk_activate')
                        ->label('Aktivieren')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->update(['is_active' => true]);
                            }

                            Notification::make()
                                ->title('Services activated')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\BulkAction::make('bulk_deactivate')
                        ->label('Deaktivieren')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->update(['is_active' => false]);
                            }

                            Notification::make()
                                ->title('Services deactivated')
                                ->warning()
                                ->send();
                        }),

                    Tables\Actions\BulkAction::make('bulk_edit')
                        ->label('Massenbearbeitung')
                        ->icon('heroicon-o-pencil-square')
                        ->color('primary')
                        ->form([
                            Forms\Components\Section::make('Massenbearbeitungsoptionen')
                                ->description('Lassen Sie Felder leer, um bestehende Werte beizubehalten')
                                ->schema([
                                    Forms\Components\Grid::make(2)->schema([
                                        Forms\Components\Select::make('category')
                                            ->label('Kategorie aktualisieren')
                                            ->options([
                                                'consultation' => 'Beratung',
                                                'treatment' => 'Behandlung',
                                                'diagnostic' => 'Diagnostik',
                                                'therapy' => 'Therapie',
                                                'training' => 'Training',
                                                'other' => 'Sonstiges',
                                            ])
                                            ->placeholder('Bestehend beibehalten'),

                                        Forms\Components\TextInput::make('price')
                                            ->label('Preis aktualisieren')
                                            ->numeric()
                                            ->prefix('€')
                                            ->step(0.01)
                                            ->placeholder('Bestehend beibehalten'),

                                        Forms\Components\TextInput::make('duration_minutes')
                                            ->label('Dauer aktualisieren')
                                            ->numeric()
                                            ->suffix('Minuten')
                                            ->minValue(5)
                                            ->maxValue(480)
                                            ->placeholder('Bestehend beibehalten'),

                                        Forms\Components\TextInput::make('buffer_time_minutes')
                                            ->label('Pufferzeit aktualisieren')
                                            ->numeric()
                                            ->suffix('Minuten')
                                            ->minValue(0)
                                            ->maxValue(60)
                                            ->placeholder('Bestehend beibehalten'),

                                        Forms\Components\Toggle::make('is_online')
                                            ->label('Online-Buchung aktivieren')
                                            ->helperText('Für alle ausgewählten Dienste aktivieren'),


                                        Forms\Components\TextInput::make('max_bookings_per_day')
                                            ->label('Max. Buchungen pro Tag')
                                            ->numeric()
                                            ->minValue(1)
                                            ->maxValue(100)
                                            ->placeholder('Bestehend beibehalten'),

                                        Forms\Components\Select::make('pause_bookable_policy')
                                            ->label(__('services.gap_policy'))
                                            ->options([
                                                'free' => 'Mitarbeiter können während Lücken gebucht werden',
                                                'blocked' => 'Mitarbeiter nicht verfügbar während Lücken',
                                                'flexible' => 'Abhängig von Verfügbarkeit',
                                            ])
                                            ->placeholder('Keep existing')
                                            ->visible(fn () => true),
                                    ]),

                                    Forms\Components\Toggle::make('apply_price_percentage')
                                        ->label('Preisänderung als Prozentsatz anwenden')
                                        ->helperText('Wenn aktiviert, wird der Preiswert als prozentuale Änderung angewendet')
                                        ->reactive(),

                                    Forms\Components\Textarea::make('bulk_notes')
                                        ->label('Notizen')
                                        ->placeholder('Optionale Notizen zu dieser Massenbearbeitung')
                                        ->rows(2),
                                ]),
                        ])
                        ->action(function ($records, array $data) {
                            $updated = 0;
                            $errors = 0;

                            foreach ($records as $record) {
                                try {
                                    $updateData = [];

                                    // Only update fields that have values
                                    if (!empty($data['category'])) {
                                        $updateData['category'] = $data['category'];
                                    }

                                    if ($data['price'] !== null && $data['price'] !== '') {
                                        if ($data['apply_price_percentage'] ?? false) {
                                            // Apply as percentage change
                                            $updateData['price'] = $record->price * (1 + ($data['price'] / 100));
                                        } else {
                                            $updateData['price'] = $data['price'];
                                        }
                                    }

                                    if ($data['duration_minutes'] !== null && $data['duration_minutes'] !== '') {
                                        $updateData['duration_minutes'] = $data['duration_minutes'];
                                    }

                                    if ($data['buffer_time_minutes'] !== null && $data['buffer_time_minutes'] !== '') {
                                        $updateData['buffer_time_minutes'] = $data['buffer_time_minutes'];
                                    }

                                    if (isset($data['is_online'])) {
                                        $updateData['is_online'] = $data['is_online'];
                                    }


                                    if ($data['max_bookings_per_day'] !== null && $data['max_bookings_per_day'] !== '') {
                                        $updateData['max_bookings_per_day'] = $data['max_bookings_per_day'];
                                    }

                                    if (!empty($data['pause_bookable_policy'])) {
                                        $updateData['pause_bookable_policy'] = $data['pause_bookable_policy'];
                                    }

                                    if (!empty($updateData)) {
                                        $record->update($updateData);
                                        $updated++;
                                    }
                                } catch (\Exception $e) {
                                    $errors++;
                                }
                            }

                            Notification::make()
                                ->title('Bulk edit complete')
                                ->body("Updated: {$updated} services" . ($errors > 0 ? ", Errors: {$errors}" : ""))
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\BulkAction::make('bulk_auto_assign')
                        ->label('Automatisch zuweisen')
                        ->icon('heroicon-o-cpu-chip')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $matcher = app(ServiceMatcher::class);
                            $assigned = 0;
                            $failed = 0;

                            foreach ($records as $service) {
                                $company = $matcher->autoAssign($service, 70);
                                if ($company) {
                                    $assigned++;
                                } else {
                                    $failed++;
                                }
                            }

                            Notification::make()
                                ->title('Bulk assignment complete')
                                ->body("Assigned: {$assigned}, Failed: {$failed}")
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\BulkAction::make('bulk_assign_to_company')
                        ->label('Zu Unternehmen zuweisen')
                        ->icon('heroicon-o-link')
                        ->form([
                            Forms\Components\Select::make('company_id')
                                ->label('Unternehmen auswählen')
                                ->options(Company::pluck('name', 'id'))
                                ->searchable()
                                ->required(),
                        ])
                        ->action(function ($records, array $data) {
                            foreach ($records as $service) {
                                $service->update([
                                    'company_id' => $data['company_id'],
                                    'assignment_method' => 'manual',
                                    'assignment_confidence' => null,
                                    'assignment_notes' => 'Bulk manual assignment',
                                    'assignment_date' => now(),
                                    'assigned_by' => auth()->id(),
                                ]);
                            }

                            Notification::make()
                                ->title('Bulk assignment complete')
                                ->body(count($records) . ' services assigned to ' . Company::find($data['company_id'])->name)
                                ->success()
                                ->send();
                        }),
                ])
                ->label('Massenaktionen')
                ->icon('heroicon-o-squares-plus')
                ->color('primary'),
            ])
            ->headerActions([
                Action::make('create_in_calcom')
                    ->label('In Cal.com erstellen')
                    ->icon('heroicon-o-plus-circle')
                    ->color('primary')
                    ->url('https://app.cal.com/event-types/new')
                    ->openUrlInNewTab(),

                Action::make('import_from_calcom')
                    ->label('Aus Cal.com importieren')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('info')
                    ->action(function () {
                        // Trigger manual import of all Cal.com event types
                        $calcomService = new CalcomService();
                        $response = Http::withHeaders(['Accept' => 'application/json'])
                            ->get(config('services.calcom.base_url') . '/event-types?apiKey=' . config('services.calcom.api_key'));

                        if ($response->successful()) {
                            $data = $response->json();
                            $imported = 0;

                            foreach ($data['event_types'] ?? [] as $eventType) {
                                ImportEventTypeJob::dispatch($eventType);
                                $imported++;
                            }

                            Notification::make()
                                ->title($imported . ' Event Types queued for import')
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Failed to fetch Event Types from Cal.com')
                                ->danger()
                                ->send();
                        }
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Import Event Types from Cal.com')
                    ->modalDescription('This will import all Event Types from Cal.com that are not yet in the system.'),

                Action::make('sync_all_company')
                    ->label('Sync All for Company')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->form([
                        Forms\Components\Select::make('company_id')
                            ->label('Unternehmen auswählen')
                            ->options(Company::pluck('name', 'id'))
                            ->required()
                            ->searchable(),
                    ])
                    ->action(function (array $data) {
                        $services = Service::where('company_id', $data['company_id'])
                            ->whereNull('calcom_event_type_id')
                            ->get();

                        $synced = 0;
                        $failed = 0;
                        $calcomService = new CalcomService();

                        foreach ($services as $service) {
                            try {
                                $response = $calcomService->createEventType($service);
                                if ($response->successful()) {
                                    $data = $response->json();
                                    if (isset($data['eventType']['id'])) {
                                        $service->update([
                                            'calcom_event_type_id' => $data['eventType']['id'],
                                        ]);
                                        $synced++;
                                    } else {
                                        $failed++;
                                    }
                                } else {
                                    $failed++;
                                }
                            } catch (\Exception $e) {
                                $failed++;
                            }
                        }

                        if ($synced > 0) {
                            Notification::make()
                                ->title($synced . ' services synced for company')
                                ->body($failed > 0 ? $failed . ' services failed to sync' : null)
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('No services synced')
                                ->body($failed . ' services failed to sync')
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->groups([
                Tables\Grouping\Group::make('company.name')
                    ->label('Unternehmen')
                    ->collapsible(),

                Tables\Grouping\Group::make('category')
                    ->label('Category')
                    ->collapsible(),
            ])
            ->defaultGroup('company.name')
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(25)
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->persistSearchInSession()
            ->persistColumnSearchesInSession()
            ->striped()
            ->poll('60s');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // Hauptübersicht
                InfoSection::make('Serviceübersicht')
                    ->description('Grundlegende Informationen zum Service')
                    ->icon('heroicon-o-briefcase')
                    ->schema([
                        InfoGrid::make(3)->schema([
                            TextEntry::make('name')
                                ->label('Service Name')
                                ->icon('heroicon-m-briefcase')
                                ->weight('bold')
                                ->size('lg')
                                ,

                            TextEntry::make('category')
                                ->label('Kategorie')
                                ->badge()
                                ->formatStateUsing(fn ($state) => match($state) {
                                    'consultation' => '💬 Beratung',
                                    'treatment' => '💊 Behandlung',
                                    'diagnostic' => '🔍 Diagnose',
                                    'therapy' => '🏥 Therapie',
                                    'training' => '📚 Schulung',
                                    'other' => '📋 Sonstiges',
                                    default => $state ?? '❓ Unbekannt'
                                })
                                ->color(fn ($state) => match($state) {
                                    'consultation' => 'info',
                                    'treatment' => 'success',
                                    'diagnostic' => 'warning',
                                    'therapy' => 'primary',
                                    'training' => 'purple',
                                    default => 'gray'
                                }),

                            TextEntry::make('status_display')
                                ->label('Status')
                                ->getStateUsing(fn ($record) =>
                                    ($record->is_active ? '✅ Aktiv' : '❌ Inaktiv') .
                                    ($record->is_online ? ' | 🌐 Online' : ' | 🏢 Vor Ort')
                                )
                                ->badge()
                                ->color(fn ($record) => $record->is_active ? 'success' : 'danger'),
                        ]),

                        TextEntry::make('description')
                            ->label('Beschreibung')
                            ->placeholder('Keine Beschreibung vorhanden')
                            ->columnSpanFull()
                            ->html(),
                    ]),

                // Unternehmenszuweisung
                InfoSection::make('Unternehmenszuweisung')
                    ->description('Zugehörigkeit zu Unternehmen und Filiale')
                    ->icon('heroicon-o-building-office-2')
                    ->collapsible()
                    ->schema([
                        InfoGrid::make(3)->schema([
                            TextEntry::make('company.name')
                                ->label('Unternehmen')
                                ->icon('heroicon-m-building-office-2')
                                ->placeholder('Nicht zugewiesen')
                                ->badge()
                                ->color(fn ($state) => $state ? 'primary' : 'danger')
                                ->url(fn ($record) => $record->company_id
                                    ? route('filament.admin.resources.companies.view', $record->company_id)
                                    : null),

                            TextEntry::make('branch.name')
                                ->label('Filiale')
                                ->icon('heroicon-m-map-pin')
                                ->placeholder('Keine Filiale')
                                ->badge()
                                ->color('info')
                                ->url(fn ($record) => $record->branch_id
                                    ? route('filament.admin.resources.branches.view', $record->branch_id)
                                    : null),

                            TextEntry::make('assignment_method')
                                ->label('Zuweisungsmethode')
                                ->formatStateUsing(fn ($state) => match($state) {
                                    'manual' => '👤 Manuell',
                                    'auto' => '🤖 Automatisch',
                                    'suggested' => '💡 Vorgeschlagen',
                                    'import' => '📥 Import',
                                    default => '❓ Unbekannt'
                                })
                                ->placeholder('Nicht zugewiesen')
                                ->badge()
                                ->color(fn ($state) => match($state) {
                                    'manual' => 'info',
                                    'auto' => 'success',
                                    'suggested' => 'warning',
                                    'import' => 'primary',
                                    default => 'gray'
                                }),
                        ]),

                        InfoGrid::make(2)->schema([
                            TextEntry::make('assignment_confidence')
                                ->label('Zuweisungskonfidenz')
                                ->formatStateUsing(fn ($state) => $state ? "{$state}%" : null)
                                ->placeholder('N/A')
                                ->badge()
                                ->color(fn ($state) =>
                                    $state >= 80 ? 'success' :
                                    ($state >= 60 ? 'warning' :
                                    ($state > 0 ? 'danger' : 'gray'))
                                ),

                            TextEntry::make('assignment_date')
                                ->label('Zugewiesen am')
                                ->dateTime('d.m.Y H:i')
                                ->icon('heroicon-m-calendar')
                                ->placeholder('Nicht zugewiesen'),
                        ]),

                        TextEntry::make('assignment_notes')
                            ->label('Zuweisungsnotizen')
                            ->placeholder('Keine Notizen')
                            ->columnSpanFull(),

                        TextEntry::make('assignedBy.name')
                            ->label('Zugewiesen von')
                            ->icon('heroicon-m-user')
                            ->placeholder('System')
                            ->badge()
                            ->color('gray'),
                    ]),

                // Preis & Zeiteinstellungen
                InfoSection::make('Preis & Zeiteinstellungen')
                    ->description('Preisgestaltung, Dauer und Buchungsregeln')
                    ->icon('heroicon-o-currency-euro')
                    ->collapsible()
                    ->schema([
                        InfoGrid::make(4)->schema([
                            TextEntry::make('price')
                                ->label('Preis')
                                ->formatStateUsing(fn ($state) => number_format($state, 2, ',', '.') . ' €')
                                ->weight('bold')
                                ->size('lg')
                                ->color(fn ($state) => $state > 0 ? 'success' : 'gray'),

                            TextEntry::make('duration_minutes')
                                ->label('Dauer')
                                ->suffix(' Minuten')
                                ->icon('heroicon-m-clock')
                                ->badge()
                                ->color('info'),

                            TextEntry::make('buffer_time_minutes')
                                ->label('Pufferzeit')
                                ->suffix(' Minuten')
                                ->placeholder('0 Minuten')
                                ->icon('heroicon-m-pause')
                                ->badge()
                                ->color('gray'),

                            TextEntry::make('max_bookings_per_day')
                                ->label('Max. Buchungen/Tag')
                                ->placeholder('Unbegrenzt')
                                ->icon('heroicon-m-calendar-days')
                                ->badge()
                                ->color('warning'),
                        ]),

                        InfoGrid::make(2)->schema([
                            TextEntry::make('hourly_rate')
                                ->label('Berechneter Stundensatz')
                                ->getStateUsing(fn ($record) =>
                                    $record->price > 0 && $record->duration_minutes > 0
                                        ? number_format($record->price / ($record->duration_minutes / 60), 2) . ' €/h'
                                        : 'N/A'
                                )
                                ->icon('heroicon-m-calculator')
                                ->badge()
                                ->color('primary'),

                            TextEntry::make('total_time')
                                ->label('Gesamtzeit (inkl. Puffer)')
                                ->getStateUsing(fn ($record) =>
                                    ($record->duration_minutes + ($record->buffer_time_minutes ?? 0)) . ' Minuten'
                                )
                                ->icon('heroicon-m-clock')
                                ->badge()
                                ->color('gray'),
                        ]),
                    ]),

                // Cal.com Integration
                InfoSection::make('Cal.com Integration')
                    ->description('Synchronisationsstatus und Einstellungen')
                    ->icon('heroicon-o-link')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        InfoGrid::make(3)->schema([
                            TextEntry::make('calcom_event_type_id')
                                ->label('Event Type ID')
                                ->placeholder('Nicht synchronisiert')
                                
                                ->badge()
                                ->color(fn ($state) => $state ? 'success' : 'warning'),

                            TextEntry::make('sync_status')
                                ->label('Synchronisierungsstatus')
                                ->getStateUsing(fn ($record) =>
                                    $record->sync_status === 'synced' ? '✅ Synchronisiert' :
                                    ($record->sync_status === 'pending' ? '⏳ Ausstehend' :
                                    ($record->sync_status === 'error' ? '❌ Fehler' : '🔄 Nie synchronisiert'))
                                )
                                ->badge()
                                ->color(fn ($record) => match($record->sync_status) {
                                    'synced' => 'success',
                                    'pending' => 'warning',
                                    'error' => 'danger',
                                    default => 'gray'
                                }),

                            TextEntry::make('last_synced_at')
                                ->label('Letzte Synchronisation')
                                ->dateTime('d.m.Y H:i:s')
                                ->placeholder('Nie synchronisiert')
                                ->icon('heroicon-m-arrow-path'),
                        ]),

                        TextEntry::make('sync_error')
                            ->label('Synchronisationsfehler')
                            ->placeholder('Keine Fehler')
                            ->columnSpanFull()
                            ->color('danger')
                            ->visible(fn ($record) => $record->sync_error),

                        TextEntry::make('external_id')
                            ->label('Externe ID')
                            ->placeholder('Keine externe ID')
                            
                            ->badge()
                            ->color('gray'),
                    ]),

                // Terminstatistiken
                InfoSection::make('Terminstatistiken')
                    ->description('Buchungen und Auslastung')
                    ->icon('heroicon-o-chart-bar')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        InfoGrid::make(4)->schema([
                            TextEntry::make('appointments_count')
                                ->label('Gesamte Termine')
                                ->getStateUsing(fn ($record) => $record->appointments()->count())
                                ->badge()
                                ->color('info')
                                ->suffix(' Termine')
                                ->icon('heroicon-m-calendar'),

                            TextEntry::make('upcoming_appointments_count')
                                ->label('Anstehende Termine')
                                ->getStateUsing(fn ($record) =>
                                    $record->appointments()
                                        ->where('starts_at', '>=', now())
                                        ->count()
                                )
                                ->badge()
                                ->color('success')
                                ->suffix(' Termine')
                                ->icon('heroicon-m-clock'),

                            TextEntry::make('completed_appointments_count')
                                ->label('Abgeschlossene Termine')
                                ->getStateUsing(fn ($record) =>
                                    $record->appointments()
                                        ->where('ends_at', '<', now())
                                        ->where('status', 'completed')
                                        ->count()
                                )
                                ->badge()
                                ->color('gray')
                                ->suffix(' Termine')
                                ->icon('heroicon-m-check-circle'),

                            TextEntry::make('cancelled_appointments_count')
                                ->label('Stornierte Termine')
                                ->getStateUsing(fn ($record) =>
                                    $record->appointments()
                                        ->where('status', 'cancelled')
                                        ->count()
                                )
                                ->badge()
                                ->color('danger')
                                ->suffix(' Termine')
                                ->icon('heroicon-m-x-circle'),
                        ]),

                        InfoGrid::make(2)->schema([
                            TextEntry::make('total_revenue')
                                ->label('Gesamtumsatz')
                                ->getStateUsing(fn ($record) =>
                                    number_format($record->appointments()
                                        ->where('status', 'completed')
                                        ->count() * ($record->price ?? 0), 2) . ' €'
                                )
                                ->icon('heroicon-m-currency-euro')
                                ->badge()
                                ->color('success')
                                ->size('lg'),

                            TextEntry::make('average_bookings_per_month')
                                ->label('Durchschn. Buchungen/Monat')
                                ->getStateUsing(function ($record) {
                                    $months = $record->created_at->diffInMonths(now()) ?: 1;
                                    $total = $record->appointments()->count();
                                    return number_format($total / $months, 1) . ' Termine';
                                })
                                ->icon('heroicon-m-chart-bar')
                                ->badge()
                                ->color('primary'),
                        ]),

                        TextEntry::make('popular_times')
                            ->label('Beliebte Zeiten')
                            ->getStateUsing(function ($record) {
                                $appointments = $record->appointments()
                                    ->selectRaw('HOUR(starts_at) as hour, COUNT(*) as count')
                                    ->groupBy('hour')
                                    ->orderByDesc('count')
                                    ->limit(3)
                                    ->get();

                                if ($appointments->isEmpty()) {
                                    return 'Keine Daten';
                                }

                                return $appointments->map(fn ($a) =>
                                    sprintf('%02d:00 Uhr (%d Termine)', $a->hour, $a->count)
                                )->join(', ');
                            })
                            ->icon('heroicon-m-clock')
                            ->columnSpanFull(),
                    ]),

                // System-Informationen
                InfoSection::make('System-Informationen')
                    ->description('Technische Details und Metadaten')
                    ->icon('heroicon-o-information-circle')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        InfoGrid::make(3)->schema([
                            TextEntry::make('id')
                                ->label('Service ID')
                                
                                ->badge()
                                ->color('gray'),

                            TextEntry::make('created_at')
                                ->label('Erstellt am')
                                ->dateTime('d.m.Y H:i:s')
                                ->icon('heroicon-m-calendar-days'),

                            TextEntry::make('updated_at')
                                ->label('Zuletzt geändert')
                                ->dateTime('d.m.Y H:i:s')
                                ->icon('heroicon-m-pencil'),
                        ]),

                        TextEntry::make('metadata_display')
                            ->label('Metadaten')
                            ->getStateUsing(fn ($record) =>
                                $record->metadata
                                    ? json_encode($record->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                                    : null
                            )
                            ->placeholder('Keine Metadaten')
                            ->columnSpanFull()
                            ,
                    ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\AppointmentsRelationManager::class,
            RelationManagers\StaffRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServices::route('/'),
            'create' => Pages\CreateService::route('/create'),
            'edit' => Pages\EditService::route('/{record}/edit'),
            'view' => Pages\ViewService::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'company:id,name',
                'branch:id,name',
                'assignedBy:id,name',
                'staff' => function ($query) {
                    $query->select('staff.id', 'staff.name')
                        ->withPivot('is_primary', 'can_book', 'skill_level', 'custom_duration_minutes');
                }
            ]);
    }

    /**
     * Disable direct creation - services must be created through Cal.com
     */
    public static function canCreate(): bool
    {
        return false;
    }

    /**
     * Generate policy configuration section for form
     */
    protected static function getPolicySection(string $policyType, string $label): Forms\Components\Section
    {
        return Forms\Components\Section::make($label)
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
                            PolicyConfiguration::where('configurable_type', Service::class)
                                ->where('configurable_id', $record->id)
                                ->where('policy_type', $policyType)
                                ->delete();
                        }
                    })
                    ->dehydrated(false)
                    ->default(function ($record) use ($policyType) {
                        if (!$record) return false;
                        return PolicyConfiguration::where('configurable_type', Service::class)
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
                        $policy = PolicyConfiguration::where('configurable_type', Service::class)
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
                            return new HtmlString('<span class="text-gray-500">Neue Dienstleistung - noch keine Vererbung</span>');
                        }

                        $hasOverride = PolicyConfiguration::where('configurable_type', Service::class)
                            ->where('configurable_id', $record->id)
                            ->where('policy_type', $policyType)
                            ->exists();

                        if ($hasOverride) {
                            return new HtmlString('<span class="text-primary-600 font-medium">Eigene Konfiguration aktiv</span>');
                        }

                        // Get inherited config from branch or company
                        $inheritedConfig = static::getInheritedPolicyConfig($record, $policyType);
                        if ($inheritedConfig['config']) {
                            $configDisplay = json_encode($inheritedConfig['config'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                            return new HtmlString(
                                '<div class="text-sm">
                                    <p class="text-gray-700 font-medium mb-2">Geerbt von ' . $inheritedConfig['source'] . '</p>
                                    <pre class="bg-gray-100 p-2 rounded text-xs overflow-auto max-h-32">' .
                                    htmlspecialchars($configDisplay) .
                                    '</pre>
                                </div>'
                            );
                        }

                        return new HtmlString('<span class="text-gray-500">Systemstandardwerte (keine Konfiguration)</span>');
                    })
                    ->visible(fn (Get $get) => $get("override_{$policyType}") !== true),

                Forms\Components\Placeholder::make("hierarchy_info_{$policyType}")
                    ->label('Hierarchie')
                    ->content(new HtmlString(
                        '<div class="text-sm text-gray-600">
                            <p class="font-medium mb-1">Vererbungsreihenfolge:</p>
                            <ol class="list-decimal list-inside space-y-1">
                                <li>Unternehmen</li>
                                <li>Filiale</li>
                                <li><strong>Dienstleistung</strong> (aktuelle Ebene)</li>
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
    protected static function getInheritedPolicyConfig($record, string $policyType): array
    {
        if (!$record) return ['config' => null, 'source' => null];

        // Check branch first if service has one
        if ($record->branch_id) {
            $branchPolicy = PolicyConfiguration::where('configurable_type', \App\Models\Branch::class)
                ->where('configurable_id', $record->branch_id)
                ->where('policy_type', $policyType)
                ->first();

            if ($branchPolicy) {
                return ['config' => $branchPolicy->config, 'source' => 'Filiale'];
            }
        }

        // Check company
        if ($record->company_id) {
            $companyPolicy = PolicyConfiguration::where('configurable_type', Company::class)
                ->where('configurable_id', $record->company_id)
                ->where('policy_type', $policyType)
                ->first();

            if ($companyPolicy) {
                return ['config' => $companyPolicy->config, 'source' => 'Unternehmen'];
            }
        }

        return ['config' => null, 'source' => null];
    }

    /**
     * Save policy configuration for a record
     */
    public static function savePolicyConfiguration($record, array $data): void
    {
        foreach (['cancellation', 'reschedule', 'recurring'] as $policyType) {
            $overrideKey = "override_{$policyType}";
            $configKey = "policy_config_{$policyType}";

            if (isset($data[$overrideKey]) && $data[$overrideKey]) {
                // Create or update policy configuration
                $config = $data[$configKey] ?? [];

                // Get parent policy ID (from branch or company)
                $parentPolicy = null;
                if ($record->branch_id) {
                    $parentPolicy = PolicyConfiguration::where('configurable_type', \App\Models\Branch::class)
                        ->where('configurable_id', $record->branch_id)
                        ->where('policy_type', $policyType)
                        ->first();
                }

                if (!$parentPolicy && $record->company_id) {
                    $parentPolicy = PolicyConfiguration::where('configurable_type', Company::class)
                        ->where('configurable_id', $record->company_id)
                        ->where('policy_type', $policyType)
                        ->first();
                }

                PolicyConfiguration::updateOrCreate(
                    [
                        'configurable_type' => Service::class,
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
                PolicyConfiguration::where('configurable_type', Service::class)
                    ->where('configurable_id', $record->id)
                    ->where('policy_type', $policyType)
                    ->delete();
            }
        }
    }
}