<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\CallResource\Pages;
use App\Filament\Admin\Resources\CallResource\Widgets;
use App\Models\Call;
use App\Models\Customer;
use App\Models\Appointment;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\ActionSize;
use Filament\Tables\Columns\IconColumn\IconColumnSize;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Support\Str;
use Illuminate\Support\HtmlString;
use App\Filament\Admin\Resources\CustomerResource;
use App\Filament\Admin\Resources\AppointmentResource;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Actions\Action;
use Filament\Infolists\Components\Actions\Action as InfolistAction;
use Filament\Notifications\Notification;
use Filament\Support\Colors\Color;

class CallResourceUltimate extends Resource
{
    protected static ?string $model = Call::class;
    protected static ?string $navigationIcon = 'heroicon-o-phone-arrow-down-left';
    protected static ?string $navigationLabel = 'Anrufe';
    protected static ?string $navigationGroup = 'Tagesgeschäft';
    protected static ?int $navigationSort = 10;
    protected static bool $shouldRegisterNavigation = false; // Für Test aktivieren
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
                // Optimiertes Eager Loading mit Single Query Optimization
                ->select([
                    'calls.*',
                    DB::raw('(SELECT COUNT(*) FROM calls c2 WHERE c2.customer_id = calls.customer_id AND c2.id != calls.id) as previous_calls_count'),
                    DB::raw('CASE 
                        WHEN appointment_made = 0 AND created_at < NOW() - INTERVAL 2 HOUR THEN 100
                        WHEN customer_id IS NOT NULL AND created_at < NOW() - INTERVAL 1 HOUR THEN 80
                        WHEN call_status = "error" THEN 60
                        ELSE 20
                    END as priority_score')
                ])
                ->with([
                    'customer:id,name,phone,email',
                    'appointment:id,call_id,starts_at',
                    'branch:id,name',
                    'company:id,name'
                ])
                // Smart Role-based Scope Management
                ->when(
                    auth()->user()?->hasRole('Super Admin'),
                    fn($q) => $q->withoutGlobalScope(\App\Scopes\TenantScope::class)
                               ->withoutGlobalScope(\App\Models\Scopes\CompanyScope::class)
                )
                // Intelligent Sorting mit Priority
                ->orderByDesc('priority_score')
                ->orderByDesc('created_at')
                ->orderByDesc('id')
            )
            ->striped()
            ->defaultSort('priority_score', 'desc')
            ->extremePaginationLinks()
            ->paginated([10, 15, 25])
            ->paginationPageOptions([10, 15, 25])
            ->defaultPaginationPageOption(10)
            ->columns([
                // Priority Indicator - NEU
                Tables\Columns\TextColumn::make('priority_score')
                    ->label('')
                    ->width('10px')
                    ->formatStateUsing(fn ($state) => match(true) {
                        $state >= 80 => new HtmlString('<div class="w-2 h-full bg-red-500 animate-pulse" title="Dringend"></div>'),
                        $state >= 60 => new HtmlString('<div class="w-2 h-full bg-yellow-500" title="Wichtig"></div>'),
                        default => new HtmlString('<div class="w-2 h-full bg-gray-300" title="Normal"></div>'),
                    })
                    ->extraAttributes(['class' => 'p-0']),

                // Mobile-optimiertes Stack Layout mit Context
                Tables\Columns\Layout\Stack::make([
                    Tables\Columns\Layout\Split::make([
                        Tables\Columns\TextColumn::make('created_at')
                            ->label('Anrufzeit')
                            ->dateTime('d.m.Y H:i')
                            ->since()
                            ->size('sm')
                            ->color('gray')
                            ->icon('heroicon-m-calendar')
                            ->iconColor('gray'),
                        
                        // Live Status Indicator
                        Tables\Columns\TextColumn::make('call_status')
                            ->label('Status')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'ended', 'completed' => '✓ Beendet',
                                'error' => '⚠ Fehler',
                                'ongoing' => '🔴 Laufend',
                                default => ucfirst($state),
                            })
                            ->color(fn (string $state): string => match ($state) {
                                'ended', 'completed' => 'success',
                                'error' => 'danger',
                                'ongoing' => 'warning',
                                default => 'gray',
                            })
                            ->extraAttributes(fn ($state) => 
                                $state === 'ongoing' ? ['class' => 'animate-pulse'] : []
                            ),
                    ]),
                    
                    // Customer with Context Badges
                    Tables\Columns\TextColumn::make('customer.name')
                        ->label('Kunde')
                        ->weight(FontWeight::Bold)
                        ->searchable()
                        ->default('Anonymer Anruf')
                        ->url(fn ($record) => $record->customer 
                            ? CustomerResource::getUrl('view', [$record->customer]) 
                            : null
                        )
                        ->description(function ($record) {
                            $badges = [];
                            
                            // Previous calls badge
                            if ($record->previous_calls_count > 0) {
                                $badges[] = "🔄 {$record->previous_calls_count} vorherige";
                            }
                            
                            // Appointment badge
                            if ($record->appointment_made) {
                                $badges[] = "✅ Termin";
                            }
                            
                            // Follow-up needed
                            if ($record->created_at < now()->subHours(2) && !$record->appointment_made) {
                                $badges[] = "⏰ Follow-up";
                            }
                            
                            return implode(' • ', $badges);
                        }),
                    
                    // Contact Info with Quick Actions
                    Tables\Columns\Layout\Split::make([
                        Tables\Columns\TextColumn::make('from_number')
                            ->label('Nummer')
                            ->icon('heroicon-m-phone')
                            ->iconColor('primary')
                            ->copyable()
                            ->copyMessage('Nummer kopiert! 📋')
                            ->formatStateUsing(fn ($state) => 
                                $state ? Str::mask($state, '*', -4, -4) : 'Anonym'
                            )
                            ->size('sm'),
                            
                        Tables\Columns\TextColumn::make('duration_sec')
                            ->label('Dauer')
                            ->formatStateUsing(fn ($state) => 
                                $state ? gmdate('i:s', $state) : '—'
                            )
                            ->badge()
                            ->color(fn ($state) => match(true) {
                                $state > 300 => 'success', // > 5 min
                                $state > 60 => 'warning',  // > 1 min
                                default => 'gray'
                            })
                            ->size('sm'),
                    ]),
                ])->space(1)->visibleFrom('md'),
                
