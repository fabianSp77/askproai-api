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
use Filament\Support\Enums\IconSize;
use Filament\Support\Enums\ActionSize;
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
use Filament\Tables\Actions\Action;
use Filament\Infolists\Components\Actions\Action as InfolistAction;

class CallResourceOptimized extends Resource
{
    protected static ?string $model = Call::class;
    protected static ?string $navigationIcon = 'heroicon-o-phone-arrow-down-left';
    protected static ?string $navigationLabel = 'Anrufe';
    protected static ?string $navigationGroup = 'Tagesgeschäft';
    protected static ?int $navigationSort = 10;
    protected static bool $shouldRegisterNavigation = false; // Temporär für Test
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
                // Optimiertes Eager Loading mit Select-only
                ->with([
                    'customer:id,name,phone,email',
                    'appointment:id,call_id,scheduled_at,appointment_made',
                    'branch:id,name',
                    'company:id,name'
                ])
                // Scopes nur bei Bedarf entfernen
                ->when(
                    auth()->user()?->hasRole('super_admin'),
                    fn($q) => $q->withoutGlobalScope(\App\Scopes\TenantScope::class)
                               ->withoutGlobalScope(\App\Models\Scopes\CompanyScope::class)
                )
                // Optimierte Sortierung für Index-Nutzung
                ->orderByDesc('created_at')
                ->orderByDesc('id')
            )
            ->striped()
            ->defaultSort('created_at', 'desc')
            ->extremePaginationLinks()
            // Reduzierte Pagination für bessere Performance
            ->paginated([10, 15, 25])
            ->paginationPageOptions([10, 15, 25])
            ->defaultPaginationPageOption(15)
            ->columns([
                // Mobile-optimiertes Stack Layout
                Tables\Columns\Layout\Stack::make([
                    Tables\Columns\TextColumn::make('created_at')
                        ->label('Anrufzeit')
                        ->dateTime('d.m.Y H:i')
                        ->size('sm')
                        ->color('gray')
                        ->icon('heroicon-m-calendar')
                        ->iconColor('gray'),
                        
                    Tables\Columns\TextColumn::make('customer.name')
                        ->label('Kunde')
                        ->weight(FontWeight::Medium)
                        ->searchable()
                        ->default('Anonymer Anruf')
                        ->url(fn ($record) => $record->customer 
                            ? CustomerResource::getUrl('view', [$record->customer]) 
                            : null
                        ),
                        
                    Tables\Columns\Layout\Split::make([
                        Tables\Columns\TextColumn::make('from_number')
                            ->label('Nummer')
                            ->icon('heroicon-m-phone')
                            ->iconColor('primary')
                            ->copyable()
                            ->copyMessage('Nummer kopiert!')
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
                            ->color('gray')
                            ->size('sm'),
                    ]),
                ])->space(1)->visibleFrom('md'),
                
                // Desktop-optimierte Spalten
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
                    ->copyMessage('Nummer kopiert!')
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
                    
                Tables\Columns\TextColumn::make('duration_sec')
                    ->label('Dauer')
                    ->formatStateUsing(fn ($state) => $state ? gmdate('i:s', $state) : '—')
                    ->sortable()
                    ->icon('heroicon-m-clock')
                    ->alignCenter()
                    ->toggleable()
                    ->hiddenFrom('md'),
                    
                // Status-Spalte mit Icons
                Tables\Columns\Layout\Stack::make([
                    Tables\Columns\IconColumn::make('appointment_made')
                        ->label('Termin')
                        ->boolean()
                        ->trueIcon('heroicon-o-calendar-days')
                        ->falseIcon('heroicon-o-minus-circle')
                        ->trueColor('success')
                        ->falseColor('gray')
                        ->size(IconSize::Small),
                        
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
                // Optimierte Filter mit Default-Wert
                SelectFilter::make('time_range')
                    ->label('Zeitraum')
                    ->default('today')
                    ->options([
                        'today' => 'Heute',
                        'yesterday' => 'Gestern',
                        'this_week' => 'Diese Woche',
                        'last_week' => 'Letzte Woche',
                        'this_month' => 'Dieser Monat',
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
                            'this_month' => $query->whereMonth('created_at', now()->month),
                            default => $query,
                        };
                    }),
                    
                TernaryFilter::make('appointment_made')
                    ->label('Terminvereinbarung')
                    ->placeholder('Alle Anrufe')
                    ->trueLabel('Mit Termin')
                    ->falseLabel('Ohne Termin'),
                    
                SelectFilter::make('call_status')
                    ->label('Status')
                    ->options([
                        'ended' => 'Beendet',
                        'error' => 'Fehler',
                        'ongoing' => 'Laufend',
                    ]),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(3)
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('')
                    ->tooltip('Details anzeigen')
                    ->icon('heroicon-m-eye')
                    ->iconButton(),
                    
                Tables\Actions\Action::make('call_customer')
                    ->label('')
                    ->tooltip('Kunde anrufen')
                    ->icon('heroicon-m-phone-arrow-up-right')
                    ->color('success')
                    ->iconButton()
                    ->visible(fn ($record) => !empty($record->from_number))
                    ->url(fn ($record) => 'tel:' . $record->from_number)
                    ->openUrlInNewTab(),
                    
                Tables\Actions\Action::make('add_note')
                    ->label('')
                    ->tooltip('Notiz hinzufügen')
                    ->icon('heroicon-m-pencil-square')
                    ->color('gray')
                    ->iconButton()
                    ->form([
                        Forms\Components\Textarea::make('note')
                            ->label('Notiz')
                            ->placeholder('Notiz zu diesem Anruf...')
                            ->rows(3)
                            ->maxLength(500)
                            ->required(),
                    ])
                    ->action(function ($record, array $data) {
                        // Notiz speichern
                        $record->notes()->create([
                            'content' => $data['note'],
                            'user_id' => auth()->id(),
                        ]);
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Notiz gespeichert')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                // Keine Bulk Actions für bessere Performance
            ])
            ->poll('30s'); // Auto-refresh alle 30 Sekunden
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // Hero Section mit Quick Actions
                Infolists\Components\Section::make()
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
                                    ->openUrlInNewTab(),
                                    
                                InfolistAction::make('book_appointment')
                                    ->label('Termin buchen')
                                    ->icon('heroicon-m-calendar-days')
                                    ->color('primary')
                                    ->size(ActionSize::Large)
                                    ->visible(fn ($record) => !$record->appointment_made && $record->customer)
                                    ->url(fn ($record) => AppointmentResource::getUrl('create', [
                                        'customer_id' => $record->customer_id
                                    ])),
                                    
                                InfolistAction::make('send_email')
                                    ->label('E-Mail senden')
                                    ->icon('heroicon-m-envelope')
                                    ->color('gray')
                                    ->size(ActionSize::Large)
                                    ->visible(fn ($record) => $record->customer?->email)
                                    ->url(fn ($record) => 'mailto:' . $record->customer->email),
                            ])->verticalAlignment('start'),
                        ]),
                    ])
                    ->headerActions([
                        InfolistAction::make('refresh')
                            ->label('Aktualisieren')
                            ->icon('heroicon-m-arrow-path')
                            ->color('gray')
                            ->action(fn () => redirect(request()->url())),
                    ]),
                    
                // Kompakte Status-Übersicht
                Infolists\Components\Section::make('Anruf-Ergebnis')
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
                                        return sprintf('%d:%02d Min', $minutes, $seconds);
                                    })
                                    ->icon('heroicon-m-clock')
                                    ->badge()
                                    ->color('primary'),
                                    
                                Infolists\Components\IconEntry::make('appointment_made')
                                    ->label('Termin vereinbart')
                                    ->boolean()
                                    ->trueIcon('heroicon-o-check-circle')
                                    ->falseIcon('heroicon-o-x-circle')
                                    ->trueColor('success')
                                    ->falseColor('gray')
                                    ->size('xl'),
                                    
                                Infolists\Components\TextEntry::make('call_status')
                                    ->label('Anruf-Status')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        'ended', 'completed' => 'Erfolgreich beendet',
                                        'error' => 'Fehler',
                                        'ongoing' => 'Laufend',
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
                                    ->copyMessage('Nummer kopiert!')
                                    ->formatStateUsing(fn ($state) => 
                                        $state ? preg_replace('/(\d{4})(\d{3})(\d+)/', '+49 $1 $2 $3', $state) : 'Anonym'
                                    ),
                            ]),
                    ]),
                    
                // Termin-Details (wenn vorhanden)
                Infolists\Components\Section::make('Terminvereinbarung')
                    ->icon('heroicon-o-calendar-days')
                    ->description('Details zur Terminbuchung')
                    ->visible(fn ($record) => $record->appointment_made)
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('appointment.scheduled_at')
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
                    
                // Audio-Player (wenn vorhanden)
                Infolists\Components\Section::make('Gesprächsaufzeichnung')
                    ->icon('heroicon-o-microphone')
                    ->description('Audio-Aufzeichnung des Anrufs')
                    ->collapsible()
                    ->collapsed()
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
                                        <audio controls class="w-full max-w-2xl" 
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
                    
                // Gesprächsverlauf (wenn vorhanden)
                Infolists\Components\Section::make('Gesprächstranskript')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->description('Verlauf des Gesprächs')
                    ->collapsible()
                    ->collapsed()
                    ->visible(fn ($record) => !empty($record->transcript))
                    ->schema([
                        Infolists\Components\TextEntry::make('transcript')
                            ->label('')
                            ->formatStateUsing(function ($state) {
                                if (!$state) return null;
                                
                                $html = '<div class="space-y-3 max-h-[600px] overflow-y-auto p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">';
                                
                                if (is_array($state)) {
                                    foreach ($state as $entry) {
                                        $speaker = $entry['role'] ?? 'unknown';
                                        $text = $entry['content'] ?? '';
                                        
                                        $speakerLabel = $speaker === 'agent' ? '🤖 KI-Assistent' : '👤 Anrufer';
                                        $bgClass = $speaker === 'agent' 
                                            ? 'bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-500' 
                                            : 'bg-white dark:bg-gray-800 border-l-4 border-gray-300';
                                        
                                        $html .= sprintf(
                                            '<div class="p-4 rounded-lg %s transition-all hover:shadow-md">
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
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        // Optimiert mit Cache
        return cache()->remember(
            'calls_today_count_' . auth()->id(),
            now()->addMinute(),
            fn() => static::getModel()::whereDate('created_at', today())->count() ?: null
        );
    }
    
    public static function getNavigationBadgeColor(): ?string
    {
        return static::getNavigationBadge() ? 'primary' : 'gray';
    }
    
    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Anrufe heute';
    }
}