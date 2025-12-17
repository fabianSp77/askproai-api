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
                    // ❌ SKIPPED: appointmentWishes (table missing from DB backup)
                    // ->with('appointmentWishes', function ($q) {
                    //     $q->where('status', 'pending')->latest();
                    // })
                    // ✅ FIX 2025-11-12: Enable appointments eager loading for name display
                    ->with('appointments.customer')
                    // 🆕 2025-11-24: Composite support - eager load service, staff, phases
                    ->with('appointments.service')
                    ->with('appointments.staff')
                    ->with(['appointments.phases' => function ($query) {
                        $query->where('staff_required', true)
                            ->orderBy('sequence_order');
                    }])
                    ->with('customer')
                    ->with('company')
                    ->with('branch')
                    ->with('phoneNumber');
            })
            ->columns(
                self::getUserViewMode() === 'compact' 
                    ? self::getCompactColumns() 
                    : self::getClassicColumns()
            )
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
                    ->label('Laufende Anrufe (LIVE)')
                    ->query(fn (Builder $query): Builder =>
                        $query->whereIn('status', ['ongoing', 'in_progress', 'active', 'ringing'])
                    )
                    ->toggle(),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'ongoing' => 'LIVE',
                        'completed' => 'Abgeschlossen',
                        'missed' => 'Verpasst',
                        'failed' => 'Fehlgeschlagen',
                        'no_answer' => 'Keine Antwort',
                        'busy' => 'Besetzt',
                        'analyzed' => 'Analysiert',
                    ])
                    ->multiple(),

                // 🔴 WITH LOGGING: Custom implementation to verify filter works
                Tables\Filters\Filter::make('has_appointment')
                    ->label('Mit Termin')
                    ->form([
                        Forms\Components\Select::make('appointment_status')
                            ->label('Booking Status')
                            ->options([
                                'with' => 'Mit Termin',
                                'without' => 'Ohne Termin',
                            ])
                            ->placeholder('Alle'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (!isset($data['appointment_status'])) {
                            \Log::debug('[CallResource] Filter: appointment_status NOT SET - showing all');
                            return $query;
                        }

                        \Log::debug('[CallResource] Filter: appointment_status = ' . $data['appointment_status']);

                        if ($data['appointment_status'] === 'with') {
                            // ONLY calls WITH appointments that have starts_at != null
                            $filtered = $query->whereHas('appointments', fn (Builder $q) =>
                                $q->where('starts_at', '!=', null)
                                  ->whereNull('deleted_at')
                            );
                            $count = $filtered->count();
                            \Log::debug("[CallResource] Filter: 'with' applied - Count: $count");
                            return $filtered;
                        } elseif ($data['appointment_status'] === 'without') {
                            // ONLY calls WITHOUT appointments that have starts_at != null
                            $filtered = $query->whereDoesntHave('appointments', fn (Builder $q) =>
                                $q->where('starts_at', '!=', null)
                                  ->whereNull('deleted_at')
                            );
                            $count = $filtered->count();
                            \Log::debug("[CallResource] Filter: 'without' applied - Count: $count");
                            return $filtered;
                        }

                        \Log::debug('[CallResource] Filter: Unknown value - showing all');
                        return $query;
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if (isset($data['appointment_status'])) {
                            $indicators['appointment_status'] = match ($data['appointment_status']) {
                                'with' => 'Mit Termin',
                                'without' => 'Ohne Termin',
                                default => '',
                            };
                        }
                        return $indicators;
                    }),

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
            ->headerActions([
                Tables\Actions\Action::make('toggle_view_mode')
                    ->label(fn() => self::getUserViewMode() === 'compact' ? '📋 Klassische Ansicht' : '⚡ Kompakte Ansicht')
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->color('gray')
                    ->action(function() {
                        $current = self::getUserViewMode();
                        $new = $current === 'compact' ? 'classic' : 'compact';

                        \App\Models\UserPreference::set(
                            auth()->id(),
                            'call_list_view_mode',
                            ['mode' => $new]
                        );
                    })
                    ->after(fn() => redirect()->to(CallResource::getUrl('index')))
                    ->requiresConfirmation(false),
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
            RelationManagers\AppointmentsRelationManager::class,
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

                                // 🚫 CANCELLATION BANNER - Shows if call HAS cancelled appointments OR PERFORMED cancellations
                                ViewEntry::make('cancellation_banner')
                                    ->view('filament.cancellation-banner')
                                    ->viewData(function ($record) {
                                        $appointmentsData = [];
                                        $bannerType = null;

                                        // Case 1: This call HAS cancelled appointments (booking call)
                                        $cancelledAppointments = $record->appointments()
                                            ->where('status', 'cancelled')
                                            ->with(['service', 'modifications' => function ($q) {
                                                $q->where('modification_type', 'cancel')
                                                  ->latest('created_at')
                                                  ->limit(1);
                                            }])
                                            ->get();

                                        if ($cancelledAppointments->isNotEmpty()) {
                                            $bannerType = 'booking_call';
                                            foreach ($cancelledAppointments as $appointment) {
                                                $summary = $appointment->getCancellationSummary();
                                                $appointmentsData[] = [
                                                    'service_name' => $appointment->service?->name ?? 'Unbekannter Service',
                                                    'appointment_time' => $appointment->starts_at?->format('d.m.Y H:i') ?? 'Unbekannt',
                                                    'cancelled_at' => $summary['cancelled_at'],
                                                    'cancelled_by' => $summary['cancelled_by'],
                                                    'reason' => $summary['reason'],
                                                    'fee' => $summary['fee'],
                                                    'within_policy' => $summary['within_policy'],
                                                    'hours_notice' => $summary['hours_notice'],
                                                    'cancellation_call_id' => $summary['cancellation_call_id'],
                                                    'booking_call_id' => $summary['booking_call_id'],
                                                ];
                                            }
                                        }
                                        // Case 2: This call PERFORMED cancellations (cancellation call)
                                        elseif ($record->retell_call_id) {
                                            $performedMods = \App\Models\AppointmentModification::query()
                                                ->where('modification_type', 'cancel')
                                                ->whereJsonContains('metadata->call_id', $record->retell_call_id)
                                                ->with(['appointment.service'])
                                                ->get();

                                            if ($performedMods->isNotEmpty()) {
                                                $bannerType = 'cancellation_call';
                                                foreach ($performedMods as $mod) {
                                                    $appointment = $mod->appointment;
                                                    if (!$appointment) continue;

                                                    $appointmentsData[] = [
                                                        'service_name' => $appointment->service?->name ?? 'Unbekannter Service',
                                                        'appointment_time' => $appointment->starts_at?->format('d.m.Y H:i') ?? 'Unbekannt',
                                                        'cancelled_at' => $mod->created_at->format('d.m.Y H:i'),
                                                        'cancelled_by' => match($mod->modified_by_type) {
                                                            'User' => 'Admin',
                                                            'Staff' => 'Mitarbeiter',
                                                            'Customer' => 'Kunde',
                                                            'System' => 'System/AI',
                                                            default => $mod->modified_by_type ?? 'Unbekannt',
                                                        },
                                                        'reason' => $mod->reason ?? 'Kein Grund angegeben',
                                                        'fee' => (float) $mod->fee_charged,
                                                        'within_policy' => (bool) $mod->within_policy,
                                                        'hours_notice' => $mod->metadata['hours_notice'] ?? null,
                                                        'cancellation_call_id' => $record->id,
                                                        'booking_call_id' => $appointment->call_id,
                                                    ];
                                                }
                                            }
                                        }

                                        if (empty($appointmentsData)) {
                                            return ['show' => false];
                                        }

                                        return [
                                            'show' => true,
                                            'banner_type' => $bannerType,
                                            'count' => count($appointmentsData),
                                            'appointments' => $appointmentsData,
                                        ];
                                    })
                                    ->columnSpanFull()
                                    ->hidden(fn ($state) => !($state['show'] ?? false)),

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
                                        // 🆕 2025-11-24: ENHANCED - Support multiple appointments & composite services
                                        InfoSection::make('Gebuchte Termine & Segmente')
                                            ->icon('heroicon-m-calendar-days')
                                            ->extraAttributes(['class' => '!max-w-full w-full'])
                                            ->schema([
                                                ViewEntry::make('appointments_composite')
                                                    ->view('filament.infolists.appointments-composite-section')
                                                    ->columnSpanFull(),
                                            ])
                                            ->visible(fn ($record) => $record->appointments()->exists())
                                            ->collapsible()
                                            ->collapsed(false)
                                            ->columnSpanFull(),
                                    ])
                                    ->visible(fn ($record) => $record->appointments()->exists())
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
                                                        
                                                        // Ensure string and handle arrays
                                                        if (is_array($summary) || is_object($summary)) {
                                                            $summary = json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                                                        } else {
                                                            $summary = (string) $summary;
                                                        }
                                                        
                                                        // Ensure valid UTF-8
                                                        $summary = mb_convert_encoding($summary, 'UTF-8', 'UTF-8');

                                                        $callId = $record->id;
                                                        
                                                        // JSON for JS
                                                        $summaryJson = json_encode($summary, JSON_UNESCAPED_UNICODE);
                                                        if ($summaryJson === false) {
                                                            $summaryJson = json_encode('Kodierungsfehler in Originaltext');
                                                        }
                                                        
                                                        // Escape for HTML Attribute
                                                        $summaryAttr = htmlspecialchars($summaryJson, ENT_QUOTES, 'UTF-8');
                                                        
                                                        $staticSummary = e($summary); 

                                                        return <<<HTML
<div 
    x-data='{
        lang: "de",
        translations: {
            de: null,
            original: {$summaryAttr},
            tr: null,
            ar: null
        },
        isLoading: false,
        error: null,
        init() {
            // Auto-fetch German translation on load
            this.switchLang("de");
        },
        switchLang: async function(target) {
            this.lang = target;
            this.error = null;
            
            // Allow showing original immediately
            if (target === "original") {
                return;
            }
            
            // If content is missing, fetch it
            if (!this.translations[target]) {
                this.isLoading = true;
                
                const controller = new AbortController();
                const timeoutId = setTimeout(function() { 
                    controller.abort(); 
                }, 10000);
                
                try {
                    const response = await fetch("/api/translate", {
                        method: "POST",
                        headers: { "Content-Type": "application/json", "Accept": "application/json" },
                        body: JSON.stringify({ 
                            text: this.translations.original, 
                            target: target, 
                            call_id: {$callId} 
                        }),
                        signal: controller.signal
                    });
                    clearTimeout(timeoutId);
                    
                    if (!response.ok) throw new Error("HTTP " + response.status);
                    
                    const data = await response.json();
                    if (data.success === false) throw new Error(data.error || "API Error");
                    
                    this.translations[target] = data.translation;
                } catch (e) {
                    console.error(e);
                    const errorMsg = e.name === "AbortError" ? "Zeitüberschreitung" : (e.message || "Fehler");
                    this.error = "Übersetzungsfehler: " + errorMsg;
                    // Fallback to original if translation fails
                    if (target === "de") {
                        this.translations.de = this.translations.original + " (Übersetzung fehlgeschlagen)";
                    }
                } finally {
                    this.isLoading = false;
                }
            }
        }
    }'
    class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 border border-blue-200 dark:border-blue-800"