                // Desktop Columns - Optimiert
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Datum')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->icon('heroicon-m-calendar')
                    ->iconColor('primary')
                    ->toggleable()
                    ->hiddenFrom('md'),
                    
                Tables\Columns\TextColumn::make('from_number')
                    ->label('Anrufer')
                    ->sortable()
                    ->searchable()
                    ->icon('heroicon-m-phone')
                    ->iconColor('primary')
                    ->copyable()
                    ->copyMessage('Nummer kopiert! 📋')
                    ->formatStateUsing(fn ($state) => 
                        $state ? Str::mask($state, '*', -4, -4) : 'Anonym'
                    )
                    ->toggleable()
                    ->hiddenFrom('md'),
                    
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Kunde')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Medium)
                    ->url(fn ($record) => $record->customer 
                        ? CustomerResource::getUrl('view', [$record->customer]) 
                        : null
                    )
                    ->default('—')
                    ->toggleable()
                    ->hiddenFrom('md'),
                    
                // Advanced Status Column
                Tables\Columns\Layout\Stack::make([
                    Tables\Columns\IconColumn::make('appointment_made')
                        ->label('Termin')
                        ->boolean()
                        ->trueIcon('heroicon-o-calendar-days')
                        ->falseIcon('heroicon-o-minus-circle')
                        ->trueColor('success')
                        ->falseColor('gray')
                        ->size(IconColumnSize::Small),
                        
                    Tables\Columns\TextColumn::make('call_status')
                        ->label('Status')
                        ->badge()
                        ->formatStateUsing(fn (string $state): string => match ($state) {
                            'ended', 'completed' => 'Beendet',
                            'error' => 'Fehler',
                            'ongoing' => 'Laufend',
                            default => ucfirst($state),
                        })
                        ->color(fn (string $state): string => match ($state) {
                            'ended', 'completed' => 'success',
                            'error' => 'danger',
                            'ongoing' => 'warning',
                            default => 'gray',
                        })
                        ->size('xs'),
                ])->space(1)->alignCenter(),
            ])
            ->filters([
                // Smart Filter mit Presets
                SelectFilter::make('preset')
                    ->label('Quick Filter')
                    ->placeholder('Alle Anrufe')
                    ->options([
                        'urgent' => '🔴 Dringend',
                        'follow_up' => '⏰ Follow-up nötig',
                        'today' => '📅 Heute',
                        'with_appointment' => '✅ Mit Termin',
                        'without_appointment' => '❌ Ohne Termin',
                        'errors' => '⚠️ Fehler',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (!$data['value']) return $query;
                        
                        return match ($data['value']) {
                            'urgent' => $query->whereRaw('created_at < NOW() - INTERVAL 2 HOUR')
                                             ->where('appointment_made', false),
                            'follow_up' => $query->whereRaw('created_at < NOW() - INTERVAL 1 HOUR')
                                                 ->where('appointment_made', false),
                            'today' => $query->whereDate('created_at', today()),
                            'with_appointment' => $query->where('appointment_made', true),
                            'without_appointment' => $query->where('appointment_made', false),
                            'errors' => $query->where('call_status', 'error'),
                            default => $query,
                        };
                    }),
                    
                SelectFilter::make('time_range')
                    ->label('Zeitraum')
                    ->placeholder('Alle Anrufe')
                    ->options([
                        'today' => 'Heute',
                        'yesterday' => 'Gestern',
                        'this_week' => 'Diese Woche',
                        'last_week' => 'Letzte Woche',
                        'this_month' => 'Dieser Monat',
                        'last_month' => 'Letzter Monat',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (!$data['value']) return $query;
                        
                        return match ($data['value']) {
                            'today' => $query->whereDate('created_at', today()),
                            'yesterday' => $query->whereDate('created_at', today()->subDay()),
                            'this_week' => $query->whereBetween('created_at', [
                                now()->startOfWeek(), now()->endOfWeek()
                            ]),
                            'last_week' => $query->whereBetween('created_at', [
                                now()->subWeek()->startOfWeek(), 
                                now()->subWeek()->endOfWeek()
                            ]),
                            'this_month' => $query->whereMonth('created_at', now()->month)
                                ->whereYear('created_at', now()->year),
                            'last_month' => $query->whereMonth('created_at', now()->subMonth()->month)
                                ->whereYear('created_at', now()->subMonth()->year),
                            default => $query,
                        };
                    }),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(2)
            ->persistFiltersInSession()
            ->actions([
                // Compound Workflow Action - NEU
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label('Details')
                        ->icon('heroicon-m-eye'),
                        
                    Tables\Actions\Action::make('complete_workflow')
                        ->label('Follow-up erledigen')
                        ->icon('heroicon-m-check-circle')
                        ->color('success')
                        ->form([
                            Forms\Components\Select::make('action_type')
                                ->label('Aktion')
                                ->options([
                                    'callback_scheduled' => '📞 Rückruf geplant',
                                    'appointment_booked' => '📅 Termin gebucht',
                                    'resolved' => '✅ Gelöst',
                                    'escalated' => '⬆️ Eskaliert',
                                ])
                                ->required(),
                            Forms\Components\Textarea::make('notes')
                                ->label('Notizen')
                                ->placeholder('Kurze Notiz zum Follow-up...')
                                ->rows(2),
                            Forms\Components\DateTimePicker::make('follow_up_date')
                                ->label('Follow-up Datum')
                                ->visible(fn($get) => $get('action_type') === 'callback_scheduled')
                                ->minDate(now())
                        ])
                        ->action(function ($record, array $data) {
                            // Update call with follow-up status
                            $record->update([
                                'follow_up_status' => $data['action_type'],
                                'follow_up_notes' => $data['notes'] ?? null,
                            ]);
                            
                            // Success celebration
                            Notification::make()
                                ->title('Follow-up erledigt! 🎉')
                                ->body(match($data['action_type']) {
                                    'appointment_booked' => 'Termin wurde erfolgreich gebucht! 📅',
                                    'callback_scheduled' => 'Rückruf ist geplant! 📞',
                                    'resolved' => 'Super! Problem gelöst! ✅',
                                    'escalated' => 'An Manager weitergeleitet! ⬆️',
                                    default => 'Aktion erfolgreich!',
                                })
                                ->success()
                                ->duration(5000)
                                ->send();
                                
                            // Konfetti animation via JavaScript
                            if ($data['action_type'] === 'appointment_booked') {
                                session()->flash('celebrate', true);
                            }
                        }),
                        
                    Tables\Actions\Action::make('call_customer')
                        ->label('Anrufen')
                        ->icon('heroicon-m-phone-arrow-up-right')
                        ->color('primary')
                        ->visible(fn ($record) => !empty($record->from_number))
                        ->url(fn ($record) => 'tel:' . $record->from_number)
                        ->openUrlInNewTab(),
                        
                    Tables\Actions\Action::make('quick_note')
                        ->label('Notiz')
                        ->icon('heroicon-m-pencil-square')
                        ->color('gray')
                        ->form([
                            Forms\Components\Textarea::make('note')
                                ->label('')
                                ->placeholder('Schnelle Notiz...')
                                ->rows(2)
                                ->required(),
                        ])
                        ->action(function ($record, array $data) {
                            $record->notes()->create([
                                'content' => $data['note'],
                                'user_id' => auth()->id(),
                            ]);
                            
                            Notification::make()
                                ->title('Notiz gespeichert! 📝')
                                ->success()
                                ->duration(3000)
                                ->send();
                        }),
                ])
                ->button()
                ->label('Aktionen')
                ->icon('heroicon-m-ellipsis-horizontal')
                ->color('gray')
                ->size('sm')
                ->extraAttributes(['class' => 'touch:min-w-[44px] touch:min-h-[44px]']),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('mark_completed')
                    ->label('Als erledigt markieren')
                    ->icon('heroicon-m-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(fn ($records) => $records->each->update(['follow_up_status' => 'completed']))
                    ->deselectRecordsAfterCompletion(),
            ])
            ->poll('30s')
            ->heading('Anruf-Zentrale')
            ->description('Manage alle eingehenden Anrufe mit KI-unterstützten Insights')
            ->emptyStateIcon('heroicon-o-phone-x-mark')
            ->emptyStateHeading('Noch keine Anrufe')
            ->emptyStateDescription('Sobald Anrufe eingehen, erscheinen sie hier automatisch.')
            ->emptyStateActions([
                Action::make('simulate')
                    ->label('Demo-Anruf simulieren')
                    ->icon('heroicon-m-play')
                    ->action(function () {
                        // Create demo call
                        Call::create([
                            'call_id' => 'demo_' . uniqid(),
                            'from_number' => '+49151' . rand(10000000, 99999999),
                            'duration_sec' => rand(30, 300),
                            'call_status' => 'ended',
                            'company_id' => auth()->user()->company_id,
                            'created_at' => now()->subMinutes(rand(5, 120)),
                        ]);
                        
                        Notification::make()
                            ->title('Demo-Anruf erstellt! 📞')
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // Hero Section mit Quick Actions und Personality
                Infolists\Components\Section::make()
                    ->key('hero-section')
                    ->schema([
                        Infolists\Components\Split::make([
                            Infolists\Components\Grid::make(2)
                                ->schema([
                                    Infolists\Components\TextEntry::make('customer.name')
                                        ->label('Kunde')
                                        ->size('lg')
                                        ->weight(FontWeight::Bold)
                                        ->default('Anonymer Anruf')
                                        ->icon('heroicon-m-user')
                                        ->iconColor('primary')
                                        ->url(fn ($record) => $record->customer 
                                            ? CustomerResource::getUrl('view', [$record->customer]) 
                                            : null),
                                        
                                    Infolists\Components\TextEntry::make('created_at')
                                        ->label('Anrufzeit')
                                        ->dateTime('d.m.Y um H:i:s Uhr')
                                        ->since()
                                        ->size('lg')
                                        ->icon('heroicon-m-calendar')
                                        ->iconColor('primary'),
                                ]),
                                
                            Infolists\Components\Actions::make([
                                InfolistAction::make('call_back')
                                    ->label('Zurückrufen')
                                    ->icon('heroicon-m-phone-arrow-up-right')
                                    ->color('success')
                                    ->size(ActionSize::Large)
                                    ->visible(fn ($record) => !empty($record->from_number))
                                    ->url(fn ($record) => 'tel:' . $record->from_number)
                                    ->openUrlInNewTab()
                                    ->extraAttributes(['class' => 'hover:scale-105 transition-transform']),
                                    
                                InfolistAction::make('book_appointment')
                                    ->label('Termin buchen')
                                    ->icon('heroicon-m-calendar-days')
                                    ->color('primary')
                                    ->size(ActionSize::Large)
                                    ->visible(fn ($record) => !$record->appointment_made && $record->customer)
                                    ->url(fn ($record) => AppointmentResource::getUrl('create', [
                                        'customer_id' => $record->customer_id
                                    ]))
                                    ->extraAttributes(['class' => 'hover:scale-105 transition-transform']),
                                    
                                InfolistAction::make('send_email')
                                    ->label('E-Mail senden')
                                    ->icon('heroicon-m-envelope')
                                    ->color('gray')
                                    ->size(ActionSize::Large)
                                    ->visible(fn ($record) => $record->customer?->email)
                                    ->url(fn ($record) => 'mailto:' . $record->customer->email)
                                    ->extraAttributes(['class' => 'hover:scale-105 transition-transform']),
                            ])->verticalAlignment('start'),
                        ]),
                    ])
                    ->headerActions([
                        InfolistAction::make('refresh')
                            ->label('Aktualisieren')
                            ->icon('heroicon-m-arrow-path')
                            ->color('gray')
                            ->action(fn () => redirect(request()->url()))
                            ->extraAttributes(['class' => 'hover:rotate-180 transition-transform duration-500']),
                    ]),
                    
                // Kompakte Status-Übersicht mit Visual Feedback
                Infolists\Components\Section::make('Anruf-Ergebnis')
                    ->key('call-results')
                    ->icon('heroicon-o-chart-bar')
                    ->description('Wichtige Metriken dieses Anrufs')
                    ->schema([
                        Infolists\Components\Grid::make(4)
                            ->schema([
                                Infolists\Components\TextEntry::make('duration_sec')
                                    ->label('Gesprächsdauer')
                                    ->formatStateUsing(function ($state) {
                                        if (!$state) return 'Nicht verfügbar';
                                        $minutes = floor($state / 60);
                                        $seconds = $state % 60;
                                        
                                        // Add personality based on duration
                                        $emoji = match(true) {
                                            $state > 300 => '🏆', // > 5 min
                                            $state > 180 => '👍', // > 3 min
                                            $state > 60 => '⏱️',  // > 1 min
                                            default => '⚡'
                                        };
                                        
                                        return sprintf('%s %d:%02d Min', $emoji, $minutes, $seconds);
                                    })
                                    ->icon('heroicon-m-clock')
                                    ->badge()
                                    ->color(fn($state) => match(true) {
                                        $state > 300 => 'success',
                                        $state > 60 => 'warning',
                                        default => 'gray'
                                    }),
                                    
                                Infolists\Components\IconEntry::make('appointment_made')
                                    ->label('Termin vereinbart')
                                    ->boolean()
                                    ->trueIcon('heroicon-o-check-circle')
                                    ->falseIcon('heroicon-o-x-circle')
                                    ->trueColor('success')
                                    ->falseColor('gray')
                                    ->size('xl')
                                    ->extraAttributes(fn($state) => 
                                        $state ? ['class' => 'animate-bounce-once'] : []
                                    ),
                                    
                                Infolists\Components\TextEntry::make('call_status')
                                    ->label('Anruf-Status')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        'ended', 'completed' => '✅ Erfolgreich beendet',
                                        'error' => '⚠️ Fehler aufgetreten',
                                        'ongoing' => '🔴 Noch laufend',
                                        default => ucfirst($state),
                                    })
                                    ->color(fn (string $state): string => match ($state) {
                                        'ended', 'completed' => 'success',
                                        'error' => 'danger',
                                        'ongoing' => 'warning',
                                        default => 'gray',
                                    }),
                                    
                                Infolists\Components\TextEntry::make('from_number')
                                    ->label('Telefonnummer')
                                    ->icon('heroicon-m-phone')
                                    ->copyable()
                                    ->copyMessage('Nummer kopiert! 📋')
                                    ->formatStateUsing(fn ($state) => 
                                        $state ? preg_replace('/(\d{4})(\d{3})(\d+)/', '+49 $1 $2 $3', $state) : 'Anonym'
                                    ),
                            ]),
                    ])
                    ->collapsible()
                    ->persistCollapsed(),
                    
                // Progressive Disclosure für weitere Details
                Infolists\Components\Tabs::make('call_details')
                    ->tabs([
                        Infolists\Components\Tabs\Tab::make('appointment')
                            ->label('Termindetails')
                            ->icon('heroicon-m-calendar-days')
                            ->visible(fn ($record) => $record->appointment_made)
                            ->schema([
                                Infolists\Components\Grid::make(3)
                                    ->schema([
                                        Infolists\Components\TextEntry::make('appointment.starts_at')
                                            ->label('Terminzeit')
                                            ->dateTime('d.m.Y um H:i Uhr')
                                            ->icon('heroicon-m-calendar-days')
                                            ->badge()
                                            ->color('success'),
                                            
                                        Infolists\Components\TextEntry::make('appointment.service.name')
                                            ->label('Gebuchter Service')
                                            ->icon('heroicon-m-briefcase')
                                            ->default('—'),
                                            
                                        Infolists\Components\TextEntry::make('appointment.branch.name')
                                            ->label('Filiale')
                                            ->icon('heroicon-m-building-office')
                                            ->default('—'),
                                    ]),
                            ]),
                            
                        Infolists\Components\Tabs\Tab::make('recording')
                            ->label('Aufzeichnung')
                            ->icon('heroicon-m-microphone')
                            ->visible(fn ($record) => 
                                $record->recording_url || 
                                $record->audio_url || 
                                ($record->webhook_data['recording_url'] ?? null)
                            )
                            ->schema([
                                Infolists\Components\TextEntry::make('recording_url')
                                    ->label('')
                                    ->formatStateUsing(function ($state, $record) {
                                        $url = $record->recording_url ?? $record->audio_url ?? 
                                               ($record->webhook_data['recording_url'] ?? null);
                                        if (!$url) return null;
                                        
                                        return new HtmlString(sprintf(
                                            '<div class="space-y-3">
                                                <audio controls class="w-full max-w-2xl modern-audio-player" 
                                                       aria-label="Aufzeichnung vom %s">
                                                    <source src="%s" type="audio/mpeg">
                                                    <source src="%s" type="audio/wav">
                                                    <p>Ihr Browser unterstützt keine Audio-Wiedergabe. 
                                                       <a href="%s" download class="text-primary-600 hover:underline">
                                                           Aufzeichnung herunterladen
                                                       </a>
                                                    </p>
                                                </audio>
                                                <div class="flex items-center gap-2 text-sm text-gray-500">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                                              d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z">
                                                        </path>
                                                    </svg>
                                                    <span>Die Aufzeichnung dient ausschließlich zu Qualitätssicherungszwecken</span>
                                                </div>
                                            </div>',
                                            $record->created_at?->format('d.m.Y H:i'),
                                            htmlspecialchars($url),
                                            htmlspecialchars($url),
                                            htmlspecialchars($url)
                                        ));
                                    })
                                    ->columnSpanFull(),
                            ]),
                            
                        Infolists\Components\Tabs\Tab::make('transcript')
                            ->label('Transkript')
                            ->icon('heroicon-m-chat-bubble-left-right')
                            ->visible(fn ($record) => !empty($record->transcript))
                            ->schema([
                                Infolists\Components\TextEntry::make('transcript')
                                    ->label('')
                                    ->formatStateUsing(function ($state) {
                                        if (!$state) return null;
                                        
                                        $html = '<div class="space-y-3 max-h-[600px] overflow-y-auto p-4 bg-gray-50 dark:bg-gray-900 rounded-lg modern-transcript">';
                                        
                                        if (is_array($state)) {
                                            foreach ($state as $entry) {
                                                $speaker = $entry['role'] ?? 'unknown';
                                                $text = $entry['content'] ?? '';
                                                
                                                $speakerLabel = $speaker === 'agent' ? '🤖 KI-Assistent' : '👤 Anrufer';
                                                $bgClass = $speaker === 'agent' 
                                                    ? 'bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-500' 
                                                    : 'bg-white dark:bg-gray-800 border-l-4 border-gray-300';
                                                
                                                $html .= sprintf(
                                                    '<div class="p-4 rounded-lg %s transition-all hover:shadow-md hover:scale-[1.01]">
                                                        <div class="font-semibold text-sm mb-2">%s</div>
                                                        <div class="text-gray-700 dark:text-gray-300">%s</div>
                                                    </div>',
                                                    $bgClass,
                                                    htmlspecialchars($speakerLabel),
                                                    htmlspecialchars($text)
                                                );
                                            }
                                        } elseif (is_string($state)) {
                                            $html .= '<div class="p-4 bg-white dark:bg-gray-800 rounded-lg">
                                                <pre class="whitespace-pre-wrap text-sm font-mono">' . 
                                                htmlspecialchars($state) . '</pre>
                                            </div>';
                                        }
                                        
                                        $html .= '</div>';
                                        return new HtmlString($html);
                                    })
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->persistTab(),
            ]);
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
            Widgets\CallStatsWidget::class,
            Widgets\CallInsightsWidget::class,
            Widgets\CallPerformanceWidget::class,
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        // Optimiert mit Cache und motivierende Messages
        $count = cache()->remember(
            'calls_today_count_' . auth()->id(),
            now()->addMinute(),
            fn() => static::getModel()::whereDate('created_at', today())->count()
        );
        
        if ($count === 0) return null;
        
        // Add personality to badge
        return match(true) {
            $count > 50 => "🔥 {$count}",
            $count > 30 => "💪 {$count}",
            $count > 10 => "📞 {$count}",
            default => (string) $count,
        };
    }
    
    public static function getNavigationBadgeColor(): ?string
    {
        $count = cache()->remember(
            'calls_today_count_' . auth()->id(),
            now()->addMinute(),
            fn() => static::getModel()::whereDate('created_at', today())->count()
        );
        
        return match(true) {
            $count > 50 => 'danger',  // Viele Anrufe
            $count > 30 => 'warning', // Mittelviele
            $count > 0 => 'primary',   // Normal
            default => 'gray'
        };
    }
    
    public static function getNavigationBadgeTooltip(): ?string
    {
        $count = cache()->remember(
            'calls_today_count_' . auth()->id(),
            now()->addMinute(),
            fn() => static::getModel()::whereDate('created_at', today())->count()
        );
        
        return match(true) {
            $count > 50 => 'Wow! Sehr viele Anrufe heute!',
            $count > 30 => 'Starker Tag heute!',
            $count > 10 => 'Guter Fortschritt!',
            $count > 0 => 'Anrufe heute',
            default => 'Noch keine Anrufe heute'
        };
    }
}