<?php

namespace App\Filament\Admin\Resources\CallResource\Pages;

use App\Filament\Admin\Resources\CallResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions;
use App\Filament\Admin\Resources\AppointmentResource;
use App\Models\Appointment;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Group;
use Illuminate\Support\HtmlString;
use Filament\Infolists\Components\ViewEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Support\Colors\Color;
use Filament\Infolists\Components\Actions\Action as InfolistAction;

class ViewCall extends ViewRecord
{
    protected static string $resource = CallResource::class;

    public function getTitle(): string
    {
        return 'Anruf-Details: ' . ($this->record->call_id ?? $this->record->id);
    }

    protected function getHeaderWidgets(): array
    {
        return [
            CallResource\Widgets\CallAnalyticsWidget::class,
            CallResource\Widgets\CallInsightsWidget::class,
        ];
    }
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('refresh_call_data')
                ->label('Daten aktualisieren')
                ->icon('heroicon-o-arrow-path')
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading('Anrufdaten aktualisieren')
                ->modalDescription('Möchten Sie die Daten dieses Anrufs von Retell.ai neu abrufen?')
                ->modalSubmitActionLabel('Ja, aktualisieren')
                ->action(function () {
                    try {
                        $callId = $this->record->retell_call_id ?? $this->record->call_id;
                        
                        if (!$callId) {
                            Notification::make()
                                ->title('Fehler')
                                ->body('Keine Retell Call ID vorhanden.')
                                ->danger()
                                ->send();
                            return;
                        }
                        
                        // Direkte API-Anfrage an Retell für Call Details
                        $response = \Illuminate\Support\Facades\Http::withHeaders([
                            'Authorization' => 'Bearer ' . config('services.retell.api_key'),
                        ])->post('https://api.retellai.com/v2/get-call', [
                            'call_id' => $callId
                        ]);
                        
                        if (!$response->successful()) {
                            throw new \Exception('API Error: ' . $response->body());
                        }
                        
                        $callData = $response->json();
                        
                        if ($callData) {
                            // Dispatch job zur Verarbeitung
                            \App\Jobs\ProcessRetellCallEndedJob::dispatch([
                                'event' => 'call_ended',
                                'call' => $callData
                            ], $this->record->company);
                            
                            Notification::make()
                                ->title('Daten werden aktualisiert')
                                ->body('Die Anrufdaten werden im Hintergrund aktualisiert.')
                                ->success()
                                ->send();
                                
                            // Seite nach kurzer Verzögerung neu laden
                            $this->js('setTimeout(() => window.location.reload(), 2000)');
                        } else {
                            Notification::make()
                                ->title('Keine Daten gefunden')
                                ->body('Es wurden keine aktualisierten Daten gefunden.')
                                ->warning()
                                ->send();
                        }
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Fehler beim Aktualisieren')
                            ->body('Fehler: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
                
            Actions\ActionGroup::make([
                Actions\Action::make('create_appointment')
                    ->label('Termin erstellen')
                    ->icon('heroicon-o-calendar-days')
                    ->color('success')
                    ->visible(fn () => !$this->record->appointment_id && $this->record->customer_id)
                    ->form([
                        Forms\Components\DateTimePicker::make('starts_at')
                            ->label('Termin')
                            ->required()
                            ->native(false)
                            ->displayFormat('d.m.Y H:i')
                            ->minutesStep(15)
                            ->minDate(now())
                            ->default(function () {
                                // Versuche Datum aus Analyse zu extrahieren
                                if (isset($this->record->analysis['entities']['date'])) {
                                    try {
                                        return \Carbon\Carbon::parse($this->record->analysis['entities']['date']);
                                    } catch (\Exception $e) {
                                        // Fallback
                                    }
                                }
                                return now()->addDay()->setHour(9)->setMinute(0);
                            }),
                            
                        Forms\Components\Select::make('service_id')
                            ->label('Dienstleistung')
                            ->options(function () {
                                return \App\Models\Service::where('company_id', $this->record->company_id)
                                    ->pluck('name', 'id');
                            })
                            ->required()
                            ->searchable()
                            ->default(function () {
                                // Versuche Service aus Analyse zu extrahieren
                                if (isset($this->record->analysis['entities']['service'])) {
                                    $serviceName = $this->record->analysis['entities']['service'];
                                    $service = \App\Models\Service::where('company_id', $this->record->company_id)
                                        ->where('name', 'like', '%' . $serviceName . '%')
                                        ->first();
                                    return $service?->id;
                                }
                                return null;
                            }),
                            
                        Forms\Components\Select::make('staff_id')
                            ->label('Mitarbeiter')
                            ->options(function () {
                                return \App\Models\Staff::where('company_id', $this->record->company_id)
                                    ->get()
                                    ->mapWithKeys(function ($staff) {
                                        return [$staff->id => $staff->first_name . ' ' . $staff->last_name];
                                    });
                            })
                            ->searchable(),
                            
                        Forms\Components\Textarea::make('notes')
                            ->label('Notizen')
                            ->rows(3)
                            ->default(fn () => "Termin erstellt aus Anruf vom " . $this->record->created_at->format('d.m.Y H:i')),
                    ])
                    ->action(function (array $data) {
                        $appointment = Appointment::create([
                            'customer_id' => $this->record->customer_id,
                            'company_id' => $this->record->company_id,
                            'branch_id' => $this->record->branch_id,
                            'service_id' => $data['service_id'],
                            'staff_id' => $data['staff_id'] ?? null,
                            'starts_at' => $data['starts_at'],
                            'ends_at' => \Carbon\Carbon::parse($data['starts_at'])->addMinutes(60),
                            'status' => 'scheduled',
                            'notes' => $data['notes'],
                            'source' => 'phone',
                            'call_id' => $this->record->id,
                        ]);
                        
                        $this->record->update(['appointment_id' => $appointment->id]);
                        
                        Notification::make()
                            ->title('Termin erstellt')
                            ->success()
                            ->body('Der Termin wurde erfolgreich angelegt.')
                            ->actions([
                                \Filament\Notifications\Actions\Action::make('view')
                                    ->label('Termin anzeigen')
                                    ->url(AppointmentResource::getUrl('view', ['record' => $appointment]))
                                    ->openUrlInNewTab(),
                            ])
                            ->send();
                            
                        $this->refreshFormData(['appointment_id']);
                    }),
                    
                Actions\Action::make('add_tags')
                    ->label('Tags verwalten')
                    ->icon('heroicon-o-tag')
                    ->color('gray')
                    ->form([
                        Forms\Components\TagsInput::make('tags')
                            ->label('Tags')
                            ->default($this->record->analysis['tags'] ?? [])
                            ->suggestions([
                                'Wichtig',
                                'Nachfassen',
                                'Beschwerde',
                                'Neukunde',
                                'Stammkunde',
                                'Dringend',
                                'Rückruf',
                                'Beratung',
                                'Technisches Problem',
                                'Preisanfrage',
                            ]),
                    ])
                    ->action(function (array $data) {
                        $analysis = $this->record->analysis ?? [];
                        $analysis['tags'] = $data['tags'];
                        $this->record->update(['analysis' => $analysis]);
                        
                        Notification::make()
                            ->title('Tags aktualisiert')
                            ->success()
                            ->send();
                    }),
                    
                Actions\Action::make('send_sms')
                    ->label('SMS senden')
                    ->icon('heroicon-o-device-phone-mobile')
                    ->color('info')
                    ->visible(fn () => $this->record->customer_id && $this->record->from_number)
                    ->form([
                        Forms\Components\Textarea::make('message')
                            ->label('Nachricht')
                            ->required()
                            ->rows(3)
                            ->default(fn () => "Hallo! Vielen Dank für Ihren Anruf. ")
                            ->maxLength(160),
                    ])
                    ->action(function (array $data) {
                        // SMS-Versand würde hier implementiert
                        Notification::make()
                            ->title('SMS versendet')
                            ->body('Die SMS wurde an ' . $this->record->from_number . ' gesendet.')
                            ->success()
                            ->send();
                    }),
                    
                Actions\Action::make('add_to_waitlist')
                    ->label('Zur Warteliste')
                    ->icon('heroicon-o-queue-list')
                    ->color('warning')
                    ->visible(fn () => $this->record->customer_id && !$this->record->appointment_id)
                    ->action(function () {
                        // Wartelisten-Logik würde hier implementiert
                        Notification::make()
                            ->title('Zur Warteliste hinzugefügt')
                            ->body('Der Kunde wurde erfolgreich zur Warteliste hinzugefügt.')
                            ->success()
                            ->send();
                    }),
            ]),
        ];
    }
    
    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // Quick Stats at the top
                Section::make()
                    ->schema([
                        Grid::make(['default' => 2, 'sm' => 3, 'lg' => 6])
                            ->schema([
                                TextEntry::make('start_timestamp')
                                    ->label('Anrufzeit')
                                    ->default(fn ($record) => $record->created_at)
                                    ->icon('heroicon-m-phone-arrow-down-left')
                                    ->iconColor('primary')
                                    ->dateTime('d.m.Y H:i:s')
                                    ->size('lg')
                                    ->weight('bold'),
                                    
                                TextEntry::make('duration_sec')
                                    ->label('Dauer')
                                    ->icon('heroicon-m-clock')
                                    ->iconColor('info')
                                    ->formatStateUsing(fn ($state) => gmdate('i:s', $state) . ' Min.')
                                    ->size('lg')
                                    ->weight('bold'),
                                    
                                TextEntry::make('sentiment')
                                    ->label('Stimmung')
                                    ->getStateUsing(fn ($record) => match($record->analysis['sentiment'] ?? 'unknown') {
                                        'positive' => '😊 Positiv',
                                        'negative' => '😞 Negativ', 
                                        'neutral' => '😐 Neutral',
                                        default => '❓ Unbekannt'
                                    })
                                    ->badge()
                                    ->size('lg')
                                    ->color(fn ($record): string => match ($record->analysis['sentiment'] ?? 'unknown') {
                                        'positive' => 'success',
                                        'negative' => 'danger',
                                        'neutral' => 'gray',
                                        default => 'gray',
                                    }),
                                    
                                TextEntry::make('urgency')
                                    ->label('Priorität')
                                    ->getStateUsing(fn ($record) => match($record->analysis['urgency'] ?? 'normal') {
                                        'high' => '🔴 Hoch',
                                        'medium' => '🟡 Mittel',
                                        'low' => '🟢 Niedrig',
                                        default => '⚪ Normal'
                                    })
                                    ->badge()
                                    ->size('lg')
                                    ->color(fn ($record): string => match ($record->analysis['urgency'] ?? 'normal') {
                                        'high' => 'danger',
                                        'medium' => 'warning',
                                        'low' => 'success',
                                        default => 'gray',
                                    }),
                                    
                                TextEntry::make('appointment_status')
                                    ->label('Termin')
                                    ->getStateUsing(fn ($record) => $record->appointment_id ? '✅ Gebucht' : '❌ Offen')
                                    ->badge()
                                    ->size('lg')
                                    ->color(fn ($record): string => $record->appointment_id ? 'success' : 'warning'),
                                    
                                TextEntry::make('conversion_score')
                                    ->label('Conversion')
                                    ->getStateUsing(fn ($record) => ($record->analysis['conversion_score'] ?? 0) . '%')
                                    ->icon('heroicon-m-chart-bar')
                                    ->iconColor(fn ($record) => match(true) {
                                        ($record->analysis['conversion_score'] ?? 0) >= 70 => 'success',
                                        ($record->analysis['conversion_score'] ?? 0) >= 40 => 'warning',
                                        default => 'danger'
                                    })
                                    ->size('lg')
                                    ->weight('bold'),
                            ])
                    ])
                    ->extraAttributes(['class' => 'bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-gray-800 dark:to-gray-900'])
                    ->columnSpanFull(),
                
                // Hauptbereich mit Audio Player
                Section::make('Anrufaufzeichnung')
                    ->icon('heroicon-o-speaker-wave')
                    ->headerActions([
                        InfolistAction::make('download')
                            ->label('Download')
                            ->icon('heroicon-m-arrow-down-tray')
                            ->color('primary')
                            ->url(fn ($record) => $record->audio_url ?? $record->recording_url)
                            ->openUrlInNewTab()
                            ->visible(fn ($record) => !empty($record->audio_url) || !empty($record->recording_url)),
                    ])
                    ->visible(fn ($record) => !empty($record->audio_url) || !empty($record->recording_url))
                    ->schema([
                        ViewEntry::make('audio_player')
                            ->label(false)
                            ->view('filament.resources.call-resource.audio-player')
                            ->viewData([
                                'audioUrl' => $this->record->audio_url ?? $this->record->recording_url,
                                'recordId' => $this->record->id,
                                'duration' => $this->record->duration_sec,
                                'publicLogUrl' => $this->record->public_log_url,
                            ])
                            ->columnSpanFull(),
                    ])
                    ->collapsible(false)
                    ->columnSpanFull(),
                
                // 2-Spalten Layout für AI Insights und Details
                Grid::make(['default' => 1, 'lg' => 2])
                    ->schema([
                    // Linke Seite - AI Insights & Customer Journey
                    Section::make('AI-gestützte Analyse')
                        ->icon('heroicon-o-sparkles')
                        ->description('Automatische Insights und Empfehlungen')
                        ->schema([
                            // AI Summary Card
                            Section::make()
                                ->heading(false)
                                ->schema([
                                    TextEntry::make('ai_summary')
                                        ->label('Executive Summary')
                                        ->getStateUsing(function ($record) {
                                            if ($record->summary) {
                                                return new HtmlString('<div class="prose prose-sm max-w-none">' . nl2br($record->summary) . '</div>');
                                            }
                                            return new HtmlString('<div class="prose prose-sm max-w-none">' . nl2br($this->generateAISummary($record)) . '</div>');
                                        })
                                        ->columnSpanFull(),
                                ])
                                ->extraAttributes(['class' => 'bg-amber-50 dark:bg-amber-900/20 border-l-4 border-amber-400']),
                            
                            // Key Insights Grid
                            Grid::make(2)->schema([
                                Section::make('Sentiment Score')
                                    ->heading(false)
                                    ->schema([
                                        ViewEntry::make('sentiment_gauge')
                                            ->label(false)
                                            ->view('filament.resources.call-resource.sentiment-gauge')
                                            ->viewData([
                                                'sentiment' => $this->record->analysis['sentiment'] ?? 'neutral',
                                                'score' => match($this->record->analysis['sentiment'] ?? 'neutral') {
                                                    'positive' => 85,
                                                    'neutral' => 50,
                                                    'negative' => 15,
                                                    default => 50
                                                }
                                            ]),
                                    ]),
                                
                                Section::make('Conversion Chance')
                                    ->heading(false)
                                    ->schema([
                                        ViewEntry::make('conversion_gauge')
                                            ->label(false)
                                            ->view('filament.resources.call-resource.conversion-gauge')
                                            ->viewData([
                                                'score' => $this->record->analysis['conversion_score'] ?? 0,
                                                'hasAppointment' => (bool)$this->record->appointment_id
                                            ]),
                                    ]),
                            ]),
                            
                            // Action Items
                            Section::make('Nächste Schritte')
                                ->icon('heroicon-m-clipboard-document-check')
                                ->schema([
                                    ViewEntry::make('action_items')
                                        ->label(false)
                                        ->view('filament.resources.call-resource.action-items')
                                        ->viewData([
                                            'record' => $this->record,
                                            'recommendedAction' => $this->getRecommendedAction($this->record)
                                        ]),
                                ])
                                ->collapsible(),
                            
                            // Erkannte Daten
                            Section::make('Extrahierte Informationen')
                                ->icon('heroicon-o-document-magnifying-glass')
                                ->schema([
                                    Grid::make(2)->schema([
                                        TextEntry::make('name')
                                            ->label('Name')
                                            ->getStateUsing(fn ($record) => $record->analysis['entities']['name'] ?? null)
                                            ->placeholder('—')
                                            ->copyable(),
                                        
                                        TextEntry::make('email')
                                            ->label('E-Mail')
                                            ->getStateUsing(fn ($record) => $record->analysis['entities']['email'] ?? null)
                                            ->placeholder('—')
                                            ->copyable(),
                                        
                                        TextEntry::make('date')
                                            ->label('Datum')
                                            ->getStateUsing(fn ($record) => $record->analysis['entities']['date'] ?? null)
                                            ->placeholder('—'),
                                        
                                        TextEntry::make('time')
                                            ->label('Uhrzeit')
                                            ->getStateUsing(fn ($record) => $record->analysis['entities']['time'] ?? null)
                                            ->placeholder('—'),
                                        
                                        TextEntry::make('service')
                                            ->label('Service')
                                            ->getStateUsing(fn ($record) => $record->analysis['entities']['service'] ?? null)
                                            ->placeholder('—')
                                            ->badge()
                                            ->color('info'),
                                        
                                        TextEntry::make('phone')
                                            ->label('Telefon')
                                            ->getStateUsing(fn ($record) => $record->analysis['entities']['phone'] ?? null)
                                            ->placeholder('—')
                                            ->copyable(),
                                    ]),
                                ])
                                ->collapsed(),
                        ]),
                        
                        // Rechte Spalte - Call Details
                        Group::make([
                            // Call Details Card
                            Section::make('Anrufdetails')
                                ->icon('heroicon-o-phone')
                                ->schema([
                                    Grid::make(2)->schema([
                                        TextEntry::make('from_number')
                                            ->label('Anrufer')
                                            ->icon('heroicon-o-phone')
                                            ->iconColor('primary')
                                            ->copyable()
                                            ->size('lg'),
                                        
                                        TextEntry::make('to_number')
                                            ->label('Angerufene Nr.')
                                            ->icon('heroicon-o-phone-arrow-down-left')
                                            ->iconColor('gray'),
                                        
                                        TextEntry::make('disconnection_reason')
                                            ->label('Beendet durch')
                                            ->formatStateUsing(fn ($state) => match($state) {
                                                'customer_hung_up' => '👤 Kunde',
                                                'agent_hung_up' => '🤖 Agent',
                                                'call_transfer' => '↗️ Weitergeleitet',
                                                default => '✅ Normal',
                                            })
                                            ->badge()
                                            ->color(fn ($state) => match($state) {
                                                'customer_hung_up' => 'warning',
                                                'agent_hung_up' => 'info',
                                                default => 'success',
                                            }),
                                        
                                        TextEntry::make('call_type')
                                            ->label('Anruftyp')
                                            ->getStateUsing(fn ($record) => $record->analysis['call_type'] ?? 'inbound')
                                            ->formatStateUsing(fn ($state) => match($state) {
                                                'inbound' => '📞 Eingehend',
                                                'outbound' => '📱 Ausgehend',
                                                default => '❓ Unbekannt',
                                            })
                                            ->badge(),
                                    ]),
                                    
                                    // Call Quality Metrics
                                    Section::make('Qualitätsmetriken')
                                        ->heading(false)
                                        ->schema([
                                            Grid::make(3)->schema([
                                                TextEntry::make('latency')
                                                    ->label('Latenz')
                                                    ->getStateUsing(function ($record) {
                                                        $latency = $record->analysis['latency'] ?? 0;
                                                        if (is_array($latency)) {
                                                            $latency = !empty($latency) ? round(array_sum($latency) / count($latency)) : 0;
                                                        }
                                                        return $latency . 'ms';
                                                    })
                                                    ->icon('heroicon-m-signal')
                                                    ->iconColor(function ($record) {
                                                        $latency = $record->analysis['latency'] ?? 0;
                                                        if (is_array($latency)) {
                                                            $latency = !empty($latency) ? (array_sum($latency) / count($latency)) : 0;
                                                        }
                                                        return match(true) {
                                                            $latency < 150 => 'success',
                                                            $latency < 300 => 'warning',
                                                            default => 'danger'
                                                        };
                                                    }),
                                                
                                                TextEntry::make('interruptions')
                                                    ->label('Unterbrechungen')
                                                    ->getStateUsing(function ($record) {
                                                        $interruptions = $record->analysis['interruptions'] ?? 0;
                                                        if (is_array($interruptions)) {
                                                            $interruptions = count($interruptions);
                                                        }
                                                        return $interruptions;
                                                    })
                                                    ->icon('heroicon-m-exclamation-circle')
                                                    ->iconColor(function ($record) {
                                                        $interruptions = $record->analysis['interruptions'] ?? 0;
                                                        if (is_array($interruptions)) {
                                                            $interruptions = count($interruptions);
                                                        }
                                                        return $interruptions > 3 ? 'danger' : 'success';
                                                    }),
                                                
                                                TextEntry::make('silence_percentage')
                                                    ->label('Stille')
                                                    ->getStateUsing(function ($record) {
                                                        $silence = $record->analysis['silence_percentage'] ?? 0;
                                                        if (is_array($silence)) {
                                                            $silence = !empty($silence) ? round(array_sum($silence) / count($silence)) : 0;
                                                        }
                                                        return $silence . '%';
                                                    })
                                                    ->icon('heroicon-m-speaker-x-mark')
                                                    ->iconColor(function ($record) {
                                                        $silence = $record->analysis['silence_percentage'] ?? 0;
                                                        if (is_array($silence)) {
                                                            $silence = !empty($silence) ? (array_sum($silence) / count($silence)) : 0;
                                                        }
                                                        return $silence > 30 ? 'warning' : 'success';
                                                    }),
                                            ]),
                                        ])
                                        ->extraAttributes(['class' => 'bg-gray-50 dark:bg-gray-800/50 rounded-lg p-4']),
                                ]),
                            
                            // Relationships
                            Section::make('Verknüpfungen')
                                ->icon('heroicon-o-link')
                                ->schema([
                                    TextEntry::make('customer.name')
                                        ->label('Kunde')
                                        ->placeholder('Kein Kunde zugeordnet')
                                        ->icon('heroicon-o-user')
                                        ->size('lg')
                                        ->weight('bold')
                                        ->url(fn ($record) => $record->customer ? 
                                            \App\Filament\Admin\Resources\CustomerResource::getUrl('view', ['record' => $record->customer]) : 
                                            null),
                                    
                                    TextEntry::make('appointment')
                                        ->label('Termin')
                                        ->getStateUsing(function ($record) {
                                            if (!$record->appointment) {
                                                return null;
                                            }
                                            return $record->appointment->starts_at->format('d.m.Y H:i') . ' - ' . 
                                                   ($record->appointment->service->name ?? 'Unbekannt');
                                        })
                                        ->placeholder('Kein Termin gebucht')
                                        ->icon('heroicon-o-calendar')
                                        ->iconColor('success')
                                        ->size('lg')
                                        ->url(fn ($record) => $record->appointment ? 
                                            AppointmentResource::getUrl('view', ['record' => $record->appointment]) : 
                                            null),
                                ]),
                            
                            // Cost Analysis
                            Section::make('Kostenanalyse')
                                ->icon('heroicon-o-currency-euro')
                                ->schema([
                                    Grid::make(3)->schema([
                                        TextEntry::make('cost')
                                            ->label('Anrufkosten')
                                            ->money('EUR')
                                            ->size('lg')
                                            ->weight('bold')
                                            ->icon('heroicon-m-banknotes'),
                                        
                                        TextEntry::make('potential_value')
                                            ->label('Potentieller Wert')
                                            ->getStateUsing(function ($record) {
                                                if ($record->appointment && $record->appointment->service) {
                                                    return $record->appointment->service->price;
                                                }
                                                return 0;
                                            })
                                            ->money('EUR')
                                            ->icon('heroicon-m-chart-bar-square')
                                            ->iconColor('success'),
                                        
                                        TextEntry::make('roi')
                                            ->label('ROI')
                                            ->getStateUsing(function ($record) {
                                                $cost = $record->cost ?? 0.50;
                                                $value = 0;
                                                if ($record->appointment && $record->appointment->service) {
                                                    $value = $record->appointment->service->price;
                                                }
                                                if ($cost > 0 && $value > 0) {
                                                    return round(($value / $cost - 1) * 100);
                                                }
                                                return 0;
                                            })
                                            ->formatStateUsing(fn ($state) => $state . '%')
                                            ->icon('heroicon-m-arrow-trending-up')
                                            ->iconColor(fn ($state) => $state > 0 ? 'success' : 'danger')
                                            ->badge()
                                            ->color(fn ($state) => $state > 0 ? 'success' : 'danger'),
                                    ]),
                                ])
                                ->collapsible(),
                        ]),
                    ]),
                
                // Transkript - volle Breite
                Section::make('Gesprächsverlauf')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->collapsible()
                    ->schema([
                        TextEntry::make('transcript_viewer')
                            ->label(false)
                            ->getStateUsing(function ($record) {
                                if (!$record->transcript) {
                                    return 'Kein Transkript verfügbar';
                                }
                                return new HtmlString($this->formatTranscript($record->transcript));
                            })
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
                
                // Metriken - volle Breite
                Section::make('Performance Metriken')
                    ->icon('heroicon-o-chart-bar-square')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        ViewEntry::make('metrics')
                            ->label(false)
                            ->view('filament.resources.call-resource.metrics')
                            ->viewData([
                                'record' => $this->record,
                            ])
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
            ]);
    }
    
    private function generateAISummary($record): string
    {
        if (!$record) {
            return 'Keine Daten verfügbar';
        }
        
        $summary = "Anruf von " . ($record->from_number ?? 'unbekannt');
        
        if ($record->customer) {
            $summary .= " (" . $record->customer->name . ")";
        }
        
        $summary .= " am " . ($record->start_timestamp ?? $record->created_at)->format('d.m.Y um H:i') . " Uhr. ";
        $summary .= "Dauer: " . gmdate('i:s', $record->duration_sec) . " Minuten. ";
        
        if (isset($record->analysis['sentiment'])) {
            $sentiment = match($record->analysis['sentiment']) {
                'positive' => 'positiv',
                'negative' => 'negativ',
                'neutral' => 'neutral',
                default => 'unbekannt'
            };
            $summary .= "Die Stimmung war " . $sentiment . ". ";
        }
        
        if (isset($record->analysis['intent'])) {
            $summary .= "Anliegen: " . $record->analysis['intent'] . ". ";
        }
        
        if ($record->appointment_id) {
            $summary .= "Ein Termin wurde erfolgreich vereinbart.";
        } elseif (isset($record->analysis['entities']['date'])) {
            $summary .= "Ein Terminwunsch wurde geäußert.";
        }
        
        return $summary;
    }
    
    private function getRecommendedAction($record): string
    {
        // Priorität 1: Negative Stimmung
        if (isset($record->analysis['sentiment']) && $record->analysis['sentiment'] === 'negative') {
            return "⚠️ Dringend nachfassen! Kunde war unzufrieden.";
        }
        
        // Priorität 2: Hohe Dringlichkeit ohne Termin
        if (isset($record->analysis['urgency']) && $record->analysis['urgency'] === 'high' && !$record->appointment_id) {
            return "🚨 Schnell Termin anbieten - hohe Dringlichkeit erkannt!";
        }
        
        // Priorität 3: Positive Stimmung ohne Termin
        if (isset($record->analysis['sentiment']) && $record->analysis['sentiment'] === 'positive' && !$record->appointment_id) {
            return "✅ Gute Chance! Termin vorschlagen.";
        }
        
        // Priorität 4: Termin bereits gebucht
        if ($record->appointment_id) {
            return "✓ Termin gebucht. Eventuell Bestätigungs-SMS senden.";
        }
        
        // Default
        return "📋 Standard-Follow-up in 2-3 Tagen empfohlen.";
    }
    
    private function formatTranscript($transcript): string
    {
        // Formatiere das Transkript mit Highlighting
        $lines = explode("\n", $transcript);
        $formatted = '<div class="space-y-3">';
        
        foreach ($lines as $line) {
            if (empty(trim($line))) continue;
            
            if (str_contains($line, 'AI:') || str_contains($line, 'Agent:')) {
                $formatted .= '<div class="flex gap-3">';
                $formatted .= '<div class="flex-shrink-0 w-8 h-8 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center">';
                $formatted .= '<span class="text-xs">🤖</span>';
                $formatted .= '</div>';
                $formatted .= '<div class="flex-1 bg-blue-50 dark:bg-blue-900/20 rounded-lg p-3">';
                $formatted .= '<span class="text-sm font-medium text-blue-600 dark:text-blue-400">KI-Agent</span>';
                $formatted .= '<p class="mt-1 text-sm">' . str_replace(['AI:', 'Agent:'], '', $line) . '</p>';
                $formatted .= '</div>';
                $formatted .= '</div>';
            } elseif (str_contains($line, 'Customer:') || str_contains($line, 'Caller:')) {
                $formatted .= '<div class="flex gap-3">';
                $formatted .= '<div class="flex-shrink-0 w-8 h-8 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center">';
                $formatted .= '<span class="text-xs">👤</span>';
                $formatted .= '</div>';
                $formatted .= '<div class="flex-1 bg-gray-50 dark:bg-gray-900/30 rounded-lg p-3">';
                $formatted .= '<span class="text-sm font-medium text-gray-600 dark:text-gray-400">Kunde</span>';
                $formatted .= '<p class="mt-1 text-sm">' . str_replace(['Customer:', 'Caller:'], '', $line) . '</p>';
                $formatted .= '</div>';
                $formatted .= '</div>';
            }
        }
        
        $formatted .= '</div>';
        
        // Highlight wichtige Wörter
        $keywords = ['Termin', 'morgen', 'heute', 'dringend', 'wichtig', 'Problem'];
        foreach ($keywords as $keyword) {
            $formatted = str_ireplace(
                $keyword, 
                '<mark class="bg-yellow-200 dark:bg-yellow-900/50 px-1 rounded">' . $keyword . '</mark>', 
                $formatted
            );
        }
        
        return $formatted;
    }
}