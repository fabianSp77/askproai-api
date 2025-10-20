<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasCachedNavigationBadge;
use App\Filament\Resources\CallResource\Pages;
use App\Filament\Resources\CallResource\RelationManagers;
use App\Filament\Resources\CustomerResource;
use App\Models\Call;
use App\Models\Customer;
use App\Services\CostCalculator;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Tabs;
use Carbon\Carbon;
use Filament\Infolists\Components\Section as InfoSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Infolists\Components\Split;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Tabs as InfolistTabs;
use Filament\Infolists\Components\RepeatableEntry;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;
use App\Helpers\FormatHelper;
use App\Services\Patterns\GermanNamePatternLibrary;

class CallResource extends Resource
{
    use HasCachedNavigationBadge;

    protected static ?string $model = Call::class;
    protected static ?string $navigationIcon = 'heroicon-o-phone';
    protected static ?string $navigationGroup = 'CRM';
    protected static ?string $navigationLabel = 'Anrufe';
    protected static ?int $navigationSort = 2;
    protected static ?string $recordTitleAttribute = null; // Using getRecordTitle instead

    public static function getNavigationBadge(): ?string
    {
        // ✅ RESTORED with caching (2025-10-03) - Memory bugs fixed
        return static::getCachedBadge(function() {
            return static::getModel()::whereDate('created_at', today())->count();
        });
    }

    public static function getNavigationBadgeColor(): ?string
    {
        // ✅ RESTORED with caching (2025-10-03)
        return static::getCachedBadgeColor(function() {
            $count = static::getModel()::whereDate('created_at', today())->count();
            return $count > 20 ? 'danger' : ($count > 10 ? 'warning' : 'success');
        });
    }

