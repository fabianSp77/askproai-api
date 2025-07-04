<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\CallResource\Pages;
use App\Filament\Admin\Resources\CallResource\Widgets;
use App\Filament\Admin\Resources\Concerns\HasManyColumns;
use App\Filament\Admin\Traits\HasConsistentNavigation;
use App\Models\Call;
use App\Models\Customer;
use App\Models\Appointment;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Support\Enums\FontWeight;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Support\Str;
use Illuminate\Support\HtmlString;
use App\Filament\Admin\Resources\CustomerResource;
use App\Filament\Admin\Resources\AppointmentResource;
use Illuminate\Support\Facades\Log;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;
use Filament\Tables\Enums\FiltersLayout;

class CallResource extends Resource
{
    use HasManyColumns;
    
    protected static ?string $model = Call::class;
    protected static ?string $navigationIcon = 'heroicon-o-phone-arrow-down-left';
    protected static ?string $navigationLabel = 'Anrufe';
    protected static ?string $navigationGroup = 'Täglicher Betrieb';
    protected static ?int $navigationSort = 10;
    protected static bool $shouldRegisterNavigation = true;
    protected static ?string $recordTitleAttribute = 'call_id';
    
    public static function canViewAny(): bool
    {
        // Temporarily bypass permission check
        return true;
    }
    
    public static function canView($record): bool
    {
        // Allow viewing calls even without company_id for now
        return true;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query
                ->withoutGlobalScope(\App\Scopes\TenantScope::class)
                ->withoutGlobalScope(\App\Models\Scopes\CompanyScope::class)
                ->with(['customer', 'appointment', 'branch', 'company', 'mlPrediction'])
            )
            // ->poll('60s') // Disabled to eliminate performance warnings
            ->striped()
            ->defaultSort('start_timestamp', 'desc')
            ->extremePaginationLinks()
            ->paginated([10, 25, 50])
            ->paginationPageOptions([10, 25, 50])
            ->defaultPaginationPageOption(25)
            ->recordClasses(fn ($record) => match($record->sentiment) {
                'positive' => 'border-l-4 border-green-500',
                'negative' => 'border-l-4 border-red-500',
                default => '',
            })
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
                    ->placeholder('Unbekannt')
                    ->icon('heroicon-m-identification')
                    ->iconColor('success')
                    ->url(fn ($record) => $record?->customer ? 
                        route('filament.admin.resources.customers.edit', ['record' => $record->customer]) : null)
                    ->getStateUsing(fn ($record) => $record?->customer?->name ?? '-')
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('company.name')
                    ->label('Company')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('branch.name')
                    ->label('Branch')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('user_sentiment')
                    ->label('Stimmung')
                    ->getStateUsing(function (Call $record) {
                        // First try user_sentiment from Retell
                        if ($record->user_sentiment) {
                            return match(strtolower($record->user_sentiment)) {
                                'positive' => 'Positiv',
                                'negative' => 'Negativ',
                                'neutral' => 'Neutral',
                                default => 'Unbekannt'
                            };
                        }
                        
                        // Fallback to analysis sentiment
                        $sentiment = $record->analysis['sentiment'] ?? null;
                        if (!$sentiment) return 'Nicht analysiert';
                        
                        return match($sentiment) {
                            'positive' => 'Positiv',
                            'negative' => 'Negativ',
                            'neutral' => 'Neutral',
                            default => 'Unbekannt'
                        };
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Positiv' => 'success',
                        'Negativ' => 'danger',
                        'Neutral' => 'gray',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'Positiv' => 'heroicon-m-face-smile',
                        'Negativ' => 'heroicon-m-face-frown', 
                        'Neutral' => 'heroicon-m-minus-circle',
                        default => 'heroicon-m-question-mark-circle',
                    })
                    ->toggleable(),
                    
