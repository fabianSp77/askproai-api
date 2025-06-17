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
use App\Services\PricingService;

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
                        ])->get('https://api.retellai.com/v2/get-call/' . $callId);
                        
                        if (!$response->successful()) {
                            throw new \Exception('API Error: ' . $response->body());
                        }
                        
                        $responseData = $response->json();
                        $callData = $responseData['call'] ?? $responseData;
                        
                        if ($callData) {
                            // CallDataRefresher direkt nutzen für sofortige Aktualisierung
                            $refresher = new \App\Services\CallDataRefresher();
                            $refreshed = $refresher->refresh($this->record);
                            
                            if ($refreshed) {
                                // Record neu laden
                                $this->record->refresh();
                                
                                Notification::make()
                                    ->title('Daten erfolgreich aktualisiert')
                                    ->body('Die Anrufdaten wurden von Retell.ai abgerufen.')
                                    ->success()
                                    ->send();
                                    
                                // Seite nach kurzer Verzögerung neu laden
                                $this->js('setTimeout(() => window.location.reload(), 1500)');
                            } else {
                                Notification::make()
                                    ->title('Aktualisierung fehlgeschlagen')
                                    ->body('Die Daten konnten nicht aktualisiert werden.')
                                    ->warning()
                                    ->send();
                            }
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
                // KPI Cards - Wichtige Metriken auf einen Blick
                Section::make('Anruf-Metriken')
                    ->description('Wichtige Kennzahlen auf einen Blick')
                    ->schema([
                        Grid::make(['default' => 2, 'sm' => 3, 'lg' => 6])
                            ->schema([
                                // Anrufzeit mit Status
                                Group::make([
                                    TextEntry::make('start_timestamp')
                                        ->label('Anrufzeit')
                                        ->default(fn ($record) => $record->created_at)
                                        ->icon('heroicon-m-phone-arrow-down-left')
                                        ->iconColor('primary')
                                        ->dateTime('d.m.Y H:i:s')
                                        ->size('lg')
                                        ->weight('bold'),
                                    
                                    TextEntry::make('call_status')
                                        ->label('Status')
                                        ->badge()
                                        ->color(fn ($state): string => match ($state) {
                                            'completed' => 'success',
                                            'failed' => 'danger',
                                            'busy' => 'warning',
                                            default => 'gray',
                                        }),
                                    
                                    // Call Success & Voicemail
                                    TextEntry::make('call_quality')
                                        ->label('Qualität')
                                        ->getStateUsing(function ($record) {
                                            $successful = $record->call_successful ?? (isset($record->analysis['call_successful']) ? $record->analysis['call_successful'] : null);
                                            $voicemail = isset($record->analysis['in_voicemail']) ? $record->analysis['in_voicemail'] : false;
                                            
                                            if ($voicemail) {
                                                return '📞 Anrufbeantworter';
                                            } elseif ($successful === true) {
                                                return '✅ Erfolgreich';
                                            } elseif ($successful === false) {
                                                return '❌ Fehlgeschlagen';
                                            }
                                            return '—';
                                        })
                                        ->badge()
                                        ->color(function ($record) {
                                            $voicemail = isset($record->analysis['in_voicemail']) ? $record->analysis['in_voicemail'] : false;
                                            if ($voicemail) return 'warning';
                                            
                                            $successful = $record->call_successful ?? (isset($record->analysis['call_successful']) ? $record->analysis['call_successful'] : null);
                                            return $successful ? 'success' : 'danger';
                                        })
                                        ->size('sm'),
                                ]),
                                    
                                // Dauer mit Kosten
                                Group::make([
                                    TextEntry::make('duration_sec')
                                        ->label('Gesprächsdauer')
                                        ->icon('heroicon-m-clock')
                                        ->iconColor('info')
                                        ->formatStateUsing(fn ($state) => gmdate('i:s', $state) . ' Min.')
                                        ->size('lg')
                                        ->weight('bold'),
                                    
                                    TextEntry::make('cost')
                                        ->label('Kosten')
                                        ->money('EUR')
                                        ->icon('heroicon-m-currency-euro')
                                        ->iconColor('warning')
                                        ->size('sm'),
                                ]),
                                    
                                // Stimmung mit Score
                                Group::make([
                                    TextEntry::make('sentiment')
                                        ->label('KI-Bewertung')
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
                                    
                                    // User Sentiment (aus Kundensicht)
                                    TextEntry::make('user_sentiment')
                                        ->label('Kundensicht')
                                        ->getStateUsing(function ($record) {
                                            $userSentiment = $record->analysis['user_sentiment'] ?? null;
                                            if (!$userSentiment) return '—';
                                            
                                            return match(strtolower($userSentiment)) {
                                                'positive' => '😊 Positiv',
                                                'negative' => '😞 Negativ',
                                                'neutral' => '😐 Neutral',
                                                default => $userSentiment
                                            };
                                        })
                                        ->badge()
                                        ->size('sm')
                                        ->color(function ($record) {
                                            $userSentiment = strtolower($record->analysis['user_sentiment'] ?? '');
                                            return match($userSentiment) {
                                                'positive' => 'success',
                                                'negative' => 'danger',
                                                'neutral' => 'gray',
                                                default => 'gray'
                                            };
                                        }),
                                ]),
                                    
                                // Priorität mit Follow-up
                                Group::make([
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
                                    
                                    TextEntry::make('follow_up_needed')
                                        ->label('Nachfassen')
                                        ->getStateUsing(fn ($record) => 
                                            (isset($record->analysis['urgency']) && $record->analysis['urgency'] === 'high') || 
                                            (isset($record->analysis['sentiment']) && $record->analysis['sentiment'] === 'negative') 
                                            ? 'Ja' : 'Nein'
                                        )
                                        ->badge()
                                        ->color(fn ($state) => $state === 'Ja' ? 'danger' : 'gray')
                                        ->size('sm'),
                                ]),
                                    
                                // Terminbuchung
                                Group::make([
                                    TextEntry::make('appointment_status')
                                        ->label('Terminbuchung')
                                        ->getStateUsing(fn ($record) => $record->appointment_id ? '✅ Erfolgreich' : '❌ Ausstehend')
                                        ->badge()
                                        ->size('lg')
                                        ->color(fn ($record): string => $record->appointment_id ? 'success' : 'warning'),
                                    
                                    TextEntry::make('appointment_date')
                                        ->label('Termin am')
                                        ->getStateUsing(fn ($record) => 
                                            $record->appointment?->starts_at?->format('d.m.Y H:i') ?? '—'
                                        )
                                        ->visible(fn ($record) => $record->appointment_id)
                                        ->size('sm'),
                                ]),
                                    
                                // Conversion Rate
                                Group::make([
                                    TextEntry::make('conversion_score')
                                        ->label('Conversion-Chance')
                                        ->getStateUsing(fn ($record) => ($record->analysis['conversion_score'] ?? 0) . '%')
                                        ->icon('heroicon-m-chart-bar')
                                        ->iconColor(fn ($record) => match(true) {
                                            ($record->analysis['conversion_score'] ?? 0) >= 70 => 'success',
                                            ($record->analysis['conversion_score'] ?? 0) >= 40 => 'warning',
                                            default => 'danger'
                                        })
                                        ->size('lg')
                                        ->weight('bold'),
                                    
                                    TextEntry::make('potential_value')
                                        ->label('Potenzial')
                                        ->getStateUsing(function ($record) {
                                            if ($record->appointment && $record->appointment->service) {
                                                return '€' . number_format($record->appointment->service->price, 2, ',', '.');
                                            }
                                            // Durchschnittswert als Fallback
                                            return '€150,00';
                                        })
                                        ->size('sm')
                                        ->visible(fn ($record) => 
                                            ($record->analysis['conversion_score'] ?? 0) > 50 || 
                                            $record->appointment_requested
                                        ),
                                ]),
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
                
                // Retell.ai Zusammenfassung prominent am Anfang
                Section::make('Gesprächszusammenfassung von Retell.ai')
                    ->icon('heroicon-o-document-text')
                    ->description('KI-generierte Zusammenfassung des Telefonats')
                    ->collapsible(false)
                    ->schema([
                        Grid::make(['default' => 1, 'lg' => 3])
                            ->schema([
                                // Hauptzusammenfassung
                                Section::make()
                                    ->columnSpan(['lg' => 2])
                                    ->heading(false)
                                    ->schema([
                                        TextEntry::make('summary')
                                            ->label(false)
                                            ->getStateUsing(function ($record) {
                                                if ($record->summary) {
                                                    return new HtmlString(
                                                        '<div class="prose prose-sm max-w-none text-gray-700 dark:text-gray-300">' . 
                                                        nl2br(e($record->summary)) . 
                                                        '</div>'
                                                    );
                                                }
                                                
                                                // Fallback zur generierten Zusammenfassung
                                                return new HtmlString(
                                                    '<div class="prose prose-sm max-w-none text-gray-500 dark:text-gray-400 italic">' . 
                                                    'Keine Retell.ai Zusammenfassung verfügbar. Automatisch generiert:<br><br>' .
                                                    nl2br($this->generateAISummary($record)) . 
                                                    '</div>'
                                                );
                                            })
                                            ->columnSpanFull(),
                                    ])
                                    ->extraAttributes(['class' => 'bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-500 p-4']),
                                
                                // Quick Insights Sidebar
                                Section::make('Schnellübersicht')
                                    ->heading(false)
                                    ->schema([
                                        // Intent/Absicht
                                        TextEntry::make('intent')
                                            ->label('Anrufgrund')
                                            ->getStateUsing(fn ($record) => 
                                                $record->intent ?? 
                                                $record->analysis['intent'] ?? 
                                                'Nicht erkannt'
                                            )
                                            ->icon('heroicon-m-light-bulb')
                                            ->iconColor('warning'),
                                        
                                        // Wichtige Phrasen
                                        TextEntry::make('important_phrases')
                                            ->label('Wichtige Stichworte')
                                            ->getStateUsing(function ($record) {
                                                $phrases = isset($record->analysis['important_phrases']) ? $record->analysis['important_phrases'] : 
                                                          (isset($record->analysis['entities']['keywords']) ? $record->analysis['entities']['keywords'] : []);
                                                
                                                if (empty($phrases)) {
                                                    return '—';
                                                }
                                                
                                                if (is_array($phrases)) {
                                                    return implode(', ', array_slice($phrases, 0, 5));
                                                }
                                                
                                                return $phrases;
                                            })
                                            ->icon('heroicon-m-tag'),
                                        
                                        // Terminwunsch
                                        TextEntry::make('appointment_requested')
                                            ->label('Terminwunsch')
                                            ->getStateUsing(fn ($record) => 
                                                $record->appointment_requested ? 'Ja' : 'Nein'
                                            )
                                            ->icon('heroicon-m-calendar')
                                            ->iconColor(fn ($record) => 
                                                $record->appointment_requested ? 'success' : 'gray'
                                            )
                                            ->badge()
                                            ->color(fn ($record) => 
                                                $record->appointment_requested ? 'success' : 'gray'
                                            ),
                                    ])
                                    ->extraAttributes(['class' => 'bg-gray-50 dark:bg-gray-800/50 rounded-lg p-4']),
                            ])
                    ])
                    ->columnSpanFull(),
                
                // Timeline des Anrufs
                Section::make('Anrufverlauf')
                    ->icon('heroicon-o-clock')
                    ->description('Chronologischer Ablauf des Gesprächs')
                    ->collapsible()
                    ->schema([
                        ViewEntry::make('call_timeline')
                            ->label(false)
                            ->view('filament.resources.call-resource.call-timeline')
                            ->viewData([
                                'startTime' => $this->record->start_timestamp?->format('H:i:s') ?? $this->record->created_at->format('H:i:s'),
                                'endTime' => $this->record->end_timestamp?->format('H:i:s') ?? $this->record->updated_at->format('H:i:s'),
                                'duration' => gmdate('i:s', $this->record->duration_sec),
                                'fromNumber' => $this->record->from_number ?? 'Unbekannt',
                                'hasTranscript' => !empty($this->record->transcript),
                                'intent' => $this->record->intent ?? (isset($this->record->analysis['intent']) ? $this->record->analysis['intent'] : null),
                                'customerIdentified' => !empty($this->record->customer_id) || !empty($this->record->extracted_name),
                                'customerName' => $this->record->customer?->name ?? $this->record->extracted_name,
                                'appointmentRequested' => $this->record->appointment_requested,
                                'requestedDate' => $this->record->extracted_date,
                                'appointmentBooked' => !empty($this->record->appointment_id),
                                'appointmentDate' => $this->record->appointment?->starts_at?->format('d.m.Y H:i'),
                                'callSuccessful' => $this->record->call_successful ?? true,
                                'disconnectionReason' => $this->record->disconnection_reason ?? 'Normal beendet',
                                'sentiment' => isset($this->record->analysis['sentiment']) ? $this->record->analysis['sentiment'] : 'neutral',
                            ])
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
                
                // 2-Spalten Layout für AI Insights und Details
                Grid::make(['default' => 1, 'lg' => 2])
                    ->schema([
                    // Linke Seite - AI Insights & Customer Journey
                    Section::make('AI-gestützte Analyse')
                        ->icon('heroicon-o-sparkles')
                        ->description('Automatische Insights und Empfehlungen')
                        ->schema([
                            
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
                                            ->getStateUsing(fn ($record) => 
                                                $record->analysis['custom_analysis_data']['_name'] ??
                                                $record->analysis['entities']['name'] ?? 
                                                $record->extracted_name ?? 
                                                null
                                            )
                                            ->placeholder('—')
                                            ->copyable()
                                            ->icon('heroicon-m-user'),
                                        
                                        TextEntry::make('email')
                                            ->label('E-Mail')
                                            ->getStateUsing(fn ($record) => 
                                                $record->analysis['custom_analysis_data']['_email'] ??
                                                $record->analysis['entities']['email'] ?? 
                                                $record->extracted_email ?? 
                                                null
                                            )
                                            ->placeholder('—')
                                            ->copyable()
                                            ->icon('heroicon-m-envelope'),
                                        
                                        TextEntry::make('phone')
                                            ->label('Telefonnummer')
                                            ->getStateUsing(fn ($record) => 
                                                isset($record->analysis['custom_analysis_data']['_telefonnummer__anrufer']) ?
                                                '+49' . $record->analysis['custom_analysis_data']['_telefonnummer__anrufer'] :
                                                ($record->analysis['entities']['phone'] ?? null)
                                            )
                                            ->placeholder('—')
                                            ->copyable()
                                            ->icon('heroicon-m-phone'),
                                        
                                        TextEntry::make('date')
                                            ->label('Gewünschtes Datum')
                                            ->getStateUsing(function ($record) {
                                                // Prüfe custom_analysis_data für Datum
                                                if (isset($record->analysis['custom_analysis_data']['_datum__termin'])) {
                                                    $days = $record->analysis['custom_analysis_data']['_datum__termin'];
                                                    if ($days == 1) {
                                                        return 'Morgen';
                                                    } elseif ($days == 0) {
                                                        return 'Heute';
                                                    } else {
                                                        return "In {$days} Tagen";
                                                    }
                                                }
                                                return $record->analysis['entities']['date'] ?? 
                                                       $record->extracted_date ?? 
                                                       null;
                                            })
                                            ->placeholder('—')
                                            ->icon('heroicon-m-calendar')
                                            ->formatStateUsing(function ($state) {
                                                if (!$state || str_contains($state, 'Morgen') || str_contains($state, 'Heute') || str_contains($state, 'Tagen')) {
                                                    return $state;
                                                }
                                                try {
                                                    return \Carbon\Carbon::parse($state)->format('d.m.Y');
                                                } catch (\Exception $e) {
                                                    return $state;
                                                }
                                            }),
                                        
                                        TextEntry::make('time')
                                            ->label('Gewünschte Uhrzeit')
                                            ->getStateUsing(fn ($record) => 
                                                isset($record->analysis['custom_analysis_data']['_uhrzeit__termin']) ?
                                                $record->analysis['custom_analysis_data']['_uhrzeit__termin'] . ':00 Uhr' :
                                                ($record->analysis['entities']['time'] ?? 
                                                 $record->extracted_time ?? 
                                                 null)
                                            )
                                            ->placeholder('—')
                                            ->icon('heroicon-m-clock'),
                                        
                                        TextEntry::make('service')
                                            ->label('Gewünschte Dienstleistung')
                                            ->getStateUsing(fn ($record) => 
                                                $record->analysis['entities']['service'] ?? 
                                                $record->extracted_service ?? 
                                                null
                                            )
                                            ->placeholder('—')
                                            ->badge()
                                            ->color('info')
                                            ->icon('heroicon-m-briefcase'),
                                        
                                        TextEntry::make('phone')
                                            ->label('Telefonnummer')
                                            ->getStateUsing(fn ($record) => 
                                                $record->analysis['entities']['phone'] ?? 
                                                $record->extracted_phone ?? 
                                                null
                                            )
                                            ->placeholder('—')
                                            ->copyable()
                                            ->icon('heroicon-m-phone'),
                                    ]),
                                    
                                    // Zusätzliche extrahierte Daten
                                    TextEntry::make('appointment_requested')
                                        ->label('Terminwunsch geäußert')
                                        ->getStateUsing(fn ($record) => 
                                            $record->appointment_requested || 
                                            ($record->analysis['appointment_requested'] ?? false)
                                        )
                                        ->formatStateUsing(fn ($state) => $state ? '✅ Ja' : '❌ Nein')
                                        ->badge()
                                        ->color(fn ($state) => $state ? 'success' : 'gray')
                                        ->columnSpanFull(),
                                        
                                    // Wichtige Phrasen
                                    TextEntry::make('important_phrases')
                                        ->label('Wichtige Phrasen')
                                        ->getStateUsing(fn ($record) => $record->analysis['important_phrases'] ?? [])
                                        ->badge()
                                        ->separator(', ')
                                        ->columnSpanFull()
                                        ->visible(fn ($record) => !empty($record->analysis['important_phrases'])),
                                        
                                    // Intent/Absicht
                                    TextEntry::make('intent')
                                        ->label('Erkannte Absicht')
                                        ->getStateUsing(fn ($record) => 
                                            $record->intent ?? 
                                            $record->analysis['intent'] ?? 
                                            null
                                        )
                                        ->placeholder('—')
                                        ->badge()
                                        ->color('warning')
                                        ->columnSpanFull()
                                        ->visible(fn ($record) => !empty($record->intent) || !empty($record->analysis['intent'])),
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
                                            ->getStateUsing(fn ($record) => $record->analysis['call_type'] ?? $record->call_type ?? 'inbound')
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
                                                        if (!isset($record->analysis['latency'])) {
                                                            return '—';
                                                        }
                                                        $latency = $record->analysis['latency'];
                                                        if (is_array($latency) && isset($latency['e2e']['p50'])) {
                                                            return round($latency['e2e']['p50']) . 'ms';
                                                        }
                                                        return '—';
                                                    })
                                                    ->icon('heroicon-m-signal')
                                                    ->iconColor(function ($record) {
                                                        if (!isset($record->analysis['latency']['e2e']['p50'])) {
                                                            return 'gray';
                                                        }
                                                        $latency = $record->analysis['latency']['e2e']['p50'];
                                                        return match(true) {
                                                            $latency < 1500 => 'success',
                                                            $latency < 3000 => 'warning',
                                                            default => 'danger'
                                                        };
                                                    }),
                                                
                                                TextEntry::make('interruptions')
                                                    ->label('Unterbrechungen')
                                                    ->getStateUsing(function ($record) {
                                                        if (!isset($record->analysis['interruptions'])) {
                                                            return '0';
                                                        }
                                                        $interruptions = $record->analysis['interruptions'];
                                                        if (is_array($interruptions)) {
                                                            return count($interruptions);
                                                        }
                                                        return $interruptions;
                                                    })
                                                    ->icon('heroicon-m-exclamation-circle')
                                                    ->iconColor(function ($record) {
                                                        if (!isset($record->analysis['interruptions'])) {
                                                            return 'success';
                                                        }
                                                        $interruptions = $record->analysis['interruptions'];
                                                        if (is_array($interruptions)) {
                                                            $interruptions = count($interruptions);
                                                        }
                                                        return $interruptions > 3 ? 'danger' : 'success';
                                                    }),
                                                
                                                TextEntry::make('silence_percentage')
                                                    ->label('Stille')
                                                    ->getStateUsing(function ($record) {
                                                        if (!isset($record->analysis['silence_percentage'])) {
                                                            return '—';
                                                        }
                                                        $silence = $record->analysis['silence_percentage'];
                                                        if (is_array($silence) && !empty($silence)) {
                                                            $silence = round(array_sum($silence) / count($silence));
                                                        }
                                                        return is_numeric($silence) ? $silence . '%' : '—';
                                                    })
                                                    ->icon('heroicon-m-speaker-x-mark')
                                                    ->iconColor(function ($record) {
                                                        if (!isset($record->analysis['silence_percentage'])) {
                                                            return 'gray';
                                                        }
                                                        $silence = $record->analysis['silence_percentage'];
                                                        if (is_array($silence) && !empty($silence)) {
                                                            $silence = array_sum($silence) / count($silence);
                                                        }
                                                        return is_numeric($silence) && $silence > 30 ? 'warning' : 'success';
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
                            
                            // Cost Analysis mit ROI
                            Section::make('Kostenanalyse & ROI')
                                ->icon('heroicon-o-currency-euro')
                                ->schema([
                                    Grid::make(2)->schema([
                                        // Unsere Kosten
                                        Group::make([
                                            TextEntry::make('cost')
                                                ->label('Unsere Kosten')
                                                ->money('EUR')
                                                ->size('lg')
                                                ->weight('bold')
                                                ->icon('heroicon-m-arrow-down-circle')
                                                ->iconColor('danger'),
                                            
                                            TextEntry::make('cost_per_minute')
                                                ->label('Kosten pro Minute')
                                                ->getStateUsing(function ($record) {
                                                    if ($record->duration_sec > 0) {
                                                        return $record->cost / ($record->duration_sec / 60);
                                                    }
                                                    return 0;
                                                })
                                                ->money('EUR')
                                                ->size('sm'),
                                        ]),
                                        
                                        // Kundenpreis (wenn Preismodell existiert)
                                        Group::make([
                                            TextEntry::make('customer_price')
                                                ->label('Kundenpreis')
                                                ->getStateUsing(function ($record) {
                                                    $pricingService = new PricingService();
                                                    $pricing = $pricingService->calculateCallPrice($record);
                                                    return $pricing['customer_price'];
                                                })
                                                ->money('EUR')
                                                ->size('lg')
                                                ->weight('bold')
                                                ->icon('heroicon-m-arrow-up-circle')
                                                ->iconColor('success'),
                                            
                                            TextEntry::make('price_per_minute')
                                                ->label('Kundenpreis pro Minute')
                                                ->getStateUsing(function ($record) {
                                                    $pricingService = new PricingService();
                                                    $pricing = $pricingService->calculateCallPrice($record);
                                                    return $pricing['price_per_minute'];
                                                })
                                                ->money('EUR')
                                                ->size('sm'),
                                        ]),
                                    ]),
                                    
                                    // Marge
                                    Grid::make(3)->schema([
                                        TextEntry::make('margin')
                                            ->label('Marge')
                                            ->getStateUsing(function ($record) {
                                                $cost = $record->cost ?? 0;
                                                $pricingService = new PricingService();
                                                $pricing = $pricingService->calculateCallPrice($record);
                                                $revenue = $pricing['customer_price'];
                                                return $revenue - $cost;
                                            })
                                            ->money('EUR')
                                            ->icon('heroicon-m-calculator')
                                            ->badge()
                                            ->color(fn ($state) => $state > 0 ? 'success' : 'danger'),
                                        
                                        TextEntry::make('margin_percentage')
                                            ->label('Marge %')
                                            ->getStateUsing(function ($record) {
                                                $cost = $record->cost ?? 0;
                                                $pricingService = new PricingService();
                                                $pricing = $pricingService->calculateCallPrice($record);
                                                $revenue = $pricing['customer_price'];
                                                if ($revenue > 0) {
                                                    return round((($revenue - $cost) / $revenue) * 100, 1);
                                                }
                                                return 0;
                                            })
                                            ->formatStateUsing(fn ($state) => $state . '%')
                                            ->icon('heroicon-m-percent-badge')
                                            ->badge()
                                            ->color(fn ($state) => match(true) {
                                                $state >= 70 => 'success',
                                                $state >= 50 => 'warning',
                                                default => 'danger'
                                            }),
                                        
                                        TextEntry::make('service_value_roi')
                                            ->label('Service ROI')
                                            ->getStateUsing(function ($record) {
                                                $cost = $record->cost ?? 0;
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
                                            ->color(fn ($state) => $state > 0 ? 'success' : 'danger')
                                            ->hint('ROI basierend auf gebuchtem Service'),
                                    ]),
                                    
                                    // Hinweis
                                    TextEntry::make('pricing_note')
                                        ->label('Preismodell')
                                        ->state(function ($record) {
                                            $pricingService = new PricingService();
                                            $pricing = $pricingService->calculateCallPrice($record);
                                            
                                            if (isset($pricing['error'])) {
                                                return 'Kein Preismodell konfiguriert';
                                            }
                                            
                                            return "Aktiv: {$pricing['included_minutes']} Inklusivminuten, €" . number_format($pricing['price_per_minute'], 4, ',', '.') . "/Min";
                                        })
                                        ->badge()
                                        ->color(function ($record) {
                                            $pricingService = new PricingService();
                                            $pricing = $pricingService->calculateCallPrice($record);
                                            return isset($pricing['error']) ? 'warning' : 'success';
                                        })
                                        ->icon(function ($record) {
                                            $pricingService = new PricingService();
                                            $pricing = $pricingService->calculateCallPrice($record);
                                            return isset($pricing['error']) ? 'heroicon-o-exclamation-triangle' : 'heroicon-o-check-circle';
                                        })
                                        ->columnSpanFull(),
                                ])
                                ->collapsible(),
                        ]),
                    ]),
                
                // Tool Calls und Transcript Object
                Section::make('Detaillierter Gesprächsverlauf')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->description('Vollständige Konversation mit Tool-Aufrufen und Zeitstempeln')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        ViewEntry::make('transcript_object_viewer')
                            ->label(false)
                            ->view('filament.resources.call-resource.transcript-object-viewer')
                            ->viewData([
                                'transcriptObject' => $this->record->transcript_object ?? (isset($this->record->analysis['tool_calls']) ? $this->record->analysis['tool_calls'] : []),
                                'hasToolCalls' => isset($this->record->analysis['tool_calls']) && !empty($this->record->analysis['tool_calls']),
                            ])
                            ->columnSpanFull()
                            ->visible(fn ($record) => !empty($record->transcript_object) || (isset($record->analysis['tool_calls']) && !empty($record->analysis['tool_calls']))),
                            
                        // Fallback zum einfachen Transkript
                        TextEntry::make('transcript_viewer')
                            ->label(false)
                            ->getStateUsing(function ($record) {
                                if (!$record->transcript) {
                                    return 'Kein Transkript verfügbar';
                                }
                                return new HtmlString($this->formatTranscript($record->transcript));
                            })
                            ->columnSpanFull()
                            ->visible(fn ($record) => empty($record->transcript_object) && (!isset($record->analysis['tool_calls']) || empty($record->analysis['tool_calls'])) && !empty($record->transcript)),
                    ])
                    ->columnSpanFull(),
                
                // Erweiterte technische Metriken
                Section::make('Technische Details & Performance')
                    ->icon('heroicon-o-cpu-chip')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Grid::make(['default' => 1, 'lg' => 2])->schema([
                            // Latenz-Details
                            Section::make('Latenz-Analyse')
                                ->heading(false)
                                ->schema([
                                    Grid::make(2)->schema([
                                        TextEntry::make('llm_latency')
                                            ->label('LLM Latenz (Durchschnitt)')
                                            ->getStateUsing(function ($record) {
                                                if (!isset($record->analysis['latency']['llm']['values'])) {
                                                    return '—';
                                                }
                                                $values = $record->analysis['latency']['llm']['values'];
                                                if (empty($values) || !is_array($values)) return '—';
                                                $avg = array_sum($values) / count($values);
                                                return round($avg) . 'ms';
                                            })
                                            ->icon('heroicon-m-cpu-chip')
                                            ->hint(function ($record) {
                                                if (!isset($record->analysis['latency']['llm'])) {
                                                    return '';
                                                }
                                                $min = $record->analysis['latency']['llm']['min'] ?? 0;
                                                $max = $record->analysis['latency']['llm']['max'] ?? 0;
                                                return "Min: {$min}ms | Max: {$max}ms";
                                            }),
                                            
                                        TextEntry::make('tts_latency')
                                            ->label('TTS Latenz (Durchschnitt)')
                                            ->getStateUsing(function ($record) {
                                                if (!isset($record->analysis['latency']['tts']['values'])) {
                                                    return '—';
                                                }
                                                $values = $record->analysis['latency']['tts']['values'];
                                                if (empty($values) || !is_array($values)) return '—';
                                                $avg = array_sum($values) / count($values);
                                                return round($avg) . 'ms';
                                            })
                                            ->icon('heroicon-m-speaker-wave')
                                            ->hint(function ($record) {
                                                if (!isset($record->analysis['latency']['tts'])) {
                                                    return '';
                                                }
                                                $min = $record->analysis['latency']['tts']['min'] ?? 0;
                                                $max = $record->analysis['latency']['tts']['max'] ?? 0;
                                                return "Min: {$min}ms | Max: {$max}ms";
                                            }),
                                            
                                        TextEntry::make('e2e_latency')
                                            ->label('End-to-End Latenz')
                                            ->getStateUsing(function ($record) {
                                                if (!isset($record->analysis['latency']['e2e']['p50'])) {
                                                    return '—';
                                                }
                                                $p50 = $record->analysis['latency']['e2e']['p50'];
                                                return round($p50) . 'ms';
                                            })
                                            ->icon('heroicon-m-arrow-path')
                                            ->badge()
                                            ->color(function ($record) {
                                                if (!isset($record->analysis['latency']['e2e']['p50'])) {
                                                    return 'gray';
                                                }
                                                $p50 = $record->analysis['latency']['e2e']['p50'];
                                                return match(true) {
                                                    $p50 < 2000 => 'success',
                                                    $p50 < 3000 => 'warning',
                                                    default => 'danger'
                                                };
                                            }),
                                            
                                        TextEntry::make('llm_requests')
                                            ->label('LLM Anfragen')
                                            ->getStateUsing(function ($record) {
                                                if (isset($record->analysis['llm_usage']['num_requests'])) {
                                                    return $record->analysis['llm_usage']['num_requests'];
                                                }
                                                if (isset($record->analysis['latency']['llm']['values']) && is_array($record->analysis['latency']['llm']['values'])) {
                                                    return count($record->analysis['latency']['llm']['values']);
                                                }
                                                return '—';
                                            })
                                            ->icon('heroicon-m-arrow-path-rounded-square'),
                                    ]),
                                    
                                    // Detaillierte Performance Metriken
                                    Grid::make(3)->schema([
                                        TextEntry::make('llm_percentiles')
                                            ->label('LLM Latenz Verteilung')
                                            ->getStateUsing(function ($record) {
                                                if (!isset($record->analysis['latency']['llm'])) {
                                                    return '—';
                                                }
                                                $llm = $record->analysis['latency']['llm'];
                                                $html = '<div class="text-xs space-y-1">';
                                                $html .= '<div>P50: ' . round($llm['p50'] ?? 0) . 'ms</div>';
                                                $html .= '<div>P90: ' . round($llm['p90'] ?? 0) . 'ms</div>';
                                                $html .= '<div>P99: ' . round($llm['p99'] ?? 0) . 'ms</div>';
                                                $html .= '</div>';
                                                return new HtmlString($html);
                                            }),
                                            
                                        TextEntry::make('tts_percentiles')
                                            ->label('TTS Latenz Verteilung')
                                            ->getStateUsing(function ($record) {
                                                if (!isset($record->analysis['latency']['tts'])) {
                                                    return '—';
                                                }
                                                $tts = $record->analysis['latency']['tts'];
                                                $html = '<div class="text-xs space-y-1">';
                                                $html .= '<div>P50: ' . round($tts['p50'] ?? 0) . 'ms</div>';
                                                $html .= '<div>P90: ' . round($tts['p90'] ?? 0) . 'ms</div>';
                                                $html .= '<div>P99: ' . round($tts['p99'] ?? 0) . 'ms</div>';
                                                $html .= '</div>';
                                                return new HtmlString($html);
                                            }),
                                            
                                        TextEntry::make('token_usage')
                                            ->label('Token Verbrauch')
                                            ->getStateUsing(function ($record) {
                                                if (!isset($record->analysis['llm_token_usage'])) {
                                                    return '—';
                                                }
                                                $usage = $record->analysis['llm_token_usage'];
                                                $avg = round($usage['average'] ?? 0);
                                                $total = array_sum($usage['values'] ?? []);
                                                $html = '<div class="text-xs space-y-1">';
                                                $html .= '<div>Durchschnitt: ' . $avg . '</div>';
                                                $html .= '<div>Gesamt: ' . number_format($total) . '</div>';
                                                $html .= '<div>Anfragen: ' . ($usage['num_requests'] ?? count($usage['values'] ?? [])) . '</div>';
                                                $html .= '</div>';
                                                return new HtmlString($html);
                                            }),
                                    ]),
                                ])
                                ->extraAttributes(['class' => 'bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4']),
                            
                            // Kosten-Breakdown
                            Section::make('Detaillierte Kostenanalyse')
                                ->heading(false)
                                ->schema([
                                    TextEntry::make('cost_breakdown')
                                        ->label('Kostenaufschlüsselung')
                                        ->getStateUsing(function ($record) {
                                            $breakdown = $record->cost_breakdown ?? $record->analysis['cost_breakdown'] ?? null;
                                            
                                            if (empty($breakdown) || !is_array($breakdown)) {
                                                return 'Keine Details verfügbar';
                                            }
                                            
                                            $html = '<div class="space-y-3">';
                                            
                                            // Gesamtkosten in EUR
                                            $totalEur = $breakdown['combined_cost_euros'] ?? ($record->cost ?? 0);
                                            $totalCents = $breakdown['combined_cost_cents'] ?? $breakdown['combined_cost'] ?? 0;
                                            $exchangeRate = $breakdown['exchange_rate'] ?? 0.92;
                                            
                                            $html .= '<div class="border-b pb-2">';
                                            $html .= '<div class="flex justify-between items-center">';
                                            $html .= '<span class="font-bold text-lg">Gesamtkosten:</span>';
                                            $html .= '<span class="font-bold text-lg text-green-600">€' . number_format($totalEur, 4, ',', '.') . '</span>';
                                            $html .= '</div>';
                                            $html .= '<div class="text-xs text-gray-500 mt-1">';
                                            $html .= $totalCents . ' cents = $' . number_format($totalCents / 100, 4) . ' × ' . $exchangeRate . ' (Kurs)';
                                            $html .= '</div>';
                                            $html .= '</div>';
                                            
                                            // Einzelposten
                                            if (isset($breakdown['product_costs']) && is_array($breakdown['product_costs'])) {
                                                $html .= '<div class="space-y-2">';
                                                $html .= '<div class="text-sm font-medium text-gray-700 dark:text-gray-300">Einzelposten:</div>';
                                                
                                                foreach ($breakdown['product_costs'] as $cost) {
                                                    if (!is_array($cost)) continue;
                                                    
                                                    $product = match($cost['product'] ?? '') {
                                                        'elevenlabs_tts' => '🔊 Text-to-Speech (ElevenLabs)',
                                                        'gemini_2_0_flash' => '🤖 AI Model (Gemini 2.0 Flash)',
                                                        'deepgram_stt' => '🎤 Speech-to-Text (Deepgram)',
                                                        'openai_whisper' => '🎤 Speech-to-Text (Whisper)',
                                                        default => $cost['product'] ?? 'Unbekannt'
                                                    };
                                                    
                                                    $costEuros = $cost['cost_euros'] ?? (($cost['cost'] ?? 0) / 100 * $exchangeRate);
                                                    $costCents = $cost['cost_cents'] ?? $cost['cost'] ?? 0;
                                                    $unitPrice = $cost['unit_price'] ?? 0;
                                                    
                                                    $html .= '<div class="bg-gray-50 dark:bg-gray-800 rounded p-2">';
                                                    $html .= '<div class="flex justify-between items-start">';
                                                    $html .= '<div>';
                                                    $html .= '<div class="font-medium">' . $product . '</div>';
                                                    $html .= '<div class="text-xs text-gray-500">';
                                                    $html .= 'Einheitspreis: $' . number_format($unitPrice, 6) . '/Min';
                                                    $html .= '</div>';
                                                    $html .= '</div>';
                                                    $html .= '<div class="text-right">';
                                                    $html .= '<div class="font-medium">€' . number_format($costEuros, 4, ',', '.') . '</div>';
                                                    $html .= '<div class="text-xs text-gray-500">' . $costCents . ' cents</div>';
                                                    $html .= '</div>';
                                                    $html .= '</div>';
                                                    $html .= '</div>';
                                                }
                                                
                                                $html .= '</div>';
                                            }
                                            
                                            // Zusätzliche Infos
                                            if (isset($breakdown['total_duration_seconds'])) {
                                                $html .= '<div class="mt-3 pt-3 border-t text-xs text-gray-500">';
                                                $html .= '<div>Abrechnungsdauer: ' . $breakdown['total_duration_seconds'] . ' Sek. (' . round($breakdown['total_duration_seconds'] / 60, 2) . ' Min.)</div>';
                                                if (isset($breakdown['converted_at'])) {
                                                    $html .= '<div>Konvertiert am: ' . \Carbon\Carbon::parse($breakdown['converted_at'])->format('d.m.Y H:i') . '</div>';
                                                }
                                                $html .= '</div>';
                                            }
                                            
                                            $html .= '</div>';
                                            return new HtmlString($html);
                                        })
                                        ->columnSpanFull(),
                                ])
                                ->extraAttributes(['class' => 'bg-green-50 dark:bg-green-900/20 rounded-lg p-4']),
                        ]),
                        
                        // Retell-spezifische Daten
                        Section::make('Retell.ai Metadaten')
                            ->heading(false)
                            ->schema([
                                Grid::make(3)->schema([
                                    TextEntry::make('agent_id')
                                        ->label('Agent ID')
                                        ->copyable()
                                        ->placeholder('—')
                                        ->extraAttributes(['class' => 'font-mono'])
                                        ->size('sm'),
                                        
                                    TextEntry::make('agent_version')
                                        ->label('Agent Version')
                                        ->placeholder('—')
                                        ->badge(),
                                        
                                    TextEntry::make('opt_out_sensitive_data')
                                        ->label('Datenschutz')
                                        ->formatStateUsing(fn ($state) => $state ? '🔒 Opt-out aktiv' : '📂 Normal')
                                        ->badge()
                                        ->color(fn ($state) => $state ? 'warning' : 'success'),
                                ]),
                                
                                // Public Log URL
                                TextEntry::make('public_log_url')
                                    ->label('Öffentliches Anrufprotokoll')
                                    ->url(fn ($record) => $record->public_log_url)
                                    ->openUrlInNewTab()
                                    ->visible(fn ($record) => !empty($record->public_log_url))
                                    ->icon('heroicon-m-arrow-top-right-on-square')
                                    ->columnSpanFull(),
                                    
                                // Telephony Identifier
                                TextEntry::make('telephony_id')
                                    ->label('Telephony Identifier')
                                    ->getStateUsing(function ($record) {
                                        if (!isset($record->analysis['telephony_identifier'])) {
                                            return '—';
                                        }
                                        
                                        $telephony = $record->analysis['telephony_identifier'];
                                        $html = '<div class="text-xs space-y-1">';
                                        
                                        if (isset($telephony['twilio_call_sid'])) {
                                            $html .= '<div><strong>Twilio SID:</strong> ' . $telephony['twilio_call_sid'] . '</div>';
                                        }
                                        
                                        foreach ($telephony as $key => $value) {
                                            if ($key !== 'twilio_call_sid' && !is_array($value)) {
                                                $html .= '<div><strong>' . $key . ':</strong> ' . $value . '</div>';
                                            }
                                        }
                                        
                                        $html .= '</div>';
                                        return new HtmlString($html);
                                    })
                                    ->columnSpanFull(),
                                
                                // Metadata
                                TextEntry::make('metadata_display')
                                    ->label('Zusätzliche Metadaten')
                                    ->getStateUsing(function ($record) {
                                        $metadata = $record->metadata ?? [];
                                        if (empty($metadata)) return 'Keine Metadaten';
                                        
                                        return new HtmlString(
                                            '<pre class="text-xs bg-gray-100 dark:bg-gray-800 p-2 rounded overflow-auto">' . 
                                            json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . 
                                            '</pre>'
                                        );
                                    })
                                    ->columnSpanFull()
                                    ->visible(fn ($record) => !empty($record->metadata)),
                            ])
                            ->columnSpanFull()
                            ->extraAttributes(['class' => 'bg-gray-50 dark:bg-gray-800/50 rounded-lg p-4']),
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