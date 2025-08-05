<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\CallResource\Actions\MarkAsNonBillableAction;
use App\Filament\Admin\Resources\CallResource\Pages;
use App\Filament\Admin\Resources\CallResource\Widgets;
use App\Filament\Admin\Resources\Concerns\HasManyColumns;
use App\Filament\Admin\Traits\HasTooltips;
use App\Models\Appointment;
use App\Models\Call;
use App\Models\Customer;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class CallResource extends Resource
{
    use HasManyColumns, HasTooltips;

    protected static ?string $model = Call::class;

    protected static ?string $navigationIcon = 'heroicon-o-phone-arrow-down-left';

    protected static ?string $navigationLabel = null;

    protected static ?string $navigationGroup = 'Täglicher Betrieb';
    
    public static function getNavigationLabel(): string
    {
        return __('admin.resources.calls');
    }
    
    public static function getNavigationGroup(): ?string
    {
        return __('admin.navigation.daily_operations');
    }

    protected static ?int $navigationSort = 1; // Erste Position in der Gruppe

    protected static bool $shouldRegisterNavigation = true;

    protected static ?string $recordTitleAttribute = 'call_id';

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        // No user logged in
        if (! $user) {
            return false;
        }

        // Super admin can view all
        if ($user->hasRole('super_admin') || $user->hasRole('Super Admin')) {
            return true;
        }

        // Check specific permission or if user belongs to a company
        return $user->can('view_any_call') || $user->company_id !== null;
    }

    public static function canView($record): bool
    {
        $user = auth()->user();

        // No user logged in
        if (! $user) {
            return false;
        }

        // Super admin can view all
        if ($user->hasRole('super_admin') || $user->hasRole('Super Admin')) {
            return true;
        }

        // Check specific permission
        if ($user->can('view_call')) {
            return true;
        }

        // Users can view calls from their own company
        return $user->company_id === $record->company_id;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(
                fn ($query) => $query
                    ->with(['customer:id,name,phone,email', 'appointment:id,status', 'branch:id,name', 'company:id,name'])
                    ->select('calls.*')
            )
            ->striped()
            ->defaultSort('start_timestamp', 'desc')
            ->extremePaginationLinks()
            ->paginated([10, 25, 50])
            ->paginationPageOptions([10, 25, 50])
            ->defaultPaginationPageOption(25)
            ->selectCurrentPageOnly()
            // Temporarily disable record classes for performance
            // ->recordClasses(fn ($record) => match ($record->sentiment) {
            //     'positive' => 'border-l-4 border-green-500',
            //     'negative' => 'border-l-4 border-red-500',
            //     default => '',
            // })
            ->columns([
                Tables\Columns\TextColumn::make('start_timestamp')
                    ->label('Anrufstart')
                    ->dateTime('d.m.Y H:i:s')
                    ->sortable()
                    ->formatStateUsing(fn ($state, $record) => $state ?? $record->created_at)
                    ->icon('heroicon-m-phone-arrow-down-left')
                    ->iconColor('primary')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('from_number')
                    ->label('Anrufer')
                    ->sortable()
                    ->searchable()
                    ->icon('heroicon-m-user')
                    ->iconColor('primary')
                    ->copyable()
                    ->copyMessage('Nummer kopiert!')
                    ->copyMessageDuration(1500)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Kunde')
                    ->searchable()
                    ->sortable()
                    ->url(
                        fn ($record) => $record->customer
                        ? CustomerResource::getUrl('view', [$record->customer])
                        : null
                    )
                    ->toggleable(),

                Tables\Columns\TextColumn::make('duration_sec')
                    ->label('Dauer')
                    ->formatStateUsing(fn ($state) => $state ? gmdate('i:s', $state) : '—')
                    ->sortable()
                    ->alignEnd()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('sentiment')
                    ->label('Stimmung')
                    ->formatStateUsing(function ($state) {
                        return match ($state) {
                            'positive' => 'Positiv',
                            'negative' => 'Negativ',
                            'neutral' => 'Neutral',
                            default => '—'
                        };
                    })
                    ->badge()
                    ->color(function ($state) {
                        return match ($state) {
                            'positive' => 'success',
                            'negative' => 'danger',
                            'neutral' => 'gray',
                            default => 'secondary'
                        };
                    })
                    ->toggleable(),

                Tables\Columns\TextColumn::make('appointment_made')
                    ->label('Termin')
                    ->formatStateUsing(function ($state, $record) {
                        if ($record && $record->appointment_made) {
                            return 'Gebucht';
                        } elseif ($record && $record->appointment_requested) {
                            return 'Angefragt';
                        }
                        return 'Kein Termin';
                    })
                    ->badge()
                    ->color(function ($state, $record) {
                        if ($record && $record->appointment_made) {
                            return 'success';
                        } elseif ($record && $record->appointment_requested) {
                            return 'warning';
                        }
                        return 'gray';
                    })
                    ->toggleable(),

                Tables\Columns\TextColumn::make('cost')
                    ->label('Kosten')
                    ->money('EUR')
                    ->sortable()
                    ->alignEnd()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('callCharge.refund_status')
                    ->label('Erstattung')
                    ->default('')
                    ->formatStateUsing(function ($state) {
                        if ($state === 'full') {
                            return 'Voll erstattet';
                        } elseif ($state === 'partial') {
                            return 'Teilweise erstattet';
                        }
                        return '';
                    })
                    ->badge()
                    ->color(function ($state) {
                        if ($state === 'full') {
                            return 'success';
                        } elseif ($state === 'partial') {
                            return 'warning';
                        }
                        return null;
                    })
                    ->toggleable(),

                // Temporarily disable complex metadata column for performance
                // Tables\Columns\IconColumn::make('non_billable_status')
                //     ->label('Nicht abrechenbar')
                //     ->getStateUsing(fn ($record) => $record->metadata['non_billable'] ?? false)
                //     ->boolean()
                //     ->trueIcon('heroicon-o-x-circle')
                //     ->falseIcon('')
                //     ->trueColor('danger')
                //     ->tooltip(
                //         fn ($record) => ($record->metadata['non_billable'] ?? false)
                //             ? 'Grund: ' . ($record->metadata['non_billable_reason'] ?? 'Unbekannt')
                //             : null
                //     )
                //     ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('branch.name')
                    ->label('Filiale')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('company.name')
                    ->label('Firma')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
            ])
            ->filters([
                // Temporarily disable complex filters for performance
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Details')
                    ->button()
                    ->size('sm')
                    ->outlined()
                    ->icon('heroicon-m-eye')
                    ->extraAttributes([
                        'class' => 'fi-ta-view-action',
                    ]),

                Tables\Actions\Action::make('share')
                    ->label('Teilen')
                    ->icon('heroicon-o-share')
                    ->color('gray')
                    ->button()
                    ->size('sm')
                    ->outlined()
                    ->modalContent(fn ($record) => view('filament.modals.share-call', ['record' => $record]))
                    ->modalHeading('Anruf teilen')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Schließen')
                    ->visible(false), // Temporarily hide share button on mobile
            ])
            ->bulkActions([
                MarkAsNonBillableAction::make(),
                Tables\Actions\BulkAction::make('createRefund')
                    ->label('Gutschrift erstellen')
                    ->icon('heroicon-o-receipt-refund')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Gutschrift für ausgewählte Anrufe erstellen')
                    ->modalDescription('Diese Aktion erstellt eine Gutschrift für die ausgewählten Anrufe und fügt den Betrag dem Prepaid-Guthaben hinzu.')
                    ->form([
                        Forms\Components\Select::make('reason')
                            ->label('Erstattungsgrund')
                            ->options([
                                'technical_issue' => 'Technisches Problem',
                                'quality_issue' => 'Qualitätsproblem',
                                'wrong_number' => 'Falsche Nummer / Verwählt',
                                'customer_complaint' => 'Kundenbeschwerde',
                                'test_call' => 'Testanruf',
                                'other' => 'Sonstiges',
                            ])
                            ->required()
                            ->default('technical_issue'),
                        Forms\Components\TextInput::make('percentage')
                            ->label('Erstattungsprozentsatz')
                            ->numeric()
                            ->default(100)
                            ->minValue(1)
                            ->maxValue(100)
                            ->suffix('%')
                            ->required(),
                        Forms\Components\Textarea::make('notes')
                            ->label('Anmerkungen')
                            ->rows(3)
                            ->maxLength(500),
                    ])
                    ->action(function (array $data, \Illuminate\Database\Eloquent\Collection $records) {
                        $callIds = $records->pluck('id')->toArray();
                        $refundService = app(\App\Services\CallRefundService::class);

                        $reasonMap = [
                            'technical_issue' => 'Technisches Problem',
                            'quality_issue' => 'Qualitätsproblem',
                            'wrong_number' => 'Falsche Nummer',
                            'customer_complaint' => 'Kundenbeschwerde',
                            'test_call' => 'Testanruf',
                        ];
                        
                        if ($data['reason'] === 'other') {
                            $reason = 'Sonstiges: ' . ($data['notes'] ?? '');
                        } else {
                            $reason = $reasonMap[$data['reason']] ?? $data['reason'];
                        }

                        $results = $refundService->refundMultipleCalls(
                            $callIds,
                            $reason,
                            $data['percentage']
                        );

                        Notification::make()
                            ->title('Gutschriften erstellt')
                            ->body(sprintf(
                                '%d Anrufe erstattet. Gesamtbetrag: %.2f €',
                                $results['total_refunded'],
                                $results['total_amount']
                            ))
                            ->success()
                            ->send();

                        if (count($results['failed']) > 0) {
                            Notification::make()
                                ->title('Einige Erstattungen fehlgeschlagen')
                                ->body(sprintf(
                                    '%d Anrufe konnten nicht erstattet werden.',
                                    count($results['failed'])
                                ))
                                ->warning()
                                ->send();
                        }
                    })
                    ->deselectRecordsAfterCompletion(),
                Tables\Actions\DeleteBulkAction::make()
            ]);

        return static::configureTableForManyColumns($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCallsWorking::route('/'),
            'clean' => Pages\ListCallsClean::route('/clean'),
            'old' => Pages\ListCalls::route('/old'),
            'fixed' => Pages\ListCallsFixed::route('/fixed'),
            'create' => Pages\CreateCall::route('/create'),
            'edit' => Pages\EditCall::route('/{record}/edit'),
            'view' => Pages\ViewCall::route('/{record}'),
        ];
    }

    public static function getWidgets(): array
    {
        return [
            Widgets\CallAnalyticsWidget::class,
        ];
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // MODERN HEADER V2 - Mobile-optimized version
                Infolists\Components\ViewEntry::make('modern_header')
                    ->label(false)
                    ->view('filament.infolists.call-header-modern-v2-mobile')
                    ->columnSpanFull(),

                // MAIN CONTENT AREA - Responsive Grid Layout
                Infolists\Components\Grid::make([
                    'default' => 1,    // Mobile: Stack everything
                    'lg' => 12,        // Desktop: 12-column grid system
                ])
                    ->schema([
                        // LEFT COLUMN - Primary Information (2/3 width)
                        Infolists\Components\Group::make([
                            // Call Analysis & Insights
                            Infolists\Components\Section::make('Analyse und Einblicke')
                                ->description('KI-gestützte Gesprächsanalyse und Erkenntnisse')
                                ->icon('heroicon-o-light-bulb')
                                ->extraAttributes(['class' => 'h-full'])
                                ->schema([
                                    // AI Analysis Grid
                                    Infolists\Components\Grid::make(2)
                                        ->schema([
                                            // Sentiment Analysis
                                            Infolists\Components\TextEntry::make('sentiment_analysis')
                                                ->label('Stimmungsanalyse')
                                                ->getStateUsing(function ($record) {
                                                    $sentiment = $record->webhook_data['call_analysis']['user_sentiment'] ??
                                                               $record->mlPrediction?->sentiment_label ??
                                                               'neutral';

                                                    $sentimentMap = [
                                                        'positive' => ['😊 Positiv', 'success'],
                                                        'negative' => ['😞 Negativ', 'danger'],
                                                        'mixed' => ['🤔 Gemischt', 'warning'],
                                                        'neutral' => ['😐 Neutral', 'gray'],
                                                    ];

                                                    $config = $sentimentMap[strtolower($sentiment)] ?? $sentimentMap['neutral'];

                                                    return $config[0];
                                                })
                                                ->badge()
                                                ->color(function ($record) {
                                                    $sentiment = $record->webhook_data['call_analysis']['user_sentiment'] ?? 'neutral';
                                                    $colorMap = [
                                                        'positive' => 'success',
                                                        'negative' => 'danger',
                                                        'mixed' => 'warning',
                                                        'neutral' => 'gray',
                                                    ];

                                                    return $colorMap[strtolower($sentiment)] ?? 'gray';
                                                }),

                                            // Detected Language
                                            Infolists\Components\TextEntry::make('language_analysis')
                                                ->label('Erkannte Sprache')
                                                ->getStateUsing(function ($record) {
                                                    $lang = $record->webhook_data['call_analysis']['language'] ??
                                                            $record->detected_language ??
                                                            'de';

                                                    $languages = [
                                                        'de' => '🇩🇪 Deutsch',
                                                        'en' => '🇬🇧 Englisch',
                                                        'fr' => '🇫🇷 Französisch',
                                                        'es' => '🇪🇸 Spanisch',
                                                        'it' => '🇮🇹 Italienisch',
                                                        'tr' => '🇹🇷 Türkisch',
                                                        'ar' => '🇸🇦 Arabisch',
                                                    ];

                                                    return $languages[$lang] ?? "🌐 $lang";
                                                })
                                                ->badge()
                                                ->color('info'),
                                        ]),

                                    // Key Insights
                                    Infolists\Components\TextEntry::make('key_insights')
                                        ->label('Wichtige Erkenntnisse')
                                        ->getStateUsing(function ($record) {
                                            $insights = [];

                                            // Check for appointment request
                                            if ($record->appointment_requested ||
                                                (! empty($record->webhook_data['dynamic_variables']['appointment_requested']) &&
                                                 $record->webhook_data['dynamic_variables']['appointment_requested'] === 'true')) {
                                                $insights[] = '📅 Kunde möchte einen Termin vereinbaren';
                                            }

                                            // Check call duration
                                            if ($record->duration_sec > 300) {
                                                $insights[] = '⏱️ Längeres Gespräch (' . gmdate('i:s', $record->duration_sec) . ') - Kunde hatte ausführliche Fragen';
                                            } elseif ($record->duration_sec < 60) {
                                                $insights[] = '⚡ Kurzes Gespräch (' . $record->duration_sec . 's) - Schnelle Abwicklung';
                                            }

                                            // Check if first-time caller
                                            if ($record->first_visit) {
                                                $insights[] = '🆕 Erstanrufer - Potentieller Neukunde';
                                            }

                                            // Check call success
                                            if (! empty($record->webhook_data['call_analysis']['call_successful'])) {
                                                if ($record->webhook_data['call_analysis']['call_successful']) {
                                                    $insights[] = '✅ Erfolgreiches Gespräch';
                                                } else {
                                                    $insights[] = '⚠️ Gespräch möglicherweise nicht erfolgreich abgeschlossen';
                                                }
                                            }

                                            // If no insights, provide helpful message
                                            if (empty($insights)) {
                                                return '<div class="text-gray-500 italic">Führen Sie eine detaillierte Analyse durch, um Erkenntnisse zu gewinnen.</div>';
                                            }

                                            return '<ul class="space-y-2">' .
                                                   implode('', array_map(fn ($insight) => "<li>$insight</li>", $insights)) .
                                                   '</ul>';
                                        })
                                        ->html(),

                                    // Action Recommendations
                                    Infolists\Components\TextEntry::make('recommendations')
                                        ->label('Empfohlene Maßnahmen')
                                        ->getStateUsing(function ($record) {
                                            $actions = [];

                                            // Based on sentiment
                                            $sentiment = strtolower($record->webhook_data['call_analysis']['user_sentiment'] ?? 'neutral');
                                            if ($sentiment === 'negative') {
                                                $actions[] = '🔴 Priorität: Kunde war unzufrieden - zeitnahe Rückmeldung empfohlen';
                                            }

                                            // If appointment requested
                                            if ($record->appointment_requested) {
                                                if (! $record->appointment_id) {
                                                    $actions[] = '📅 Termin vereinbaren - Kunde wartet auf Rückmeldung';
                                                }
                                            }

                                            // For new customers
                                            if ($record->first_visit && ! $record->customer_id) {
                                                $actions[] = '👤 Kundenprofil anlegen für bessere Betreuung';
                                            }

                                            // For short calls
                                            if ($record->duration_sec < 30) {
                                                $actions[] = '📞 Rückruf erwägen - Gespräch war sehr kurz';
                                            }

                                            if (empty($actions)) {
                                                return '<div class="text-green-600 dark:text-green-400">✅ Keine dringenden Maßnahmen erforderlich</div>';
                                            }

                                            return '<ul class="space-y-2">' .
                                                   implode('', array_map(fn ($action) => "<li>$action</li>", $actions)) .
                                                   '</ul>';
                                        })
                                        ->html(),

                                    // Customer Intent
                                    Infolists\Components\Grid::make(2)
                                        ->schema([
                                            Infolists\Components\TextEntry::make('appointment_intent')
                                                ->label('Terminwunsch')
                                                ->getStateUsing(fn ($record) => $record->appointment_requested ? 'Ja' : 'Nein')
                                                ->badge()
                                                ->color(fn ($record) => $record->appointment_requested ? 'success' : 'gray'),

                                            Infolists\Components\TextEntry::make('urgency_level')
                                                ->label('Dringlichkeit')
                                                ->getStateUsing(
                                                    fn ($record) => $record->urgency_level ??
                                                    $record->analysis['urgency'] ??
                                                    'Normal'
                                                )
                                                ->badge()
                                                ->color(fn ($state) => match (strtolower($state)) {
                                                    'hoch', 'high' => 'danger',
                                                    'mittel', 'medium' => 'warning',
                                                    'niedrig', 'low' => 'success',
                                                    default => 'gray'
                                                }),
                                        ])
                                        ->visible(
                                            fn ($record) => $record->appointment_requested ||
                                            ! empty($record->analysis['urgency']) ||
                                            ! empty($record->urgency_level)
                                        ),

                                    // Additional Call Information
                                    Infolists\Components\Grid::make(2)
                                        ->schema([
                                            Infolists\Components\TextEntry::make('analysis.detected_language')
                                                ->label('Sprache')
                                                ->getStateUsing(fn ($record) => match ($record->analysis['detected_language'] ?? 'de') {
                                                    'de' => '🇩🇪 Deutsch',
                                                    'en' => '🇬🇧 English',
                                                    'fr' => '🇫🇷 Français',
                                                    'es' => '🇪🇸 Español',
                                                    'it' => '🇮🇹 Italiano',
                                                    'tr' => '🇹🇷 Türkçe',
                                                    default => $record->analysis['detected_language'] ?? 'Unbekannt'
                                                })
                                                ->visible(fn ($record) => ! empty($record->analysis['detected_language'])),

                                            Infolists\Components\TextEntry::make('analysis.call_successful')
                                                ->label('Anruf erfolgreich')
                                                ->getStateUsing(
                                                    fn ($record) => ($record->analysis['call_successful'] ?? false) ? 'Ja' : 'Nein'
                                                )
                                                ->badge()
                                                ->color(fn ($state) => $state === 'Ja' ? 'success' : 'danger')
                                                ->visible(fn ($record) => isset($record->analysis['call_successful'])),
                                        ]),
                                ])
                                ->collapsible()
                                ->collapsed(false),

                            // Customer Data Section - Structured data collected during the call
                            Infolists\Components\Section::make('Erfasste Kundendaten')
                                ->description('Strukturierte Informationen aus dem Gespräch')
                                ->icon('heroicon-o-user-circle')
                                ->schema([
                                    // Show message if no customer data
                                    Infolists\Components\TextEntry::make('no_customer_data')
                                        ->label(false)
                                        ->getStateUsing(function ($record) {
                                            // Check if data was mentioned in transcript but not saved
                                            if (! empty($record->transcript) && str_contains($record->transcript, 'technisches Problem beim Speichern')) {
                                                return 'Kundendaten wurden im Gespräch erfasst, konnten aber aufgrund eines technischen Fehlers nicht gespeichert werden. Bitte Transkript prüfen.';
                                            }

                                            return 'Keine strukturierten Kundendaten erfasst';
                                        })
                                        ->visible(
                                            fn ($record) => empty($record->metadata) ||
                                            ! isset($record->metadata['customer_data'])
                                        )
                                        ->extraAttributes(['class' => 'text-gray-500 italic']),

                                    // Show customer data grid if available
                                    Infolists\Components\Grid::make(2)
                                        ->visible(
                                            fn ($record) => ! empty($record->metadata) &&
                                            isset($record->metadata['customer_data'])
                                        )
                                        ->schema([
                                            // Contact Information
                                            Infolists\Components\Group::make([
                                                Infolists\Components\TextEntry::make('heading_contact')
                                                    ->label(false)
                                                    ->getStateUsing(fn () => new HtmlString('<h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Kontaktinformationen</h4>'))
                                                    ->html()
                                                    ->columnSpanFull(),
                                                Infolists\Components\TextEntry::make('customer_name')
                                                    ->label('Name')
                                                    ->getStateUsing(
                                                        fn ($record) => $record->metadata['customer_data']['full_name'] ??
                                                        $record->extracted_name ??
                                                        $record->customer?->name ??
                                                        '-'
                                                    )
                                                    ->icon('heroicon-m-user'),

                                                Infolists\Components\TextEntry::make('customer_company')
                                                    ->label('Firma')
                                                    ->getStateUsing(
                                                        fn ($record) => $record->metadata['customer_data']['company'] ?? '-'
                                                    )
                                                    ->icon('heroicon-m-building-office'),

                                                Infolists\Components\TextEntry::make('customer_email')
                                                    ->label('E-Mail')
                                                    ->getStateUsing(
                                                        fn ($record) => $record->metadata['customer_data']['email'] ?? '-'
                                                    )
                                                    ->icon('heroicon-m-envelope')
                                                    ->copyable(fn ($state) => $state !== '-'),

                                                Infolists\Components\TextEntry::make('customer_phone')
                                                    ->label('Telefon (Primär)')
                                                    ->getStateUsing(
                                                        fn ($record) => $record->metadata['customer_data']['phone_primary'] ?? '-'
                                                    )
                                                    ->icon('heroicon-m-phone')
                                                    ->copyable(fn ($state) => $state !== '-'),

                                                Infolists\Components\TextEntry::make('customer_phone_secondary')
                                                    ->label('Telefon (Sekundär)')
                                                    ->getStateUsing(
                                                        fn ($record) => $record->metadata['customer_data']['phone_secondary'] ?? '-'
                                                    )
                                                    ->icon('heroicon-m-phone')
                                                    ->copyable(fn ($state) => $state !== '-'),

                                                Infolists\Components\TextEntry::make('customer_number')
                                                    ->label('Kundennummer')
                                                    ->getStateUsing(
                                                        fn ($record) => $record->metadata['customer_data']['customer_number'] ?? '-'
                                                    )
                                                    ->icon('heroicon-m-identification'),
                                            ]),

                                            // Request and Consent
                                            Infolists\Components\Group::make([
                                                Infolists\Components\TextEntry::make('heading_request')
                                                    ->label(false)
                                                    ->getStateUsing(fn () => new HtmlString('<h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Anfrage & Einwilligung</h4>'))
                                                    ->html()
                                                    ->columnSpanFull(),
                                                Infolists\Components\TextEntry::make('customer_request')
                                                    ->label('Anliegen')
                                                    ->getStateUsing(
                                                        fn ($record) => $record->metadata['customer_data']['request'] ?? '-'
                                                    )
                                                    ->icon('heroicon-m-chat-bubble-left-right')
                                                    ->columnSpanFull(),

                                                Infolists\Components\TextEntry::make('customer_notes')
                                                    ->label('Notizen')
                                                    ->getStateUsing(
                                                        fn ($record) => $record->metadata['customer_data']['notes'] ?? '-'
                                                    )
                                                    ->icon('heroicon-m-document-text')
                                                    ->columnSpanFull(),

                                                Infolists\Components\TextEntry::make('customer_consent')
                                                    ->label('Datenspeicherung erlaubt')
                                                    ->getStateUsing(
                                                        fn ($record) => ($record->metadata['customer_data']['consent'] ?? false) ? 'Ja' : 'Nein'
                                                    )
                                                    ->badge()
                                                    ->color(fn ($state) => $state === 'Ja' ? 'success' : 'danger')
                                                    ->icon(
                                                        fn ($state) => $state === 'Ja' ? 'heroicon-m-check-circle' : 'heroicon-m-x-circle'
                                                    ),

                                                Infolists\Components\TextEntry::make('data_collected_at')
                                                    ->label('Erfasst am')
                                                    ->getStateUsing(
                                                        fn ($record) => isset($record->metadata['customer_data']['collected_at'])
                                                            ? Carbon::parse($record->metadata['customer_data']['collected_at'])
                                                                ->timezone('Europe/Berlin')
                                                                ->format('d.m.Y H:i:s')
                                                            : '-'
                                                    )
                                                    ->icon('heroicon-m-clock'),
                                            ]),
                                        ]),
                                ])
                                ->collapsible()
                                ->collapsed(false),

                            // Customer Journey & Interaction Widget
                            Infolists\Components\ViewEntry::make('customer_journey')
                                ->label(false)
                                ->view('filament.infolists.customer-journey-widget')
                                ->columnSpanFull()
                                ->extraAttributes(['class' => 'overflow-visible']),

                            // Audio & Transcript Section
                            Infolists\Components\Section::make('Gesprächsaufzeichnung')
                                ->description('Audio-Aufnahme und Transkript')
                                ->icon('heroicon-o-microphone')
                                ->extraAttributes(['class' => 'h-full mt-6'])
                                ->schema([
                                    // Audio Player
                                    Infolists\Components\ViewEntry::make('audio')
                                        ->label(false)
                                        ->view('filament.components.audio-player-enterprise-improved')
                                        ->visible(
                                            fn ($record) => ! empty($record->audio_url) ||
                                            ! empty($record->recording_url)
                                        ),

                                    // Transcript with Toggle
                                    Infolists\Components\ViewEntry::make('transcript')
                                        ->label(false)
                                        ->view('filament.infolists.toggleable-transcript')
                                        ->visible(
                                            fn ($record) => ! empty($record->transcript) ||
                                            ! empty($record->transcript_object)
                                        ),
                                ])
                                ->collapsible()
                                ->collapsed(false),
                        ])
                            ->columnSpan(['default' => 1, 'lg' => 8]),

                        // RIGHT COLUMN - Secondary Information (1/3 width)
                        Infolists\Components\Group::make([
                            // Customer Information
                            Infolists\Components\Section::make('Kundeninformationen')
                                ->icon('heroicon-o-user')
                                ->extraAttributes(['class' => 'h-full'])
                                ->schema([
                                    // Customer Details Grid
                                    Infolists\Components\Grid::make(1)
                                        ->schema([
                                            // Name & Status
                                            Infolists\Components\TextEntry::make('customer_name')
                                                ->label('Name')
                                                ->getStateUsing(
                                                    fn ($record) => $record->customer?->name ??
                                                    $record->extracted_name ??
                                                    'Nicht erfasst'
                                                )
                                                ->weight('bold'),

                                            // Company
                                            Infolists\Components\TextEntry::make('customer_company')
                                                ->label('Firma')
                                                ->getStateUsing(
                                                    fn ($record) => $record->customer?->company_name ??
                                                    $record->metadata['customer_data']['company_name'] ??
                                                    '-'
                                                )
                                                ->visible(
                                                    fn ($record) => ! empty($record->customer?->company_name) ||
                                                    ! empty($record->metadata['customer_data']['company_name'])
                                                ),

                                            // Phone Number
                                            Infolists\Components\TextEntry::make('phone_number')
                                                ->label('Telefon')
                                                ->copyable()
                                                ->copyMessage('Nummer kopiert')
                                                ->copyMessageDuration(2000),

                                            // Email
                                            Infolists\Components\TextEntry::make('customer.email')
                                                ->label('E-Mail')
                                                ->getStateUsing(
                                                    fn ($record) => $record->customer?->email ??
                                                    $record->extracted_email ??
                                                    '-'
                                                )
                                                ->copyable()
                                                ->visible(
                                                    fn ($record) => ! empty($record->customer?->email) ||
                                                    ! empty($record->extracted_email)
                                                ),

                                            // Customer Status Badge
                                            Infolists\Components\TextEntry::make('customer_status')
                                                ->label('Status')
                                                ->getStateUsing(
                                                    fn ($record) => $record->first_visit ? 'Neukunde' : 'Bestandskunde'
                                                )
                                                ->badge()
                                                ->color(
                                                    fn ($state) => $state === 'Neukunde' ? 'info' : 'success'
                                                ),

                                            // Call History
                                            Infolists\Components\TextEntry::make('call_history')
                                                ->label('Anrufhistorie')
                                                ->getStateUsing(function ($record) {
                                                    if (! $record->customer) {
                                                        return 'Erster Anruf';
                                                    }

                                                    $callCount = $record->customer->calls()->count();
                                                    $lastCall = $record->customer->calls()
                                                        ->where('id', '!=', $record->id)
                                                        ->latest()
                                                        ->first();

                                                    $text = "$callCount " . ($callCount === 1 ? 'Anruf' : 'Anrufe') . ' insgesamt';

                                                    if ($lastCall) {
                                                        $text .= ' • Letzter: ' . $lastCall->created_at->diffForHumans();
                                                    }

                                                    return $text;
                                                })
                                                ->visible(fn ($record) => $record->customer_id),

                                            // Tags
                                            Infolists\Components\TextEntry::make('customer.tags')
                                                ->label('Tags')
                                                ->badge()
                                                ->separator(',')
                                                ->visible(
                                                    fn ($record) => $record->customer &&
                                                    ! empty($record->customer->tags)
                                                ),

                                            // Address
                                            Infolists\Components\TextEntry::make('customer.address')
                                                ->label('Adresse')
                                                ->visible(fn ($record) => $record->customer?->address)
                                                ->icon('heroicon-m-map-pin'),

                                            // Birthday
                                            Infolists\Components\TextEntry::make('customer.birthdate')
                                                ->label('Geburtstag')
                                                ->date('d.m.Y')
                                                ->visible(fn ($record) => $record->customer?->birthdate)
                                                ->icon('heroicon-m-cake'),

                                            // Created Date
                                            Infolists\Components\TextEntry::make('customer.created_at')
                                                ->label('Kunde seit')
                                                ->dateTime('d.m.Y')
                                                ->visible(fn ($record) => $record->customer_id)
                                                ->icon('heroicon-m-calendar'),

                                            // Total Appointments
                                            Infolists\Components\TextEntry::make('appointment_count')
                                                ->label('Termine insgesamt')
                                                ->getStateUsing(
                                                    fn ($record) => $record->customer?->appointments()->count() ?? 0
                                                )
                                                ->visible(fn ($record) => $record->customer_id)
                                                ->icon('heroicon-m-calendar-days'),

                                            // No-Show Count
                                            Infolists\Components\TextEntry::make('no_show_info')
                                                ->label('No-Shows')
                                                ->getStateUsing(
                                                    fn ($record) => $record->customer?->appointments()
                                                        ->where('status', 'no_show')
                                                        ->count() ?? 0
                                                )
                                                ->color(fn ($state) => $state > 2 ? 'danger' : 'gray')
                                                ->visible(fn ($record) => $record->customer_id)
                                                ->icon('heroicon-m-x-circle'),

                                            // Last Visit
                                            Infolists\Components\TextEntry::make('last_visit')
                                                ->label('Letzter Besuch')
                                                ->getStateUsing(function ($record) {
                                                    if (! $record->customer) {
                                                        return '-';
                                                    }

                                                    $lastAppointment = $record->customer->appointments()
                                                        ->where('status', 'completed')
                                                        ->orderBy('starts_at', 'desc')
                                                        ->first();

                                                    return $lastAppointment ?
                                                        $lastAppointment->starts_at->format('d.m.Y') :
                                                        'Noch kein Besuch';
                                                })
                                                ->visible(fn ($record) => $record->customer_id)
                                                ->icon('heroicon-m-clock'),

                                            // Notes
                                            Infolists\Components\TextEntry::make('customer.notes')
                                                ->label('Notizen')
                                                ->visible(fn ($record) => $record->customer?->notes)
                                                ->html()
                                                ->columnSpanFull(),
                                        ]),

                                    // Customer Actions
                                    Infolists\Components\Actions::make([
                                        // View Customer Action (only for existing customers)
                                        Infolists\Components\Actions\Action::make('view_customer')
                                            ->label('Kundenprofil')
                                            ->icon('heroicon-m-arrow-top-right-on-square')
                                            ->color('gray')
                                            ->visible(fn ($record) => $record->customer_id)
                                            ->url(
                                                fn ($record) => $record->customer_id ?
                                                CustomerResource::getUrl('view', [$record->customer]) :
                                                null
                                            )
                                            ->openUrlInNewTab(),

                                        // Create Customer Action (only for non-existing customers)
                                        Infolists\Components\Actions\Action::make('create_customer')
                                            ->label('Kunde anlegen')
                                            ->icon('heroicon-m-user-plus')
                                            ->color('primary')
                                            ->visible(fn ($record) => ! $record->customer_id)
                                            ->url(fn ($record) => CustomerResource::getUrl('create', [
                                                'data' => [
                                                    'name' => $record->extracted_name ?? '',
                                                    'email' => $record->extracted_email ?? '',
                                                    'phone' => $record->from_number,
                                                ],
                                            ])),

                                        // Add Note Action
                                        Infolists\Components\Actions\Action::make('add_note')
                                            ->label('Notiz hinzufügen')
                                            ->icon('heroicon-m-pencil')
                                            ->color('gray')
                                            ->form([
                                                Forms\Components\Textarea::make('note')
                                                    ->label('Notiz')
                                                    ->required()
                                                    ->rows(3),
                                            ])
                                            ->action(function ($record, array $data) {
                                                // Add note to customer or call
                                                if ($record->customer) {
                                                    $record->customer->notes .= "\n\n" . now()->format('d.m.Y H:i') . ":\n" . $data['note'];
                                                    $record->customer->save();
                                                } else {
                                                    // Add to call metadata
                                                    $metadata = $record->metadata ?? [];
                                                    $metadata['notes'] = $metadata['notes'] ?? [];
                                                    $metadata['notes'][] = [
                                                        'content' => $data['note'],
                                                        'created_at' => now()->toIso8601String(),
                                                        'user_id' => auth()->id(),
                                                    ];
                                                    $record->update(['metadata' => $metadata]);
                                                }

                                                Notification::make()
                                                    ->success()
                                                    ->title('Notiz hinzugefügt')
                                                    ->send();
                                            }),
                                    ])
                                        ->fullWidth()
                                        ->extraAttributes(['class' => 'mt-4']),
                                ])
                                ->compact(),

                            // Appointment Information
                            Infolists\Components\Section::make('Termininformationen')
                                ->icon('heroicon-o-calendar-days')
                                ->extraAttributes(['class' => 'h-full mt-6'])
                                ->schema([
                                    Infolists\Components\TextEntry::make('appointment_status')
                                        ->hiddenLabel()
                                        ->getStateUsing(function ($record) {
                                            if ($record->appointment) {
                                                $date = $record->appointment->starts_at->format('d.m.Y');
                                                $time = $record->appointment->starts_at->format('H:i');
                                                $service = $record->appointment->service?->name ?? 'Allgemein';

                                                return new HtmlString("
                                                <div class='space-y-2'>
                                                    <div class='flex items-center gap-2'>
                                                        <svg class='w-5 h-5 text-green-500' fill='currentColor' viewBox='0 0 20 20'>
                                                            <path fill-rule='evenodd' d='M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z' clip-rule='evenodd'></path>
                                                        </svg>
                                                        <span class='font-medium text-green-700 dark:text-green-400'>Termin gebucht</span>
                                                    </div>
                                                    <div class='ml-7 space-y-1'>
                                                        <div class='text-sm'><span class='text-gray-500'>Datum:</span> $date</div>
                                                        <div class='text-sm'><span class='text-gray-500'>Uhrzeit:</span> $time</div>
                                                        <div class='text-sm'><span class='text-gray-500'>Service:</span> $service</div>
                                                    </div>
                                                </div>
                                            ");
                                            } elseif ($record->appointment_requested) {
                                                return new HtmlString("
                                                <div class='flex items-center gap-2'>
                                                    <svg class='w-5 h-5 text-yellow-500' fill='currentColor' viewBox='0 0 20 20'>
                                                        <path fill-rule='evenodd' d='M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z' clip-rule='evenodd'></path>
                                                    </svg>
                                                    <span class='font-medium text-yellow-700 dark:text-yellow-400'>Termin angefragt</span>
                                                </div>
                                            ");
                                            } else {
                                                return new HtmlString("
                                                <div class='text-gray-500 dark:text-gray-400'>
                                                    Kein Termin
                                                </div>
                                            ");
                                            }
                                        }),

                                    // Appointment Actions
                                    Infolists\Components\Actions::make([
                                        Infolists\Components\Actions\Action::make('view_appointment')
                                            ->label('Termin anzeigen')
                                            ->icon('heroicon-m-arrow-top-right-on-square')
                                            ->url(
                                                fn ($record) => $record->appointment ?
                                                AppointmentResource::getUrl('view', [$record->appointment]) :
                                                null
                                            )
                                            ->visible(fn ($record) => $record->appointment),

                                        Infolists\Components\Actions\Action::make('create_appointment')
                                            ->label('Termin buchen')
                                            ->icon('heroicon-m-calendar-days')
                                            ->color('success')
                                            ->url(fn ($record) => AppointmentResource::getUrl('create', [
                                                'data' => [
                                                    'customer_id' => $record->customer_id,
                                                    'branch_id' => $record->branch_id,
                                                    'reason' => $record->reason_for_visit,
                                                ],
                                            ]))
                                            ->visible(
                                                fn ($record) => ! $record->appointment &&
                                                $record->appointment_requested
                                            ),
                                    ])
                                        ->fullWidth()
                                        ->extraAttributes(['class' => 'mt-4']),
                                ])
                                ->compact()
                                ->visible(
                                    fn ($record) => $record->appointment ||
                                    $record->appointment_requested
                                ),

                            // Analytics & Insights
                            Infolists\Components\Section::make('Analyse & Einblicke')
                                ->icon('heroicon-o-chart-bar')
                                ->extraAttributes(['class' => 'h-full mt-6'])
                                ->schema([
                                    // Retell AI Analysis
                                    Infolists\Components\Grid::make(1)
                                        ->schema([
                                            // Call Summary from Retell with Toggle
                                            Infolists\Components\ViewEntry::make('call_summary')
                                                ->label('Anrufzusammenfassung (AI)')
                                                ->view('filament.infolists.toggleable-summary')
                                                ->columnSpanFull()
                                                ->visible(
                                                    fn ($record) => isset($record->webhook_data['call_analysis']['call_summary'])
                                                ),

                                            // User Sentiment from Retell
                                            Infolists\Components\TextEntry::make('retell_sentiment')
                                                ->label('Kundenstimmung')
                                                ->getStateUsing(function ($record) {
                                                    $sentiment = $record->webhook_data['call_analysis']['user_sentiment'] ?? null;
                                                    if (! $sentiment) {
                                                        // Fallback to ML prediction
                                                        if ($record->mlPrediction) {
                                                            $sentiment = ucfirst($record->mlPrediction->sentiment_label);
                                                            $confidence = round($record->mlPrediction->prediction_confidence * 100);
                                                            $score = number_format($record->mlPrediction->sentiment_score, 2);

                                                            return "$sentiment (Score: $score, Konfidenz: $confidence%)";
                                                        }

                                                        return '—';
                                                    }

                                                    // Format Retell sentiment
                                                    $sentimentMap = [
                                                        'Positive' => '😊 Positiv',
                                                        'Negative' => '😞 Negativ',
                                                        'Neutral' => '😐 Neutral',
                                                        'Mixed' => '🤔 Gemischt',
                                                    ];

                                                    return $sentimentMap[$sentiment] ?? ucfirst($sentiment);
                                                })
                                                ->badge()
                                                ->color(fn ($state) => match (true) {
                                                    str_contains($state, 'Positiv') => 'success',
                                                    str_contains($state, 'Negativ') => 'danger',
                                                    str_contains($state, 'Neutral') => 'gray',
                                                    str_contains($state, 'Gemischt') => 'warning',
                                                    default => 'gray'
                                                }),

                                            // Call Success Status
                                            Infolists\Components\TextEntry::make('call_successful')
                                                ->label('Anruf erfolgreich')
                                                ->getStateUsing(function ($record) {
                                                    $successful = $record->webhook_data['call_analysis']['call_successful'] ?? null;
                                                    if ($successful === null) {
                                                        return '—';
                                                    }

                                                    return $successful ? '✅ Ja' : '❌ Nein';
                                                })
                                                ->badge()
                                                ->color(fn ($state) => match ($state) {
                                                    '✅ Ja' => 'success',
                                                    '❌ Nein' => 'danger',
                                                    default => 'gray'
                                                }),

                                            // In-Call Analysis Details
                                            Infolists\Components\TextEntry::make('in_call_analysis')
                                                ->label('Detailanalyse')
                                                ->getStateUsing(function ($record) {
                                                    $analysis = $record->webhook_data['call_analysis']['in_call_analysis'] ?? [];
                                                    if (empty($analysis)) {
                                                        return '—';
                                                    }

                                                    // Format as list
                                                    $items = [];
                                                    foreach ($analysis as $key => $value) {
                                                        if (is_array($value)) {
                                                            $items[] = ucfirst(str_replace('_', ' ', $key)) . ': ' . json_encode($value);
                                                        } else {
                                                            $items[] = ucfirst(str_replace('_', ' ', $key)) . ': ' . $value;
                                                        }
                                                    }

                                                    return implode("\n", $items);
                                                })
                                                ->visible(
                                                    fn ($record) => ! empty($record->webhook_data['call_analysis']['in_call_analysis'])
                                                ),

                                            // Intent Detection
                                            Infolists\Components\TextEntry::make('ml_intent')
                                                ->label('Erkannte Absicht')
                                                ->getStateUsing(
                                                    fn ($record) => ucfirst($record->mlPrediction?->intent ??
                                                    $record->analysis['intent'] ??
                                                    '—')
                                                ),

                                            // Key Features from ML
                                            Infolists\Components\ViewEntry::make('ml_features')
                                                ->label('ML Schlüsselfaktoren')
                                                ->view('filament.infolists.ml-features-list')
                                                ->visible(
                                                    fn ($record) => $record->mlPrediction?->top_features
                                                ),
                                        ]),

                                    // Latency Metrics
                                    Infolists\Components\Grid::make(3)
                                        ->schema([
                                            Infolists\Components\TextEntry::make('latency_e2e')
                                                ->label('Gesamt-Latenz')
                                                ->getStateUsing(function ($record) {
                                                    $latencyData = $record->webhook_data['latency']['e2e'] ?? null;
                                                    if (is_array($latencyData)) {
                                                        // Check if it's a statistics array with p50, p90, etc.
                                                        if (isset($latencyData['p50'])) {
                                                            // Use median (p50) as the representative value
                                                            $latency = $latencyData['p50'];
                                                        } elseif (isset($latencyData['values']) && is_array($latencyData['values']) && ! empty($latencyData['values'])) {
                                                            // Use average of values array
                                                            $latency = array_sum($latencyData['values']) / count($latencyData['values']);
                                                        } else {
                                                            $latency = null;
                                                        }
                                                    } else {
                                                        $latency = $latencyData;
                                                    }

                                                    return $latency !== null && is_numeric($latency) ? round($latency) . ' ms' : '—';
                                                })
                                                ->icon('heroicon-m-clock'),

                                            Infolists\Components\TextEntry::make('latency_llm')
                                                ->label('AI-Antwortzeit')
                                                ->getStateUsing(function ($record) {
                                                    $latencyData = $record->webhook_data['latency']['llm'] ?? null;
                                                    if (is_array($latencyData)) {
                                                        if (isset($latencyData['p50'])) {
                                                            $latency = $latencyData['p50'];
                                                        } elseif (isset($latencyData['values']) && is_array($latencyData['values']) && ! empty($latencyData['values'])) {
                                                            $latency = array_sum($latencyData['values']) / count($latencyData['values']);
                                                        } else {
                                                            $latency = null;
                                                        }
                                                    } else {
                                                        $latency = $latencyData;
                                                    }

                                                    return $latency !== null && is_numeric($latency) ? round($latency) . ' ms' : '—';
                                                })
                                                ->icon('heroicon-m-cpu-chip'),

                                            Infolists\Components\TextEntry::make('latency_tts')
                                                ->label('Sprachausgabe')
                                                ->getStateUsing(function ($record) {
                                                    $latencyData = $record->webhook_data['latency']['tts'] ?? null;
                                                    if (is_array($latencyData)) {
                                                        if (isset($latencyData['p50'])) {
                                                            $latency = $latencyData['p50'];
                                                        } elseif (isset($latencyData['values']) && is_array($latencyData['values']) && ! empty($latencyData['values'])) {
                                                            $latency = array_sum($latencyData['values']) / count($latencyData['values']);
                                                        } else {
                                                            $latency = null;
                                                        }
                                                    } else {
                                                        $latency = $latencyData;
                                                    }

                                                    return $latency !== null && is_numeric($latency) ? round($latency) . ' ms' : '—';
                                                })
                                                ->icon('heroicon-m-speaker-wave'),
                                        ])
                                        ->visible(
                                            fn ($record) => isset($record->webhook_data['latency'])
                                        ),
                                ])
                                ->compact()
                                ->visible(
                                    fn ($record) => $record->mlPrediction ||
                                    ! empty($record->webhook_data['call_analysis']) ||
                                    $record->cost > 0
                                ),
                        ])
                            ->columnSpan(['default' => 1, 'lg' => 4]),
                    ]),

                // TECHNICAL DETAILS - Collapsible at bottom
                Infolists\Components\Section::make('Technische Details')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->collapsed()
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('call_id')
                                    ->label('Call ID')
                                    ->copyable()
                                    ->fontFamily('mono'),

                                Infolists\Components\TextEntry::make('retell_call_id')
                                    ->label('Retell Call ID')
                                    ->copyable()
                                    ->fontFamily('mono'),

                                Infolists\Components\TextEntry::make('agent_id')
                                    ->label('Agent ID')
                                    ->placeholder('—')
                                    ->fontFamily('mono'),

                                Infolists\Components\TextEntry::make('branch.name')
                                    ->label('Filiale'),

                                Infolists\Components\TextEntry::make('company.name')
                                    ->label('Firma'),

                                Infolists\Components\TextEntry::make('created_at')
                                    ->label('Erstellt am')
                                    ->dateTime('d.m.Y H:i:s'),
                            ]),
                    ])
                    ->persistCollapsed(true)
                    ->extraAttributes(['class' => 'mt-6']),
            ]);
    }

    public static function getNavigationBadge(): ?string
    {
        $user = auth()->user();

        // No badge if not authenticated
        if (! $user) {
            return null;
        }

        try {
            // Only count calls from user's company
            if ($user->company_id) {
                return static::getModel()::where('company_id', $user->company_id)
                    ->whereDate('created_at', today())
                    ->count();
            }

            // Super admin sees all
            if ($user->hasRole('super_admin') || $user->hasRole('Super Admin')) {
                return static::getModel()::whereDate('created_at', today())->count();
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    public static function getNavigationBadgeColor(): ?string
    {
        // Use getNavigationBadge to determine count
        $count = static::getNavigationBadge();

        return $count && $count > 0 ? 'primary' : 'gray';
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['customer', 'appointment', 'company', 'mlPrediction', 'branch', 'callCharge']);

        $user = auth()->user();

        // This should never happen due to canViewAny(), but as safety check
        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        // Super admin sees all
        if ($user->hasRole('super_admin') || $user->hasRole('Super Admin')) {
            return $query;
        }

        // Regular users only see their company's calls
        if ($user->company_id) {
            return $query->where('company_id', $user->company_id);
        }

        // No company assigned - no results
        return $query->whereRaw('1 = 0');
    }
}