                Tables\Columns\IconColumn::make('call_successful')
                    ->label('Erfolgreich')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\TextColumn::make('urgency_level')
                    ->label('Dringlichkeit')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'high' => 'danger',
                        'medium' => 'warning',
                        'low' => 'success',
                        default => 'gray',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\TextColumn::make('call_status')
                    ->label('Status')
                    ->sortable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'completed' => 'success',
                        'in_progress' => 'warning',
                        'failed' => 'danger',
                        'analyzed' => 'info',
                        default => 'gray',
                    })
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('duration_sec')
                    ->label('Dauer')
                    ->sortable()
                    ->formatStateUsing(function ($state) {
                        if (!$state) return '—';
                        $minutes = floor($state / 60);
                        $seconds = $state % 60;
                        return sprintf('%d:%02d', $minutes, $seconds);
                    })
                    ->badge()
                    ->color(fn ($state) => match(true) {
                        !$state => 'gray',
                        $state >= 180 => 'success',
                        $state >= 60 => 'warning',
                        default => 'danger'
                    })
                    ->icon('heroicon-m-clock')
                    ->iconColor(fn ($state) => match(true) {
                        !$state => 'gray',
                        $state >= 180 => 'success',
                        $state >= 60 => 'warning',
                        default => 'danger'
                    })
                    ->toggleable(),
                    
                Tables\Columns\TagsColumn::make('tags')
                    ->label('Tags')
                    ->getStateUsing(function (Call $record) {
                        $tags = [];
                        
                        // Extrahiere Tags aus der Analyse
                        if (isset($record->analysis['tags'])) {
                            $tags = array_merge($tags, $record->analysis['tags']);
                        }
                        
                        // Füge automatische Tags hinzu
                        if ($record->appointment_id) {
                            $tags[] = 'Termin gebucht';
                        }
                        
                        if ($record->duration_sec > 300) {
                            $tags[] = 'Langes Gespräch';
                        }
                        
                        return array_unique($tags);
                    })
                    ->separator(',')
                    ->toggleable(),
                    
                Tables\Columns\IconColumn::make('has_recording')
                    ->label('Aufnahme')
                    ->getStateUsing(fn ($record) => !empty($record->audio_url))
                    ->boolean()
                    ->trueIcon('heroicon-o-speaker-wave')
                    ->falseIcon('heroicon-o-speaker-x-mark')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('cost')
                    ->label('Kosten')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 2, ',', '.') . ' €' : '—')
                    ->sortable()
                    ->badge()
                    ->color('warning')
                    ->icon('heroicon-m-currency-euro')
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\TextColumn::make('appointment.starts_at')
                    ->label('Gebuchter Termin')
                    ->dateTime('d.m.Y H:i')
                    ->placeholder('Kein Termin')
                    ->icon('heroicon-m-calendar-days')
                    ->iconColor('info')
                    ->url(fn ($record) => $record->appointment ? AppointmentResource::getUrl('edit', ['record' => $record->appointment]) : null)
                    ->toggleable(),
                    
                Tables\Columns\IconColumn::make('converted_to_appointment')
                    ->label('Konvertiert')
                    ->getStateUsing(fn ($record) => !is_null($record->appointment_id))
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->tooltip(fn ($record) => $record->appointment_id ? 'Erfolgreich zu Termin konvertiert' : 'Kein Termin gebucht')
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('urgency')
                    ->label('Dringlichkeit')
                    ->getStateUsing(function (Call $record) {
                        return $record->analysis['urgency'] ?? 'normal';
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'high' => 'danger',
                        'medium' => 'warning',
                        'low', 'normal' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'high' => 'Hoch',
                        'medium' => 'Mittel',
                        'low' => 'Niedrig',
                        'normal' => 'Normal',
                        default => ucfirst($state),
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                // Weitere wichtige Spalten (standardmäßig ausgeblendet)
                Tables\Columns\TextColumn::make('call_id')
                    ->label('Call ID')
                    ->searchable()
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\TextColumn::make('retell_call_id')
                    ->label('Retell ID')
                    ->searchable()
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\TextColumn::make('to_number')
                    ->label('Angerufene Nummer')
                    ->copyable()
                    ->icon('heroicon-m-phone')
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                // Tables\Columns\TextColumn::make('agent.name')
                //     ->label('AI Agent')
                //     ->placeholder('Standard Agent')
                //     ->icon('heroicon-m-cpu-chip')
                //     ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\TextColumn::make('end_timestamp')
                    ->label('Anrufende')
                    ->dateTime('d.m.Y H:i:s')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\TextColumn::make('call_type')
                    ->label('Anruftyp')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'inbound' => 'info',
                        'outbound' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'inbound' => 'Eingehend',
                        'outbound' => 'Ausgehend',
                        default => ucfirst($state),
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\TextColumn::make('disconnection_reason')
                    ->label('Beendigungsgrund')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\TextColumn::make('intent')
                    ->label('Erkannte Absicht')
                    ->getStateUsing(function (Call $record) {
                        return $record->analysis['intent'] ?? null;
                    })
                    ->placeholder('Nicht erkannt')
                    ->badge()
                    ->color('info')
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\TextColumn::make('summary')
                    ->label('Zusammenfassung')
                    ->getStateUsing(function (Call $record) {
                        return $record->analysis['summary'] ?? null;
                    })
                    ->placeholder('Keine Zusammenfassung')
                    ->limit(50)
                    ->tooltip(function (Call $record) {
                        return $record->analysis['summary'] ?? null;
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\TextColumn::make('extracted_email')
                    ->label('E-Mail (erkannt)')
                    ->getStateUsing(function (Call $record) {
                        return $record->analysis['entities']['email'] ?? null;
                    })
                    ->placeholder('—')
                    ->copyable()
                    ->icon('heroicon-m-envelope')
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\TextColumn::make('extracted_name')
                    ->label('Name (erkannt)')
                    ->getStateUsing(function (Call $record) {
                        return $record->analysis['entities']['name'] ?? null;
                    })
                    ->placeholder('—')
                    ->icon('heroicon-m-user')
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\TextColumn::make('extracted_date')
                    ->label('Datum (erkannt)')
                    ->getStateUsing(function (Call $record) {
                        return $record->analysis['entities']['date'] ?? null;
                    })
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                // Customer Data Fields
                Tables\Columns\TextColumn::make('insurance_info')
                    ->label('Versicherung')
                    ->getStateUsing(function (Call $record) {
                        $parts = [];
                        if ($record->insurance_type) {
                            $parts[] = $record->insurance_type;
                        }
                        if ($record->insurance_company || $record->health_insurance_company) {
                            $parts[] = $record->insurance_company ?? $record->health_insurance_company;
                        }
                        if ($record->versicherungsstatus) {
                            $parts[] = $record->versicherungsstatus;
                        }
                        return implode(' - ', $parts) ?: null;
                    })
                    ->placeholder('Keine Versicherungsdaten')
                    ->icon('heroicon-m-shield-check')
                    ->iconColor('info')
                    ->wrap()
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('reason_for_visit')
                    ->label('Anrufgrund')
                    ->placeholder('Nicht angegeben')
                    ->icon('heroicon-m-clipboard-document-list')
                    ->limit(50)
                    ->tooltip(fn ($state) => $state)
                    ->toggleable(),
                    
                Tables\Columns\IconColumn::make('appointment_requested')
                    ->label('Termin angefragt')
                    ->boolean()
                    ->trueIcon('heroicon-o-calendar')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('warning')
                    ->falseColor('gray')
                    ->toggleable(),
                    
                Tables\Columns\IconColumn::make('appointment_made')
                    ->label('Termin gebucht')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('urgency_level')
                    ->label('Dringlichkeit')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'high', 'hoch' => 'danger',
                        'medium', 'mittel' => 'warning',
                        'low', 'niedrig' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match (strtolower($state)) {
                        'high' => 'Hoch',
                        'medium' => 'Mittel',
                        'low' => 'Niedrig',
                        'hoch' => 'Hoch',
                        'mittel' => 'Mittel',
                        'niedrig' => 'Niedrig',
                        default => ucfirst($state),
                    })
                    ->toggleable(),
                    
                Tables\Columns\ViewColumn::make('customer_data')
                    ->label('Kundendaten')
                    ->view('filament.tables.columns.customer-data-preview')
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\ViewColumn::make('custom_analysis_data')
                    ->label('Analyse-Daten')
                    ->view('filament.tables.columns.custom-analysis-preview')
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\TextColumn::make('extracted_time')
                    ->label('Uhrzeit (erkannt)')
                    ->getStateUsing(function (Call $record) {
                        return $record->analysis['entities']['time'] ?? null;
                    })
                    ->placeholder('—')
                    ->icon('heroicon-m-clock')
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\TextColumn::make('extracted_service')
                    ->label('Service (erkannt)')
                    ->getStateUsing(function (Call $record) {
                        return $record->analysis['entities']['service'] ?? null;
                    })
                    ->placeholder('—')
                    ->badge()
                    ->color('primary')
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\BooleanColumn::make('appointment_requested')
                    ->label('Termin gewünscht')
                    ->getStateUsing(function (Call $record) {
                        return $record->analysis['appointment_requested'] ?? false;
                    })
                    ->icon('heroicon-m-calendar-days')
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\TextColumn::make('language')
                    ->label('Sprache')
                    ->getStateUsing(function (Call $record) {
                        return $record->analysis['language'] ?? 'de';
                    })
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'de' => 'Deutsch',
                        'en' => 'Englisch',
                        'fr' => 'Französisch',
                        'es' => 'Spanisch',
                        'it' => 'Italienisch',
                        'tr' => 'Türkisch',
                        default => strtoupper($state),
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt')
                    ->dateTime('d.m.Y H:i:s')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Aktualisiert')
                    ->dateTime('d.m.Y H:i:s')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                // Neue Retell.ai Datenfelder
                Tables\Columns\TextColumn::make('cost')
                    ->label('Kosten')
                    ->money('EUR')
                    ->sortable()
                    ->getStateUsing(fn ($record) => $record->cost ?? $record->retell_cost ?? 0)
                    ->summarize([
                        Tables\Columns\Summarizers\Sum::make()
                            ->money('EUR'),
                    ])
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('reason_for_visit')
                    ->label('Anrufgrund')
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->reason_for_visit)
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\TextColumn::make('extracted_name')
                    ->label('Kunde')
                    ->searchable()
                    ->icon('heroicon-m-user')
                    ->formatStateUsing(function ($record) {
                        // First try metadata customer_data
                        if (!empty($record->metadata['customer_data']['full_name'])) {
                            return $record->metadata['customer_data']['full_name'];
                        }
                        // Then customer_data_backup
                        if (!empty($record->customer_data_backup['full_name'])) {
                            return $record->customer_data_backup['full_name'];
                        }
                        // Finally extracted_name
                        return $record->extracted_name;
                    })
                    ->description(function ($record) {
                        // Show company if available
                        $company = $record->metadata['customer_data']['company'] ?? 
                                  $record->customer_data_backup['company'] ?? null;
                        return $company;
                    })
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('health_insurance_company')
                    ->label('Krankenkasse')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\IconColumn::make('has_customer_data')
                    ->label('Kundendaten')
                    ->getStateUsing(fn ($record) => 
                        !empty($record->metadata['customer_data']) || 
                        !empty($record->customer_data_backup) ||
                        !empty($record->custom_analysis_data['customer_data_backup'])
                    )
                    ->boolean()
                    ->trueIcon('heroicon-o-user-circle')
                    ->falseIcon('heroicon-o-user')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->tooltip(function ($record) {
                        if (!empty($record->metadata['customer_data'])) {
                            $data = $record->metadata['customer_data'];
                            return $data['full_name'] ?? 'Kundendaten vorhanden';
                        }
                        return 'Keine Kundendaten';
                    })
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('agent_name')
                    ->label('AI Agent')
                    ->limit(25)
                    ->tooltip(fn ($record) => $record->agent_name)
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\TextColumn::make('end_to_end_latency')
                    ->label('Latenz')
                    ->suffix(' ms')
                    ->numeric()
                    ->color(fn ($state) => match(true) {
                        !$state => 'gray',
                        $state <= 1500 => 'success',
                        $state <= 3000 => 'warning',
                        default => 'danger'
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\TextColumn::make('appointment_made')
                    ->label('Termin erstellt')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? 'Ja' : 'Nein')
                    ->color(fn ($state) => $state ? 'success' : 'gray')
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\TextColumn::make('public_log_url')
                    ->label('Call Log')
                    ->url(fn ($record) => $record->public_log_url)
                    ->openUrlInNewTab()
                    ->limit(10)
                    ->formatStateUsing(fn () => 'Log')
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->columnToggleFormColumns(2)
            ->columnToggleFormMaxHeight('500px')
            ->filters([
                // Company filter for super admins
                Tables\Filters\SelectFilter::make('company_id')
                    ->label('Unternehmen')
                    ->relationship('company', 'name')
                    ->searchable()
                    ->preload()
                    ->indicator('Unternehmen')
                    ->visible(fn () => auth()->user() && (auth()->user()->hasRole('super_admin') || auth()->user()->hasRole('reseller'))),
                    
                // Branch filter
                Tables\Filters\SelectFilter::make('branch_id')
                    ->label('Filiale')
                    ->relationship('branch', 'name')
                    ->searchable()
                    ->preload()
                    ->indicator('Filiale'),
                    Tables\Filters\Filter::make('date_range')
                        ->form([
                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\DatePicker::make('from')
                                        ->label('Von')
                                        ->displayFormat('d.m.Y')
                                        ->native(false)
                                        ->closeOnDateSelection()
                                        ->placeholder('Startdatum'),
                                    Forms\Components\DatePicker::make('to')
                                        ->label('Bis')
                                        ->displayFormat('d.m.Y')
                                        ->native(false)
                                        ->closeOnDateSelection()
                                        ->placeholder('Enddatum'),
                                ]),
                        ])
                        ->query(function (Builder $query, array $data): Builder {
                            return $query
                                ->when($data['from'] ?? null, fn($q, $v) => $q->whereDate('start_timestamp', '>=', $v))
                                ->when($data['to'] ?? null, fn($q, $v) => $q->whereDate('start_timestamp', '<=', $v));
                        })
                        ->indicateUsing(function (array $data): array {
                            $indicators = [];
                            if ($data['from'] ?? null) {
                                $indicators[] = 'Von: ' . Carbon::parse($data['from'])->format('d.m.Y');
                            }
                            if ($data['to'] ?? null) {
                                $indicators[] = 'Bis: ' . Carbon::parse($data['to'])->format('d.m.Y');
                            }
                            return $indicators;
                        }),
                        
                    Tables\Filters\SelectFilter::make('sentiment')
                        ->label('Stimmung')
                        ->placeholder('Alle Stimmungen')
                        ->options([
                            'positive' => 'Positiv',
                            'negative' => 'Negativ',
                            'neutral' => 'Neutral',
                        ])
                        ->query(function (Builder $query, array $data): Builder {
                            if (!empty($data['value'])) {
                                return $query->whereJsonContains('analysis->sentiment', $data['value']);
                            }
                            return $query;
                        }),
                        
                    Tables\Filters\SelectFilter::make('urgency')
                        ->label('Dringlichkeit')
                        ->placeholder('Alle Dringlichkeiten')
                        ->options([
                            'high' => 'Hoch',
                            'medium' => 'Mittel',
                            'low' => 'Niedrig',
                            'normal' => 'Normal',
                        ])
                        ->query(function (Builder $query, array $data): Builder {
                            if (!empty($data['value'])) {
                                return $query->whereJsonContains('analysis->urgency', $data['value']);
                            }
                            return $query;
                        }),
                        
                    Tables\Filters\Filter::make('duration')
                        ->form([
                            Forms\Components\Select::make('duration_range')
                                ->label('Anrufdauer')
                                ->options([
                                    '0-60' => 'Unter 1 Minute',
                                    '60-180' => '1-3 Minuten',
                                    '180-300' => '3-5 Minuten',
                                    '300+' => 'Über 5 Minuten',
                                ])
                                ->placeholder('Alle Dauern'),
                        ])
                        ->query(function (Builder $query, array $data): Builder {
                            if (!empty($data['duration_range'])) {
                                switch ($data['duration_range']) {
                                    case '0-60':
                                        return $query->where('duration_sec', '<', 60);
                                    case '60-180':
                                        return $query->whereBetween('duration_sec', [60, 180]);
                                    case '180-300':
                                        return $query->whereBetween('duration_sec', [180, 300]);
                                    case '300+':
                                        return $query->where('duration_sec', '>', 300);
                                }
                            }
                            return $query;
                        })
                        ->indicateUsing(function (array $data): ?string {
                            if (!empty($data['duration_range'])) {
                                return match($data['duration_range']) {
                                    '0-60' => 'Dauer: < 1 Min',
                                    '60-180' => 'Dauer: 1-3 Min',
                                    '180-300' => 'Dauer: 3-5 Min',
                                    '300+' => 'Dauer: > 5 Min',
                                    default => null,
                                };
                            }
                            return null;
                        }),
                        
                    Tables\Filters\TernaryFilter::make('appointment_status')
                        ->label('Termin-Status')
                        ->placeholder('Alle Anrufe')
                        ->trueLabel('Mit Termin')
                        ->falseLabel('Ohne Termin')
                        ->queries(
                            true: fn (Builder $query) => $query->whereNotNull('appointment_id'),
                            false: fn (Builder $query) => $query->whereNull('appointment_id'),
                        ),
                        
                    Tables\Filters\Filter::make('phone_number')
                        ->form([
                            Forms\Components\TextInput::make('number')
                                ->label('Telefonnummer')
                                ->placeholder('+49...')
                                ->tel()
                                ->prefixIcon('heroicon-m-phone'),
                        ])
                        ->query(function (Builder $query, array $data): Builder {
                            if (!empty($data['number'])) {
                                $number = str_replace([' ', '-', '(', ')'], '', $data['number']);
                                return $query->where('from_number', 'like', '%' . $number . '%');
                            }
                            return $query;
                        }),
                        
                    Tables\Filters\Filter::make('today')
                        ->label('Heute')
                        ->query(fn (Builder $query): Builder => $query->whereDate('created_at', today()))
                        ->toggle(),
                        
                    Tables\Filters\Filter::make('yesterday')
                        ->label('Gestern')
                        ->query(fn (Builder $query): Builder => $query->whereDate('created_at', today()->subDay()))
                        ->toggle(),
                        
                    Tables\Filters\Filter::make('converted')
                        ->label('Mit Termin')
                        ->query(fn (Builder $query): Builder => $query->whereNotNull('appointment_id'))
                        ->toggle(),
                        
                    Tables\Filters\Filter::make('not_converted')
                        ->label('Ohne Termin')
                        ->query(fn (Builder $query): Builder => $query->whereNull('appointment_id'))
                        ->toggle(),
                        
                    Tables\Filters\Filter::make('long_calls')
                        ->label('Lange Anrufe (>5 Min)')
                        ->query(fn (Builder $query): Builder => $query->where('duration_sec', '>', 300))
                        ->toggle(),
            ], layout: Tables\Enums\FiltersLayout::Dropdown)
            ->filtersFormColumns(3)
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Anzeigen')
                    ->icon('heroicon-m-eye'),
                    
                Tables\Actions\Action::make('create_appointment')
                    ->label('Termin erstellen')
                    ->icon('heroicon-m-calendar-days')
                    ->color('success')
                    ->visible(fn ($record) => !$record->appointment_id && $record->customer_id)
                    ->form([
                        Forms\Components\Section::make('Termindetails')
                            ->description(function ($record) {
                                $extractedData = [];
                                if (isset($record->analysis['entities']['date'])) {
                                    $extractedData[] = 'Datum: ' . $record->analysis['entities']['date'];
                                }
                                if (isset($record->analysis['entities']['time'])) {
                                    $extractedData[] = 'Zeit: ' . $record->analysis['entities']['time'];
                                }
                                if (isset($record->analysis['entities']['service'])) {
                                    $extractedData[] = 'Service: ' . $record->analysis['entities']['service'];
                                }
                                
                                return !empty($extractedData) 
                                    ? 'Erkannte Daten aus dem Anruf: ' . implode(', ', $extractedData)
                                    : 'Keine Termindaten im Anruf erkannt';
                            })
                            ->schema([
                                Forms\Components\DateTimePicker::make('starts_at')
                                    ->label('Termin')
                                    ->required()
                                    ->native(false)
                                    ->displayFormat('d.m.Y H:i')
                                    ->minutesStep(15)
                                    ->minDate(now())
                                    ->default(function ($record) {
                                        // Try to extract date and time from analysis
                                        $date = null;
                                        $time = null;
                                        
                                        if (isset($record->analysis['entities']['date'])) {
                                            try {
                                                $date = Carbon::parse($record->analysis['entities']['date']);
                                            } catch (\Exception $e) {
                                                // Invalid date format
                                            }
                                        }
                                        
                                        if (isset($record->analysis['entities']['time'])) {
                                            try {
                                                $time = Carbon::parse($record->analysis['entities']['time']);
                                            } catch (\Exception $e) {
                                                // Invalid time format
                                            }
                                        }
                                        
                                        if ($date && $time) {
                                            return $date->setTimeFrom($time);
                                        } elseif ($date) {
                                            return $date->setHour(9)->setMinute(0);
                                        }
                                        
                                        return now()->addDay()->setHour(9)->setMinute(0);
                                    })
                                    ->helperText('Wählen Sie Datum und Uhrzeit für den Termin'),
                                    
                                Forms\Components\Select::make('service_id')
                                    ->label('Dienstleistung')
                                    ->options(function ($record) {
                                        return \App\Models\Service::where('company_id', $record->company_id)
                                            ->pluck('name', 'id');
                                    })
                                    ->required()
                                    ->searchable()
                                    ->default(function ($record) {
                                        // Try to match extracted service
                                        if (isset($record->analysis['entities']['service'])) {
                                            $serviceName = $record->analysis['entities']['service'];
                                            $service = \App\Models\Service::where('company_id', $record->company_id)
                                                ->where('name', 'LIKE', "%{$serviceName}%")
                                                ->first();
                                            
                                            if ($service) {
                                                return $service->id;
                                            }
                                        }
                                        
                                        return null;
                                    })
                                    ->helperText('Wählen Sie die gewünschte Dienstleistung'),
                                    
                                Forms\Components\Select::make('staff_id')
                                    ->label('Mitarbeiter')
                                    ->options(function ($record) {
                                        return \App\Models\Staff::where('company_id', $record->company_id)
                                            ->get()
                                            ->mapWithKeys(function ($staff) {
                                                return [$staff->id => $staff->first_name . ' ' . $staff->last_name];
                                            });
                                    })
                                    ->searchable()
                                    ->helperText('Optional: Wählen Sie einen bestimmten Mitarbeiter'),
                                    
                                Forms\Components\Toggle::make('send_confirmation')
                                    ->label('Bestätigung senden')
                                    ->default(true)
                                    ->helperText('E-Mail-Bestätigung an den Kunden senden'),
                                    
                                Forms\Components\Textarea::make('notes')
                                    ->label('Notizen')
                                    ->rows(3)
                                    ->default(function ($record) {
                                        $notes = "Termin erstellt aus Anruf vom " . $record->created_at->format('d.m.Y H:i');
                                        
                                        if (isset($record->analysis['summary'])) {
                                            $notes .= "\n\nAnrufzusammenfassung: " . $record->analysis['summary'];
                                        }
                                        
                                        return $notes;
                                    })
                                    ->helperText('Zusätzliche Notizen zum Termin'),
                            ]),
                    ])
                    ->modalWidth('md')
                    ->modalHeading('Termin aus Anruf erstellen')
                    ->modalSubmitActionLabel('Termin erstellen')
                    ->modalCancelActionLabel('Abbrechen')
                    ->action(function (array $data, Call $record) {
                        $appointment = Appointment::create([
                            'customer_id' => $record->customer_id,
                            'company_id' => $record->company_id,
                            'branch_id' => $record->branch_id,
                            'service_id' => $data['service_id'],
                            'staff_id' => $data['staff_id'] ?? null,
                            'starts_at' => $data['starts_at'],
                            'ends_at' => \Carbon\Carbon::parse($data['starts_at'])->addMinutes(60),
                            'status' => 'scheduled',
                            'notes' => $data['notes'],
                            'source' => 'phone',
                            'call_id' => $record->id,
                        ]);
                        
                        $record->update(['appointment_id' => $appointment->id]);
                        
                        if ($data['send_confirmation'] ?? false) {
                            // Here you would trigger the confirmation email
                        }
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Termin erstellt')
                            ->success()
                            ->body('Der Termin wurde erfolgreich angelegt.')
                            ->actions([
                                \Filament\Notifications\Actions\Action::make('view')
                                    ->label('Termin anzeigen')
                                    ->url(AppointmentResource::getUrl('view', ['record' => $appointment]))
                                    ->button(),
                            ])
                            ->send();
                    }),
                    
                Tables\Actions\Action::make('play_recording')
                    ->label('Anhören')
                    ->icon('heroicon-m-play-circle')
                    ->color('info')
                    ->visible(fn ($record) => !empty($record->audio_url) || !empty($record->recording_url))
                    ->modalContent(function ($record) {
                        $audioUrl = $record->audio_url ?? $record->recording_url;
                        return new HtmlString('
                            <div class="space-y-4">
                                <audio controls class="w-full" id="modal-audio-' . $record->id . '">
                                    <source src="' . $audioUrl . '" type="audio/mpeg">
                                    Ihr Browser unterstützt kein Audio.
                                </audio>
                                
                                <div class="grid grid-cols-4 gap-2">
                                    <button onclick="document.getElementById(\'modal-audio-' . $record->id . '\').playbackRate = 0.5" 
                                        class="px-3 py-2 text-sm rounded-lg bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 transition">
                                        0.5x
                                    </button>
                                    <button onclick="document.getElementById(\'modal-audio-' . $record->id . '\').playbackRate = 1" 
                                        class="px-3 py-2 text-sm rounded-lg bg-primary-600 text-white hover:bg-primary-700 transition">
                                        1x
                                    </button>
                                    <button onclick="document.getElementById(\'modal-audio-' . $record->id . '\').playbackRate = 1.5" 
                                        class="px-3 py-2 text-sm rounded-lg bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 transition">
                                        1.5x
                                    </button>
                                    <button onclick="document.getElementById(\'modal-audio-' . $record->id . '\').playbackRate = 2" 
                                        class="px-3 py-2 text-sm rounded-lg bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 transition">
                                        2x
                                    </button>
                                </div>
                                
                                <div class="flex items-center justify-between pt-4 border-t dark:border-gray-700">
                                    <div class="text-sm text-gray-600 dark:text-gray-400">
                                        <span class="font-medium">Dauer:</span> ' . gmdate('i:s', $record->duration_sec) . ' Min.
                                    </div>
                                    <a href="' . $audioUrl . '" 
                                        download="anruf-' . $record->id . '-' . $record->created_at->format('Y-m-d') . '.mp3" 
                                        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                        </svg>
                                        Download
                                    </a>
                                </div>
                                
                                ' . ($record->public_log_url ? '
                                <div class="pt-4 border-t dark:border-gray-700">
                                    <a href="' . $record->public_log_url . '" 
                                        target="_blank"
                                        class="inline-flex items-center gap-2 text-sm text-primary-600 hover:text-primary-700 dark:text-primary-400">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                        </svg>
                                        Public Log öffnen
                                    </a>
                                </div>
                                ' : '') . '
                            </div>
                        ');
                    })
                    ->modalHeading('Anrufaufzeichnung')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Schließen')
                    ->modalWidth('lg'),
                    
                Tables\Actions\Action::make('share_call')
                    ->label('Teilen')
                    ->icon('heroicon-m-share')
                    ->color('gray')
                    ->visible(fn ($record) => !empty($record->public_log_url))
                    ->modalHeading('Anruf teilen')
                    ->modalContent(function ($record) {
                        $publicUrl = $record->public_log_url ?? '';
                        $customerName = htmlspecialchars($record->customer ? $record->customer->name : 'Unbekannter Anrufer');
                        $phoneNumber = htmlspecialchars($record->from_number ?? 'Keine Nummer');
                        $callDate = $record->start_timestamp ? $record->start_timestamp->format('d.m.Y') : $record->created_at->format('d.m.Y');
                        $callTime = $record->start_timestamp ? $record->start_timestamp->format('H:i') : $record->created_at->format('H:i');
                        $callDateTime = $record->start_timestamp ?? $record->created_at;
                        $duration = gmdate('i:s', $record->duration_sec ?? 0);
                        $durationMinutes = round(($record->duration_sec ?? 0) / 60, 1);
                        
                        // Analyse-Daten
                        $analysis = $record->analysis ?? [];
                        $sentiment = $analysis['sentiment'] ?? 'neutral';
                        $urgency = $analysis['urgency'] ?? 'normal';
                        $appointmentRequested = $analysis['appointment_requested'] ?? false;
                        $entities = $analysis['entities'] ?? [];
                        $summary = $analysis['summary'] ?? 'Keine Zusammenfassung verfügbar';
                        
                        // Sentiment Text und Farben
                        $sentimentData = match($sentiment) {
                            'positive' => ['text' => 'Positiv', 'emoji' => '😊', 'color' => '#10b981', 'bgColor' => '#d1fae5'],
                            'negative' => ['text' => 'Negativ', 'emoji' => '😞', 'color' => '#ef4444', 'bgColor' => '#fee2e2'],
                            'neutral' => ['text' => 'Neutral', 'emoji' => '😐', 'color' => '#6b7280', 'bgColor' => '#f3f4f6'],
                            default => ['text' => 'Unbekannt', 'emoji' => '❓', 'color' => '#6b7280', 'bgColor' => '#f3f4f6']
                        };
                        
                        $urgencyData = match($urgency) {
                            'high' => ['text' => 'Hoch', 'color' => '#dc2626'],
                            'medium' => ['text' => 'Mittel', 'color' => '#f59e0b'],
                            'low' => ['text' => 'Niedrig', 'color' => '#10b981'],
                            default => ['text' => 'Normal', 'color' => '#6b7280']
                        };
                        
                        // Company Info
                        $companyName = $record->company->name ?? 'AskProAI';
                        $branchName = $record->branch->name ?? '';
                        
                        // Audio-URL
                        $audioUrl = $record->audio_url ?? $record->recording_url ?? '';
                        
                        // Professionelles HTML E-Mail Template
                        $emailHtml = '
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Anrufaufzeichnung - ' . $customerName . '</title>
    <!--[if mso]>
    <noscript>
        <xml>
            <o:OfficeDocumentSettings>
                <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
    </noscript>
    <![endif]-->
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, \'Helvetica Neue\', Arial, sans-serif; background-color: #f9fafb;">
    <table cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color: #f9fafb; padding: 20px 0;">
        <tr>
            <td align="center">
                <table cellpadding="0" cellspacing="0" border="0" width="600" style="background-color: #ffffff; border-radius: 12px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1); overflow: hidden;">
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); padding: 40px 30px; text-align: center;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 28px; font-weight: 700;">Anrufaufzeichnung</h1>
                            <p style="margin: 10px 0 0 0; color: #e0e7ff; font-size: 16px;">' . $companyName . ($branchName ? ' - ' . $branchName : '') . '</p>
                        </td>
                    </tr>
                    
                    <!-- Caller Info -->
                    <tr>
                        <td style="padding: 30px;">
                            <table cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color: #f8fafc; border-radius: 8px; padding: 20px;">
                                <tr>
                                    <td>
                                        <h2 style="margin: 0 0 15px 0; color: #1f2937; font-size: 20px; font-weight: 600;">📞 Anrufer-Details</h2>
                                        <table cellpadding="0" cellspacing="0" border="0" width="100%">
                                            <tr>
                                                <td width="50%" style="padding: 8px 0;">
                                                    <span style="color: #6b7280; font-size: 14px;">Name:</span><br>
                                                    <strong style="color: #1f2937; font-size: 16px;">' . $customerName . '</strong>
                                                </td>
                                                <td width="50%" style="padding: 8px 0;">
                                                    <span style="color: #6b7280; font-size: 14px;">Telefon:</span><br>
                                                    <strong style="color: #1f2937; font-size: 16px;">' . $phoneNumber . '</strong>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td width="50%" style="padding: 8px 0;">
                                                    <span style="color: #6b7280; font-size: 14px;">Datum:</span><br>
                                                    <strong style="color: #1f2937; font-size: 16px;">' . $callDate . '</strong>
                                                </td>
                                                <td width="50%" style="padding: 8px 0;">
                                                    <span style="color: #6b7280; font-size: 14px;">Uhrzeit:</span><br>
                                                    <strong style="color: #1f2937; font-size: 16px;">' . $callTime . ' Uhr</strong>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    
                    <!-- Call Analysis -->
                    <tr>
                        <td style="padding: 0 30px 30px;">
                            <h2 style="margin: 0 0 15px 0; color: #1f2937; font-size: 20px; font-weight: 600;">📊 Anruf-Analyse</h2>
                            
                            <!-- Stats Grid -->
                            <table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-bottom: 20px;">
                                <tr>
                                    <td width="33%" style="padding-right: 10px;">
                                        <div style="background-color: #f3f4f6; border-radius: 8px; padding: 15px; text-align: center;">
                                            <div style="color: #6b7280; font-size: 12px; margin-bottom: 5px;">Dauer</div>
                                            <div style="color: #1f2937; font-size: 20px; font-weight: 600;">' . $duration . '</div>
                                            <div style="color: #6b7280; font-size: 12px;">' . $durationMinutes . ' Min.</div>
                                        </div>
                                    </td>
                                    <td width="33%" style="padding: 0 5px;">
                                        <div style="background-color: ' . $sentimentData['bgColor'] . '; border-radius: 8px; padding: 15px; text-align: center;">
                                            <div style="color: #6b7280; font-size: 12px; margin-bottom: 5px;">Stimmung</div>
                                            <div style="color: ' . $sentimentData['color'] . '; font-size: 20px; font-weight: 600;">' . $sentimentData['emoji'] . ' ' . $sentimentData['text'] . '</div>
                                        </div>
                                    </td>
                                    <td width="33%" style="padding-left: 10px;">
                                        <div style="background-color: #f3f4f6; border-radius: 8px; padding: 15px; text-align: center;">
                                            <div style="color: #6b7280; font-size: 12px; margin-bottom: 5px;">Dringlichkeit</div>
                                            <div style="color: ' . $urgencyData['color'] . '; font-size: 20px; font-weight: 600;">' . $urgencyData['text'] . '</div>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                            
                            ' . ($appointmentRequested ? '
                            <div style="background-color: #fef3c7; border-left: 4px solid #f59e0b; padding: 12px 16px; margin-bottom: 20px; border-radius: 4px;">
                                <strong style="color: #92400e;">⚠️ Terminwunsch erkannt</strong>
                                <p style="margin: 5px 0 0 0; color: #78350f; font-size: 14px;">Der Anrufer hat Interesse an einem Termin bekundet.</p>
                            </div>
                            ' : '') . '
                            
                            <!-- Summary -->
                            <div style="background-color: #f8fafc; border-radius: 8px; padding: 20px;">
                                <h3 style="margin: 0 0 10px 0; color: #1f2937; font-size: 16px; font-weight: 600;">📝 Zusammenfassung</h3>
                                <p style="margin: 0; color: #4b5563; line-height: 1.6;">' . nl2br(htmlspecialchars($summary)) . '</p>
                            </div>
                        </td>
                    </tr>
                    
                    <!-- Action Buttons -->
                    <tr>
                        <td style="padding: 0 30px 30px;">
                            <table cellpadding="0" cellspacing="0" border="0" width="100%">
                                <tr>
                                    <td align="center">
                                        <a href="' . $publicUrl . '" style="display: inline-block; background-color: #3b82f6; color: #ffffff; text-decoration: none; padding: 14px 28px; border-radius: 8px; font-weight: 600; font-size: 16px;">🔊 Aufzeichnung anhören</a>
                                    </td>
                                </tr>
                                ' . ($audioUrl ? '
                                <tr>
                                    <td align="center" style="padding-top: 12px;">
                                        <a href="' . $audioUrl . '" style="display: inline-block; background-color: #10b981; color: #ffffff; text-decoration: none; padding: 12px 24px; border-radius: 8px; font-weight: 600; font-size: 14px;">💾 Audio herunterladen</a>
                                    </td>
                                </tr>
                                ' : '') . '
                            </table>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f9fafb; padding: 30px; text-align: center; border-top: 1px solid #e5e7eb;">
                            <p style="margin: 0 0 10px 0; color: #6b7280; font-size: 14px;">Diese E-Mail wurde automatisch von AskProAI generiert.</p>
                            <p style="margin: 0; color: #9ca3af; font-size: 12px;">© ' . date('Y') . ' AskProAI. Alle Rechte vorbehalten.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
';
                        
                        // Erweiterte Plain Text E-Mail mit besserer Formatierung
                        $emailPlainText = "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
                        $emailPlainText .= "📞 ANRUFAUFZEICHNUNG\n";
                        $emailPlainText .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
                        
                        $emailPlainText .= "👤 ANRUFER-DETAILS\n";
                        $emailPlainText .= "─────────────────────────────────────────────────\n";
                        $emailPlainText .= "   Name:     $customerName\n";
                        $emailPlainText .= "   Telefon:  $phoneNumber\n";
                        $emailPlainText .= "   Datum:    $callDate\n";
                        $emailPlainText .= "   Uhrzeit:  $callTime Uhr\n";
                        $emailPlainText .= "   Firma:    $companyName" . ($branchName ? " - $branchName" : "") . "\n\n";
                        
                        $emailPlainText .= "📊 ANRUF-ANALYSE\n";
                        $emailPlainText .= "─────────────────────────────────────────────────\n";
                        $emailPlainText .= "   Dauer:         $duration ($durationMinutes Min.)\n";
                        $emailPlainText .= "   Stimmung:      {$sentimentData['emoji']} {$sentimentData['text']}\n";
                        $emailPlainText .= "   Dringlichkeit: {$urgencyData['text']}\n";
                        if ($appointmentRequested) {
                            $emailPlainText .= "   ⚠️  TERMINWUNSCH ERKANNT!\n";
                        }
                        $emailPlainText .= "\n";
                        
                        if ($summary !== 'Keine Zusammenfassung verfügbar') {
                            $emailPlainText .= "📝 ZUSAMMENFASSUNG\n";
                            $emailPlainText .= "─────────────────────────────────────────────────\n";
                            $emailPlainText .= wordwrap($summary, 65, "\n   ") . "\n\n";
                        }
                        
                        $emailPlainText .= "🔗 LINKS\n";
                        $emailPlainText .= "─────────────────────────────────────────────────\n";
                        $emailPlainText .= "   Aufzeichnung ansehen:\n";
                        $emailPlainText .= "   $publicUrl\n";
                        if ($audioUrl) {
                            $emailPlainText .= "\n   Audio herunterladen:\n";
                            $emailPlainText .= "   $audioUrl\n";
                        }
                        $emailPlainText .= "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
                        $emailPlainText .= "Diese E-Mail wurde automatisch von AskProAI generiert.\n";
                        $emailPlainText .= "© " . date('Y') . " AskProAI. Alle Rechte vorbehalten.\n";
                        $emailPlainText .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━";
                        
                        // WhatsApp Text
                        $whatsappText = "*🎙️ Anrufaufzeichnung*\n\n";
                        $whatsappText .= "*Anrufer:* $customerName\n";
                        $whatsappText .= "*Telefon:* $phoneNumber\n";
                        $whatsappText .= "*Datum:* $callDate, $callTime Uhr\n\n";
                        $whatsappText .= "*📊 Analyse:*\n";
                        $whatsappText .= "• Dauer: $duration\n";
                        $whatsappText .= "• Stimmung: {$sentimentData['emoji']} {$sentimentData['text']}\n";
                        $whatsappText .= "• Dringlichkeit: {$urgencyData['text']}\n";
                        if ($appointmentRequested) {
                            $whatsappText .= "• ⚠️ Terminwunsch\n";
                        }
                        $whatsappText .= "\n*📝 Zusammenfassung:*\n$summary\n\n";
                        $whatsappText .= "🔗 $publicUrl";
                        
                        // URL-encode the HTML for mailto link
                        $emailHtmlEncoded = rawurlencode($emailHtml);
                        
                        return new HtmlString('
                            <div class="fi-modal-content space-y-6 p-6 max-w-3xl mx-auto">
                                <!-- Header mit Gradient -->
                                <div class="relative overflow-hidden rounded-xl bg-gradient-to-br from-primary-600 to-primary-800 p-6 text-white">
                                    <div class="absolute inset-0 bg-black/10"></div>
                                    <div class="relative z-10">
                                        <div class="flex items-center gap-4 mb-4">
                                            <div class="flex h-14 w-14 items-center justify-center rounded-full bg-white/20 backdrop-blur-sm">
                                                <svg class="h-8 w-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                                </svg>
                                            </div>
                                            <div>
                                                <h3 class="text-2xl font-bold">' . $customerName . '</h3>
                                                <p class="text-white/80">' . $phoneNumber . '</p>
                                            </div>
                                        </div>
                                        <div class="grid grid-cols-3 gap-4">
                                            <div class="bg-white/10 backdrop-blur-sm rounded-lg p-3 text-center">
                                                <div class="text-white/70 text-xs mb-1">Datum</div>
                                                <div class="font-semibold">' . $callDate . '</div>
                                                <div class="text-sm text-white/80">' . $callTime . ' Uhr</div>
                                            </div>
                                            <div class="bg-white/10 backdrop-blur-sm rounded-lg p-3 text-center">
                                                <div class="text-white/70 text-xs mb-1">Dauer</div>
                                                <div class="font-semibold text-lg">' . $duration . '</div>
                                                <div class="text-sm text-white/80">' . $durationMinutes . ' Min.</div>
                                            </div>
                                            <div class="bg-white/10 backdrop-blur-sm rounded-lg p-3 text-center">
                                                <div class="text-white/70 text-xs mb-1">Stimmung</div>
                                                <div class="font-semibold text-lg">' . $sentimentData['emoji'] . '</div>
                                                <div class="text-sm text-white/80">' . $sentimentData['text'] . '</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Analyse-Details -->
                                <div class="fi-section rounded-xl bg-gray-50 dark:bg-gray-900/50 p-6">
                                    <h4 class="text-lg font-semibold text-gray-950 dark:text-white mb-4 flex items-center gap-2">
                                        <svg class="h-5 w-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                        </svg>
                                        Anruf-Analyse
                                    </h4>
                                    
                                    <div class="grid grid-cols-2 gap-4 mb-4">
                                        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm">
                                            <div class="flex items-center justify-between">
                                                <span class="text-sm text-gray-600 dark:text-gray-400">Dringlichkeit</span>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" style="background-color: ' . $urgencyData['color'] . '20; color: ' . $urgencyData['color'] . '">
                                                    ' . $urgencyData['text'] . '
                                                </span>
                                            </div>
                                        </div>
                                        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm">
                                            <div class="flex items-center justify-between">
                                                <span class="text-sm text-gray-600 dark:text-gray-400">Terminwunsch</span>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ' . ($appointmentRequested ? 'bg-warning-100 text-warning-800 dark:bg-warning-900/20 dark:text-warning-400' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300') . '">
                                                    ' . ($appointmentRequested ? 'Ja' : 'Nein') . '
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    ' . ($summary !== 'Keine Zusammenfassung verfügbar' ? '
                                    <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm">
                                        <h5 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Zusammenfassung</h5>
                                        <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">' . nl2br(htmlspecialchars($summary)) . '</p>
                                    </div>
                                    ' : '') . '
                                </div>
                                
                                <!-- Share Options mit besserer Gestaltung -->
                                <div class="space-y-4">
                                    <h4 class="text-base font-semibold text-gray-950 dark:text-white flex items-center gap-2">
                                        <svg class="h-5 w-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m9.032 4.026a9.001 9.001 0 010-5.284m-9.032 4.026A8.963 8.963 0 016 12c0-1.18.23-2.305.644-3.342m7.072 6.684a9.001 9.001 0 01-5.432 0m7.072 0c.886-.404 1.692-.938 2.396-1.584M6.284 8.658c.704-.646 1.51-1.18 2.396-1.584m8.036 0A8.963 8.963 0 0120 12c0 1.18-.23 2.305-.644 3.342m-2.64-5.284a9.001 9.001 0 010 5.284"></path>
                                        </svg>
                                        Anruf teilen
                                    </h4>
                                    
                                    <div class="grid gap-3">
                                        <!-- HTML E-Mail Button -->
                                        <button type="button"
                                                onclick="openEmailModal' . $record->id . '()" 
                                                class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-btn-size-lg gap-2 px-4 py-3 text-sm inline-grid shadow-sm bg-primary-600 text-white hover:bg-primary-500 dark:bg-primary-500 dark:hover:bg-primary-400 focus-visible:ring-primary-600/50 dark:focus-visible:ring-primary-400/50">
                                            <svg class="fi-btn-icon h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                            </svg>
                                            <span class="fi-btn-label">Professionelle HTML-E-Mail versenden</span>
                                        </button>
                                        
                                        <!-- Direct Mail Button (Fallback) -->
                                        <a href="mailto:?subject=' . urlencode('Anrufaufzeichnung: ' . $customerName . ' - ' . $callDate) . '&body=' . urlencode($emailPlainText) . '" 
                                           class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-btn-size-md gap-1.5 px-3 py-2 text-sm inline-grid shadow-sm bg-gray-50 text-gray-950 hover:bg-gray-100 dark:bg-white/5 dark:text-white dark:hover:bg-white/10 ring-1 ring-gray-950/10 dark:ring-white/20">
                                            <svg class="fi-btn-icon h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"></path>
                                            </svg>
                                            <span class="fi-btn-label">E-Mail-Client öffnen</span>
                                        </a>
                                        
                                        <!-- WhatsApp Button -->
                                        <a href="https://wa.me/?text=' . urlencode($whatsappText) . '" 
                                           target="_blank"
                                           class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-btn-size-md gap-1.5 px-3 py-2 text-sm inline-grid shadow-sm bg-success-600 text-white hover:bg-success-500 dark:bg-success-500 dark:hover:bg-success-400 focus-visible:ring-success-600/50 dark:focus-visible:ring-success-400/50">
                                            <svg class="fi-btn-icon h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                                            </svg>
                                            <span class="fi-btn-label">WhatsApp</span>
                                        </a>
                                        
                                        <!-- Copy Link Button -->
                                        <button type="button"
                                                onclick="copyShareLink' . $record->id . '()" 
                                                class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-btn-size-md gap-1.5 px-3 py-2 text-sm inline-grid shadow-sm bg-gray-50 text-gray-950 hover:bg-gray-100 dark:bg-white/5 dark:text-white dark:hover:bg-white/10 ring-1 ring-gray-950/10 dark:ring-white/20">
                                            <svg class="fi-btn-icon h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                            </svg>
                                            <span class="fi-btn-label" id="copy-btn-text-' . $record->id . '">Link kopieren</span>
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Link Display mit besserem Design -->
                                <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-4">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Direkter Link zur Aufzeichnung:
                                    </label>
                                    <div class="flex items-center gap-2">
                                        <input id="share-link-' . $record->id . '" 
                                               type="text" 
                                               value="' . $publicUrl . '" 
                                               readonly 
                                               class="fi-input block w-full border-none bg-white dark:bg-gray-800 py-2 pe-3 ps-3 text-sm text-gray-950 transition duration-75 rounded-lg shadow-sm outline-none focus:ring-2 focus:ring-inset disabled:text-gray-500 dark:text-white sm:text-sm sm:leading-6 ring-1 ring-gray-950/10 dark:ring-white/20 font-mono">
                                        <button type="button"
                                                onclick="copyShareLink' . $record->id . '()" 
                                                class="fi-icon-btn relative flex items-center justify-center rounded-lg outline-none transition duration-75 focus-visible:ring-2 disabled:pointer-events-none disabled:opacity-70 h-10 w-10 text-gray-400 hover:text-gray-500 focus-visible:ring-primary-600 dark:text-gray-500 dark:hover:text-gray-400 bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-950/10 dark:ring-white/20">
                                            <svg class="fi-icon-btn-icon h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <script>
                                function copyShareLink' . $record->id . '() {
                                    const input = document.getElementById("share-link-' . $record->id . '");
                                    const btnText = document.getElementById("copy-btn-text-' . $record->id . '");
                                    
                                    input.select();
                                    input.setSelectionRange(0, 99999);
                                    
                                    navigator.clipboard.writeText(input.value).then(() => {
                                        // Zeige Erfolgsmeldung
                                        if (btnText) {
                                            const originalText = btnText.textContent;
                                            btnText.textContent = "✓ Kopiert!";
                                            setTimeout(() => {
                                                btnText.textContent = originalText;
                                            }, 2000);
                                        }
                                        
                                        // Filament notification
                                        window.$wireui?.notify({
                                            title: "Link kopiert!",
                                            description: "Der Link wurde in die Zwischenablage kopiert.",
                                            icon: "success",
                                            timeout: 2500
                                        });
                                    }).catch(() => {
                                        // Fallback für ältere Browser
                                        document.execCommand("copy");
                                        if (btnText) {
                                            const originalText = btnText.textContent;
                                            btnText.textContent = "✓ Kopiert!";
                                            setTimeout(() => {
                                                btnText.textContent = originalText;
                                            }, 2000);
                                        }
                                    });
                                }
                                
                                function openEmailModal' . $record->id . '() {
                                    // Close the share modal
                                    window.dispatchEvent(new CustomEvent("close-modal", { detail: { id: "share-modal" } }));
                                    
                                    // Find the send email action and trigger it
                                    const sendEmailBtn = document.querySelector(\'[wire\\\\:click*="mountTableAction"][wire\\\\:click*="send_email"][wire\\\\:click*="' . $record->id . '"]\');
                                    if (sendEmailBtn) {
                                        sendEmailBtn.click();
                                    } else {
                                        // Fallback: Show notification to use the table action
                                        alert("Bitte nutzen Sie die \'Per E-Mail senden\' Aktion in der Tabelle, um eine HTML-E-Mail zu versenden.");
                                    }
                                }
                                
                                function copyEmailText' . $record->id . '() {
                                    const emailContent = ' . json_encode($emailPlainText) . ';
                                    const btnText = document.getElementById("copy-email-text-' . $record->id . '");
                                    
                                    navigator.clipboard.writeText(emailContent).then(() => {
                                        // Zeige Erfolgsmeldung
                                        if (btnText) {
                                            const originalText = btnText.textContent;
                                            btnText.textContent = "✓ E-Mail-Text kopiert!";
                                            setTimeout(() => {
                                                btnText.textContent = originalText;
                                            }, 2000);
                                        }
                                        
                                        // Show notification with instructions
                                        const notification = document.createElement("div");
                                        notification.className = "fixed bottom-4 right-4 bg-primary-600 text-white p-4 rounded-lg shadow-lg z-50 max-w-md";
                                        notification.innerHTML = `
                                            <div class="flex items-start gap-3">
                                                <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                                <div>
                                                    <h4 class="font-semibold mb-1">E-Mail-Text kopiert!</h4>
                                                    <p class="text-sm text-white/90">Der formatierte Text wurde in die Zwischenablage kopiert. Sie können ihn jetzt in Ihr E-Mail-Programm einfügen.</p>
                                                </div>
                                            </div>
                                        `;
                                        document.body.appendChild(notification);
                                        
                                        setTimeout(() => {
                                            notification.style.opacity = "0";
                                            notification.style.transition = "opacity 0.3s ease-out";
                                            setTimeout(() => {
                                                document.body.removeChild(notification);
                                            }, 300);
                                        }, 4000);
                                    }).catch((err) => {
                                        console.error("Failed to copy:", err);
                                        alert("Der Text konnte nicht kopiert werden. Bitte versuchen Sie es erneut.");
                                    });
                                }
                            </script>
                        ');
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Schließen')
                    ->modalWidth('4xl'),
                    
                Tables\Actions\Action::make('send_email')
                    ->label('Per E-Mail senden')
                    ->icon('heroicon-m-envelope')
                    ->color('primary')
                    ->form([
                        Forms\Components\TextInput::make('recipient_email')
                            ->label('Empfänger E-Mail')
                            ->email()
                            ->required()
                            ->placeholder('beispiel@domain.de'),
                            
                        Forms\Components\TextInput::make('subject')
                            ->label('Betreff')
                            ->default(fn ($record) => 'Anrufaufzeichnung - ' . $record->created_at->format('d.m.Y H:i'))
                            ->required(),
                            
                        Forms\Components\Textarea::make('message')
                            ->label('Nachricht (optional)')
                            ->placeholder('Fügen Sie eine persönliche Nachricht hinzu...')
                            ->rows(3),
                    ])
                    ->action(function ($record, array $data) {
                        try {
                            // Load call with all relations
                            $record->load([
                                'customer',
                                'company',
                                'branch',
                                'staff',
                                'service',
                                'appointment'
                            ]);

                            // Prepare email data
                            $emailData = [
                                'call' => $record,
                                'subject' => $data['subject'],
                                'custom_message' => $data['message'] ?? null,
                                'sender_name' => auth()->user()->name,
                                'sender_email' => auth()->user()->email
                            ];

                            // Send the email
                            \Illuminate\Support\Facades\Mail::to($data['recipient_email'])
                                ->send(new \App\Mail\CallRecordingMail($emailData));

                            \Filament\Notifications\Notification::make()
                                ->title('E-Mail gesendet')
                                ->body('Die E-Mail wurde erfolgreich an ' . $data['recipient_email'] . ' gesendet.')
                                ->success()
                                ->send();

                        } catch (\Exception $e) {
                            \Log::error('Failed to send call recording email', [
                                'call_id' => $record->id,
                                'error' => $e->getMessage()
                            ]);

                            \Filament\Notifications\Notification::make()
                                ->title('Fehler')
                                ->body('Die E-Mail konnte nicht gesendet werden. Bitte versuchen Sie es später erneut.')
                                ->danger()
                                ->send();
                        }
                    })
                    ->modalHeading('Anrufdetails per E-Mail senden')
                    ->modalSubmitActionLabel('E-Mail senden')
                    ->modalCancelActionLabel('Abbrechen'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
        
        // Apply configuration for handling many columns
        return static::configureTableForManyColumns($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCalls::route('/'),
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
                // HERO HEADER - Ultracompact status bar (max 2 lines)
                Infolists\Components\Group::make([
                    Infolists\Components\Grid::make([
                        'default' => 2,
                        'sm' => 3,
                        'md' => 5,
                        'lg' => 6,
                        'xl' => 7,
                    ])
                    ->schema([
                        // Status Badge
                        Infolists\Components\TextEntry::make('call_status')
                            ->hiddenLabel()
                            ->badge()
                            ->size('lg')
                            ->color(fn (string $state): string => match ($state) {
                                'completed' => 'success',
                                'in_progress' => 'warning',
                                'failed' => 'danger',
                                'analyzed' => 'info',
                                default => 'gray',
                            }),
                            
                        // Phone Number (most important)
                        Infolists\Components\TextEntry::make('from_number')
                            ->hiddenLabel()
                            ->size('lg')
                            ->weight(FontWeight::Bold)
                            ->icon('heroicon-m-phone')
                            ->iconColor('primary')
                            ->copyable()
                            ->copyMessage('Kopiert!')
                            ->copyMessageDuration(1500),
                            
                        // Customer Name
                        Infolists\Components\TextEntry::make('customer_display')
                            ->hiddenLabel()
                            ->getStateUsing(function ($record) {
                                $name = $record->customer?->name 
                                    ?? $record->extracted_name
                                    ?? $record->metadata['customer_data']['full_name']
                                    ?? null;
                                    
                                if (!$name) return '—';
                                
                                // Shorten if needed
                                return mb_strlen($name) > 20 ? mb_substr($name, 0, 18) . '...' : $name;
                            })
                            ->icon('heroicon-m-user')
                            ->size('lg'),
                            
                        // Duration
                        Infolists\Components\TextEntry::make('duration_sec')
                            ->hiddenLabel()
                            ->formatStateUsing(fn ($state) => $state ? gmdate('i:s', $state) : '—')
                            ->icon('heroicon-m-clock')
                            ->size('lg'),
                            
                        // Date/Time
                        Infolists\Components\TextEntry::make('start_timestamp')
                            ->hiddenLabel()
                            ->dateTime('d.m. H:i')
                            ->size('lg')
                            ->formatStateUsing(fn ($state, $record) => $state ?? $record->created_at?->format('d.m. H:i')),
                            
                        // Sentiment Emoji
                        Infolists\Components\TextEntry::make('sentiment_icon')
                            ->hiddenLabel()
                            ->getStateUsing(function ($record) {
                                if ($record->mlPrediction) {
                                    return match($record->mlPrediction->sentiment_label) {
                                        'positive' => '😊',
                                        'negative' => '😔',
                                        'neutral' => '😐',
                                        default => ''
                                    };
                                }
                                return match($record->sentiment ?? $record->analysis['sentiment'] ?? null) {
                                    'positive' => '😊',
                                    'negative' => '😔',
                                    'neutral' => '😐',
                                    default => ''
                                };
                            })
                            ->size('lg')
                            ->visible(fn ($record) => $record->mlPrediction || $record->sentiment || isset($record->analysis['sentiment'])),
                            
                        // Urgent Indicator
                        Infolists\Components\TextEntry::make('urgency_indicator')
                            ->hiddenLabel()
                            ->getStateUsing(function ($record) {
                                $urgency = $record->urgency_level 
                                    ?? $record->metadata['urgency']
                                    ?? $record->analysis['urgency']
                                    ?? null;
                                    
                                return match(strtolower($urgency ?? '')) {
                                    'high', 'hoch' => '🚨',
                                    'medium', 'mittel' => '⚠️',
                                    default => ''
                                };
                            })
                            ->size('lg')
                            ->visible(fn ($record) => 
                                in_array(strtolower($record->urgency_level ?? $record->metadata['urgency'] ?? $record->analysis['urgency'] ?? ''), 
                                ['high', 'hoch', 'medium', 'mittel'])
                            ),
                    ]),
                ])
                ->extraAttributes([
                    'class' => 'bg-white dark:bg-gray-900 p-3 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 mb-4',
                ]),
                
                // PRIMARY CONTENT BOX - Call reason and audio
                Infolists\Components\Section::make()
                    ->heading('Anrufgrund & Aufzeichnung')
                    ->headerActions([
                        Infolists\Components\Actions\Action::make('play_audio')
                            ->label('Abspielen')
                            ->icon('heroicon-m-play')
                            ->visible(fn ($record) => !empty($record->audio_url) || !empty($record->recording_url)),
                    ])
                    ->schema([
                        // Call reason/summary - PRIMARY FOCUS
                        Infolists\Components\TextEntry::make('primary_reason')
                            ->label('Anrufgrund')
                            ->getStateUsing(function ($record) {
                                // Priority: ML summary > reason_for_visit > extracted request > analysis intent
                                return $record->mlPrediction?->summary
                                    ?? $record->reason_for_visit
                                    ?? $record->metadata['customer_data']['request']
                                    ?? $record->analysis['intent']
                                    ?? $record->summary
                                    ?? 'Kein spezifischer Grund erfasst';
                            })
                            ->size('lg')
                            ->weight(FontWeight::SemiBold)
                            ->icon('heroicon-m-chat-bubble-left-right')
                            ->iconColor('primary')
                            ->columnSpanFull()
                            ->extraAttributes(['class' => 'text-lg leading-relaxed']),
                            
                        // Audio Player with sentiment timeline
                        Infolists\Components\ViewEntry::make('audio_player')
                            ->label(false)
                            ->view('filament.components.audio-player-sentiment')
                            ->visible(fn ($record) => !empty($record->audio_url) || !empty($record->recording_url))
                            ->columnSpanFull(),
                    ])
                    ->extraAttributes([
                        'class' => 'bg-primary-50 dark:bg-primary-900/10 rounded-lg',
                    ]),
                    
                // ACTION BAR - Clear next steps
                Infolists\Components\Section::make()
                    ->heading(false)
                    ->schema([
                        Infolists\Components\Actions::make([
                            // Create customer if not exists
                            Infolists\Components\Actions\Action::make('create_customer')
                                ->label('Kunde anlegen')
                                ->icon('heroicon-m-user-plus')
                                ->size('lg')
                                ->color('primary')
                                ->visible(fn ($record) => !$record->customer && (
                                    $record->extracted_name || 
                                    isset($record->metadata['customer_data']['full_name'])
                                ))
                                ->url(fn ($record) => CustomerResource::getUrl('create', [
                                    'data' => [
                                        'name' => $record->extracted_name ?? $record->metadata['customer_data']['full_name'] ?? '',
                                        'email' => $record->extracted_email ?? $record->metadata['customer_data']['email'] ?? '',
                                        'phone' => $record->from_number,
                                    ]
                                ])),
                                
                            // Create appointment if requested
                            Infolists\Components\Actions\Action::make('create_appointment')
                                ->label('Termin buchen')
                                ->icon('heroicon-m-calendar-days')
                                ->size('lg')
                                ->color('success')
                                ->visible(fn ($record) => !$record->appointment && $record->appointment_requested)
                                ->url(fn ($record) => AppointmentResource::getUrl('create', [
                                    'data' => [
                                        'customer_id' => $record->customer_id,
                                        'branch_id' => $record->branch_id,
                                        'reason' => $record->reason_for_visit,
                                    ]
                                ])),
                                
                            // View customer if exists
                            Infolists\Components\Actions\Action::make('view_customer')
                                ->label('Kunde anzeigen')
                                ->icon('heroicon-m-eye')
                                ->size('lg')
                                ->color('info')
                                ->visible(fn ($record) => $record->customer)
                                ->url(fn ($record) => $record->customer ? CustomerResource::getUrl('view', ['record' => $record->customer]) : null),
                                
                            // View appointment if exists
                            Infolists\Components\Actions\Action::make('view_appointment')
                                ->label('Termin anzeigen')
                                ->icon('heroicon-m-calendar')
                                ->size('lg')
                                ->color('info')
                                ->visible(fn ($record) => $record->appointment)
                                ->url(fn ($record) => $record->appointment ? AppointmentResource::getUrl('view', ['record' => $record->appointment]) : null),
                        ])
                        ->fullWidth()
                        ->extraAttributes(['class' => 'justify-center gap-4']),
                    ])
                    ->extraAttributes([
                        'class' => 'bg-gray-50 dark:bg-gray-900 rounded-lg shadow-sm mb-4',
                    ])
                    ->visible(fn ($record) => 
                        (!$record->customer && ($record->extracted_name || isset($record->metadata['customer_data']['full_name']))) ||
                        (!$record->appointment && $record->appointment_requested) ||
                        $record->customer ||
                        $record->appointment
                    ),
                    
                // INFORMATION CARDS - Grid layout
                Infolists\Components\Grid::make([
                    'default' => 1,
                    'sm' => 1,
                    'md' => 2,
                    'lg' => 3,
                    'xl' => 3,
                ])
                ->schema([
                    // Customer Information Card
                    Infolists\Components\Section::make()
                        ->heading('Kunde')
                        ->icon('heroicon-m-user')
                        ->schema([
                            Infolists\Components\TextEntry::make('customer_name_display')
                                ->label('Name')
                                ->getStateUsing(function ($record) {
                                    return $record->customer?->name 
                                        ?? $record->extracted_name 
                                        ?? $record->metadata['customer_data']['full_name'] 
                                        ?? 'Nicht erfasst';
                                })
                                ->size('lg')
                                ->weight(FontWeight::Bold),
                                    
                                Infolists\Components\TextEntry::make('customer_email_consolidated')
                                    ->label('E-Mail')
                                    ->getStateUsing(function ($record) {
                                        return $record->customer?->email 
                                            ?? $record->extracted_email 
                                            ?? $record->metadata['customer_data']['email'] 
                                            ?? null;
                                    })
                                    ->placeholder('-')
                                    ->icon('heroicon-m-envelope')
                                    ->copyable(),
                                    
                            Infolists\Components\TextEntry::make('customer_company')
                                ->label('Firma')
                                ->getStateUsing(function ($record) {
                                    return $record->metadata['customer_data']['company_name'] 
                                        ?? $record->metadata['customer_data']['company']
                                        ?? $record->customer?->company_name
                                        ?? null;
                                })
                                ->placeholder('Privatkunde')
                                ->icon('heroicon-m-building-office-2'),
                                
                            // Customer Links
                            Infolists\Components\TextEntry::make('customer_link')
                                ->label('Kundenprofil')
                                ->getStateUsing(fn ($record) => $record->customer ? 'Anzeigen →' : 'Nicht verknüpft')
                                ->url(fn ($record) => $record->customer ? CustomerResource::getUrl('view', ['record' => $record->customer]) : null)
                                ->color(fn ($record) => $record->customer ? 'primary' : 'gray')
                                ->icon('heroicon-m-arrow-top-right-on-square'),
                        ])
                        ->extraAttributes([
                            'class' => 'h-full',
                        ]),
                            
                        // Anrufgrund und Dringlichkeit
                        Infolists\Components\Grid::make(1)
                            ->schema([
                                Infolists\Components\TextEntry::make('reason_consolidated')
                                    ->label('Anrufgrund')
                                    ->getStateUsing(function ($record) {
                                        return $record->reason_for_visit 
                                            ?? $record->metadata['customer_data']['request']
                                            ?? $record->metadata['customer_data']['reason_for_visit']
                                            ?? $record->analysis['intent']
                                            ?? null;
                                    })
                                    ->placeholder('Nicht angegeben')
                                    ->icon('heroicon-m-chat-bubble-left-right')
                                    ->columnSpanFull(),
                            ]),
                            
                        // Status-Badges in einer Reihe
                        Infolists\Components\Grid::make(4)
                            ->schema([
                                Infolists\Components\TextEntry::make('urgency_consolidated')
                                    ->label('Dringlichkeit')
                                    ->getStateUsing(function ($record) {
                                        $urgency = $record->urgency_level 
                                            ?? $record->metadata['customer_data']['urgency']
                                            ?? $record->analysis['urgency']
                                            ?? 'normal';
                                        return strtolower($urgency);
                                    })
                                    ->formatStateUsing(fn ($state) => match($state) {
                                        'high', 'hoch' => 'Hoch',
                                        'medium', 'mittel' => 'Mittel',
                                        'low', 'niedrig' => 'Niedrig',
                                        default => 'Normal',
                                    })
                                    ->badge()
                                    ->color(fn ($state) => match($state) {
                                        'high', 'hoch' => 'danger',
                                        'medium', 'mittel' => 'warning',
                                        'low', 'niedrig' => 'success',
                                        default => 'gray',
                                    })
                                    ->icon(fn ($state) => match($state) {
                                        'high', 'hoch' => 'heroicon-m-exclamation-triangle',
                                        'medium', 'mittel' => 'heroicon-m-exclamation-circle',
                                        default => 'heroicon-m-check-circle',
                                    }),
                                    
                                Infolists\Components\IconEntry::make('appointment_requested')
                                    ->label('Terminwunsch')
                                    ->boolean()
                                    ->trueIcon('heroicon-o-calendar')
                                    ->falseIcon('heroicon-o-x-circle')
                                    ->trueColor('warning')
                                    ->falseColor('gray'),
                                    
                                Infolists\Components\IconEntry::make('appointment_made')
                                    ->label('Termin gebucht')
                                    ->boolean()
                                    ->trueIcon('heroicon-o-check-circle')
                                    ->falseIcon('heroicon-o-x-circle')
                                    ->trueColor('success')
                                    ->falseColor('gray'),
                                    
                                Infolists\Components\IconEntry::make('first_visit')
                                    ->label('Erstbesuch')
                                    ->boolean()
                                    ->trueIcon('heroicon-o-star')
                                    ->falseIcon('heroicon-o-arrow-path')
                                    ->trueColor('info')
                                    ->falseColor('gray'),
                            ]),
                            
                        // Versicherungsinformationen
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('insurance_type')
                                    ->label('Versicherungsart')
                                    ->placeholder('-')
                                    ->icon('heroicon-m-shield-check')
                                    ->visible(fn ($record) => $record->insurance_type || $record->insurance_company),
                                    
                                Infolists\Components\TextEntry::make('insurance_company_consolidated')
                                    ->label('Versicherung')
                                    ->getStateUsing(fn ($record) => 
                                        $record->insurance_company 
                                        ?? $record->health_insurance_company
                                        ?? $record->metadata['customer_data']['insurance_company']
                                        ?? null
                                    )
                                    ->placeholder('-')
                                    ->icon('heroicon-m-building-library')
                                    ->visible(fn ($record) => $record->insurance_type || $record->insurance_company || $record->health_insurance_company),
                                    
                                Infolists\Components\TextEntry::make('versicherungsstatus')
                                    ->label('Status')
                                    ->placeholder('-')
                                    ->badge()
                                    ->color('info')
                                    ->visible(fn ($record) => $record->versicherungsstatus),
                            ])
                            ->visible(fn ($record) => 
                                $record->insurance_type || 
                                $record->insurance_company || 
                                $record->health_insurance_company ||
                                $record->versicherungsstatus
                            ),
                            
                        // Kundenhistorie
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('customer.created_at')
                                    ->label('Kunde seit')
                                    ->dateTime('d.m.Y')
                                    ->placeholder('Neukunde')
                                    ->icon('heroicon-m-calendar-days'),
                                    
                                Infolists\Components\TextEntry::make('no_show_count')
                                    ->label('No-Shows')
                                    ->placeholder('0')
                                    ->badge()
                                    ->color(fn ($state) => match(true) {
                                        $state >= 3 => 'danger',
                                        $state >= 1 => 'warning',
                                        default => 'success',
                                    }),
                                    
                                Infolists\Components\TextEntry::make('customer.appointments_count')
                                    ->label('Termine gesamt')
                                    ->placeholder('0')
                                    ->getStateUsing(fn ($record) => $record->customer?->appointments()->count() ?? 0),
                            ])
                            ->visible(fn ($record) => $record->customer),
                    ])
                    ->collapsible()
                    ->persistCollapsed(false),
                    
                // 3. GESPRÄCHSANALYSE - Alle AI-Insights zusammen
                Infolists\Components\Section::make('Gesprächsanalyse')
                    ->schema([
                        // Zusammenfassung prominent
                        Infolists\Components\TextEntry::make('call_summary_consolidated')
                            ->label('Zusammenfassung')
                            ->getStateUsing(function ($record) {
                                // Priorität: 1. ML Summary, 2. Analysis Summary, 3. Custom Summary
                                if ($record->mlPrediction && $record->mlPrediction->summary) {
                                    return $record->mlPrediction->summary;
                                }
                                if ($record->analysis && isset($record->analysis['call_summary'])) {
                                    return $record->analysis['call_summary'];
                                }
                                if ($record->summary) {
                                    return $record->summary;
                                }
                                return null;
                            })
                            ->placeholder('Keine Zusammenfassung verfügbar')
                            ->columnSpanFull(),
                            
                        // Sentiment und Intent nebeneinander
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('sentiment_consolidated')
                                    ->label('Stimmung')
                                    ->getStateUsing(function ($record) {
                                        if ($record->mlPrediction) {
                                            $confidence = $record->mlPrediction->confidence_percentage;
                                            return $record->mlPrediction->sentiment_label . " ({$confidence})";
                                        }
                                        return $record->sentiment ?? $record->analysis['sentiment'] ?? 'unknown';
                                    })
                                    ->formatStateUsing(fn ($state) => match(true) {
                                        str_contains(strtolower($state), 'positive') => '😊 Positiv',
                                        str_contains(strtolower($state), 'negative') => '😔 Negativ',
                                        str_contains(strtolower($state), 'neutral') => '😐 Neutral',
                                        default => '❓ Unbekannt'
                                    })
                                    ->badge()
                                    ->color(fn ($record) => 
                                        $record->mlPrediction 
                                            ? $record->mlPrediction->sentiment_color
                                            : match(strtolower($record->sentiment ?? 'unknown')) {
                                                'positive' => 'success',
                                                'negative' => 'danger',
                                                'neutral' => 'gray',
                                                default => 'gray',
                                            }
                                    ),
                                    
                                Infolists\Components\TextEntry::make('analysis.intent')
                                    ->label('Erkannte Absicht')
                                    ->placeholder('Nicht erkannt')
                                    ->badge()
                                    ->color('info'),
                                    
                                Infolists\Components\IconEntry::make('call_successful')
                                    ->label('Anruf erfolgreich')
                                    ->boolean()
                                    ->trueIcon('heroicon-o-check-circle')
                                    ->falseIcon('heroicon-o-x-circle')
                                    ->trueColor('success')
                                    ->falseColor('danger')
                                    ->visible(fn ($record) => $record->call_successful !== null),
                            ]),
                            
                        // Transkript mit Sentiment
                        Infolists\Components\ViewEntry::make('transcript')
                            ->label('Transkript')
                            ->view('filament.infolists.transcript-sentiment-viewer')
                            ->columnSpanFull(),
                            
                        // Erkannte Entitäten kompakt
                        Infolists\Components\KeyValueEntry::make('analysis.entities')
                            ->label('Erkannte Informationen')
                            ->keyLabel('Typ')
                            ->valueLabel('Wert')
                            ->getStateUsing(function ($record) {
                                $entities = $record->analysis['entities'] ?? [];
                                if (empty($entities)) return null;
                                
                                $formatted = [];
                                $labels = [
                                    'name' => 'Name',
                                    'email' => 'E-Mail',
                                    'phone' => 'Telefon',
                                    'date' => 'Datum',
                                    'time' => 'Uhrzeit',
                                    'service' => 'Service',
                                ];
                                
                                foreach ($entities as $key => $value) {
                                    if (!empty($value)) {
                                        $formatted[$labels[$key] ?? $key] = $value;
                                    }
                                }
                                
                                return $formatted;
                            })
                            ->visible(fn ($record) => !empty($record->analysis['entities'])),
                    ])
                    ->collapsible()
                    ->persistCollapsed(false)
                    ->visible(fn ($record) => 
                        !empty($record->analysis) || 
                        !empty($record->transcript) || 
                        $record->mlPrediction
                    ),
                    
                // 4. VERKNÜPFUNGEN & AKTIONEN
                Infolists\Components\Section::make('Verknüpfungen & Aktionen')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('customer.name')
                                    ->label('Verknüpfter Kunde')
                                    ->placeholder('Kein Kunde zugeordnet')
                                    ->icon('heroicon-m-user')
                                    ->url(fn ($record) => $record->customer ? CustomerResource::getUrl('view', ['record' => $record->customer]) : null),
                                    
                                Infolists\Components\TextEntry::make('appointment.starts_at')
                                    ->label('Gebuchter Termin')
                                    ->dateTime('d.m.Y H:i')
                                    ->placeholder('Kein Termin gebucht')
                                    ->icon('heroicon-m-calendar-days')
                                    ->url(fn ($record) => $record->appointment ? AppointmentResource::getUrl('view', ['record' => $record->appointment]) : null),
                            ]),
                            
                        // Aktions-Buttons
                        Infolists\Components\Actions::make([
                            Infolists\Components\Actions\Action::make('create_customer')
                                ->label('Kunde anlegen')
                                ->icon('heroicon-m-user-plus')
                                ->color('primary')
                                ->visible(fn ($record) => !$record->customer && ($record->extracted_name || isset($record->metadata['customer_data']['full_name'])))
                                ->url(fn ($record) => CustomerResource::getUrl('create', [
                                    'data' => [
                                        'name' => $record->extracted_name ?? $record->metadata['customer_data']['full_name'] ?? '',
                                        'email' => $record->extracted_email ?? $record->metadata['customer_data']['email'] ?? '',
                                        'phone' => $record->from_number,
                                    ]
                                ])),
                                
                            Infolists\Components\Actions\Action::make('create_appointment')
                                ->label('Termin anlegen')
                                ->icon('heroicon-m-calendar-days')
                                ->color('success')
                                ->visible(fn ($record) => !$record->appointment && $record->appointment_requested)
                                ->url(fn ($record) => AppointmentResource::getUrl('create', [
                                    'data' => [
                                        'customer_id' => $record->customer_id,
                                        'branch_id' => $record->branch_id,
                                        'reason' => $record->reason_for_visit,
                                    ]
                                ])),
                        ]),
                    ])
                    ->collapsible(),
                    
                // 5. TECHNISCHE DETAILS - Collapsed by default
                Infolists\Components\Section::make('Technische Details')
                    ->schema([
                        // IDs und Referenzen
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('call_id')
                                    ->label('Call ID')
                                    ->copyable()
                                    ->copyMessage('ID kopiert!'),
                                    
                                Infolists\Components\TextEntry::make('retell_call_id')
                                    ->label('Retell Call ID')
                                    ->copyable()
                                    ->copyMessage('ID kopiert!'),
                                    
                                Infolists\Components\TextEntry::make('agent_id')
                                    ->label('Agent ID')
                                    ->placeholder('-'),
                            ]),
                            
                        // Agent Information
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('agent_name')
                                    ->label('AI Agent')
                                    ->icon('heroicon-m-cpu-chip')
                                    ->placeholder('Standard Agent'),
                                    
                                Infolists\Components\TextEntry::make('agent_version')
                                    ->label('Version')
                                    ->badge()
                                    ->color('info')
                                    ->placeholder('v1.0'),
                            ]),
                            
                        // Kosten & Performance
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('cost')
                                    ->label('Gesamtkosten')
                                    ->money('EUR')
                                    ->icon('heroicon-m-currency-euro')
                                    ->placeholder('0,00 €'),
                                    
                                Infolists\Components\TextEntry::make('cost_breakdown')
                                    ->label('Kostenaufschlüsselung')
                                    ->getStateUsing(function ($record) {
                                        if (!$record->cost_breakdown) return null;
                                        
                                        $breakdown = is_string($record->cost_breakdown) 
                                            ? json_decode($record->cost_breakdown, true) 
                                            : $record->cost_breakdown;
                                        
                                        if (!$breakdown || !isset($breakdown['product_costs'])) return null;
                                        
                                        $costs = [];
                                        foreach ($breakdown['product_costs'] as $item) {
                                            $costs[] = $item['product'] . ': €' . number_format($item['cost'] / 100, 2);
                                        }
                                        
                                        return implode(' | ', $costs);
                                    })
                                    ->placeholder('Keine Details'),
                            ])
                            ->visible(fn ($record) => $record->cost > 0),
                            
                        // Performance Metriken
                        Infolists\Components\KeyValueEntry::make('latency_metrics')
                            ->label('Latenz-Metriken')
                            ->getStateUsing(function ($record) {
                                if (!$record->latency_metrics) return null;
                                
                                $metrics = is_string($record->latency_metrics) 
                                    ? json_decode($record->latency_metrics, true) 
                                    : $record->latency_metrics;
                                
                                if (!$metrics) return null;
                                
                                $output = [];
                                if (isset($metrics['llm']['p50'])) {
                                    $output['LLM Response'] = $metrics['llm']['p50'] . 'ms';
                                }
                                if (isset($metrics['e2e']['p50'])) {
                                    $output['End-to-End'] = $metrics['e2e']['p50'] . 'ms';
                                }
                                if (isset($metrics['tts']['p50'])) {
                                    $output['Text-to-Speech'] = $metrics['tts']['p50'] . 'ms';
                                }
                                
                                return $output;
                            })
                            ->visible(fn ($record) => !empty($record->latency_metrics)),
                            
                        // Debug Links
                        Infolists\Components\TextEntry::make('public_log_url')
                            ->label('Retell Debug Log')
                            ->url(fn ($record) => $record->public_log_url)
                            ->openUrlInNewTab()
                            ->icon('heroicon-m-arrow-top-right-on-square')
                            ->visible(fn ($record) => !empty($record->public_log_url)),
                    ])
                    ->collapsed()
                    ->persistCollapsed(true),
            ]);
    }
    
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::whereDate('created_at', today())->count();
    }
    
    public static function getNavigationBadgeColor(): ?string
    {
        return static::getModel()::whereDate('created_at', today())->count() > 0 ? 'primary' : 'gray';
    }
    
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['customer', 'appointment', 'company']);
    }
}