>
    <!-- Header -->
    <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
        <div class="flex items-center gap-2">
            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Zusammenfassung</span>
            
            <!-- Loader in Header -->
            <div x-show="isLoading" class="flex items-center ml-2" style="display: none;">
                <svg class="animate-spin h-4 w-4 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
            </div>
        </div>
        
        <div class="flex gap-1">
             <button type="button" @click="switchLang('de')" 
                :class="lang === 'de' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 dark:bg-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600'"
                class="px-2 py-1 text-xs font-medium rounded-md border transition-colors shadow-sm">
                🇩🇪 Deutsch
             </button>
             <button type="button" @click="switchLang('tr')" 
                :class="lang === 'tr' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 dark:bg-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600'"
                class="px-2 py-1 text-xs font-medium rounded-md border transition-colors shadow-sm">
                🇹🇷 Türkçe
             </button>
             <button type="button" @click="switchLang('ar')" 
                :class="lang === 'ar' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 dark:bg-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600'"
                class="px-2 py-1 text-xs font-medium rounded-md border transition-colors shadow-sm">
                🇸🇦 العربية
             </button>
             <button type="button" @click="switchLang('original')" 
                :class="lang === 'original' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 dark:bg-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600'"
                class="px-2 py-1 text-xs font-medium rounded-md border transition-colors shadow-sm">
                🇬🇧 Original
             </button>
        </div>
    </div>

    <!-- Error Message -->
    <div x-show="error" x-text="error" class="mb-2 text-xs text-red-600 dark:text-red-400 font-medium bg-red-50 dark:bg-red-900/20 p-2 rounded" style="display: none;"></div>

    <!-- Content Area -->
    <div class="text-sm text-gray-900 dark:text-gray-100 leading-relaxed min-h-[3rem]">
        <!-- Fallback to static summary (Original) only if lang is original OR if de translation hasn't loaded yet and specific condition met -->
        <div x-text="translations[lang] || (lang === 'original' ? '$staticSummary' : '')" class="whitespace-pre-wrap">{$staticSummary}</div>
    </div>