    public static function getRecordTitle(?\Illuminate\Database\Eloquent\Model $record): ?string
    {
        if (!$record) {
            return null;
        }

        // Create intelligent title with customer name and date
        // Filter out transcript fragments
        $nonNamePhrases = ['mir nicht', 'guten tag', 'guten morgen', 'hallo', 'ja', 'nein', 'gleich fertig'];
        $customerNameLower = $record->customer_name ? strtolower(trim($record->customer_name)) : '';
        $isTranscriptFragment = in_array($customerNameLower, $nonNamePhrases);

        // Priority: real customer_name > linked customer > anonymous
        if ($record->customer_name && !$isTranscriptFragment) {
            $customerName = $record->customer_name;
        } elseif ($record->customer?->name) {
            $customerName = $record->customer->name;
        } elseif ($record->from_number === 'anonymous') {
            $customerName = 'Anonymer Anrufer';
        } else {
            $customerName = 'Unbekannter Kunde';
        }
        $date = $record->created_at?->format('d.m. H:i') ?? '';
        $status = match($record->status) {
            'completed' => '✅',
            'missed' => '📵',
            'failed' => '❌',
            'busy' => '🔴',
            'no_answer' => '🔇',
            default => '📞',
        };

        return $status . ' ' . $customerName . ' - ' . $date;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Basic call information for editing
                Section::make('Anrufinformationen')
                    ->schema([
                        Forms\Components\Select::make('customer_id')
                            ->label('Kunde')
                            ->relationship('customer', 'name')
                            ->searchable()
                            ->preload(),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'completed' => 'Abgeschlossen',
                                'missed' => 'Verpasst',
                                'failed' => 'Fehlgeschlagen',
                                'busy' => 'Besetzt',
                                'no_answer' => 'Keine Antwort',
                            ])
                            ->required(),

                        Forms\Components\Toggle::make('appointment_made')
                            ->label('Termin vereinbart'),

                        Forms\Components\Select::make('sentiment')
                            ->label('Stimmung')
                            ->options([
                                'Positive' => 'Positiv',
                                'Neutral' => 'Neutral',
                                'Negative' => 'Negativ',
                            ]),

                        Forms\Components\Select::make('session_outcome')
                            ->label('Gesprächsergebnis')
                            ->options([
                                'appointment_scheduled' => 'Termin vereinbart',
                                'information_provided' => 'Info gegeben',
                                'callback_requested' => 'Rückruf erwünscht',
                                'complaint_registered' => 'Beschwerde',
                                'no_interest' => 'Kein Interesse',
                                'transferred' => 'Weitergeleitet',
                                'voicemail' => 'Voicemail',
                            ]),

                        Forms\Components\Select::make('urgency_level')
                            ->label('Dringlichkeit')
                            ->options([
                                'urgent' => 'Dringend',
                                'high' => 'Hoch',
                                'medium' => 'Mittel',
                                'low' => 'Niedrig',
                            ]),

                        Forms\Components\Textarea::make('notes')
                            ->label('Notizen')
                            ->rows(4)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('summary')
                            ->label('Zusammenfassung')
                            ->rows(5)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                // Technical information (read-only)
                Section::make('Technische Informationen')
                    ->schema([
                        Forms\Components\TextInput::make('external_id')
                            ->label('Externe ID')
                            ->disabled(),

                        Forms\Components\TextInput::make('retell_call_id')
                            ->label('Retell Anruf-ID')
                            ->disabled(),

                        Forms\Components\TextInput::make('duration_sec')
                            ->label('Dauer (Sekunden)')
                            ->disabled(),

                        Forms\Components\TextInput::make('cost')
                            ->label('Kosten (€)')
                            ->prefix('€')
                            ->disabled()
                            ->formatStateUsing(fn ($state) => number_format($state / 100, 2, ',', '.')),
                    ])
                    ->columns(2)
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            // 🚀 PERFORMANCE: Eager load relationships to prevent N+1 queries
            ->modifyQueryUsing(function (Builder $query) {
                return $query
                    ->with('appointmentWishes', function ($q) {
                        $q->where('status', 'pending')->latest();
                    })
                    ->with('appointments', function ($q) {
                        $q->with('service');
                    })
                    ->with('customer')
                    ->with('company')
                    ->with('branch')
                    ->with('phoneNumber');
            })
            ->columns([
                // 🟢 PREMIUM: Status / Zeit / Dauer Column (ALL-IN-ONE!)
                Tables\Columns\TextColumn::make('call_status_display')
                    ->label('Status / Zeit / Dauer')
                    ->html()
                    ->getStateUsing(function ($record) {
                        // Determine status icon and text
                        $status = match ($record->status ?? 'unknown') {
                            'ongoing', 'in_progress', 'active', 'ringing' => '🔴 LIVE',
                            'completed' => '✅ Completed',
                            'missed' => '📵 Missed',
                            'failed' => '❌ Failed',
                            'no_answer' => '🔇 No Answer',
                            'busy' => '📳 Busy',
                            'analyzed' => '📊 Analyzed',
                            'call_analyzed' => '📊 Analyzed',
                            default => '📞 ' . ucfirst($record->status ?? 'Unknown')
                        };

                        // Format as HTML with multiple lines
                        return $status;
                    })
                    ->badge()
                    ->color(function ($record) {
                        return match ($record->status ?? 'unknown') {
                            'ongoing', 'in_progress', 'active', 'ringing' => 'danger',
                            'completed' => 'success',
                            'missed', 'busy' => 'warning',
                            'failed', 'no_answer' => 'danger',
                            default => 'gray'
                        };
                    })
                    ->icon(function ($record) {
                        return match ($record->status ?? 'unknown') {
                            'ongoing', 'in_progress', 'active', 'ringing' => 'heroicon-m-signal',
                            'completed' => 'heroicon-m-check-circle',
                            'missed' => 'heroicon-m-phone-x-mark',
                            'failed' => 'heroicon-m-x-circle',
                            'no_answer' => 'heroicon-m-bell-slash',
                            'busy' => 'heroicon-m-ellipsis-horizontal-circle',
                            default => 'heroicon-m-phone'
                        };
                    })
                    ->extraAttributes(function ($record) {
                        // Animate only LIVE calls
                        if (in_array($record->status, ['ongoing', 'in_progress', 'active', 'ringing'])) {
                            return ['class' => 'animate-pulse font-bold'];
                        }
                        return [];
                    })
                    ->sortable(query: fn($query, $direction) =>
                        $query
                            ->orderByRaw("CASE
                                WHEN status IN ('ongoing','in_progress','active','ringing') THEN 0
                                WHEN status = 'completed' THEN 1
                                WHEN status IN ('missed','busy') THEN 2
                                ELSE 3
                                END {$direction}")
                            ->orderBy('created_at', $direction === 'desc' ? 'desc' : 'asc')
                    )
                    // 💾 NEW: Multi-line description (Datum + Dauer)
                    ->description(function ($record) {
                        if (!$record->created_at) {
                            return '';
                        }

                        // Line 1: Datum + Uhrzeit (z.B. "20 Oktober 14:51 Uhr")
                        $dateTime = $record->created_at->locale('de')->isoFormat('DD MMMM HH:mm') . ' Uhr';

                        // Line 2: Dauer (z.B. "⏱️ 5:23")
                        $duration = '';
                        if ($record->duration_sec) {
                            $mins = intval($record->duration_sec / 60);
                            $secs = $record->duration_sec % 60;
                            $duration = sprintf('⏱️ %d:%02d', $mins, $secs);
                        } else {
                            $duration = '⏱️ --:--';
                        }

                        return $dateTime . "\n" . $duration;
                    })
                    // 💾 PREMIUM: Tooltip shows EVERYTHING (full precision)
                    ->tooltip(function ($record) {
                        if (!$record->created_at) {
                            return null;
                        }

                        $lines = [];

                        // Full datetime with YEAR and SECONDS
                        $lines[] = "📅 " . $record->created_at->format('d.m.Y H:i:s') . ' Uhr';

                        // Duration with label
                        if ($record->duration_sec) {
                            $mins = intval($record->duration_sec / 60);
                            $secs = $record->duration_sec % 60;
                            $lines[] = "⏱️ Dauer: " . sprintf('%d:%02d Min', $mins, $secs);
                        }

                        // Relative time
                        $lines[] = "🕐 " . $record->created_at->diffForHumans();

                        return implode("\n", $lines);
                    }),

                // 💾 OPTIONAL: Zeit Spalte (versteckt, weil in Status/Zeit zusammengefasst)
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Zeit (optional)')
                    ->dateTime('d.m.Y H:i:s')
                    ->sortable()
                    ->description(fn ($record) => $record->created_at?->diffForHumans())
                    ->icon('heroicon-m-clock')
                    ->toggleable(isToggledHiddenByDefault: true), // 🔴 HIDDEN BY DEFAULT

                // Company/Branch column
                Tables\Columns\TextColumn::make('company.name')
                    ->label('Unternehmen/Filiale')
                    ->getStateUsing(function ($record) {
                        if ($record->branch_id) {
                            return $record->branch->name ?? 'Filiale';
                        }
                        return $record->company->name ?? 'Unternehmen';
                    })
                    ->description(fn ($record) => $record->company->name ?? '')
                    ->icon('heroicon-m-building-office')
                    ->searchable()
                    ->toggleable(),

                // Optimized Customer column with mobile-friendly verification badges
                Tables\Columns\TextColumn::make('customer_name')
                    ->label('Anrufer')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query
                            ->where('customer_name', 'like', "%{$search}%")
                            ->orWhereHas('customer', fn (Builder $q) => $q->where('name', 'like', "%{$search}%"));
                    })
                    ->sortable()
                    ->html()
                    ->getStateUsing(function ($record) {
                        // Filter out transcript fragments that are NOT real names
                        $nonNamePhrases = ['mir nicht', 'guten tag', 'guten morgen', 'hallo', 'ja', 'nein', 'gleich fertig', 'ja bitte', 'danke'];
                        $customerNameLower = $record->customer_name ? strtolower(trim($record->customer_name)) : '';

                        $isTranscriptFragment = in_array($customerNameLower, $nonNamePhrases);

                        // Truly anonymous (no name, transcript fragment, OR anonymous number without real name)
                        if ($record->from_number === 'anonymous' && (!$record->customer_name || trim($record->customer_name) === '' || $isTranscriptFragment)) {
                            return '<span class="text-gray-600">Anonym</span>';
                        }

                        // Has customer linked - use mobile-friendly badge
                        if ($record->customer_id && $record->customer) {
                            return view('components.mobile-verification-badge', [
                                'name' => $record->customer->name,
                                'verified' => true,
                                'verificationSource' => 'customer_linked',
                                'additionalInfo' => $record->customer_link_confidence ? round($record->customer_link_confidence) . '%' : null,
                                'phone' => $record->from_number !== 'anonymous' ? $record->from_number : null,
                            ])->render();
                        }

                        // Has customer_name - use mobile-friendly badge
                        if ($record->customer_name) {
                            $verificationSource = match($record->verification_method ?? null) {
                                'retell_agent_provided' => 'retell_agent',
                                'phone_match' => 'phone_verified',
                                'phonetic_match' => 'phonetic_match',
                                default => 'ai_extracted'
                            };

                            return view('components.mobile-verification-badge', [
                                'name' => $record->customer_name,
                                'verified' => $record->customer_name_verified,
                                'verificationSource' => $verificationSource,
                                'additionalInfo' => $record->from_number === 'anonymous' ? 'Anonyme Telefonnummer' : null,
                                'phone' => $record->from_number !== 'anonymous' ? $record->from_number : null,
                            ])->render();
                        }

                        // No customer data - show phone or "Anonym"
                        $fallback = $record->from_number === 'anonymous' ? 'Anonym' : ($record->from_number ?? 'Unbekannt');
                        return '<span class="text-gray-600">' . htmlspecialchars($fallback) . '</span>';
                    })
                    ->icon(fn ($record): string => match ($record->direction) {
                        'inbound' => 'heroicon-m-phone-arrow-down-left',
                        'outbound' => 'heroicon-m-phone-arrow-up-right',
                        default => 'heroicon-m-user',
                    })
                    ->iconColor(fn ($record): string => match ($record->direction) {
                        'inbound' => 'success',
                        'outbound' => 'info',
                        default => 'gray',
                    })
                    ->description(function ($record) {
                        $directionLabel = match ($record->direction) {
                            'inbound' => '↓ Eingehend',
                            'outbound' => '↑ Ausgehend',
                            default => ''
                        };

                        // Don't show "anonymous" as phone number
                        $phoneNumber = $record->from_number ?? $record->to_number;
                        if ($phoneNumber === 'anonymous') {
                            return $directionLabel . ($directionLabel ? ' • Anonyme Nummer' : 'Anonyme Nummer');
                        }

                        return $directionLabel . ($phoneNumber ? ' • ' . $phoneNumber : '');
                    })
                    ->url(fn ($record) => $record->customer_id
                        ? CustomerResource::getUrl('view', ['record' => $record->customer_id])
                        : null
                    )
                    ->toggleable(),

                // 💾 OPTIONAL: Dauer Spalte (versteckt, weil in Status/Zeit zusammengefasst)
                Tables\Columns\TextColumn::make('duration_sec')
                    ->label('Dauer (optional)')
                    ->formatStateUsing(fn ($state) => FormatHelper::duration($state, 'long'))
                    ->icon('heroicon-m-clock')
                    ->color(fn ($state) => $state > 300 ? 'success' : ($state > 60 ? 'warning' : 'gray'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true), // 🔴 HIDDEN BY DEFAULT

                Tables\Columns\TextColumn::make('summary')
                    ->label('Zusammenfassung')
                    ->limit(80)
                    ->tooltip(function ($record) {
                        return $record->summary;
                    })
                    ->getStateUsing(function ($record) {
                        if ($record->summary) {
                            // Return first 80 chars of summary
                            $summary = is_string($record->summary) ? $record->summary : json_encode($record->summary);
                            return mb_substr($summary, 0, 80) . (mb_strlen($summary) > 80 ? '...' : '');
                        }
                        return 'Keine Zusammenfassung';
                    })
                    ->wrap()
                    ->toggleable(),

                // 💾 OPTIMIZED: Show ACTUAL booked services from appointments
                Tables\Columns\TextColumn::make('service_type')
                    ->label('Service')
                    ->badge()
                    ->getStateUsing(function ($record) {
                        try {
                            // Use already-loaded appointments (eager-loaded on line 201)
                            $appointments = $record->appointments ?? collect();

                            // No appointments → show "-"
                            if (!$appointments || $appointments->isEmpty()) {
                                return '-';
                            }

                            // Get unique service names, safely handling null services
                            $services = $appointments
                                ->filter(fn($appt) => $appt && $appt->service)
                                ->pluck('service.name')
                                ->filter()
                                ->unique()
                                ->values();

                            // No services found → show "-"
                            if ($services->isEmpty()) {
                                return '-';
                            }

                            // Return comma-separated list of all services
                            return $services->implode(', ');
                        } catch (\Throwable $e) {
                            return '-';
                        }
                    })
                    ->tooltip(function ($record) {
                        try {
                            // Use already-loaded appointments (eager-loaded on line 201)
                            $appointments = $record->appointments ?? collect();

                            if (!$appointments || $appointments->isEmpty()) {
                                return 'Kein Termin gebucht';
                            }

                            // Show details: Service name + Duration (filter out null appointments)
                            $details = $appointments
                                ->filter(fn($appt) => $appt)
                                ->map(function ($appt) {
                                    $name = $appt->service?->name ?? 'Unbekannt';
                                    $duration = $appt->service?->duration ?? $appt->duration ?? '?';
                                    return "{$name} ({$duration} Min)";
                                })
                                ->implode("\n");

                            return $details ?: 'Kein Termin gebucht';
                        } catch (\Throwable $e) {
                            return 'Fehler beim Laden';
                        }
                    })
                    ->color(function ($state): string {
                        if ($state === '-') return 'gray';
                        return 'success';
                    })
                    ->icon(function ($state): string {
                        if ($state === '-') return 'heroicon-m-dash';
                        return 'heroicon-m-calendar-days';
                    })
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('sentiment')
                    ->label('Stimmung')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true) // Hidden by default - 95% are "Neutral"
                    ->formatStateUsing(fn (?string $state): string => match (ucfirst(strtolower($state ?? ''))) {
                        'Positive' => '😊 Positiv',
                        'Neutral' => '😐 Neutral',
                        'Negative' => '😟 Negativ',
                        default => '❓ Unbekannt',
                    })
                    ->color(fn (?string $state): string => match (ucfirst(strtolower($state ?? ''))) {
                        'Positive' => 'success',
                        'Neutral' => 'gray',
                        'Negative' => 'danger',
                        default => 'gray',
                    }),

                // Ultra-compact appointment display with calendar icon + wish tracking
                Tables\Columns\TextColumn::make('appointment_details')
                    ->label('Termin')
                    ->getStateUsing(function (Call $record) {
                        // Smart accessor automatically loads from call_id or converted_appointment_id
                        $appointment = $record->appointment;

                        // 💾 NEW PHASE: Check for unfulfilled wishes
                        $unresolvedWishes = $record->appointmentWishes()
                            ->where('status', 'pending')
                            ->orderBy('created_at', 'desc')
                            ->get();

                        if (!$appointment) {
                            // No appointment - show wish if exists
                            if ($unresolvedWishes->isNotEmpty()) {
                                $wish = $unresolvedWishes->first();
                                $formattedDate = '';
                                if ($wish->desired_date) {
                                    $formattedDate = \Carbon\Carbon::parse($wish->desired_date)->format('d.m. H:i');
                                } elseif ($wish->desired_time) {
                                    $formattedDate = $wish->desired_time;
                                } else {
                                    $formattedDate = 'TBD';
                                }

                                return new HtmlString(
                                    '<div class="flex items-center gap-1">' .
                                    '<span class="text-lg">⏰</span>' .
                                    '<span class="text-xs text-orange-600 font-medium">Wunsch: ' . htmlspecialchars($formattedDate) . '</span>' .
                                    '</div>'
                                );
                            }

                            return new HtmlString('<span class="text-gray-400 text-xs">Kein Termin</span>');
                        }

                        // Load appointment relationships if needed
                        if ($appointment && !$appointment->relationLoaded('service')) {
                            $appointment->load(['service', 'staff', 'customer']);
                        }

                        if (!$appointment || !$appointment->starts_at) {
                            // No appointment data, just show the flag
                            return new HtmlString(
                                '<div class="flex items-center gap-1">' .
                                '<svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>' .
                                '<span class="text-xs text-green-600">Vereinbart</span>' .
                                '</div>'
                            );
                        }

                        // Parse dates
                        $startDate = \Carbon\Carbon::parse($appointment->starts_at);
                        $endDate = $appointment->ends_at ? \Carbon\Carbon::parse($appointment->ends_at) : null;

                        // Calculate duration
                        $duration = $endDate ? $startDate->diffInMinutes($endDate) : ($appointment->duration ?? 30);

                        // Format date and time
                        $dateStr = $startDate->format('d.m.');
                        $timeStr = $startDate->format('H:i');

                        // Build compact display
                        return new HtmlString(
                            '<div class="flex flex-col gap-0.5">' .
                            '<div class="flex items-center gap-1">' .
                            '<svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>' .
                            '<span class="text-xs font-medium">' . $dateStr . ' ' . $timeStr . '</span>' .
                            '</div>' .
                            '<span class="text-xs text-gray-500 pl-5">' . $duration . ' Min</span>' .
                            '</div>'
                        );
                    })
                    ->html()
                    ->tooltip(function (Call $record) {
                        // 💾 NEW PHASE: Show wishes in tooltip if no appointment
                        $unresolvedWishes = $record->appointmentWishes()
                            ->where('status', 'pending')
                            ->orderBy('created_at', 'desc')
                            ->first();

                        if (!$record->appointment && $unresolvedWishes && $unresolvedWishes->desired_date) {
                            $tooltip = "⏰ Terminwunsch:\n";
                            $tooltip .= "━━━━━━━━━━━━━━━\n";
                            $tooltip .= "Datum: " . \Carbon\Carbon::parse($unresolvedWishes->desired_date)->format('d.m.Y') . "\n";
                            if ($unresolvedWishes->desired_time) {
                                $tooltip .= "Zeit: " . $unresolvedWishes->desired_time . "\n";
                            }
                            $tooltip .= "Dauer: " . $unresolvedWishes->desired_duration . " Minuten\n";
                            if ($unresolvedWishes->desired_service) {
                                $tooltip .= "Service: " . $unresolvedWishes->desired_service . "\n";
                            }
                            $tooltip .= "\nGrund:\n";
                            $tooltip .= ($unresolvedWishes->rejection_reason_label ?? $unresolvedWishes->rejection_reason) . "\n";

                            if ($unresolvedWishes->alternatives_offered && count($unresolvedWishes->alternatives_offered) > 0) {
                                $tooltip .= "\nAngebotene Alternativen:\n";
                                foreach ($unresolvedWishes->alternatives_offered as $alt) {
                                    $tooltip .= "• " . ($alt['datetime'] ?? 'N/A') . "\n";
                                }
                            }

                            return $tooltip;
                        }

                        if (!$record->appointment) {
                            return null;
                        }

                        $appointment = $record->appointment;
                        if (!$appointment->starts_at) {
                            return 'Termin vereinbart (Zeit noch nicht festgelegt)';
                        }

                        $startDate = \Carbon\Carbon::parse($appointment->starts_at);
                        $endDate = $appointment->ends_at ? \Carbon\Carbon::parse($appointment->ends_at) : null;

                        $tooltip = "Termindetails:\n";
                        $tooltip .= "━━━━━━━━━━━━━━━\n";
                        $tooltip .= "Datum: " . $startDate->format('d.m.Y') . "\n";
                        $tooltip .= "Zeit: " . $startDate->format('H:i') . " - " . ($endDate ? $endDate->format('H:i') : '?') . "\n";
                        $tooltip .= "Dauer: " . ($endDate ? $startDate->diffInMinutes($endDate) : ($appointment->duration ?? '?')) . " Minuten\n";

                        if ($appointment->service) {
                            $tooltip .= "Service: " . $appointment->service->name . "\n";
                        }

                        return $tooltip;
                    })
                    ->toggleable(),

                // Staff column - shows which employee handles the appointment
                Tables\Columns\TextColumn::make('appointment_staff')
                    ->label('Mitarbeiter:in')
                    ->getStateUsing(function (Call $record) {
                        // Smart accessor automatically loads appointment
                        $appointment = $record->appointment;

                        if (!$appointment) {
                            return new HtmlString('<span class="text-gray-400 text-xs">-</span>');
                        }

                        // Load appointment relationships if needed
                        if (!$appointment->relationLoaded('service')) {
                            $appointment->load(['service', 'staff']);
                        }

                        $output = [];

                        // Show staff member prominently
                        if ($appointment->staff) {
                            $output[] = '<span class="text-xs font-medium text-blue-600">' . $appointment->staff->name . '</span>';
                        } else {
                            $output[] = '<span class="text-xs text-gray-400">Nicht zugewiesen</span>';
                        }

                        // Add service as secondary info (smaller, gray)
                        if ($appointment->service) {
                            $serviceName = \Str::limit($appointment->service->name, 20);
                            $output[] = '<span class="text-xs text-gray-500">' . $serviceName . '</span>';
                        }

                        return new HtmlString(
                            '<div class="flex flex-col gap-0.5">' .
                            implode('', $output) .
                            '</div>'
                        );
                    })
                    ->html()
                    ->tooltip(function (Call $record) {
                        if (!$record->appointment) {
                            return null;
                        }

                        $appointment = $record->appointment;

                        $tooltip = "Mitarbeiter:innen-Details:\n";
                        $tooltip .= "━━━━━━━━━━━━━━━━━━━\n";

                        if ($appointment->staff) {
                            $tooltip .= "Zuständige:r Mitarbeiter:in: " . $appointment->staff->name . "\n";
                        } else {
                            $tooltip .= "Status: Noch nicht zugewiesen\n";
                        }

                        if ($appointment->service) {
                            $tooltip .= "\nService: " . $appointment->service->name . "\n";
                            if ($appointment->service->duration) {
                                $tooltip .= "Standard-Dauer: " . $appointment->service->duration . " Min\n";
                            }
                        }

                        return $tooltip;
                    })
                    ->toggleable(),

                Tables\Columns\TextColumn::make('session_outcome')
                    ->label('Ergebnis')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'appointment_scheduled' => 'Termin vereinbart',
                        'information_provided' => 'Info gegeben',
                        'callback_requested' => 'Rückruf',
                        'complaint_registered' => 'Beschwerde',
                        'no_interest' => 'Kein Interesse',
                        'transferred' => 'Weitergeleitet',
                        'voicemail' => 'Voicemail',
                        default => '-',
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'appointment_scheduled' => 'success',
                        'information_provided' => 'info',
                        'callback_requested' => 'warning',
                        'complaint_registered' => 'danger',
                        'no_interest' => 'gray',
                        default => 'gray',
                    }),

