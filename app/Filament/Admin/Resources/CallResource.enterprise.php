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
        return true;
    }
    
    public static function canView($record): bool
    {
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
                    ->sortable()
                    ->url(fn ($record) => $record->customer 
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
                    
                Tables\Columns\BadgeColumn::make('sentiment')
                    ->label('Stimmung')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'positive' => 'Positiv',
                        'negative' => 'Negativ',
                        'neutral' => 'Neutral',
                        default => '—'
                    })
                    ->color(fn ($state) => match($state) {
                        'positive' => 'success',
                        'negative' => 'danger',
                        'neutral' => 'gray',
                        default => 'secondary'
                    })
                    ->icon(fn ($state) => match($state) {
                        'positive' => 'heroicon-m-face-smile',
                        'negative' => 'heroicon-m-face-frown',
                        'neutral' => 'heroicon-m-minus-circle',
                        default => 'heroicon-m-question-mark-circle'
                    })
                    ->toggleable(),
                    
                Tables\Columns\BadgeColumn::make('appointment_made')
                    ->label('Termin')
                    ->formatStateUsing(fn ($state, $record) => match(true) {
                        $record->appointment_made => 'Gebucht',
                        $record->appointment_requested => 'Angefragt',
                        default => 'Kein Termin'
                    })
                    ->color(fn ($state, $record) => match(true) {
                        $record->appointment_made => 'success',
                        $record->appointment_requested => 'warning',
                        default => 'gray'
                    })
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('cost')
                    ->label('Kosten')
                    ->money('EUR')
                    ->sortable()
                    ->alignEnd()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\TextColumn::make('branch.name')
                    ->label('Filiale')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\TextColumn::make('company.name')
                    ->label('Firma')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // Existing filters...
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->iconButton(),
                    
                Tables\Actions\Action::make('share')
                    ->icon('heroicon-o-share')
                    ->color('gray')
                    ->iconButton()
                    ->modalContent(fn ($record) => view('filament.modals.share-call', ['record' => $record]))
                    ->modalHeading('Anruf teilen')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Schließen'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
        
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
                // ENTERPRISE HEADER DESIGN - Modern, Clean, Professional
                Infolists\Components\Section::make()
                    ->schema([
                        Infolists\Components\Grid::make([
                            'default' => 1,
                            'md' => 3,
                        ])
                        ->schema([
                            // Left - Customer Info
                            Infolists\Components\Group::make([
                                Infolists\Components\TextEntry::make('customer_header')
                                    ->hiddenLabel()
                                    ->getStateUsing(function ($record) {
                                        $customerName = $record->customer?->name 
                                            ?? $record->extracted_name
                                            ?? $record->metadata['customer_data']['full_name']
                                            ?? 'Unbekannter Anrufer';
                                        
                                        $interest = '';
                                        if ($record->reason_for_visit) {
                                            $interest = Str::limit($record->reason_for_visit, 50);
                                        } elseif ($record->appointment_requested) {
                                            $interest = 'Terminanfrage';
                                        } elseif ($record->mlPrediction?->intent) {
                                            $interest = ucfirst($record->mlPrediction->intent);
                                        }
                                        
                                        return new HtmlString("
                                            <div class='space-y-1'>
                                                <h1 class='text-2xl font-bold text-gray-900 dark:text-white'>$customerName</h1>
                                                <p class='text-lg text-gray-600 dark:text-gray-400'>$interest</p>
                                            </div>
                                        ");
                                    }),
                                    
                                Infolists\Components\TextEntry::make('phone_badge')
                                    ->hiddenLabel()
                                    ->getStateUsing(fn ($record) => $record->from_number)
                                    ->badge()
                                    ->color('gray')
                                    ->icon('heroicon-m-phone')
                                    ->copyable()
                                    ->extraAttributes(['class' => 'mt-2']),
                            ])
                            ->columnSpan(['default' => 1, 'md' => 2]),
                            
                            // Right - Key Metrics
                            Infolists\Components\Group::make([
                                Infolists\Components\Grid::make(2)
                                    ->schema([
                                        // Call Status
                                        Infolists\Components\TextEntry::make('status_metric')
                                            ->hiddenLabel()
                                            ->getStateUsing(function ($record) {
                                                $icon = match($record->call_status) {
                                                    'completed' => '<svg class="w-5 h-5 text-green-500 inline" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>',
                                                    'failed' => '<svg class="w-5 h-5 text-red-500 inline" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>',
                                                    default => '<svg class="w-5 h-5 text-gray-500 inline" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z" clip-rule="evenodd"></path></svg>'
                                                };
                                                
                                                $label = match($record->call_status) {
                                                    'completed' => 'Erfolgreich',
                                                    'failed' => 'Fehlgeschlagen',
                                                    default => 'In Bearbeitung'
                                                };
                                                
                                                return new HtmlString("
                                                    <div class='text-center'>
                                                        <div class='text-sm text-gray-500 dark:text-gray-400'>Status</div>
                                                        <div class='mt-1'>$icon <span class='ml-1 font-medium'>$label</span></div>
                                                    </div>
                                                ");
                                            }),
                                            
                                        // Duration
                                        Infolists\Components\TextEntry::make('duration_metric')
                                            ->hiddenLabel()
                                            ->getStateUsing(function ($record) {
                                                $duration = $record->duration_sec ? gmdate('i:s', $record->duration_sec) : '00:00';
                                                return new HtmlString("
                                                    <div class='text-center'>
                                                        <div class='text-sm text-gray-500 dark:text-gray-400'>Dauer</div>
                                                        <div class='text-lg font-semibold mt-1'>$duration</div>
                                                    </div>
                                                ");
                                            }),
                                            
                                        // Sentiment
                                        Infolists\Components\TextEntry::make('sentiment_metric')
                                            ->hiddenLabel()
                                            ->getStateUsing(function ($record) {
                                                $sentiment = $record->mlPrediction?->sentiment_label ?? $record->sentiment ?? 'neutral';
                                                $score = $record->mlPrediction?->sentiment_score ?? 0;
                                                
                                                $color = match($sentiment) {
                                                    'positive' => 'green',
                                                    'negative' => 'red',
                                                    default => 'gray'
                                                };
                                                
                                                $label = match($sentiment) {
                                                    'positive' => 'Positiv',
                                                    'negative' => 'Negativ',
                                                    default => 'Neutral'
                                                };
                                                
                                                return new HtmlString("
                                                    <div class='text-center'>
                                                        <div class='text-sm text-gray-500 dark:text-gray-400'>Stimmung</div>
                                                        <div class='mt-1'>
                                                            <span class='inline-flex items-center gap-1'>
                                                                <span class='w-2 h-2 rounded-full bg-$color-500'></span>
                                                                <span class='font-medium'>$label</span>
                                                            </span>
                                                        </div>
                                                    </div>
                                                ");
                                            }),
                                            
                                        // Date
                                        Infolists\Components\TextEntry::make('date_metric')
                                            ->hiddenLabel()
                                            ->getStateUsing(function ($record) {
                                                $date = $record->created_at->format('d.m.Y');
                                                $time = $record->created_at->format('H:i');
                                                return new HtmlString("
                                                    <div class='text-center'>
                                                        <div class='text-sm text-gray-500 dark:text-gray-400'>Zeitpunkt</div>
                                                        <div class='font-medium mt-1'>$time</div>
                                                    </div>
                                                ");
                                            }),
                                    ]),
                            ])
                            ->columnSpan(1),
                        ]),
                    ])
                    ->extraAttributes([
                        'class' => 'bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-900 rounded-xl p-6 mb-6 shadow-sm',
                    ]),
                
                // MAIN CONTENT AREA - Split Layout
                Infolists\Components\Split::make([
                    // LEFT COLUMN - Primary Information
                    Infolists\Components\Group::make([
                        // Call Summary Card
                        Infolists\Components\Section::make('Anrufzusammenfassung')
                            ->description('Wichtigste Informationen aus dem Gespräch')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                // Call Reason
                                Infolists\Components\TextEntry::make('reason_display')
                                    ->label('Anrufgrund')
                                    ->getStateUsing(fn ($record) => 
                                        $record->reason_for_visit ?? 
                                        $record->summary ?? 
                                        'Nicht erfasst'
                                    )
                                    ->prose()
                                    ->extraAttributes(['class' => 'text-gray-700 dark:text-gray-300']),
                                    
                                // Key Points
                                Infolists\Components\ViewEntry::make('key_points')
                                    ->label('Wichtige Punkte')
                                    ->view('filament.infolists.key-points-list')
                                    ->visible(fn ($record) => !empty($record->analysis)),
                                    
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
                                            ->getStateUsing(fn ($record) => 
                                                $record->analysis['urgency'] ?? 'Normal'
                                            )
                                            ->badge()
                                            ->color(fn ($state) => match($state) {
                                                'Hoch' => 'danger',
                                                'Mittel' => 'warning',
                                                default => 'gray'
                                            }),
                                    ])
                                    ->visible(fn ($record) => 
                                        $record->appointment_requested || 
                                        !empty($record->analysis['urgency'])
                                    ),
                            ])
                            ->collapsible()
                            ->collapsed(false),
                            
                        // Audio & Transcript Section
                        Infolists\Components\Section::make('Gesprächsaufzeichnung')
                            ->description('Audio-Aufnahme und Transkript')
                            ->icon('heroicon-o-microphone')
                            ->schema([
                                // Audio Player
                                Infolists\Components\ViewEntry::make('audio')
                                    ->label(false)
                                    ->view('filament.components.audio-player-enterprise')
                                    ->visible(fn ($record) => 
                                        !empty($record->audio_url) || 
                                        !empty($record->recording_url)
                                    ),
                                    
                                // Transcript
                                Infolists\Components\ViewEntry::make('transcript')
                                    ->label(false)
                                    ->view('filament.infolists.transcript-viewer-enterprise')
                                    ->visible(fn ($record) => 
                                        !empty($record->transcript) || 
                                        !empty($record->transcript_object)
                                    ),
                            ])
                            ->collapsible()
                            ->collapsed(false),
                    ])
                    ->grow(true),
                    
                    // RIGHT COLUMN - Secondary Information
                    Infolists\Components\Group::make([
                        // Customer Information
                        Infolists\Components\Section::make('Kundeninformationen')
                            ->icon('heroicon-o-user')
                            ->schema([
                                Infolists\Components\TextEntry::make('customer_info')
                                    ->hiddenLabel()
                                    ->getStateUsing(function ($record) {
                                        $name = $record->customer?->name ?? 
                                               $record->extracted_name ?? 
                                               'Nicht erfasst';
                                        
                                        $status = $record->first_visit ? 
                                                 'Neukunde' : 'Bestandskunde';
                                        
                                        $company = $record->customer?->company_name ??
                                                  $record->metadata['customer_data']['company_name'] ?? '';
                                        
                                        $html = "<div class='space-y-2'>";
                                        $html .= "<div class='font-medium text-gray-900 dark:text-white'>$name</div>";
                                        
                                        if ($company) {
                                            $html .= "<div class='text-sm text-gray-600 dark:text-gray-400'>$company</div>";
                                        }
                                        
                                        $html .= "<div class='inline-flex items-center gap-2 mt-2'>";
                                        $html .= "<span class='px-2 py-1 text-xs rounded-full " . 
                                                ($record->first_visit ? 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300' : 
                                                'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300') . 
                                                "'>$status</span>";
                                        
                                        if ($record->no_show_count > 0) {
                                            $html .= "<span class='px-2 py-1 text-xs rounded-full bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300'>";
                                            $html .= "{$record->no_show_count} No-Shows</span>";
                                        }
                                        
                                        $html .= "</div></div>";
                                        
                                        return new HtmlString($html);
                                    }),
                                    
                                // Contact Details
                                Infolists\Components\Grid::make(1)
                                    ->schema([
                                        Infolists\Components\TextEntry::make('email_info')
                                            ->label('E-Mail')
                                            ->getStateUsing(fn ($record) => 
                                                $record->customer?->email ?? 
                                                $record->extracted_email ?? 
                                                '—'
                                            )
                                            ->copyable()
                                            ->icon('heroicon-m-envelope'),
                                            
                                        Infolists\Components\TextEntry::make('address_info')
                                            ->label('Adresse')
                                            ->getStateUsing(fn ($record) => 
                                                $record->customer?->address ?? '—'
                                            )
                                            ->icon('heroicon-m-map-pin')
                                            ->visible(fn ($record) => $record->customer?->address),
                                    ])
                                    ->extraAttributes(['class' => 'mt-4']),
                                    
                                // Customer Actions
                                Infolists\Components\Actions::make([
                                    Infolists\Components\Actions\Action::make('view_customer')
                                        ->label('Kunde anzeigen')
                                        ->icon('heroicon-m-arrow-top-right-on-square')
                                        ->url(fn ($record) => 
                                            $record->customer ? 
                                            CustomerResource::getUrl('view', [$record->customer]) : 
                                            null
                                        )
                                        ->visible(fn ($record) => $record->customer),
                                        
                                    Infolists\Components\Actions\Action::make('create_customer')
                                        ->label('Kunde anlegen')
                                        ->icon('heroicon-m-user-plus')
                                        ->color('primary')
                                        ->url(fn ($record) => CustomerResource::getUrl('create', [
                                            'data' => [
                                                'name' => $record->extracted_name ?? '',
                                                'email' => $record->extracted_email ?? '',
                                                'phone' => $record->from_number,
                                            ]
                                        ]))
                                        ->visible(fn ($record) => 
                                            !$record->customer && 
                                            ($record->extracted_name || $record->from_number)
                                        ),
                                ])
                                ->fullWidth()
                                ->extraAttributes(['class' => 'mt-4']),
                            ])
                            ->compact(),
                            
                        // Appointment Information
                        Infolists\Components\Section::make('Termininformationen')
                            ->icon('heroicon-o-calendar-days')
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
                                        ->url(fn ($record) => 
                                            $record->appointment ? 
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
                                            ]
                                        ]))
                                        ->visible(fn ($record) => 
                                            !$record->appointment && 
                                            $record->appointment_requested
                                        ),
                                ])
                                ->fullWidth()
                                ->extraAttributes(['class' => 'mt-4']),
                            ])
                            ->compact()
                            ->visible(fn ($record) => 
                                $record->appointment || 
                                $record->appointment_requested
                            ),
                            
                        // Analytics & Insights
                        Infolists\Components\Section::make('Analyse & Einblicke')
                            ->icon('heroicon-o-chart-bar')
                            ->schema([
                                // ML Predictions
                                Infolists\Components\Grid::make(1)
                                    ->schema([
                                        // Sentiment Analysis
                                        Infolists\Components\TextEntry::make('ml_sentiment')
                                            ->label('Sentiment-Analyse')
                                            ->getStateUsing(function ($record) {
                                                if (!$record->mlPrediction) {
                                                    return '—';
                                                }
                                                
                                                $sentiment = ucfirst($record->mlPrediction->sentiment_label);
                                                $confidence = round($record->mlPrediction->prediction_confidence * 100);
                                                $score = number_format($record->mlPrediction->sentiment_score, 2);
                                                
                                                return "$sentiment (Score: $score, Konfidenz: $confidence%)";
                                            }),
                                            
                                        // Intent Detection
                                        Infolists\Components\TextEntry::make('ml_intent')
                                            ->label('Erkannte Absicht')
                                            ->getStateUsing(fn ($record) => 
                                                ucfirst($record->mlPrediction?->intent ?? 
                                                $record->analysis['intent'] ?? 
                                                '—')
                                            ),
                                            
                                        // Key Features
                                        Infolists\Components\ViewEntry::make('ml_features')
                                            ->label('Wichtige Faktoren')
                                            ->view('filament.infolists.ml-features-list')
                                            ->visible(fn ($record) => 
                                                $record->mlPrediction?->top_features
                                            ),
                                    ]),
                                    
                                // Cost Analysis
                                Infolists\Components\TextEntry::make('cost_display')
                                    ->label('Anrufkosten')
                                    ->money('EUR')
                                    ->icon('heroicon-m-currency-euro')
                                    ->visible(fn ($record) => $record->cost > 0),
                            ])
                            ->compact()
                            ->visible(fn ($record) => 
                                $record->mlPrediction || 
                                !empty($record->analysis) || 
                                $record->cost > 0
                            ),
                    ])
                    ->grow(false),
                ])
                ->from('md'),
                
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
        return static::getModel()::whereDate('created_at', today())->count();
    }
    
    public static function getNavigationBadgeColor(): ?string
    {
        return static::getModel()::whereDate('created_at', today())->count() > 0 ? 'primary' : 'gray';
    }
    
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['customer', 'appointment', 'company', 'mlPrediction']);
    }
}