</div>
HTML;
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
                'company:id,name',  // ✅ FIXED: Removed parent_company_id (doesn't exist in Sept 21 backup)
                'appointment:id,customer_id,starts_at,status,price',  // ✅ FIX: Added price for N+1 prevention
                'phoneNumber:id,number,label',
            ])
            // 🔥 FIX: Hide temporary calls from default view (created during call_inbound, upgraded on call_started)
            ->where(function ($q) {
                $q->where('retell_call_id', 'LIKE', 'call_%')
                  ->orWhereNull('retell_call_id');
            });

        // ⚠️ DISABLED: Reseller filtering requires parent_company_id column (missing in Sept 21 backup)
        // TODO: Re-enable when database is fully restored
        // $user = auth()->user();
        // if ($user && $user->hasRole(['reseller_admin', 'reseller_owner', 'reseller_support'])) {
        //     $query->where(function($q) use ($user) {
        //         $q->where('calls.company_id', $user->company_id)
        //           ->orWhereHas('company', fn($subQ) => $subQ->where('parent_company_id', $user->company_id));
        //     });
        // }

        return $query;
    }

    /*
    |--------------------------------------------------------------------------
    | View Mode Management (Compact vs Classic)
    |--------------------------------------------------------------------------
    | User preference system for toggling between:
    | - Compact View (6 columns - optimized for speed)
    | - Classic View (12 columns - full details)
    */

    /**
     * Get classic columns (all 12 columns - existing implementation)
     *
     * @return array
     */
    protected static function getClassicColumns(): array
    {
        return [
                    // 1️⃣ Company/Branch column with Phone number
                    Tables\Columns\ViewColumn::make('company_phone_display')
                        ->label('Unternehmen/Filiale')
                        ->view('filament.columns.company-phone')
                        ->searchable(query: function (Builder $query, string $search): Builder {
                            return $query
                                ->orWhereHas('branch', fn (Builder $q) => $q->where('name', 'like', "%{$search}%"))
                                ->orWhereHas('company', fn (Builder $q) => $q->where('name', 'like', "%{$search}%"));
                        })
                        ->toggleable(),

                    // 2️⃣ Optimized Customer column with 3-line layout
                    Tables\Columns\ViewColumn::make('anrufer_display')
                        ->label('Anrufer')
                        ->view('filament.columns.anrufer-3lines')
                        ->searchable(query: function (Builder $query, string $search): Builder {
                            return $query
                                // ✅ FIXED: customer_name is in metadata JSON, not a direct column
                                ->where(function ($q) use ($search) {
                                    $q->whereRaw("JSON_EXTRACT(metadata, '$.customer_name') LIKE ?", ["%{$search}%"])
                                      ->orWhereHas('customer', fn (Builder $query) => $query->where('name', 'like', "%{$search}%"));
                                });
                        })
                        ->sortable()
                        ->toggleable(),

                    // 3️⃣ Ereignis / Zeit / Dauer - 🔄 2025-11-26: Verschoben vor Termin/Mitarbeiter
                    Tables\Columns\ViewColumn::make('status_time_duration')
                        ->label('Ereignis / Zeit / Dauer')
                        ->view('filament.columns.status-time-duration')
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
                        ->toggleable(isToggledHiddenByDefault: false),

                    // 🆕 BOOKING STATUS: Now embedded in Status/Zeit/Dauer column
                    // (Separate column kept for backwards compatibility but hidden)
                    Tables\Columns\TextColumn::make('booking_status')
                        ->label('Buchung')
                        ->getStateUsing(function (Call $record) {
                            if ($record->appointment && $record->appointment->starts_at) {
                                return '✅ Gebucht';
                            }
                            // ❌ SKIPPED: appointmentWishes check (table missing from DB backup)
                            // } elseif ($record->appointmentWishes()->where('status', 'pending')->exists()) {
                            //     return '⏰ Wunsch';
                            // }
                            return '❓ Offen';
                        })
                        ->badge()
                        ->color(function ($state) {
                            return match($state) {
                                '✅ Gebucht' => 'success',
                                '⏰ Wunsch' => 'warning',
                                '❓ Offen' => 'danger',
                                default => 'gray',
                            };
                        })
                        ->sortable(query: fn($query, $direction) =>
                            $query->orderByRaw("CASE
                                WHEN appointments.starts_at IS NOT NULL THEN 0
                                ELSE 1
                                END {$direction}")
                                ->leftJoin('appointments', 'calls.id', '=', 'appointments.call_id')
                        )
                        ->hidden(),  // 🚫 Hidden - now integrated into Status/Zeit/Dauer column

                    // 🎯 CALL TYPE (LEGACY: hidden - consolidated into Ereignis column)
                    Tables\Columns\TextColumn::make('call_type')
                        ->label('Aktion (Legacy)')
                        ->getStateUsing(function (Call $record) {
                            $hasActiveAppointments = $record->appointments()
                                ->whereIn('status', ['scheduled', 'confirmed', 'booked', 'pending'])
                                ->exists();

                            $hasCancelledAppointments = $record->appointments()
                                ->where('status', 'cancelled')
                                ->exists();

                            $performedCancellations = false;
                            if ($record->retell_call_id) {
                                $performedCancellations = \App\Models\AppointmentModification::query()
                                    ->where('modification_type', 'cancel')
                                    ->whereJsonContains('metadata->call_id', $record->retell_call_id)
                                    ->exists();
                            }

                            $actions = [];

                            // 🔥 2025-11-20: Enhanced labels with call ID references
                            if ($hasActiveAppointments) {
                                if ($hasCancelledAppointments) {
                                    $actions[] = '✅ Buchung (später storniert)';
                                } else {
                                    $actions[] = '✅ Buchung';
                                }
                            }

                            if ($performedCancellations && !$hasCancelledAppointments) {
                                // This call ONLY performed cancellations (didn't book them)
                                // Get the first cancelled appointment to show reference
                                $performedMod = \App\Models\AppointmentModification::query()
                                    ->where('modification_type', 'cancel')
                                    ->whereJsonContains('metadata->call_id', $record->retell_call_id)
                                    ->with('appointment')
                                    ->first();

                                if ($performedMod && $performedMod->appointment && $performedMod->appointment->call_id) {
                                    $bookingCallId = $performedMod->appointment->call_id;
                                    if ($bookingCallId !== $record->id) {
                                        $actions[] = "🚫 Storno (#$bookingCallId)";
                                    } else {
                                        $actions[] = '🚫 Storno';
                                    }
                                } else {
                                    $actions[] = '🚫 Storno';
                                }
                            } elseif ($hasCancelledAppointments && !($hasActiveAppointments)) {
                                // Has cancelled appointments but no active ones (could be self-cancellation)
                                $actions[] = '🚫 Storno';
                            }

                            if (empty($actions)) {
                                return null; // Don't show badge if no appointments
                            }

                            return implode(' + ', $actions);
                        })
                        ->badge()
                        ->color(fn ($state) => match(true) {
                            str_contains($state ?? '', '+') => 'info', // Both booking and cancellation
                            str_contains($state ?? '', '✅') => 'success', // Just booking
                            str_contains($state ?? '', '🚫') => 'warning', // Just cancellation
                            default => 'gray',
                        })
                        ->tooltip(function (Call $record) {
                            $lines = [];

                            // Active appointments
                            $activeAppointments = $record->appointments()
                                ->whereIn('status', ['scheduled', 'confirmed', 'booked', 'pending'])
                                ->with('service')
                                ->get();

                            if ($activeAppointments->isNotEmpty()) {
                                $lines[] = '✅ GEBUCHTE TERMINE:';
                                foreach ($activeAppointments as $appt) {
                                    $service = $appt->service?->name ?? 'Unbekannt';
                                    $time = $appt->starts_at?->format('d.m.Y H:i') ?? 'Unbekannt';
                                    $lines[] = "  📅 {$service} - {$time}";
                                }
                                $lines[] = '';
                            }

                            // Cancelled appointments of this call
                            $cancelledAppointments = $record->appointments()
                                ->where('status', 'cancelled')
                                ->with('service')
                                ->get();

                            if ($cancelledAppointments->isNotEmpty()) {
                                $lines[] = '🚫 STORNIERTE TERMINE (dieses Calls):';
                                foreach ($cancelledAppointments as $appt) {
                                    $service = $appt->service?->name ?? 'Unbekannt';
                                    $time = $appt->starts_at?->format('d.m.Y H:i') ?? 'Unbekannt';
                                    $summary = $appt->getCancellationSummary();
                                    $lines[] = "  📅 {$service} - {$time}";
                                    $lines[] = "     Storniert: {$summary['cancelled_at']} von {$summary['cancelled_by']}";
                                    if ($summary['cancellation_call_id'] && $summary['cancellation_call_id'] !== $record->id) {
                                        $lines[] = "     → Storniert in Call #{$summary['cancellation_call_id']}";
                                    }
                                }
                                $lines[] = '';
                            }

                            // Cancellations performed in this call
                            if ($record->retell_call_id) {
                                $performedMods = \App\Models\AppointmentModification::query()
                                    ->where('modification_type', 'cancel')
                                    ->whereJsonContains('metadata->call_id', $record->retell_call_id)
                                    ->with('appointment.service')
                                    ->get();

                                if ($performedMods->isNotEmpty()) {
                                    $lines[] = '🚫 STORNIERUNGEN IN DIESEM CALL:';
                                    foreach ($performedMods as $mod) {
                                        $appointment = $mod->appointment;
                                        if (!$appointment) continue;

                                        $service = $appointment->service?->name ?? 'Unbekannt';
                                        $time = $appointment->starts_at?->format('d.m.Y H:i') ?? 'Unbekannt';
                                        $lines[] = "  📅 {$service} - {$time}";
                                        if ($appointment->call_id && $appointment->call_id !== $record->id) {
                                            $lines[] = "     → Ursprünglich gebucht in Call #{$appointment->call_id}";
                                        }
                                    }
                                }
                            }

                            return !empty($lines) ? implode("\n", $lines) : null;
                        })
                        ->url(function (Call $record) {
                            // Priority: Link to cancellation call if this is a booking call with cancelled appointments
                            $cancelledAppointment = $record->appointments()
                                ->where('status', 'cancelled')
                                ->first();

                            if ($cancelledAppointment) {
                                $summary = $cancelledAppointment->getCancellationSummary();
                                if ($summary['cancellation_call_id'] && $summary['cancellation_call_id'] !== $record->id) {
                                    return CallResource::getUrl('view', ['record' => $summary['cancellation_call_id']]);
                                }
                            }

                            // Or link to booking call if this is a cancellation call
                            if ($record->retell_call_id) {
                                $performedMod = \App\Models\AppointmentModification::query()
                                    ->where('modification_type', 'cancel')
                                    ->whereJsonContains('metadata->call_id', $record->retell_call_id)
                                    ->with('appointment')
                                    ->first();

                                if ($performedMod && $performedMod->appointment && $performedMod->appointment->call_id !== $record->id) {
                                    return CallResource::getUrl('view', ['record' => $performedMod->appointment->call_id]);
                                }
                            }

                            // Otherwise link to first active appointment's detail
                            $activeAppointment = $record->appointments()
                                ->whereIn('status', ['scheduled', 'confirmed', 'booked', 'pending'])
                                ->first();

                            if ($activeAppointment) {
                                return \App\Filament\Resources\AppointmentResource::getUrl('view', ['record' => $activeAppointment->id]);
                            }

                            return null;
                        }, shouldOpenInNewTab: false)
                        ->hidden()  // 🚫 Hidden - consolidated into Ereignis column (rollback: remove this line)
                        ->toggleable(),

                    // 🆕 COMBINED: Appointment + Staff (Termin & Mitarbeiter)
                    // 🔥 2025-11-20: Show ALL appointments, not just one
                    Tables\Columns\TextColumn::make('appointment_summary')
                        ->label('Termin / Mitarbeiter')
                        ->getStateUsing(function (Call $record) {
                            $appointments = $record->appointments;

                            // 🆕 2025-11-25: Check if THIS CALL performed a reschedule
                            $rescheduleDetails = null;
                            if ($record->retell_call_id) {
                                try {
                                    $rescheduleDetails = \App\Models\AppointmentModification::where('modification_type', 'reschedule')
                                        ->whereJsonContains('metadata->call_id', $record->retell_call_id)
                                        ->with('appointment.service', 'appointment.staff')
                                        ->first();
                                } catch (\Exception $e) {
                                    // Silently ignore
                                }
                            }

                            // If this call performed a reschedule, show old/new time + staff
                            if ($rescheduleDetails && $rescheduleDetails->appointment) {
                                $appt = $rescheduleDetails->appointment;
                                $metadata = $rescheduleDetails->metadata ?? [];
                                $staffName = $appt->staff?->name ?? 'Kein MA';

                                $oldDateTime = isset($metadata['original_time'])
                                    ? \Carbon\Carbon::parse($metadata['original_time'])->locale('de')->isoFormat('dd. D. MMM HH:mm')
                                    : null;
                                $newDt = isset($metadata['new_time'])
                                    ? \Carbon\Carbon::parse($metadata['new_time'])
                                    : ($appt->starts_at ? \Carbon\Carbon::parse($appt->starts_at) : null);

                                $lines = [];
                                if ($oldDateTime) {
                                    $lines[] = '<span class="text-xs text-gray-500 dark:text-gray-400" style="text-decoration: line-through;">Alt: ' . $oldDateTime . '</span>';
                                }
                                if ($newDt) {
                                    $endTime = $appt->ends_at ? \Carbon\Carbon::parse($appt->ends_at)->format('H:i') : null;
                                    $newDateTime = $newDt->locale('de')->isoFormat('dd. D. MMM') . ' ' . $newDt->format('H:i') . ($endTime ? ' - ' . $endTime : '') . ' Uhr';
                                    $lines[] = '<span class="text-xs text-gray-800 dark:text-gray-200 font-semibold">' . $newDateTime . '</span>';
                                }
                                $lines[] = '<span class="text-xs text-gray-700 dark:text-gray-300" title="Mitarbeiter">MA: ' . $staffName . '</span>';

                                return new HtmlString(implode('<br>', $lines));
                            }

                            if ($appointments->isEmpty()) {
                                return new HtmlString('<span class="text-xs text-gray-500 dark:text-gray-400">-</span>');
                            }

                            $lines = [];
                            foreach ($appointments->take(3) as $appointment) {
                                if (!$appointment->starts_at) {
                                    continue;
                                }

                                $startDt = \Carbon\Carbon::parse($appointment->starts_at)->locale('de');
                                $endTime = $appointment->ends_at ? \Carbon\Carbon::parse($appointment->ends_at)->format('H:i') : null;
                                $fullDateTime = $startDt->isoFormat('dd. D. MMM') . ' ' . $startDt->format('H:i') . ($endTime ? ' - ' . $endTime : '') . ' Uhr';
                                $staffName = $appointment->staff?->name ?? 'Kein MA';

                                // 🆕 CANCELLED: Show cancelled status
                                if ($appointment->status === 'cancelled') {
                                    $lines[] = '<span class="text-xs text-orange-600 dark:text-orange-400 font-semibold">Storniert</span>';
                                    $lines[] = '<span class="text-xs text-gray-500 dark:text-gray-400" style="text-decoration: line-through;">' . $fullDateTime . '</span>';
                                } else {
                                    // 🆕 2025-11-26: Simplified - only date/time + staff
                                    // Service info + segments now in "Service / Preis" column
                                    // Line 1: Date/Time (bold)
                                    $lines[] = '<span class="text-xs text-gray-800 dark:text-gray-200 font-semibold">' . $fullDateTime . '</span>';
                                    // Line 2: Staff
                                    $staffColor = $appointment->staff ? 'text-gray-700 dark:text-gray-300' : 'text-orange-600';
                                    $lines[] = '<span class="text-xs ' . $staffColor . '" title="Mitarbeiter">MA: ' . $staffName . '</span>';
                                }

                                // Add blank line between appointments
                                if ($appointment !== $appointments->take(3)->last()) {
                                    $lines[] = '';
                                }
                            }

                            // Show "+X more" if there are more than 3 appointments
                            if ($appointments->count() > 3) {
                                $remaining = $appointments->count() - 3;
                                $lines[] = '<span class="text-xs text-gray-500 italic">+' . $remaining . ' weitere</span>';
                            }

                            return new HtmlString(implode('<br>', $lines));
                        })
                        ->html()
                        ->sortable(query: fn($query, $direction) =>
                            $query->orderBy('appointments.starts_at', $direction)
                                ->leftJoin('appointments', 'calls.id', '=', 'appointments.call_id')
                        )
                        ->tooltip(function (Call $record) {
                            // 🆕 2025-11-25: Check if THIS CALL performed a reschedule
                            $rescheduleDetails = null;
                            if ($record->retell_call_id) {
                                try {
                                    $rescheduleDetails = \App\Models\AppointmentModification::where('modification_type', 'reschedule')
                                        ->whereJsonContains('metadata->call_id', $record->retell_call_id)
                                        ->with('appointment.service', 'appointment.staff')
                                        ->first();
                                } catch (\Exception $e) {
                                    // Silently ignore
                                }
                            }

                            // If this call performed a reschedule, show reschedule details
                            if ($rescheduleDetails && $rescheduleDetails->appointment) {
                                $appt = $rescheduleDetails->appointment;
                                $metadata = $rescheduleDetails->metadata ?? [];

                                $parts = [];
                                $parts[] = '🔄 Termin verschoben:';
                                $parts[] = '━━━━━━━━━━━━━━━━━━━━━━━━';

                                if (isset($metadata['original_time'])) {
                                    $parts[] = '❌ Von: ' . \Carbon\Carbon::parse($metadata['original_time'])->format('d.m.Y H:i');
                                }
                                if (isset($metadata['new_time'])) {
                                    $parts[] = '✅ Auf: ' . \Carbon\Carbon::parse($metadata['new_time'])->format('d.m.Y H:i');
                                }
                                if ($appt->staff) {
                                    $parts[] = '👤 Mitarbeiter: ' . $appt->staff->name;
                                }

                                return implode("\n", $parts);
                            }

                            $appointments = $record->appointments;
                            if ($appointments->isEmpty()) {
                                return null;
                            }

                            $parts = [];
                            $parts[] = '📋 TERMINE DIESES ANRUFS (' . $appointments->count() . '):';
                            $parts[] = '━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━';

                            foreach ($appointments as $index => $appointment) {
                                if ($index > 0) {
                                    $parts[] = '';
                                    $parts[] = '---';
                                    $parts[] = '';
                                }

                                // Status indicator
                                $statusIcon = match($appointment->status) {
                                    'cancelled' => '🚫',
                                    'confirmed', 'scheduled', 'booked' => '✅',
                                    'completed' => '✓',
                                    default => '•'
                                };

                                $parts[] = $statusIcon . ' TERMIN #' . ($index + 1);

                                // Service
                                if ($appointment->service) {
                                    $parts[] = '📋 Service: ' . $appointment->service->name;
                                }

                                // 🆕 CANCELLED: Show cancellation status first
                                if ($appointment->status === 'cancelled') {
                                    $parts[] = '⚠️ STATUS: STORNIERT';

                                    // Get cancellation details
                                    $cancellationSummary = $appointment->getCancellationSummary();
                                    if ($cancellationSummary['cancelled_at']) {
                                        $parts[] = '🚫 Storniert am: ' . $cancellationSummary['cancelled_at'];
                                    }
                                    if ($cancellationSummary['cancelled_by']) {
                                        $parts[] = '👤 Storniert von: ' . $cancellationSummary['cancelled_by'];
                                    }
                                    if ($cancellationSummary['reason']) {
                                        $parts[] = '💬 Grund: ' . $cancellationSummary['reason'];
                                    }
                                }

                                // Date and time
                                if ($appointment->starts_at) {
                                    $start = \Carbon\Carbon::parse($appointment->starts_at);
                                    $parts[] = '📅 Datum: ' . $start->format('d.m.Y');
                                    $parts[] = '🕐 Zeit: ' . $start->format('H:i') . ($appointment->ends_at ? ' - ' . \Carbon\Carbon::parse($appointment->ends_at)->format('H:i') : '');
                                }

                                // Staff
                                if ($appointment->staff) {
                                    $parts[] = '👤 Mitarbeiter: ' . $appointment->staff->name;
                                } else {
                                    $parts[] = '⚠️ Mitarbeiter: Nicht zugewiesen';
                                }
                            }

                            return implode("\n", $parts);
                        })
                        ->toggleable(isToggledHiddenByDefault: false),  // 🆕 2025-11-26: Im Spalten-Selector anzeigen

                    // Staff column - DEPRECATED but kept for backwards compatibility (hidden)
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
                        ->toggleable()
                        ->hidden(),  // 🚫 Hidden in favor of combined appointment_summary column

                    // 💾 OPTIMIZED: Show ACTUAL booked services + prices + segments from appointments
                    Tables\Columns\TextColumn::make('service_type')
                        ->label('Service / Preis')
                        ->html()
                        ->getStateUsing(function ($record) {
                            try {
                                // 🆕 2025-11-25: Check if THIS CALL performed a reschedule
                                $rescheduleDetails = null;
                                if ($record->retell_call_id) {
                                    try {
                                        $rescheduleDetails = \App\Models\AppointmentModification::where('modification_type', 'reschedule')
                                            ->whereJsonContains('metadata->call_id', $record->retell_call_id)
                                            ->with('appointment.service')
                                            ->first();
                                    } catch (\Exception $e) {
                                        // Silently ignore
                                    }
                                }

                                // If this call performed a reschedule, show the service
                                if ($rescheduleDetails && $rescheduleDetails->appointment && $rescheduleDetails->appointment->service) {
                                    $appt = $rescheduleDetails->appointment;
                                    $service = $appt->service;
                                    $name = ($service->display_name && trim($service->display_name) !== '')
                                        ? $service->display_name
                                        : $service->name;
                                    $price = $service->price;
                                    $isComposite = $service->composite ?? false;

                                    $lines = [];

                                    // Service name (+ segment count if composite)
                                    if ($isComposite) {
                                        $phaseCount = 0;
                                        try {
                                            $phaseCount = $appt->phases()->where('staff_required', true)->count();
                                        } catch (\Exception $e) {}
                                        $lines[] = '<span class="text-xs text-gray-800 dark:text-gray-200 font-semibold">' . htmlspecialchars($name) . ' (' . $phaseCount . ' Segmente)</span>';
                                    } else {
                                        $lines[] = '<span class="text-xs text-gray-800 dark:text-gray-200 font-semibold">' . htmlspecialchars($name) . '</span>';
                                    }

                                    // Price
                                    if ($price && $price > 0) {
                                        $formattedPrice = number_format($price, 0, ',', '.');
                                        $lines[] = '<span class="text-xs text-green-600 dark:text-green-400">' . $formattedPrice . ' €</span>';
                                    }

                                    return '<div>' . implode('<br>', $lines) . '</div>';
                                }

                                // Use already-loaded appointments (eager-loaded on line 201)
                                $appointments = $record->appointments ?? collect();

                                // No appointments → show "-"
                                if (!$appointments || $appointments->isEmpty()) {
                                    return '<span class="text-gray-500 dark:text-gray-400 text-xs">-</span>';
                                }

                                // Build service + price + segments display
                                $allLines = [];
                                $seen = [];

                                foreach ($appointments as $appt) {
                                    if (!$appt || !$appt->service) continue;

                                    $serviceId = $appt->service->id;
                                    if (in_array($serviceId, $seen)) continue; // Skip duplicates
                                    $seen[] = $serviceId;

                                    $name = ($appt->service->display_name && trim($appt->service->display_name) !== '')
                                        ? $appt->service->display_name
                                        : $appt->service->name;
                                    $price = $appt->service->price;
                                    $isComposite = $appt->service->composite ?? false;
                                    $isCancelled = $appt->status === 'cancelled';

                                    $lines = [];

                                    // Line 1: Service name (+ segment count if composite)
                                    $textStyle = $isCancelled ? 'text-gray-500 dark:text-gray-400 line-through' : 'text-gray-800 dark:text-gray-200 font-semibold';
                                    if ($isComposite) {
                                        $phaseCount = 0;
                                        try {
                                            $phaseCount = $appt->phases()->where('staff_required', true)->count();
                                        } catch (\Exception $e) {}
                                        $lines[] = '<span class="text-xs ' . $textStyle . '">' . htmlspecialchars($name) . ' (' . $phaseCount . ' Segmente)</span>';
                                    } else {
                                        $lines[] = '<span class="text-xs ' . $textStyle . '">' . htmlspecialchars($name) . '</span>';
                                    }

                                    // Line 2: Price
                                    if ($price && $price > 0) {
                                        $formattedPrice = number_format($price, 0, ',', '.');
                                        $priceColor = $isCancelled ? 'text-gray-400' : 'text-green-600 dark:text-green-400';
                                        $lines[] = '<span class="text-xs ' . $priceColor . '">' . $formattedPrice . ' €' . ($isCancelled ? ' (storniert)' : '') . '</span>';
                                    }

                                    // Lines 3+: Segments (for composite services, max 4)
                                    if ($isComposite && !$isCancelled) {
                                        try {
                                            $phases = $appt->phases()
                                                ->where('staff_required', true)
                                                ->orderBy('sequence_order')
                                                ->limit(4)
                                                ->get();

                                            foreach ($phases as $index => $phase) {
                                                $number = $index + 1;
                                                $phaseName = $phase->segment_name;
                                                if (strlen($phaseName) > 20) {
                                                    $phaseName = substr($phaseName, 0, 17) . '...';
                                                }
                                                $lines[] = '<span class="text-xs text-gray-600 dark:text-gray-400" style="margin-left: 0.5rem;">' .
                                                    $number . '. ' . $phaseName . ' (' . $phase->duration_minutes . ' min)</span>';
                                            }

                                            $totalPhases = $appt->phases()->where('staff_required', true)->count();
                                            if ($totalPhases > 4) {
                                                $lines[] = '<span class="text-xs text-gray-500 italic" style="margin-left: 0.5rem;">... +' . ($totalPhases - 4) . ' weitere</span>';
                                            }
                                        } catch (\Exception $e) {
                                            // Silently ignore
                                        }
                                    }

                                    $allLines[] = implode('<br>', $lines);
                                }

                                if (empty($allLines)) {
                                    return '<span class="text-gray-500 dark:text-gray-400 text-xs">-</span>';
                                }

                                return '<div class="text-xs">' . implode('<br><br>', $allLines) . '</div>';
                            } catch (\Throwable $e) {
                                return '<span class="text-gray-400 text-xs">-</span>';
                            }
                        })
                        ->tooltip(function ($record) {
                            try {
                                // 🆕 2025-11-25: Check if THIS CALL performed a reschedule
                                if ($record->retell_call_id) {
                                    try {
                                        $rescheduleDetails = \App\Models\AppointmentModification::where('modification_type', 'reschedule')
                                            ->whereJsonContains('metadata->call_id', $record->retell_call_id)
                                            ->with('appointment.service')
                                            ->first();

                                        if ($rescheduleDetails && $rescheduleDetails->appointment && $rescheduleDetails->appointment->service) {
                                            $service = $rescheduleDetails->appointment->service;
                                            $name = $service->name ?? 'Service';
                                            $duration = $service->duration ?? '?';
                                            $price = $service->price ?? 0;
                                            $formattedPrice = ($price && $price > 0) ? number_format($price, 0, ',', '.') . '€' : 'Kein Preis';

                                            return "{$name}\nDauer: {$duration} Min\nPreis: {$formattedPrice}";
                                        }
                                    } catch (\Exception $e) {
                                        // Silently ignore
                                    }
                                }

                                // Use already-loaded appointments (eager-loaded on line 201)
                                $appointments = $record->appointments ?? collect();

                                if (!$appointments || $appointments->isEmpty()) {
                                    return 'Kein Termin gebucht';
                                }

                                // Show details: Service name + Price + Duration
                                $details = $appointments
                                    ->filter(fn($appt) => $appt)
                                    ->map(function ($appt) {
                                        $name = $appt->service?->name ?? 'Unbekannt';
                                        $duration = $appt->service?->duration ?? $appt->duration ?? '?';
                                        $price = $appt->service?->price ?? 0;

                                        if ($price && $price > 0) {
                                            // Price is stored as decimal(10,2) in EUR, not cents
                                            // Display as full euros only (no cents)
                                            $formattedPrice = number_format($price, 0, ',', '.');
                                            return "{$name} ({$duration} Min) - {$formattedPrice}€";
                                        }
                                        return "{$name} ({$duration} Min)";
                                    })
                                    ->implode("\n");

                                return $details ?: 'Kein Termin gebucht';
                            } catch (\Throwable $e) {
                                return 'Fehler beim Laden';
                            }
                        })
                        ->color(function ($state): string {
                            if (strip_tags($state) === '-') return 'gray';
                            return 'success';
                        })
                        ->icon(null)  // No icon - cleaner display
                        // 🔧 FIX 2025-11-26: Removed ->limit(150) - was truncating HTML before price could show
                        ->wrap()
                        ->searchable()
                        ->sortable()
                        ->toggleable(),

                    // 🎙️ Combined: Summary + Audio Player (State of the Art)
                    Tables\Columns\TextColumn::make('summary_audio')
                        ->label('Zusammenfassung & Audio')
                        ->html()
                        ->getStateUsing(function ($record) {
                            $summary = '';
                            if ($record->summary) {
                                $summaryText = is_string($record->summary) ? $record->summary : json_encode($record->summary);

                                // Show up to 3 lines (~150 chars for classic view)
                                $maxLength = 150;
                                $summaryDisplay = mb_strlen($summaryText) > $maxLength
                                    ? mb_substr($summaryText, 0, $maxLength) . '...'
                                    : $summaryText;

                                // 3-line display with CSS line-clamp
                                $summary = '<div class="text-xs text-gray-700 dark:text-gray-300 leading-tight mb-2" style="max-width: 220px; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;">' .
                                          htmlspecialchars($summaryDisplay) .
                                          '</div>';
                            }

                            $audio = '';
                            if (!empty($record->recording_url)) {
                                $url = $record->recording_url;
                                // Audio player width matches text width
                                $audio = '<div class="mt-1">
                                            <audio controls preload="none"
                                                   class="h-6"
                                                   style="height: 24px; width: 220px; max-width: 100%;"
                                                   controlsList="nodownload">
                                                <source src="' . htmlspecialchars($url) . '" type="audio/mpeg">
                                                <source src="' . htmlspecialchars($url) . '" type="audio/wav">
                                            </audio>
                                          </div>';
                            }

                            if (empty($summary) && empty($audio)) {
                                return '<span class="text-gray-400 text-xs">-</span>';
                            }

                            return '<div style="max-width: 220px;">' . $summary . $audio . '</div>';
                        })
                        ->tooltip(function ($record) {
                            $lines = [];

                            if ($record->summary) {
                                $summaryText = is_string($record->summary) ? $record->summary : json_encode($record->summary);
                                $lines[] = "📝 Zusammenfassung:\n" . $summaryText;
                            }

                            if (!empty($record->recording_url)) {
                                $lines[] = "🎙️ Audio-Aufnahme verfügbar";
                            }

                            // Add sentiment/mood information
                            $sentiment = $record->sentiment;
                            if ($sentiment) {
                                $sentimentLabel = match(ucfirst(strtolower($sentiment))) {
                                    'Positive' => '😊 Positiv',
                                    'Neutral' => '😐 Neutral',
                                    'Negative' => '😟 Negativ',
                                    default => '❓ Unbekannt',
                                };
                                $lines[] = "💭 Stimmung: " . $sentimentLabel;
                            }

                            return !empty($lines) ? implode("\n\n", $lines) : 'Keine Informationen';
                        })
                        ->wrap()
                        ->toggleable(),

                    // 💰 SECURE Cost display with HIERARCHICAL access
                    Tables\Columns\TextColumn::make('financials')
                        ->label('Tel.-Kosten')
                        ->getStateUsing(function (Call $record) {
                            $user = auth()->user();

                            // SECURITY: Verify tenant access before showing cost
                            if (!$user) {
                                return new HtmlString('<span class="text-gray-400">-</span>');
                            }

                            // COST HIERARCHY - 3 LEVELS:
                            // Level 1 (AskProAI/Super-Admin): base_cost (OUR costs)
                            // Level 2 (Reseller): reseller_cost (their costs from us)
                            // Level 3 (Customer): customer_cost (what THEY charge their customers)

                            // 🔐 LEVEL 1: Super-admin sees BASE costs + PROFIT
                            if ($user->hasRole(['super-admin', 'super_admin', 'Super Admin'])) {
                                $primaryCost = $record->base_cost ?? 0;
                                // AskProAI's profit: what we charge reseller minus our cost
                                $profitCost = ($record->reseller_cost ?? 0) - ($record->base_cost ?? 0);
                                $costType = 'Cost';
                                $profitLabel = 'AskProAI Profit';
                            }
                            // 🔐 LEVEL 2: Reseller can see RESELLER costs + PROFIT
                            elseif ($user->hasRole(['reseller_admin', 'reseller_owner', 'reseller_support'])) {
                                // Check if reseller owns this branch/company
                                $canViewResellerCost = ($record->branch_id && $user->branches?->contains('id', $record->branch_id))
                                    || ($record->company_id && $user->company_id === $record->company_id);

                                if ($canViewResellerCost) {
                                    // Reseller sees THEIR cost
                                    $primaryCost = $record->reseller_cost ?? $record->base_cost ?? 0;
                                    // Reseller's profit: what customer pays minus what reseller pays us
                                    $profitCost = ($record->customer_cost ?? 0) - ($record->reseller_cost ?? 0);
                                    $costType = 'Your Cost';
                                    $profitLabel = 'Your Profit';
                                } else {
                                    // SECURITY: Don't show costs for calls from other resellers
                                    return new HtmlString('<span class="text-gray-400 text-xs">-</span>');
                                }
                            }
                            // 🔐 LEVEL 3: Customer can see THEIR costs only
                            else {
                                // Check if customer owns this branch (direct employee)
                                $canViewCustomerCost = ($record->branch_id && $user->branches?->contains('id', $record->branch_id))
                                    || ($record->company_id && $user->company_id === $record->company_id);

                                if (!$canViewCustomerCost) {
                                    // SECURITY: Don't show costs for calls from other customers/branches
                                    return new HtmlString('<span class="text-gray-400 text-xs">-</span>');
                                }

                                // Customer sees what THEY are charged
                                $primaryCost = $record->customer_cost ?? $record->cost ?? 0;
                                if (is_numeric($record->cost) && $primaryCost == 0) {
                                    $primaryCost = round($record->cost * 100);
                                }
                                $profitCost = 0; // Customer doesn't see profit
                                $costType = 'Your Charge';
                                $profitLabel = '';
                            }

                            $formattedCost = number_format($primaryCost / 100, 2, ',', '.');
                            $formattedProfit = ($profitCost > 0) ? number_format($profitCost / 100, 2, ',', '.') : '0,00';

                            // Build tooltip with cost breakdown
                            $tooltipParts = [$costType . ': ' . $formattedCost . '€'];
                            if ($profitCost > 0 && $profitLabel) {
                                $tooltipParts[] = $profitLabel . ': ' . $formattedProfit . '€';
                            }
                            $tooltipText = implode(' | ', $tooltipParts);

                            // Status indicator (actual vs estimated)
                            $statusDot = '';
                            if ($record->total_external_cost_eur_cents > 0) {
                                // Green dot for actual costs
                                $statusDot = '<span class="inline-block w-1.5 h-1.5 rounded-full bg-green-500 dark:bg-green-400 ml-1" title="Tatsächliche Kosten"></span>';
                            } else {
                                // Yellow dot for estimated costs
                                $statusDot = '<span class="inline-block w-1.5 h-1.5 rounded-full bg-yellow-500 dark:bg-yellow-400 ml-1" title="Geschätzte Kosten"></span>';
                            }

                            return new HtmlString(
                                '<div class="flex flex-col gap-0.5">' .
                                '<div class="flex items-center gap-1">' .
                                '<span class="font-semibold" title="' . $tooltipText . '">' . $formattedCost . '€</span>' .
                                ($profitCost > 0 ? '<span class="text-green-600 text-xs font-medium">(+' . $formattedProfit . '€)</span>' : '') .
                                $statusDot .
                                '</div>' .
                                '<span class="text-xs text-gray-600">' . $costType . ($profitLabel ? ' / ' . $profitLabel : '') . '</span>' .
                                '</div>'
                            );
                        })
                        ->html()
                        ->sortable(query: function (Builder $query, string $direction): Builder {
                            return $query->orderBy('customer_cost', $direction);
                        })
                        ->extraAttributes(['class' => 'font-mono'])
                        ->toggleable(),

                    // 🚫 CANCELLATION STATUS: Shows if call has cancelled appointments OR performed cancellations
                    Tables\Columns\TextColumn::make('cancellation_status')
                        ->label('Stornierungen')
                        ->getStateUsing(function (Call $record) {
                            // Case 1: This call HAS cancelled appointments (booking call)
                            $cancelledAppointments = $record->appointments()
                                ->where('status', 'cancelled')
                                ->with(['modifications' => function ($q) {
                                    $q->where('modification_type', 'cancel')
                                      ->latest('created_at');
                                }])
                                ->get();

                            if ($cancelledAppointments->isNotEmpty()) {
                                $count = $cancelledAppointments->count();
                                return $count === 1 ? 'Termin storniert' : "{$count} Termine storniert";
                            }

                            // Case 2: This call PERFORMED cancellations (cancellation call)
                            // Find appointments cancelled via this call's retell_call_id
                            if ($record->retell_call_id) {
                                $performedCancellations = \App\Models\AppointmentModification::query()
                                    ->where('modification_type', 'cancel')
                                    ->whereJsonContains('metadata->call_id', $record->retell_call_id)
                                    ->count();

                                if ($performedCancellations > 0) {
                                    return $performedCancellations === 1
                                        ? 'Stornierung durchgeführt'
                                        : "{$performedCancellations} Stornierungen durchgeführt";
                                }
                            }

                            return null; // No cancellations related to this call
                        })
                        ->badge()
                        ->color('warning') // Orange badge
                        ->icon('heroicon-m-x-circle')
                        ->tooltip(function (Call $record) {
                            // Case 1: This call HAS cancelled appointments
                            $cancelledAppointments = $record->appointments()
                                ->where('status', 'cancelled')
                                ->with(['modifications' => function ($q) {
                                    $q->where('modification_type', 'cancel')
                                      ->latest('created_at')
                                      ->limit(1);
                                }, 'service'])
                                ->get();

                            if ($cancelledAppointments->isNotEmpty()) {
                                $lines = ['📋 TERMINE DIESES CALLS WURDEN STORNIERT:'];
                                $lines[] = '';

                                foreach ($cancelledAppointments as $appointment) {
                                    $summary = $appointment->getCancellationSummary();

                                    $lines[] = '━━━━━━━━━━━━━━━━━━━';
                                    if ($appointment->service) {
                                        $lines[] = '📋 Service: ' . $appointment->service->name;
                                    }
                                    if ($appointment->starts_at) {
                                        $lines[] = '📅 Geplant: ' . $appointment->starts_at->format('d.m.Y H:i');
                                    }
                                    $lines[] = '🚫 Storniert am: ' . $summary['cancelled_at'];
                                    $lines[] = '👤 Storniert von: ' . $summary['cancelled_by'];

                                    if ($summary['reason']) {
                                        $lines[] = '💬 Grund: ' . \Str::limit($summary['reason'], 50);
                                    }
                                    if ($summary['fee'] > 0) {
                                        $lines[] = '💰 Gebühr: ' . number_format($summary['fee'], 2) . ' €';
                                    }

                                    $policyIcon = $summary['within_policy'] ? '✅' : '⚠️';
                                    $policyText = $summary['within_policy'] ? 'Innerhalb Richtlinien' : 'Außerhalb Richtlinien';
                                    $lines[] = $policyIcon . ' ' . $policyText;

                                    if ($summary['cancellation_call_id'] && $summary['cancellation_call_id'] !== $record->id) {
                                        $lines[] = '📞 Storniert in Anruf #' . $summary['cancellation_call_id'];
                                        $lines[] = '   → Klick für Details zum Stornierungsanruf';
                                    }
                                }

                                return implode("\n", $lines);
                            }

                            // Case 2: This call PERFORMED cancellations
                            if ($record->retell_call_id) {
                                $performedMods = \App\Models\AppointmentModification::query()
                                    ->where('modification_type', 'cancel')
                                    ->whereJsonContains('metadata->call_id', $record->retell_call_id)
                                    ->with(['appointment.service'])
                                    ->get();

                                if ($performedMods->isNotEmpty()) {
                                    $lines = ['📞 IN DIESEM CALL WURDEN TERMINE STORNIERT:'];
                                    $lines[] = '';

                                    foreach ($performedMods as $mod) {
                                        $appointment = $mod->appointment;
                                        if (!$appointment) continue;

                                        $lines[] = '━━━━━━━━━━━━━━━━━━━';
                                        if ($appointment->service) {
                                            $lines[] = '📋 Service: ' . $appointment->service->name;
                                        }
                                        if ($appointment->starts_at) {
                                            $lines[] = '📅 War geplant: ' . $appointment->starts_at->format('d.m.Y H:i');
                                        }
                                        $lines[] = '🚫 Storniert am: ' . $mod->created_at->format('d.m.Y H:i');

                                        if ($mod->reason) {
                                            $lines[] = '💬 Grund: ' . \Str::limit($mod->reason, 50);
                                        }
                                        if ($mod->fee_charged > 0) {
                                            $lines[] = '💰 Gebühr: ' . number_format($mod->fee_charged, 2) . ' €';
                                        }

                                        $policyIcon = $mod->within_policy ? '✅' : '⚠️';
                                        $policyText = $mod->within_policy ? 'Innerhalb Richtlinien' : 'Außerhalb Richtlinien';
                                        $lines[] = $policyIcon . ' ' . $policyText;

                                        if ($appointment->call_id && $appointment->call_id !== $record->id) {
                                            $lines[] = '📞 Ursprünglich gebucht in Anruf #' . $appointment->call_id;
                                            $lines[] = '   → Klick für Details zum Buchungsanruf';
                                        }
                                    }

                                    return implode("\n", $lines);
                                }
                            }

                            return null;
                        })
                        ->url(function (Call $record) {
                            // Case 1: If this call has cancelled appointments, link to cancellation call
                            $cancelledAppointment = $record->appointments()
                                ->where('status', 'cancelled')
                                ->first();

                            if ($cancelledAppointment) {
                                $summary = $cancelledAppointment->getCancellationSummary();
                                if ($summary['cancellation_call_id'] && $summary['cancellation_call_id'] !== $record->id) {
                                    return CallResource::getUrl('view', ['record' => $summary['cancellation_call_id']]);
                                }
                            }

                            // Case 2: If this call performed cancellations, link to booking call
                            if ($record->retell_call_id) {
                                $performedMod = \App\Models\AppointmentModification::query()
                                    ->where('modification_type', 'cancel')
                                    ->whereJsonContains('metadata->call_id', $record->retell_call_id)
                                    ->with('appointment')
                                    ->first();

                                if ($performedMod && $performedMod->appointment && $performedMod->appointment->call_id !== $record->id) {
                                    return CallResource::getUrl('view', ['record' => $performedMod->appointment->call_id]);
                                }
                            }

                            return null;
                        }, shouldOpenInNewTab: false)
                        ->toggleable(),

                    // 🔥 NEW 2025-11-20: Call Relationships - Links between booking and cancellation calls
                    Tables\Columns\ViewColumn::make('call_relationships')
                        ->label('Verknüpfungen')
                        ->view('filament.columns.call-relationships')
                        ->toggleable(),
        ];
    }

    /**
     * Get compact columns (6 optimized columns)
     *
     * @return array
     */
    protected static function getCompactColumns(): array
    {
        return [
            // 1️⃣ Anrufer - 🔄 2025-11-26: Als erste Spalte
            Tables\Columns\ViewColumn::make('anrufer_display')
                ->label('Anrufer')
                ->view('filament.columns.anrufer-3lines')
                ->width('140px')
                ->searchable(query: function (Builder $query, string $search): Builder {
                    return $query
                        ->where(function ($q) use ($search) {
                            $q->whereRaw("JSON_EXTRACT(metadata, '$.customer_name') LIKE ?", ["%{$search}%"])
                              ->orWhereHas('customer', fn (Builder $query) => $query->where('name', 'like', "%{$search}%"));
                        });
                })
                ->sortable()
                ->toggleable(),

            // 2️⃣ Ereignis & Zeit - 🔄 2025-11-26: Verschoben vor Termin/Mitarbeiter
            Tables\Columns\ViewColumn::make('action_time_duration')
                ->label('Ereignis / Zeit / Dauer')
                ->view('filament.columns.action-time-duration')
                ->width('100px')
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
                ->toggleable(isToggledHiddenByDefault: false),

            // 3. Status (LEGACY: hidden - consolidated into Ereignis column)
            Tables\Columns\TextColumn::make('booking_status')
                ->label('Status (Legacy)')
                ->getStateUsing(function (Call $record) {
                    $appointments = $record->appointments;

                    if ($appointments->isEmpty()) {
                        return 'Offen';
                    }

                    // Check for active appointments
                    $hasActive = $appointments->whereIn('status', ['scheduled', 'confirmed', 'booked', 'pending'])->isNotEmpty();
                    $hasCancelled = $appointments->where('status', 'cancelled')->isNotEmpty();

                    // Priority: Active > Cancelled > Offen
                    if ($hasActive && $hasCancelled) {
                        return 'Teilstorno';
                    } elseif ($hasActive) {
                        return 'Gebucht';
                    } elseif ($hasCancelled) {
                        return 'Storniert';
                    }

                    return 'Offen';
                })
                ->badge()
                ->color(fn ($state) => match($state) {
                    'Gebucht' => 'success',
                    'Storniert' => 'warning',
                    'Teilstorno' => 'info',
                    'Offen' => 'danger',
                    default => 'gray',
                })
                ->tooltip(function (Call $record) {
                    $appointments = $record->appointments;
                    if ($appointments->isEmpty()) {
                        return 'Keine Termine in diesem Anruf';
                    }

                    $lines = ['📊 TERMIN-STATUS:'];
                    $lines[] = '━━━━━━━━━━━━━━━━━━━';

                    $active = $appointments->whereIn('status', ['scheduled', 'confirmed', 'booked', 'pending']);
                    $cancelled = $appointments->where('status', 'cancelled');

                    if ($active->isNotEmpty()) {
                        $lines[] = "✅ Gebucht: {$active->count()} Termin" . ($active->count() > 1 ? 'e' : '');
                    }

                    if ($cancelled->isNotEmpty()) {
                        $lines[] = "🚫 Storniert: {$cancelled->count()} Termin" . ($cancelled->count() > 1 ? 'e' : '');
                    }

                    return implode("\n", $lines);
                })
                ->hidden()  // 🚫 Hidden - consolidated into Ereignis column (rollback: remove this line)
                ->toggleable(),

            // 4. Termin / Mitarbeiter (compact version of appointment_summary) - 🆕 2025-11-24: Enhanced for composite
            Tables\Columns\TextColumn::make('appointment_summary')
                ->label('Termin / Mitarbeiter')
                ->width('150px')  // 🆕 2025-11-26: Moderate Breite für Termin + MA
                ->getStateUsing(function (Call $record) {
                    $appointments = $record->appointments;

                    // 🆕 2025-11-25: Check if THIS CALL performed a reschedule
                    $rescheduleDetails = null;
                    if ($record->retell_call_id) {
                        try {
                            $rescheduleDetails = \App\Models\AppointmentModification::where('modification_type', 'reschedule')
                                ->whereJsonContains('metadata->call_id', $record->retell_call_id)
                                ->with('appointment.service', 'appointment.staff')
                                ->first();
                        } catch (\Exception $e) {
                            // Silently ignore
                        }
                    }

                    // If this call performed a reschedule, show old/new time + staff (compact)
                    if ($rescheduleDetails && $rescheduleDetails->appointment) {
                        $appt = $rescheduleDetails->appointment;
                        $metadata = $rescheduleDetails->metadata ?? [];
                        $staffName = $appt->staff ? \Str::limit($appt->staff->name, 12) : 'Kein MA';
                        $staffColor = $appt->staff ? 'text-green-600' : 'text-orange-600';

                        $oldDateTime = isset($metadata['original_time'])
                            ? \Carbon\Carbon::parse($metadata['original_time'])->locale('de')->isoFormat('DD.MM HH:mm')
                            : null;
                        $newDateTime = isset($metadata['new_time'])
                            ? \Carbon\Carbon::parse($metadata['new_time'])->locale('de')->isoFormat('DD.MM HH:mm')
                            : ($appt->starts_at ? \Carbon\Carbon::parse($appt->starts_at)->locale('de')->isoFormat('DD.MM HH:mm') : 'unbekannt');

                        $lines = [];
                        if ($oldDateTime) {
                            $lines[] = '<span class="text-xs text-gray-400" style="text-decoration: line-through;">❌ ' . $oldDateTime . '</span>';
                        }
                        $lines[] = '<span class="text-xs ' . $staffColor . '">✅ ' . $newDateTime . ' → ' . $staffName . '</span>';

                        return new HtmlString(implode('<br>', $lines));
                    }

                    if ($appointments->isEmpty()) {
                        return new HtmlString('<span class="text-xs text-gray-400">-</span>');
                    }

                    $lines = [];
                    foreach ($appointments->take(2) as $appointment) {
                        if (!$appointment->starts_at) {
                            continue;
                        }

                        $startDate = \Carbon\Carbon::parse($appointment->starts_at);
                        $dateTime = $startDate->locale('de')->isoFormat('DD.MM HH:mm');

                        if ($appointment->status === 'cancelled') {
                            $lines[] = '<span class="text-xs text-orange-600 dark:text-orange-400 font-semibold">Storniert</span>';
                            $lines[] = '<span class="text-xs text-gray-500 dark:text-gray-400" style="text-decoration: line-through;">' . $dateTime . '</span>';
                        } else {
                            // 🆕 2025-11-26: Simplified - only date/time + staff
                            // Service info + segments now in "Service / Preis" column
                            $startDt = \Carbon\Carbon::parse($appointment->starts_at)->locale('de');
                            $endTime = $appointment->ends_at ? \Carbon\Carbon::parse($appointment->ends_at)->format('H:i') : null;
                            $fullDateTime = $startDt->isoFormat('dd. D.M.') . ' ' . $startDt->format('H:i') . ($endTime ? '-' . $endTime : '');
                            $staffName = $appointment->staff ? \Str::limit($appointment->staff->name, 12) : 'Kein MA';

                            // Line 1: Date/Time (bold)
                            $lines[] = '<span class="text-xs text-gray-800 dark:text-gray-200 font-semibold">' . $fullDateTime . ' Uhr</span>';
                            // Line 2: Staff
                            $staffColor = $appointment->staff ? 'text-gray-700 dark:text-gray-300' : 'text-orange-600';
                            $lines[] = '<span class="text-xs ' . $staffColor . '" title="Mitarbeiter">MA: ' . $staffName . '</span>';
                        }
                    }

                    if ($appointments->count() > 2) {
                        $remaining = $appointments->count() - 2;
                        $lines[] = '<span class="text-xs text-gray-500">+' . $remaining . '</span>';
                    }

                    return new HtmlString(implode('<br>', $lines));
                })
                ->html()
                ->sortable(query: fn($query, $direction) =>
                    $query->orderBy('appointments.starts_at', $direction)
                        ->leftJoin('appointments', 'calls.id', '=', 'appointments.call_id')
                )
                ->tooltip(function (Call $record) {
                    // 🆕 2025-11-25: Check if THIS CALL performed a reschedule
                    $rescheduleDetails = null;
                    if ($record->retell_call_id) {
                        try {
                            $rescheduleDetails = \App\Models\AppointmentModification::where('modification_type', 'reschedule')
                                ->whereJsonContains('metadata->call_id', $record->retell_call_id)
                                ->with('appointment.service', 'appointment.staff')
                                ->first();
                        } catch (\Exception $e) {
                            // Silently ignore
                        }
                    }

                    // If this call performed a reschedule, show reschedule details
                    if ($rescheduleDetails && $rescheduleDetails->appointment) {
                        $appt = $rescheduleDetails->appointment;
                        $metadata = $rescheduleDetails->metadata ?? [];

                        $parts = [];
                        $parts[] = '🔄 Termin verschoben:';
                        $parts[] = '━━━━━━━━━━━━━━━━━━━━━━━━';

                        if (isset($metadata['original_time'])) {
                            $parts[] = '❌ Von: ' . \Carbon\Carbon::parse($metadata['original_time'])->format('d.m.Y H:i');
                        }
                        if (isset($metadata['new_time'])) {
                            $parts[] = '✅ Auf: ' . \Carbon\Carbon::parse($metadata['new_time'])->format('d.m.Y H:i');
                        }
                        if ($appt->staff) {
                            $parts[] = '👤 Mitarbeiter: ' . $appt->staff->name;
                        }

                        return implode("\n", $parts);
                    }

                    $appointments = $record->appointments;
                    if ($appointments->isEmpty()) {
                        return null;
                    }

                    $parts = [];
                    $parts[] = '📋 TERMINE (' . $appointments->count() . '):';
                    $parts[] = '━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━';

                    foreach ($appointments as $index => $appointment) {
                        if ($index > 0) {
                            $parts[] = '';
                            $parts[] = '---';
                            $parts[] = '';
                        }

                        $statusIcon = match($appointment->status) {
                            'cancelled' => '🚫',
                            'confirmed', 'scheduled', 'booked' => '✅',
                            'completed' => '✓',
                            default => '•'
                        };

                        $parts[] = $statusIcon . ' TERMIN #' . ($index + 1);

                        if ($appointment->service) {
                            $parts[] = '📋 Service: ' . $appointment->service->name;

                            // 🆕 2025-11-24: Show segments for composite services (numbered list)
                            if ($appointment->service->composite) {
                                try {
                                    $phases = $appointment->phases()->where('staff_required', true)->orderBy('sequence_order')->get();
                                    if ($phases->isNotEmpty()) {
                                        $parts[] = 'Compound-Service (' . $phases->count() . ' Schritte):';
                                        foreach ($phases as $index => $phase) {
                                            $number = $index + 1;
                                            $parts[] = '   ' . $number . '. ' . $phase->segment_name . ' (' . $phase->duration_minutes . ' min)';
                                        }
                                    }
                                } catch (\Exception $e) {
                                    // Silently handle if phases can't be loaded
                                }
                            }
                        }

                        if ($appointment->status === 'cancelled') {
                            $parts[] = '⚠️ STATUS: STORNIERT';
                            $cancellationSummary = $appointment->getCancellationSummary();
                            if ($cancellationSummary['cancelled_at']) {
                                $parts[] = '🚫 Storniert am: ' . $cancellationSummary['cancelled_at'];
                            }
                            if ($cancellationSummary['cancelled_by']) {
                                $parts[] = '👤 Von: ' . $cancellationSummary['cancelled_by'];
                            }
                        }

                        if ($appointment->starts_at) {
                            $start = \Carbon\Carbon::parse($appointment->starts_at);
                            $parts[] = '📅 Datum: ' . $start->format('d.m.Y');
                            $parts[] = '🕐 Zeit: ' . $start->format('H:i') . ($appointment->ends_at ? ' - ' . \Carbon\Carbon::parse($appointment->ends_at)->format('H:i') : '');
                        }

                        if ($appointment->staff) {
                            $parts[] = '👤 Mitarbeiter: ' . $appointment->staff->name;
                        } else {
                            $parts[] = '⚠️ Mitarbeiter: Nicht zugewiesen';
                        }
                    }

                    return implode("\n", $parts);
                })
                ->toggleable(isToggledHiddenByDefault: false),  // 🆕 2025-11-26: Im Spalten-Selector anzeigen

            // 5. Service/Preis - 🆕 2025-11-26: Show service + price + segments (compact)
            Tables\Columns\TextColumn::make('service_type')
                ->label('Service / Preis')
                ->width('180px')  // 🆕 2025-11-26: Mehr Platz für Service + Segmente
                ->html()
                ->getStateUsing(function ($record) {
                    try {
                        // 🆕 2025-11-25: Check if THIS CALL performed a reschedule
                        $rescheduleDetails = null;
                        if ($record->retell_call_id) {
                            try {
                                $rescheduleDetails = \App\Models\AppointmentModification::where('modification_type', 'reschedule')
                                    ->whereJsonContains('metadata->call_id', $record->retell_call_id)
                                    ->with('appointment.service')
                                    ->first();
                            } catch (\Exception $e) {
                                // Silently ignore
                            }
                        }

                        // If this call performed a reschedule, show the service
                        if ($rescheduleDetails && $rescheduleDetails->appointment && $rescheduleDetails->appointment->service) {
                            $appt = $rescheduleDetails->appointment;
                            $service = $appt->service;
                            $name = ($service->display_name && trim($service->display_name) !== '')
                                ? $service->display_name
                                : $service->name;
                            $price = $service->price;
                            $isComposite = $service->composite ?? false;

                            $lines = [];
                            if ($isComposite) {
                                $phaseCount = 0;
                                try { $phaseCount = $appt->phases()->where('staff_required', true)->count(); } catch (\Exception $e) {}
                                $lines[] = '<span class="text-xs text-gray-800 dark:text-gray-200 font-semibold">' . htmlspecialchars(\Str::limit($name, 15)) . ' (' . $phaseCount . ')</span>';
                            } else {
                                $lines[] = '<span class="text-xs text-gray-800 dark:text-gray-200 font-semibold">' . htmlspecialchars(\Str::limit($name, 15)) . '</span>';
                            }

                            if ($price && $price > 0) {
                                $formattedPrice = number_format($price, 0, ',', '.');
                                $lines[] = '<span class="text-xs text-green-600 dark:text-green-400">' . $formattedPrice . ' €</span>';
                            }

                            return '<div>' . implode('<br>', $lines) . '</div>';
                        }

                        $appointments = $record->appointments ?? collect();

                        if (!$appointments || $appointments->isEmpty()) {
                            return '<span class="text-gray-500 dark:text-gray-400 text-xs">-</span>';
                        }

                        $allLines = [];
                        $seen = [];

                        foreach ($appointments as $appt) {
                            if (!$appt || !$appt->service) continue;

                            $serviceId = $appt->service->id;
                            if (in_array($serviceId, $seen)) continue;
                            $seen[] = $serviceId;

                            $name = ($appt->service->display_name && trim($appt->service->display_name) !== '')
                                ? $appt->service->display_name
                                : $appt->service->name;
                            $price = $appt->service->price;
                            $isComposite = $appt->service->composite ?? false;
                            $isCancelled = $appt->status === 'cancelled';

                            $lines = [];

                            // Line 1: Service name (+ segment count if composite)
                            $textStyle = $isCancelled ? 'text-gray-500 dark:text-gray-400 line-through' : 'text-gray-800 dark:text-gray-200 font-semibold';
                            if ($isComposite) {
                                $phaseCount = 0;
                                try { $phaseCount = $appt->phases()->where('staff_required', true)->count(); } catch (\Exception $e) {}
                                $lines[] = '<span class="text-xs ' . $textStyle . '">' . htmlspecialchars(\Str::limit($name, 15)) . ' (' . $phaseCount . ')</span>';
                            } else {
                                $lines[] = '<span class="text-xs ' . $textStyle . '">' . htmlspecialchars(\Str::limit($name, 18)) . '</span>';
                            }

                            // Line 2: Price
                            if ($price && $price > 0) {
                                $formattedPrice = number_format($price, 0, ',', '.');
                                $priceColor = $isCancelled ? 'text-gray-400' : 'text-green-600 dark:text-green-400';
                                $lines[] = '<span class="text-xs ' . $priceColor . '">' . $formattedPrice . ' €' . ($isCancelled ? ' (storniert)' : '') . '</span>';
                            }

                            // Lines 3+: Segments (for composite services, max 3 in compact view)
                            if ($isComposite && !$isCancelled) {
                                try {
                                    $phases = $appt->phases()
                                        ->where('staff_required', true)
                                        ->orderBy('sequence_order')
                                        ->limit(3)
                                        ->get();

                                    foreach ($phases as $index => $phase) {
                                        $number = $index + 1;
                                        $phaseName = $phase->segment_name;
                                        if (strlen($phaseName) > 18) {
                                            $phaseName = substr($phaseName, 0, 15) . '...';
                                        }
                                        $lines[] = '<span class="text-xs text-gray-600 dark:text-gray-400">' . $number . '. ' . $phaseName . '</span>';
                                    }

                                    $totalPhases = $appt->phases()->where('staff_required', true)->count();
                                    if ($totalPhases > 3) {
                                        $lines[] = '<span class="text-xs text-gray-500 italic">+' . ($totalPhases - 3) . ' weitere</span>';
                                    }
                                } catch (\Exception $e) {}
                            }

                            $allLines[] = implode('<br>', $lines);
                        }

                        if (empty($allLines)) {
                            return '<span class="text-gray-500 dark:text-gray-400 text-xs">-</span>';
                        }

                        return '<div class="text-xs">' . implode('<br><br>', $allLines) . '</div>';
                    } catch (\Throwable $e) {
                        return '<span class="text-gray-400 text-xs">-</span>';
                    }
                })
                ->tooltip(function ($record) {
                    try {
                        // 🆕 2025-11-25: Check if THIS CALL performed a reschedule
                        if ($record->retell_call_id) {
                            try {
                                $rescheduleDetails = \App\Models\AppointmentModification::where('modification_type', 'reschedule')
                                    ->whereJsonContains('metadata->call_id', $record->retell_call_id)
                                    ->with('appointment.service')
                                    ->first();

                                if ($rescheduleDetails && $rescheduleDetails->appointment && $rescheduleDetails->appointment->service) {
                                    $service = $rescheduleDetails->appointment->service;
                                    $name = $service->name ?? 'Service';
                                    $duration = $service->duration ?? '?';
                                    $price = $service->price ?? 0;
                                    $formattedPrice = ($price && $price > 0) ? number_format($price, 0, ',', '.') . '€' : 'Kein Preis';

                                    return "{$name}\nDauer: {$duration} Min\nPreis: {$formattedPrice}";
                                }
                            } catch (\Exception $e) {
                                // Silently ignore
                            }
                        }

                        $appointments = $record->appointments ?? collect();

                        if (!$appointments || $appointments->isEmpty()) {
                            return 'Kein Termin gebucht';
                        }

                        $details = $appointments
                            ->filter(fn($appt) => $appt)
                            ->map(function ($appt) {
                                $name = $appt->service?->name ?? 'Unbekannt';
                                $duration = $appt->service?->duration ?? $appt->duration ?? '?';
                                $price = $appt->service?->price ?? 0;

                                if ($price && $price > 0) {
                                    $formattedPrice = number_format($price, 0, ',', '.');
                                    return "{$name} ({$duration} Min) - {$formattedPrice}€";
                                }
                                return "{$name} ({$duration} Min)";
                            })
                            ->implode("\n");

                        return $details ?: 'Kein Termin gebucht';
                    } catch (\Throwable $e) {
                        return 'Fehler beim Laden';
                    }
                })
                ->wrap()
                ->toggleable(),

            // 6. Details (summary text + audio player)
            Tables\Columns\TextColumn::make('summary_audio')
                ->label('Details')
                ->width('200px')  // 🆕 2025-11-26: Mehr Platz für Zusammenfassung + Audio
                ->html()
                ->getStateUsing(function ($record) {
                    $summary = '';
                    if ($record->summary) {
                        $summaryText = is_string($record->summary) ? $record->summary : json_encode($record->summary);

                        // Show up to 3 lines (~120 chars) of summary text
                        $maxLength = 120;
                        $summaryDisplay = mb_strlen($summaryText) > $maxLength
                            ? mb_substr($summaryText, 0, $maxLength) . '...'
                            : $summaryText;

                        // Wrap into 3 lines max
                        $summary = '<div class="text-xs text-gray-700 dark:text-gray-300 leading-tight mb-1" style="max-width: 180px; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;">' .
                                  htmlspecialchars($summaryDisplay) .
                                  '</div>';
                    }

                    $audio = '';
                    if (!empty($record->recording_url)) {
                        $url = $record->recording_url;

                        // Audio player width matches text width
                        $audio = '<div class="mt-1">
                                    <audio controls preload="none"
                                           class="h-6"
                                           style="height: 24px; width: 180px; max-width: 100%;"
                                           controlsList="nodownload">
                                        <source src="' . htmlspecialchars($url) . '" type="audio/mpeg">
                                        <source src="' . htmlspecialchars($url) . '" type="audio/wav">
                                    </audio>
                                  </div>';
                    }

                    if (empty($summary) && empty($audio)) {
                        return '<span class="text-gray-400 text-xs">-</span>';
                    }

                    return '<div style="max-width: 180px;">' . $summary . $audio . '</div>';
                })
                ->tooltip(function ($record) {
                    $lines = [];

                    if ($record->summary) {
                        $summaryText = is_string($record->summary) ? $record->summary : json_encode($record->summary);
                        $lines[] = "📝 Zusammenfassung:\n" . $summaryText;
                    }

                    if (!empty($record->recording_url)) {
                        $lines[] = "🎙️ Audio-Aufnahme verfügbar";
                    }

                    $sentiment = $record->sentiment;
                    if ($sentiment) {
                        $sentimentLabel = match(ucfirst(strtolower($sentiment))) {
                            'Positive' => '😊 Positiv',
                            'Neutral' => '😐 Neutral',
                            'Negative' => '😟 Negativ',
                            default => '❓ Unbekannt',
                        };
                        $lines[] = "💭 Stimmung: " . $sentimentLabel;
                    }

                    return !empty($lines) ? implode("\n\n", $lines) : 'Keine Informationen';
                })
                ->wrap()
                ->toggleable(),
        ];
    }

    /**
     * Get user's preferred view mode
     *
     * @return string 'compact' or 'classic'
     */
    protected static function getUserViewMode(): string
    {
        if (!auth()->check()) {
            return 'compact'; // Default for unauthenticated
        }

        return \App\Models\UserPreference::get(
            auth()->id(),
            'call_list_view_mode',
            'compact' // DEFAULT: Compact view
        )['mode'] ?? 'compact';
    }

    /**
     * SECURITY: Safe scope bypass - Super Admin role-based authorization
     * Pattern: withoutGlobalScopes() with explicit role check before bypass
     * @see HasSecureScopeBypass::resolveRecordWithAdminBypass()
     *
     * 🔧 FIX 2025-11-24: Resolve record route binding for Super Admin access
     * Super Admins see ALL calls across all companies for system-wide support.
     */
    public static function resolveRecordRouteBinding($key): ?\Illuminate\Database\Eloquent\Model
    {
        $user = auth()->user();

        // Super Admins bypass company scoping - supports all role name variations
        if ($user && $user->hasRole(['super_admin', 'Super Admin', 'superadmin'])) {
            return static::getModel()::withoutGlobalScopes()->find($key);
        }

        // Regular users get default scoped behavior
        return parent::resolveRecordRouteBinding($key);
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