                // Audio recording player column - simple HTML5 version
                Tables\Columns\TextColumn::make('recording_url')
                    ->label('Audio')
                    ->html()
                    ->getStateUsing(function ($record) {
                        if (empty($record->recording_url)) {
                            return '<span class="text-gray-400 text-xs">-</span>';
                        }

                        $url = $record->recording_url;
                        $duration = $record->duration_sec ? gmdate("i:s", $record->duration_sec) : '--:--';

                        // Simple HTML5 audio player that works reliably
                        return '
                            <div class="flex items-center gap-2">
                                <audio controls preload="none"
                                       class="h-8"
                                       style="height: 32px; max-width: 200px;"
                                       controlsList="nodownload">
                                    <source src="' . htmlspecialchars($url) . '" type="audio/mpeg">
                                    <source src="' . htmlspecialchars($url) . '" type="audio/wav">
                                </audio>
                                <span class="text-xs text-gray-600 dark:text-gray-400 whitespace-nowrap">' . $duration . '</span>
                            </div>
                        ';
                    })
                    ->toggleable(),

                // Service Price column - shows the price of the booked service
                Tables\Columns\TextColumn::make('service_price')
                    ->label('Service-Preis')
                    ->getStateUsing(function ($record) {
                        // Smart accessor automatically loads appointment
                        $appointment = $record->appointment;

                        // Load service relationship if needed
                        if ($appointment && !$appointment->relationLoaded('service')) {
                            $appointment->load('service');
                        }

                        // Check if appointment exists and has service with price
                        if (!$appointment || !$appointment->service) {
                            return new HtmlString('<span class="text-gray-400 text-xs">Kein Service</span>');
                        }

                        $service = $appointment->service;

                        // Check for service price
                        if (!$service->price || $service->price == 0) {
                            return new HtmlString('<span class="text-gray-400 text-xs">Kein Preis</span>');
                        }

                        // Format price (assuming price is in cents)
                        $price = is_numeric($service->price) ? $service->price / 100 : $service->price;
                        $formattedPrice = number_format($price, 2, ',', '.');

                        // Get service duration for per-minute calculation if available
                        $duration = $service->duration ?? 30; // Default 30 minutes
                        $pricePerMinute = $price / $duration;

                        return new HtmlString(
                            '<div class="flex flex-col gap-0.5">' .
                            '<span class="font-medium text-sm text-green-600 dark:text-green-400">' . $formattedPrice . '€</span>' .
                            '<span class="text-xs text-gray-500">' . number_format($pricePerMinute, 2, ',', '.') . '€/Min</span>' .
                            '</div>'
                        );
                    })
                    ->html()
                    ->tooltip(function ($record) {
                        if (!$record->appointment || !$record->appointment->service) {
                            return 'Kein Service gebucht';
                        }

                        $service = $record->appointment->service;
                        $price = is_numeric($service->price) ? $service->price / 100 : $service->price;

                        $tooltip = "💰 Service-Preisdetails:\n";
                        $tooltip .= "━━━━━━━━━━━━━━━━━\n";
                        $tooltip .= "Service: " . $service->name . "\n";
                        $tooltip .= "Preis: " . number_format($price, 2, ',', '.') . "€\n";

                        if ($service->duration) {
                            $tooltip .= "Dauer: " . $service->duration . " Minuten\n";
                            $tooltip .= "Preis/Min: " . number_format($price / $service->duration, 2, ',', '.') . "€\n";
                        }

                        if ($service->description) {
                            $tooltip .= "\nBeschreibung:\n" . \Str::limit($service->description, 100);
                        }

                        return $tooltip;
                    })
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->leftJoin('appointments', 'calls.id', '=', 'appointments.call_id')
                                    ->leftJoin('services', 'appointments.service_id', '=', 'services.id')
                                    ->orderBy('services.price', $direction);
                    })
                    ->toggleable(),

                // 💰 Streamlined cost display - Mobile-first, minimal info density
                Tables\Columns\TextColumn::make('financials')
                    ->label('Tel.-Kosten')
                    ->getStateUsing(function (Call $record) {
                        $user = auth()->user();
                        $primaryCost = 0;

                        if ($user->hasRole(['super-admin', 'super_admin', 'Super Admin'])) {
                            $primaryCost = $record->base_cost ?? 0;
                        } elseif ($user->hasRole(['reseller_admin', 'reseller_owner', 'reseller_support'])) {
                            $primaryCost = $record->reseller_cost ?? $record->base_cost ?? 0;
                        } else {
                            $primaryCost = $record->customer_cost ?? $record->cost ?? 0;
                            if (is_numeric($record->cost) && $primaryCost == 0) {
                                $primaryCost = round($record->cost * 100);
                            }
                        }

                        $formattedCost = number_format($primaryCost / 100, 2, ',', '.');

                        // Minimal status indicator (actual vs estimated)
                        $statusDot = '';
                        if ($record->total_external_cost_eur_cents > 0) {
                            // Green dot for actual costs
                            $statusDot = '<span class="inline-block w-1.5 h-1.5 rounded-full bg-green-500 dark:bg-green-400 ml-1" title="Tatsächliche Kosten"></span>';
                        } else {
                            // Yellow dot for estimated costs
                            $statusDot = '<span class="inline-block w-1.5 h-1.5 rounded-full bg-yellow-500 dark:bg-yellow-400 ml-1" title="Geschätzte Kosten"></span>';
                        }

                        return new HtmlString(
                            '<div class="flex items-center gap-0.5">' .
                            '<span class="font-semibold">' . $formattedCost . '€</span>' .
                            $statusDot .
                            '</div>'
                        );
                    })
                    ->html()
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy('customer_cost', $direction);
                    })
                    ->extraAttributes(['class' => 'font-mono'])
                    ->toggleable(),

                // 💰 Streamlined revenue display - Minimal, focused on key amounts
                Tables\Columns\TextColumn::make('revenue_profit')
                    ->label('Einnahmen/Gewinn')
                    ->getStateUsing(function (Call $record) {
                        $revenue = $record->getAppointmentRevenue();
                        $profit = $record->getCallProfit();

                        // No revenue: Simple dash indicator
                        if ($revenue === 0) {
                            return new HtmlString('<span class="text-gray-400 text-sm">-</span>');
                        }

                        $revenueFormatted = number_format($revenue / 100, 2, ',', '.');
                        $profitFormatted = number_format(abs($profit) / 100, 2, ',', '.');

                        // Minimal profit indicator
                        $isProfitable = $profit > 0;
                        $profitColor = $isProfitable ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400';
                        $profitSign = $isProfitable ? '+' : '-';

                        return new HtmlString(
                            '<div class="space-y-0.5">' .
                            // Revenue (primary)
                            '<div class="font-semibold">' . $revenueFormatted . '€</div>' .
                            // Profit (secondary, minimal)
                            '<div class="text-xs ' . $profitColor . '">' .
                            $profitSign . $profitFormatted . '€' .
                            '</div>' .
                            '</div>'
                        );
                    })
                    ->html()
                    ->visible(fn () =>
                        auth()->user()->hasRole(['super-admin', 'super_admin', 'Super Admin']) ||
                        auth()->user()->hasRole(['reseller_admin', 'reseller_owner', 'reseller_support'])
                    )
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('external_id')
                    ->label('ID')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('notes')
                    ->label('Notizen')
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->notes)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Von Datum'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Bis Datum'),
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
                            $indicators['created_from'] = 'Von: ' . Carbon::parse($data['created_from'])->format('d.m.Y');
                        }
                        if ($data['created_until'] ?? null) {
                            $indicators['created_until'] = 'Bis: ' . Carbon::parse($data['created_until'])->format('d.m.Y');
                        }
                        return $indicators;
                    }),

                Tables\Filters\SelectFilter::make('customer_id')
                    ->label('Kunde')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload(),

                // 🔴 NEW: Filter für Live Calls
                Tables\Filters\Filter::make('live_calls')
                    ->label('🔴 Laufende Anrufe')
                    ->query(fn (Builder $query): Builder =>
                        $query->whereIn('status', ['ongoing', 'in_progress', 'active', 'ringing'])
                    )
                    ->toggle(),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'ongoing' => '🔴 LIVE',
                        'completed' => '✅ Abgeschlossen',
                        'missed' => '📵 Verpasst',
                        'failed' => '❌ Fehlgeschlagen',
                        'no_answer' => '🔇 Keine Antwort',
                        'busy' => '📳 Besetzt',
                        'analyzed' => '📊 Analysiert',
                    ])
                    ->multiple(),

                Tables\Filters\TernaryFilter::make('appointment_made')
                    ->label('Mit Termin')
                    ->placeholder('Alle')
                    ->trueLabel('Mit Termin')
                    ->falseLabel('Ohne Termin'),

                Tables\Filters\SelectFilter::make('sentiment')
                    ->label('Stimmung')
                    ->options([
                        'Positive' => 'Positiv',
                        'Neutral' => 'Neutral',
                        'Negative' => 'Negativ',
                    ])
                    ->query(function (Builder $query, array $data) {
                        // Support both old lowercase and new capitalized values
                        if (isset($data['value']) && $data['value']) {
                            $query->where(function ($q) use ($data) {
                                $q->where('sentiment', $data['value'])
                                  ->orWhere('sentiment', strtolower($data['value']));
                            });
                        }
                    }),

                Tables\Filters\Filter::make('cost_range')
                    ->form([
                        Forms\Components\TextInput::make('cost_from')
                            ->label('Kosten ab (€)')
                            ->numeric()
                            ->prefix('€'),
                        Forms\Components\TextInput::make('cost_until')
                            ->label('Kosten bis (€)')
                            ->numeric()
                            ->prefix('€'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['cost_from'],
                                fn (Builder $query, $cost): Builder => $query->where('cost', '>=', $cost * 100),
                            )
                            ->when(
                                $data['cost_until'],
                                fn (Builder $query, $cost): Builder => $query->where('cost', '<=', $cost * 100),
                            );
                    }),

                Tables\Filters\Filter::make('today')
                    ->label('Heute')
                    ->query(fn (Builder $query): Builder => $query->whereDate('created_at', today()))
                    ->toggle(),

                Tables\Filters\Filter::make('this_week')
                    ->label('Diese Woche')
                    ->query(fn (Builder $query): Builder =>
                        $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
                    )
                    ->toggle(),

                Tables\Filters\Filter::make('long_calls')
                    ->label('Lange Anrufe (>5 Min.)')
                    ->query(fn (Builder $query): Builder => $query->where('duration_sec', '>', 300))
                    ->toggle(),

                Tables\Filters\Filter::make('callback_needed')
                    ->label('Rückruf erforderlich')
                    ->query(fn (Builder $query): Builder => $query->where('session_outcome', 'callback_requested'))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label('Anzeigen'),
                    Tables\Actions\EditAction::make()
                        ->label('Bearbeiten'),

                    Tables\Actions\Action::make('playRecording')
                        ->label('Aufnahme abspielen')
                        ->icon('heroicon-o-play')
                        ->color('info')
                        ->url(fn ($record) => $record->recording_url)
                        ->openUrlInNewTab()
                        ->visible(fn ($record) => !empty($record->recording_url)),

                    Tables\Actions\Action::make('createAppointment')
                        ->label('Termin erstellen')
                        ->icon('heroicon-o-calendar-days')
                        ->color('success')
                        ->visible(fn ($record) => !$record->appointment && $record->customer_id)
                        ->url(fn ($record) => CustomerResource::getUrl('edit', [
                            'record' => $record->customer_id,
                            'activeRelationManager' => 'appointments'
                        ])),

                    Tables\Actions\Action::make('addNote')
                        ->label('Notiz hinzufügen')
                        ->icon('heroicon-o-pencil-square')
                        ->form([
                            Forms\Components\Textarea::make('note')
                                ->label('Notiz')
                                ->required()
                                ->rows(3),
                        ])
                        ->action(function ($record, array $data): void {
                            $existing = $record->notes ?? '';
                            $newNote = "\n[" . now()->format('d.m.Y H:i') . " - " . auth()->user()->name . "]\n" . $data['note'];
                            $record->update(['notes' => $existing . $newNote]);

                            \Filament\Notifications\Notification::make()
                                ->title('Notiz hinzugefügt')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\Action::make('markSuccessful')
                        ->label('Als erfolgreich markieren')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(fn ($record) => $record->update(['call_successful' => true]))
                        ->visible(fn ($record): bool => !$record->call_successful),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),

                    Tables\Actions\BulkAction::make('markAsSuccessful')
                        ->label('Als erfolgreich markieren')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(fn (Collection $records) => $records->each->update(['call_successful' => true]))
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('export')
                        ->label('Exportieren')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->action(function (Collection $records): void {
                            // Export-Funktion wird implementiert
                            \Filament\Notifications\Notification::make()
                                ->title('Export gestartet')
                                ->body($records->count() . ' Anrufe werden exportiert...')
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s')
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->extremePaginationLinks()
            ->recordUrl(
                fn (Model $record): string => static::getUrl('view', ['record' => $record])
            )
            ->recordClasses(fn ($record) => $record->appointment_made ? 'hover:bg-green-50 dark:hover:bg-green-900/10' : 'hover:bg-gray-50 dark:hover:bg-gray-800');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // Tab-based organization with all important features
                InfolistTabs::make('Anrufdetails')
                    ->tabs([
                        // ÜBERSICHT TAB
                        InfolistTabs\Tab::make('Übersicht')
                            ->icon('heroicon-m-chart-bar-square')
                            ->extraAttributes(['class' => '!px-0 !mx-0'])
                            ->schema([
                                // Status Banner - Visual call outcome indicator (Full-Width with ViewEntry)
                                ViewEntry::make('status_banner')
                                    ->view('filament.status-banner-entry')
                                    ->viewData(function ($record) {
                                        $statusConfig = match($record->status) {
                                            'completed' => [
                                                'text' => 'Anruf erfolgreich abgeschlossen',
                                                'subtext' => $record->appointment_made ? 'Termin vereinbart' : 'Gespräch beendet',
                                                'color' => 'success',
                                                'icon' => 'heroicon-m-check-circle',
                                                'bg' => 'bg-green-50 dark:bg-green-900/20',
                                                'border' => 'border-green-200 dark:border-green-800',
                                                'text_color' => 'text-green-800 dark:text-green-200'
                                            ],
                                            'missed' => [
                                                'text' => 'Anruf verpasst',
                                                'subtext' => 'Kunde hat nicht abgehoben',
                                                'color' => 'warning',
                                                'icon' => 'heroicon-m-phone-x-mark',
                                                'bg' => 'bg-yellow-50 dark:bg-yellow-900/20',
                                                'border' => 'border-yellow-200 dark:border-yellow-800',
                                                'text_color' => 'text-yellow-800 dark:text-yellow-200'
                                            ],
                                            'failed' => [
                                                'text' => 'Anruf fehlgeschlagen',
                                                'subtext' => 'Technischer Fehler oder Verbindungsproblem',
                                                'color' => 'danger',
                                                'icon' => 'heroicon-m-x-circle',
                                                'bg' => 'bg-red-50 dark:bg-red-900/20',
                                                'border' => 'border-red-200 dark:border-red-800',
                                                'text_color' => 'text-red-800 dark:text-red-200'
                                            ],
                                            'busy' => [
                                                'text' => 'Besetzt',
                                                'subtext' => 'Kunde war beschäftigt',
                                                'color' => 'warning',
                                                'icon' => 'heroicon-m-phone',
                                                'bg' => 'bg-orange-50 dark:bg-orange-900/20',
                                                'border' => 'border-orange-200 dark:border-orange-800',
                                                'text_color' => 'text-orange-800 dark:text-orange-200'
                                            ],
                                            'no_answer' => [
                                                'text' => 'Keine Antwort',
                                                'subtext' => 'Anruf nicht angenommen',
                                                'color' => 'gray',
                                                'icon' => 'heroicon-m-phone-arrow-down-left',
                                                'bg' => 'bg-gray-50 dark:bg-gray-900/20',
                                                'border' => 'border-gray-200 dark:border-gray-800',
                                                'text_color' => 'text-gray-800 dark:text-gray-200'
                                            ],
                                            default => [
                                                'text' => 'Status unbekannt',
                                                'subtext' => '',
                                                'color' => 'gray',
                                                'icon' => 'heroicon-m-question-mark-circle',
                                                'bg' => 'bg-gray-50 dark:bg-gray-900/20',
                                                'border' => 'border-gray-200 dark:border-gray-800',
                                                'text_color' => 'text-gray-800 dark:text-gray-200'
                                            ]
                                        };

                                        $iconSvg = match($statusConfig['icon']) {
                                            'heroicon-m-check-circle' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>',
                                            'heroicon-m-x-circle' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>',
                                            'heroicon-m-phone-x-mark' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.75 3.75L18 6m0 0l2.25 2.25M18 6l2.25-2.25M18 6l-2.25 2.25" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.75 6.75h.007v.008H3.75V6.75zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" /></svg>',
                                            default => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>'
                                        };

                                        return [
                                            'bg' => $statusConfig['bg'],
                                            'border' => $statusConfig['border'],
                                            'textColor' => $statusConfig['text_color'],
                                            'icon' => $iconSvg,
                                            'text' => $statusConfig['text'],
                                            'subtext' => $statusConfig['subtext']
                                        ];
                                    })
                                    ->columnSpanFull(),

                                // KPI Cards at the top - Optimized responsive grid
                                Grid::make(['default' => 1, 'sm' => 2, 'lg' => 2, 'xl' => 4])
                                    ->schema([
                                        TextEntry::make('duration_display')
                                            ->label('Dauer')
                                            ->getStateUsing(fn ($record) =>
                                                view('filament.kpi-card', [
                                                    'label' => 'Gesprächsdauer',
                                                    'value' => gmdate('i:s', $record->duration_sec ?? 0),
                                                    'sublabel' => 'Minuten:Sekunden',
                                                    'icon' => 'heroicon-m-clock',
                                                    'color' => $record->duration_sec > 300 ? 'success' : 'warning'
                                                ])->render()
                                            )
                                            ->html(),

                                        TextEntry::make('appointment_display')
                                            ->label('Termin')
                                            ->getStateUsing(fn ($record) =>
                                                view('filament.kpi-card', [
                                                    'label' => 'Terminvereinbarung',
                                                    'value' => $record->appointment_made ? '✓' : '−',
                                                    'sublabel' => $record->appointment_made ? 'Termin vereinbart' : 'Kein Termin',
                                                    'icon' => 'heroicon-m-calendar',
                                                    'color' => $record->appointment_made ? 'success' : 'gray'
                                                ])->render()
                                            )
                                            ->html(),

                                        TextEntry::make('cost_display')
                                            ->label('Kosten')
                                            ->getStateUsing(fn ($record) =>
                                                view('filament.kpi-card', [
                                                    'label' => 'Anrufkosten',
                                                    'value' => number_format(($record->cost ?? 0) / 100, 2, ',', '.') . '€',
                                                    'sublabel' => 'Gesamtkosten',
                                                    'icon' => 'heroicon-m-currency-euro',
                                                    'color' => 'info'
                                                ])->render()
                                            )
                                            ->html(),

                                        TextEntry::make('sentiment_display')
                                            ->label('Stimmung')
                                            ->getStateUsing(fn ($record) =>
                                                view('filament.kpi-card', [
                                                    'label' => 'Stimmungsanalyse',
                                                    'value' => match(ucfirst(strtolower($record->sentiment ?? ''))) {
                                                        'Positive' => '😊',
                                                        'Negative' => '😟',
                                                        'Neutral' => '😐',
                                                        default => '?',
                                                    },
                                                    'sublabel' => match(ucfirst(strtolower($record->sentiment ?? ''))) {
                                                        'Positive' => 'Positiv',
                                                        'Negative' => 'Negativ',
                                                        'Neutral' => 'Neutral',
                                                        default => 'Unbekannt',
                                                    },
                                                    'icon' => 'heroicon-m-face-smile',
                                                    'color' => match(ucfirst(strtolower($record->sentiment ?? ''))) {
                                                        'Positive' => 'success',
                                                        'Negative' => 'danger',
                                                        'Neutral' => 'warning',
                                                        default => 'gray',
                                                    }
                                                ])->render()
                                            )
                                            ->html(),
                                    ])
                                    ->columnSpanFull(),

                                // Termin Details - Promoted to full width for prominence (Grid-wrapped + CSS Override)
                                Grid::make(1)
                                    ->extraAttributes(['class' => '!max-w-full w-full'])
                                    ->schema([
                                        InfoSection::make('Termin Details')
                                            ->icon('heroicon-m-calendar-days')
                                            ->extraAttributes(['class' => '!max-w-full w-full'])
                                            ->schema([
                                                TextEntry::make('appointment_status')
                                                    ->label('Termin Status')
                                            ->badge()
                                            ->getStateUsing(function ($record) {
                                                $appointment = $record->appointment;

                                                if (!$appointment) {
                                                    return $record->appointment_made
                                                        ? 'Geplant (noch nicht erstellt)'
                                                        : 'Kein Termin';
                                                }

                                                return match($appointment->status) {
                                                    'confirmed' => 'Bestätigt',
                                                    'pending' => 'Ausstehend',
                                                    'cancelled' => 'Abgesagt',
                                                    'completed' => 'Abgeschlossen',
                                                    'scheduled' => 'Geplant',
                                                    default => $appointment->status
                                                };
                                            })
                                            ->color(function ($record) {
                                                $appointment = $record->appointment;

                                                if (!$appointment) {
                                                    return $record->appointment_made ? 'warning' : 'gray';
                                                }

                                                return match($appointment->status) {
                                                    'confirmed', 'completed', 'scheduled' => 'success',
                                                    'pending' => 'warning',
                                                    'cancelled' => 'danger',
                                                    default => 'gray'
                                                };
                                            })
                                            ->icon('heroicon-m-calendar-days'),

                                        Grid::make(2)
                                            ->schema([
                                                TextEntry::make('appointment.starts_at')
                                                    ->label('Startzeit')
                                                    ->dateTime('d.m.Y H:i')
                                                    ->placeholder('-')
                                                    ->getStateUsing(fn ($record) => $record->appointment?->starts_at)
                                                    ->icon('heroicon-m-clock'),

                                                TextEntry::make('appointment.ends_at')
                                                    ->label('Endzeit')
                                                    ->dateTime('d.m.Y H:i')
                                                    ->placeholder('-')
                                                    ->getStateUsing(fn ($record) => $record->appointment?->ends_at)
                                                    ->icon('heroicon-m-clock'),

                                                TextEntry::make('appointment.staff.name')
                                                    ->label('Mitarbeiter')
                                                    ->placeholder('Nicht zugewiesen')
                                                    ->getStateUsing(function ($record) {
                                                        $appointment = $record->appointment;
                                                        if (!$appointment) return null;

                                                        if (!$appointment->relationLoaded('staff')) {
                                                            $appointment->load('staff');
                                                        }

                                                        return $appointment->staff?->name;
                                                    })
                                                    ->icon('heroicon-m-user-circle'),

                                                TextEntry::make('appointment.service.name')
                                                    ->label('Service')
                                                    ->placeholder('Kein Service')
                                                    ->getStateUsing(function ($record) {
                                                        $appointment = $record->appointment;
                                                        if (!$appointment) return null;

                                                        if (!$appointment->relationLoaded('service')) {
                                                            $appointment->load('service');
                                                        }

                                                        return $appointment->service?->name;
                                                    })
                                                    ->icon('heroicon-m-wrench-screwdriver'),
                                            ]),

                                        TextEntry::make('appointment.notes')
                                            ->label('Notizen')
                                            ->placeholder('Keine Notizen')
                                            ->columnSpanFull()
                                            ->getStateUsing(fn ($record) => $record->appointment?->notes),

                                        TextEntry::make('appointment_link')
                                            ->label('Termin verwalten')
                                            ->getStateUsing(function ($record) {
                                                $appointment = $record->appointment;

                                                if (!$appointment) {
                                                    return '-';
                                                }

                                                try {
                                                    $url = route('filament.admin.resources.appointments.view',
                                                        ['record' => $appointment->id]);

                                                    return new HtmlString(
                                                        '<a href="' . $url . '" ' .
                                                        'class="text-primary-600 hover:text-primary-700 dark:text-primary-400 hover:underline font-medium" ' .
                                                        'target="_blank">' .
                                                        '🔗 Termin #' . $appointment->id . ' öffnen' .
                                                        '</a>'
                                                    );
                                                } catch (\Exception $e) {
                                                    return 'Termin #' . $appointment->id;
                                                }
                                            })
                                                ->html()
                                                ->visible(fn ($record) => $record->appointment !== null),
                                            ])
                                            ->visible(fn ($record) => $record->appointment !== null)
                                            ->collapsible()
                                            ->collapsed(false)
                                            ->columnSpanFull(),
                                    ])
                                    ->visible(fn ($record) => $record->appointment !== null)
                                    ->columnSpanFull(),

                                // Gesprächszusammenfassung - Prominent am Anfang (Grid-wrapped + CSS Override)
                                Grid::make(1)
                                    ->extraAttributes(['class' => '!max-w-full w-full'])
                                    ->schema([
                                        InfoSection::make('Gesprächszusammenfassung')
                                            ->icon('heroicon-m-document-text')
                                            ->extraAttributes(['class' => '!max-w-full w-full'])
                                            ->schema([
                                                TextEntry::make('summary')
                                            ->label('')
                                            ->getStateUsing(function ($record) {
                                                if (empty($record->summary)) {
                                                    return '<div class="text-gray-500 dark:text-gray-400 italic">Keine Zusammenfassung vorhanden</div>';
                                                }

                                                $summary = $record->summary;
                                                if (is_array($summary)) {
                                                    $summary = json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                                                }

                                                // Auto-translate to German if needed (with error handling)
                                                try {
                                                    $translationService = app(\App\Services\FreeTranslationService::class);
                                                    $translatedSummary = $translationService->translateToGerman($summary);
                                                    $isTranslated = ($translatedSummary !== $summary);
                                                } catch (\Exception $e) {
                                                    // Fallback to original if translation fails
                                                    \Log::warning('Translation failed for call ' . $record->id, [
                                                        'error' => $e->getMessage()
                                                    ]);
                                                    $translatedSummary = $summary;
                                                    $isTranslated = false;
                                                }

                                                $callId = $record->id;

                                                return '<div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 border border-blue-200 dark:border-blue-800">' .
                                                       '<div class="mb-3">' .
                                                       '<div class="flex items-center justify-between flex-wrap gap-2">' .
                                                       '<div class="flex items-center gap-2">' .
                                                       '<svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">' .
                                                       '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />' .
                                                       '</svg>' .
                                                       '<span class="text-sm font-medium text-gray-700 dark:text-gray-300">Zusammenfassung</span>' .
                                                       ($isTranslated ? '<span class="text-xs bg-green-100 text-green-700 dark:bg-green-800 dark:text-green-200 px-2 py-0.5 rounded-full">Übersetzt</span>' : '') .
                                                       '</div>' .
                                                       '<div class="flex gap-1" id="lang-buttons-' . $callId . '">' .
                                                       '<button type="button" onclick="window.showTranslation_' . $callId . '(\'de\')" class="lang-btn-' . $callId . ' lang-btn-de-' . $callId . ' px-2 py-1 text-xs font-medium rounded-md bg-blue-600 text-white border border-blue-600 transition-colors">🇩🇪 Deutsch</button>' .
                                                       '<button type="button" onclick="window.showTranslation_' . $callId . '(\'tr\')" class="lang-btn-' . $callId . ' lang-btn-tr-' . $callId . ' px-2 py-1 text-xs font-medium rounded-md bg-white text-gray-700 dark:bg-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">🇹🇷 Türkçe</button>' .
                                                       '<button type="button" onclick="window.showTranslation_' . $callId . '(\'ar\')" class="lang-btn-' . $callId . ' lang-btn-ar-' . $callId . ' px-2 py-1 text-xs font-medium rounded-md bg-white text-gray-700 dark:bg-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">🇸🇦 العربية</button>' .
                                                       '<button type="button" onclick="window.showTranslation_' . $callId . '(\'original\')" class="lang-btn-' . $callId . ' lang-btn-original-' . $callId . ' px-2 py-1 text-xs font-medium rounded-md bg-white text-gray-700 dark:bg-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">🇬🇧 Original</button>' .
                                                       '</div>' .
                                                       '</div>' .
                                                       '</div>' .
                                                       '<div id="summary-content-' . $callId . '" class="text-sm text-gray-900 dark:text-gray-100 leading-relaxed">' .
                                                       '<div class="summary-de-' . $callId . '">' . nl2br(htmlspecialchars($translatedSummary)) . '</div>' .
                                                       '<div class="summary-tr-' . $callId . ' hidden"><div class="text-center py-4"><svg class="animate-spin h-5 w-5 mx-auto text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg><span class="text-xs text-gray-500 mt-2 block">Übersetzung lädt...</span></div></div>' .
                                                       '<div class="summary-ar-' . $callId . ' hidden"><div class="text-center py-4"><svg class="animate-spin h-5 w-5 mx-auto text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg><span class="text-xs text-gray-500 mt-2 block">Übersetzung lädt...</span></div></div>' .
                                                       '<div class="summary-original-' . $callId . ' hidden">' . nl2br(htmlspecialchars($summary)) . '</div>' .
                                                       '</div>' .
                                                       '<script>' .
                                                       '(function() {' .
                                                       '  const callId = ' . $callId . ';' .
                                                       '  window.translations_' . $callId . ' = {};' .
                                                       '  window.translations_' . $callId . '[\'de\'] = ' . json_encode($translatedSummary) . ';' .
                                                       '  window.translations_' . $callId . '[\'original\'] = ' . json_encode($summary) . ';' .
                                                       '  window.showTranslation_' . $callId . ' = function(lang) {' .
                                                       '    console.log("showTranslation_' . $callId . ' called with lang:", lang);' .
                                                       '    const buttons = document.querySelectorAll(".lang-btn-' . $callId . '");' .
                                                       '    buttons.forEach(btn => {' .
                                                       '      btn.classList.remove("bg-blue-600", "text-white");' .
                                                       '      btn.classList.add("bg-white", "text-gray-700", "dark:bg-gray-700", "dark:text-gray-300");' .
                                                       '    });' .
                                                       '    const activeBtn = document.querySelector(".lang-btn-" + lang + "-' . $callId . '");' .
                                                       '    if (activeBtn) {' .
                                                       '      activeBtn.classList.remove("bg-white", "text-gray-700", "dark:bg-gray-700", "dark:text-gray-300");' .
                                                       '      activeBtn.classList.add("bg-blue-600", "text-white");' .
                                                       '    }' .
                                                       '    const summaryDivs = document.querySelectorAll(".summary-de-' . $callId . ', .summary-tr-' . $callId . ', .summary-ar-' . $callId . ', .summary-original-' . $callId . '");' .
                                                       '    console.log("Found divs to hide:", summaryDivs.length);' .
                                                       '    summaryDivs.forEach(div => div.classList.add("hidden"));' .
                                                       '    const targetDiv = document.querySelector(".summary-" + lang + "-' . $callId . '");' .
                                                       '    console.log("Target div to show:", targetDiv ? ".summary-" + lang + "-' . $callId . '" : "not found");' .
                                                       '    if (targetDiv) targetDiv.classList.remove("hidden");' .
                                                       '    if ((lang === "tr" || lang === "ar") && !window.translations_' . $callId . '[lang]) {' .
                                                       '      console.log("Fetching translation for:", lang);' .
                                                       '      const loadingDiv = document.querySelector(".summary-" + lang + "-' . $callId . '");' .
                                                       '      if (loadingDiv) loadingDiv.innerHTML = "<div class=\'text-center py-4\'><svg class=\'animate-spin h-5 w-5 mx-auto text-gray-500\' xmlns=\'http://www.w3.org/2000/svg\' fill=\'none\' viewBox=\'0 0 24 24\'><circle class=\'opacity-25\' cx=\'12\' cy=\'12\' r=\'10\' stroke=\'currentColor\' stroke-width=\'4\'></circle><path class=\'opacity-75\' fill=\'currentColor\' d=\'M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z\'></path></svg><span class=\'text-xs text-gray-500 mt-2 block\'>Übersetzung lädt...</span></div>";' .
                                                       '      fetch("/api/translate", {' .
                                                       '        method: "POST",' .
                                                       '        headers: {"Content-Type": "application/json", "Accept": "application/json"},' .
                                                       '        body: JSON.stringify({text: window.translations_' . $callId . '[\'original\'], target: lang, call_id: ' . $callId . '})' .
                                                       '      })' .
                                                       '      .then(r => r.json())' .
                                                       '      .then(data => {' .
                                                       '        console.log("Translation received:", data);' .
                                                       '        window.translations_' . $callId . '[lang] = data.translation;' .
                                                       '        const div = document.querySelector(".summary-" + lang + "-' . $callId . '");' .
                                                       '        if (div) div.innerHTML = data.translation.replace(/\\n/g, "<br>");' .
                                                       '      })' .
                                                       '      .catch(err => {' .
                                                       '        console.error("Translation error:", err);' .
                                                       '        const div = document.querySelector(".summary-" + lang + "-' . $callId . '");' .
                                                       '        if (div) div.innerHTML = "<span class=\'text-red-500\'>Übersetzung fehlgeschlagen</span>";' .
                                                       '      });' .
                                                       '    }' .
                                                       '  };' .
                                                       '})();' .
                                                       '</script>' .
                                                       '</div>';
                                            })
                                            ->html()
                                            ->columnSpanFull(),
                                            ])
                                            ->collapsible()
                                            ->collapsed(false)
                                            ->columnSpanFull(),
                                    ])
                                    ->columnSpanFull(),

                                // Main call information (Changed to Full-Width single column + CSS Override)
                                Grid::make(1)
                                    ->extraAttributes(['class' => '!max-w-full w-full'])
                                    ->schema([
                                        InfoSection::make('Anrufinformationen')
                                            ->extraAttributes(['class' => '!max-w-full w-full'])
                                            ->schema([
                                                TextEntry::make('customer_name')
                                                    ->label(function ($record) {
                                                        // Dynamic label based on verification status
                                                        if ($record->customer_id && $record->customer) {
                                                            return 'Kunde';  // Verified customer with profile
                                                        } elseif ($record->customer_name_verified === true) {
                                                            return 'Kunde';  // Verified through phone number
                                                        } elseif ($record->customer_name_verified === false && $record->from_number === 'anonymous') {
                                                            return 'Anrufer';  // Unverified anonymous caller
                                                        } else {
                                                            return 'Anrufer';  // Default to caller
                                                        }
                                                    })
                                                    ->html()
                                                    ->getStateUsing(function ($record) {
                                                        $name = '';
                                                        $verificationIcon = '';

                                                        // Filter out transcript fragments
                                                        $nonNamePhrases = ['mir nicht', 'guten tag', 'guten morgen', 'hallo', 'ja', 'nein', 'gleich fertig'];
                                                        $customerNameLower = $record->customer_name ? strtolower(trim($record->customer_name)) : '';
                                                        $isTranscriptFragment = in_array($customerNameLower, $nonNamePhrases);

                                                        // Anonymous (no name, transcript fragment, OR anonymous number without real name)
                                                        if ($record->from_number === 'anonymous' && (!$record->customer_name || trim($record->customer_name) === '' || $isTranscriptFragment)) {
                                                            return '<div class="flex items-center"><span class="font-bold text-lg text-gray-600">Anonym</span></div>';
                                                        }

                                                        // Check for customer_name first (even with anonymous number, if it's a real name)
                                                        if ($record->customer_name && !$isTranscriptFragment) {
                                                            $name = htmlspecialchars($record->customer_name);

                                                            // Add verification icon based on status
                                                            if ($record->customer_name_verified === true) {
                                                                $verificationIcon = ' <span class="inline-flex items-center" title="Verifizierter Name - Telefonnummer bekannt (99% Sicherheit)"><svg class="w-5 h-5 ml-1 text-green-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg></span>';
                                                            } elseif ($record->customer_name_verified === false) {
                                                                $verificationIcon = ' <span class="inline-flex items-center" title="Unverifizierter Name - Aus anonymem Anruf extrahiert (0% Sicherheit)"><svg class="w-5 h-5 ml-1 text-orange-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/></svg></span>';
                                                            }
                                                        } elseif ($record->customer_id && $record->customer) {
                                                            $name = htmlspecialchars($record->customer->name);
                                                            $verificationIcon = ' <span class="inline-flex items-center" title="Verifizierter Kunde - Mit Kundenprofil verknüpft"><svg class="w-5 h-5 ml-1 text-green-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg></span>';
                                                        } else {
                                                            $name = $record->from_number === 'anonymous' ? 'Anonym' : 'Unbekannt';
                                                        }

                                                        return '<div class="flex items-center"><span class="font-bold text-lg">' . $name . '</span>' . $verificationIcon . '</div>';
                                                    })
                                                    ->icon('heroicon-m-user'),

                                                TextEntry::make('from_number')
                                                    ->label('Anrufer-Nummer')
                                                    ->icon('heroicon-m-phone-arrow-up-right')
                                                    ->getStateUsing(fn ($record) => $record->from_number === 'anonymous' ? 'Anonyme Nummer' : $record->from_number)
                                                    ->copyable(fn ($record) => $record->from_number !== 'anonymous'),

                                                TextEntry::make('to_number')
                                                    ->label('Angerufene Nummer')
                                                    ->icon('heroicon-m-phone-arrow-down-left')
                                                    ->copyable(),

                                                TextEntry::make('direction')
                                                    ->label('Anrufrichtung')
                                                    ->badge()
                                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                                        'inbound' => 'Eingehend',
                                                        'outbound' => 'Ausgehend',
                                                        default => $state,
                                                    })
                                                    ->color(fn (string $state): string => match ($state) {
                                                        'inbound' => 'success',
                                                        'outbound' => 'info',
                                                        default => 'gray',
                                                    }),

                                                TextEntry::make('created_at')
                                                    ->label('Anrufzeit')
                                                    ->icon('heroicon-m-calendar')
                                                    ->dateTime('d.m.Y H:i:s'),
                                            ]),

                                        InfoSection::make('Ergebnis')
                                            ->extraAttributes(['class' => '!max-w-full w-full'])
                                            ->schema([
                                                TextEntry::make('status')
                                                    ->label('Status')
                                                    ->badge()
                                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                                        'completed' => 'Abgeschlossen',
                                                        'missed' => 'Verpasst',
                                                        'failed' => 'Fehlgeschlagen',
                                                        'busy' => 'Besetzt',
                                                        'no_answer' => 'Keine Antwort',
                                                        default => $state,
                                                    })
                                                    ->color(fn (string $state): string => match ($state) {
                                                        'completed' => 'success',
                                                        'missed' => 'warning',
                                                        'failed' => 'danger',
                                                        'busy' => 'warning',
                                                        'no_answer' => 'gray',
                                                        default => 'gray',
                                                    }),

                                                TextEntry::make('appointment_made')
                                                    ->label('Termin vereinbart')
                                                    ->badge()
                                                    ->formatStateUsing(fn ($state): string => $state ? 'Ja' : 'Nein')
                                                    ->color(fn ($state): string => $state ? 'success' : 'gray'),

                                                TextEntry::make('session_outcome')
                                                    ->label('Gesprächsergebnis')
                                                    ->badge()
                                                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                                                        'appointment_scheduled' => 'Termin vereinbart',
                                                        'information_provided' => 'Info gegeben',
                                                        'callback_requested' => 'Rückruf erwünscht',
                                                        'complaint_registered' => 'Beschwerde',
                                                        'no_interest' => 'Kein Interesse',
                                                        'transferred' => 'Weitergeleitet',
                                                        'voicemail' => 'Voicemail',
                                                        default => $state ?? 'Nicht definiert',
                                                    })
                                                    ->color(fn (?string $state): string => match ($state) {
                                                        'appointment_scheduled' => 'success',
                                                        'information_provided' => 'info',
                                                        'callback_requested' => 'warning',
                                                        'complaint_registered' => 'danger',
                                                        'no_interest' => 'gray',
                                                        'transferred' => 'info',
                                                        'voicemail' => 'warning',
                                                        default => 'gray',
                                                    }),

                                                TextEntry::make('sentiment')
                                                    ->label('Stimmung')
                                                    ->badge()
                                                    ->formatStateUsing(fn (?string $state): string => match (ucfirst(strtolower($state ?? ''))) {
                                                        'Positive' => 'Positiv',
                                                        'Neutral' => 'Neutral',
                                                        'Negative' => 'Negativ',
                                                        default => 'Unbekannt',
                                                    })
                                                    ->color(fn (?string $state): string => match (ucfirst(strtolower($state ?? ''))) {
                                                        'Positive' => 'success',
                                                        'Neutral' => 'gray',
                                                        'Negative' => 'danger',
                                                        default => 'gray',
                                                    }),
                                            ]),
                                    ]),
                            ]),

                        // AUFNAHME & TRANSKRIPT TAB
                        InfolistTabs\Tab::make('Aufnahme & Transkript')
                            ->icon('heroicon-m-microphone')
                            ->schema([
                                // Audio Player
                                TextEntry::make('audio_player_display')
                                    ->label('')
                                    ->getStateUsing(function ($record) {
                                        if (empty($record->recording_url)) {
                                            return '<div class="text-center text-gray-500 py-8">Keine Aufnahme verfügbar</div>';
                                        }
                                        return view('filament.audio-player', [
                                            'url' => $record->recording_url,
                                            'duration' => $record->duration_sec ?? 0,
                                            'callId' => $record->id
                                        ])->render();
                                    })
                                    ->html()
                                    ->columnSpanFull(),

                                // Transcript Viewer
                                TextEntry::make('transcript_viewer_display')
                                    ->label('')
                                    ->getStateUsing(function ($record) {
                                        if (empty($record->transcript)) {
                                            return '<div class="text-center text-gray-500 py-8">Kein Transkript verfügbar</div>';
                                        }

                                        $text = $record->transcript;
                                        if (is_array($text)) {
                                            $text = isset($text['text']) ? $text['text'] :
                                                   (isset($text['transcript']) ? $text['transcript'] :
                                                   json_encode($text, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                                        }

                                        $wordCount = str_word_count($text);
                                        $readingTime = ceil($wordCount / 200);

                                        return view('filament.transcript-viewer', [
                                            'text' => $text,
                                            'wordCount' => $wordCount,
                                            'readingTime' => $readingTime
                                        ])->render();
                                    })
                                    ->html()
                                    ->columnSpanFull(),
                            ]),

                        // KOSTEN & PROFIT TAB
                        InfolistTabs\Tab::make('Kosten & Profit')
                            ->icon('heroicon-m-currency-euro')
                            ->visible(fn () => auth()->user() &&
                                (auth()->user()->hasRole(['super-admin', 'super_admin', 'Super Admin']) ||
                                 auth()->user()->hasRole(['reseller_admin', 'reseller_owner', 'reseller_support'])))
                            ->schema([
                                TextEntry::make('profit_display_view')
                                    ->label('')
                                    ->getStateUsing(function ($record) {
                                        $user = auth()->user();
                                        if (!$user) return '<div class="text-center text-gray-500 py-8">Keine Berechtigung</div>';

                                        $calculator = app(\App\Services\CostCalculator::class);
                                        $profitData = $calculator->getDisplayProfit($record, $user);

                                        if ($profitData['type'] === 'none') {
                                            return '<div class="text-center text-gray-500 py-8">Keine Profit-Daten verfügbar</div>';
                                        }

                                        return view('filament.profit-display', [
                                            'profitData' => $profitData,
                                            'baseCost' => ($record->base_cost ?? 0) / 100,
                                            'resellerCost' => ($record->reseller_cost ?? 0) / 100,
                                            'customerCost' => (($record->customer_cost ?? $record->cost ?? 0)) / 100
                                        ])->render();
                                    })
                                    ->html()
                                    ->columnSpanFull(),
                            ]),

                        // ANALYSE TAB
                        InfolistTabs\Tab::make('Analyse')
                            ->icon('heroicon-m-chart-bar')
                            ->schema([
                                TextEntry::make('notes')
                                    ->label('Notizen')
                                    ->formatStateUsing(fn ($state) => !empty($state) ? $state : 'Keine Notizen vorhanden')
                                    ->columnSpanFull(),

                                TextEntry::make('urgency_level')
                                    ->label('Dringlichkeit')
                                    ->badge()
                                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                                        'urgent' => 'Dringend',
                                        'high' => 'Hoch',
                                        'medium' => 'Mittel',
                                        'low' => 'Niedrig',
                                        default => $state ?? 'Unbekannt',
                                    })
                                    ->color(fn (?string $state): string => match ($state) {
                                        'urgent' => 'danger',
                                        'high' => 'warning',
                                        'medium' => 'info',
                                        'low' => 'success',
                                        default => 'gray',
                                    }),

                                TextEntry::make('lead_status')
                                    ->label('Lead-Status')
                                    ->badge()
                                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                                        'appointment_set' => 'Termin vereinbart',
                                        'qualified' => 'Qualifiziert',
                                        'not_interested' => 'Kein Interesse',
                                        'contacted' => 'Kontaktiert',
                                        'no_answer' => 'Nicht erreicht',
                                        'busy' => 'Beschäftigt',
                                        'failed' => 'Fehlgeschlagen',
                                        'connected' => 'Verbunden',
                                        'follow_up_required' => 'Follow-up nötig',
                                        default => $state ?? 'Unbekannt',
                                    })
                                    ->color(fn (?string $state): string => match ($state) {
                                        'appointment_set' => 'success',
                                        'qualified' => 'success',
                                        'not_interested' => 'danger',
                                        'contacted' => 'info',
                                        'no_answer' => 'warning',
                                        'busy' => 'warning',
                                        'failed' => 'danger',
                                        'connected' => 'info',
                                        'follow_up_required' => 'warning',
                                        default => 'gray',
                                    }),
                            ]),

                        // TECHNISCHE DETAILS TAB
                        InfolistTabs\Tab::make('Technische Details')
                            ->icon('heroicon-m-cog')
                            ->schema([
                                Grid::make(3)
                                    ->schema([
                                        TextEntry::make('external_id')
                                            ->label('Externe ID')
                                            ->badge()
                                            ->color('gray')
                                            ->copyable(),

                                        TextEntry::make('retell_call_id')
                                            ->label('Retell Anruf-ID')
                                            ->copyable(),

                                        TextEntry::make('conversation_id')
                                            ->label('Konversations-ID')
                                            ->copyable(),

                                        TextEntry::make('agent_id')
                                            ->label('Agent-ID'),

                                        TextEntry::make('phone_number_id')
                                            ->label('Telefonnummer-ID'),

                                        TextEntry::make('disconnection_reason')
                                            ->label('Trennungsgrund')
                                            ->formatStateUsing(fn (?string $state): string => match ($state) {
                                                'customer_hangup' => 'Kunde hat aufgelegt',
                                                'agent_hangup' => 'Agent hat beendet',
                                                'call_transfer' => 'Anruf weitergeleitet',
                                                'voicemail_reached' => 'Voicemail erreicht',
                                                'inactivity' => 'Inaktivität',
                                                'error' => 'Fehler',
                                                'normal' => 'Normal beendet',
                                                default => $state ?? 'Unbekannt',
                                            }),

                                        TextEntry::make('cost_cents')
                                            ->label('Kosten (Cents)')
                                            ->badge()
                                            ->formatStateUsing(fn ($state): string => $state ? $state . ' ¢' : '0 ¢')
                                            ->color('warning'),

                                        TextEntry::make('wait_time_sec')
                                            ->label('Wartezeit')
                                            ->badge()
                                            ->formatStateUsing(fn ($state): string => $state ? $state . ' Sek.' : '0 Sek.')
                                            ->icon('heroicon-m-clock'),

                                        TextEntry::make('duration_ms')
                                            ->label('Dauer (ms)')
                                            ->badge()
                                            ->formatStateUsing(fn ($state): string => $state ? number_format($state) . ' ms' : '0 ms')
                                            ->color('info'),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    /**
     * Optimize queries with eager loading to prevent N+1 problems
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with([
                'customer:id,name,phone,email,company_id',
                'company:id,name,parent_company_id',  // ✅ FIX: Added parent_company_id
                'appointment:id,customer_id,starts_at,status,price',  // ✅ FIX: Added price for N+1 prevention
                'phoneNumber:id,number,label',
            ])
            // 🔥 FIX: Hide temporary calls from default view (created during call_inbound, upgraded on call_started)
            ->where(function ($q) {
                $q->where('retell_call_id', 'LIKE', 'call_%')
                  ->orWhereNull('retell_call_id');
            });

        // ✅ FIX: Custom company filtering for resellers to see their customers' calls
        // VULN-001: Resellers need to see calls from their customer companies (parent_company_id match)
        $user = auth()->user();
        if ($user && $user->hasRole(['reseller_admin', 'reseller_owner', 'reseller_support'])) {
            $query->where(function($q) use ($user) {
                // See calls from own company OR from customer companies
                $q->where('calls.company_id', $user->company_id)
                  ->orWhereHas('company', function($subQ) use ($user) {
                      $subQ->where('parent_company_id', $user->company_id);
                  });
            });
        }

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCalls::route('/'),
            'create' => Pages\CreateCall::route('/create'),
            'view' => Pages\ViewCall::route('/{record}'),
            'edit' => Pages\EditCall::route('/{record}/edit'),
        ];
    }

    public static function getWidgets(): array
    {
        return [
            \App\Filament\Resources\CallResource\Widgets\CallStatsOverview::class,
            \App\Filament\Resources\CallResource\Widgets\CallVolumeChart::class,
            \App\Filament\Resources\CallResource\Widgets\RecentCallsActivity::class,
        ];
    }
}