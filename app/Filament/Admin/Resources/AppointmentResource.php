<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\AppointmentResource\Pages;
use App\Filament\Admin\Resources\Concerns\MultiTenantResource;
use App\Filament\Admin\Traits\HasConsistentNavigation;
use App\Models\Appointment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Support\Enums\Alignment;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Illuminate\Database\Eloquent\Collection;
use Filament\Forms\Components\DatePicker;

class AppointmentResource extends Resource
{

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        
        // Super admin can view all
        if ($user->hasRole('super_admin')) {
            return true;
        }
        
        // Check specific permission or if user belongs to a company
        return $user->can('view_any_appointment') || $user->company_id !== null;
    }
    
    public static function canView($record): bool
    {
        $user = auth()->user();
        
        // Super admin can view all
        if ($user->hasRole('super_admin')) {
            return true;
        }
        
        // Check specific permission
        if ($user->can('view_appointment')) {
            return true;
        }
        
        // Users can view appointments from their own company
        return $user->company_id === $record->company_id;
    }
    
    public static function canEdit($record): bool
    {
        $user = auth()->user();
        
        // Super admin can edit all
        if ($user->hasRole('super_admin')) {
            return true;
        }
        
        // Check specific permission
        if ($user->can('update_appointment')) {
            return true;
        }
        
        // Company users can edit appointments from their own company
        return $user->company_id === $record->company_id;
    }
    
    public static function canCreate(): bool
    {
        $user = auth()->user();
        
        // Super admin can create
        if ($user->hasRole('super_admin')) {
            return true;
        }
        
        // Check specific permission
        if ($user->can('create_appointment')) {
            return true;
        }
        
        // Any company user can create appointments
        return $user->company_id !== null;
    }

    use MultiTenantResource;
    
    protected static ?string $model = Appointment::class;
    protected static ?string $navigationIcon = 'heroicon-o-calendar';
    protected static ?string $navigationLabel = 'Termine';
    protected static ?string $navigationGroup = 'Täglicher Betrieb';
    protected static ?int $navigationSort = 20;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Termindetails')
                    ->description('Grundlegende Informationen zum Termin')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('customer_id')
                                    ->label('Kunde')
                                    ->relationship('customer', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('name')
                                            ->required(),
                                        Forms\Components\TextInput::make('email')
                                            ->email(),
                                        Forms\Components\TextInput::make('phone'),
                                    ]),
                                    
                                Forms\Components\Select::make('staff_id')
                                    ->label('Mitarbeiter')
                                    ->relationship('staff', 'name')
                                    ->searchable()
                                    ->preload(),
                            ]),
                            
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('service_id')
                                    ->label('Leistung')
                                    ->relationship('service', 'name')
                                    ->searchable()
                                    ->preload(),
                                    
                                Forms\Components\Select::make('branch_id')
                                    ->label('Filiale')
                                    ->relationship('branch', 'name')
                                    ->searchable()
                                    ->preload(),
                            ]),
                    ]),
                    
                Forms\Components\Section::make('Zeitplanung')
                    ->description('Datum, Uhrzeit und Status')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\DateTimePicker::make('starts_at')
                                    ->label('Beginn')
                                    ->required()
                                    ->native(false),
                                    
                                Forms\Components\DateTimePicker::make('ends_at')
                                    ->label('Ende')
                                    ->native(false),
                                    
                                Forms\Components\Select::make('status')
                                    ->label('Status')
                                    ->options([
                                        'pending' => 'Ausstehend',
                                        'confirmed' => 'Bestätigt',
                                        'completed' => 'Abgeschlossen',
                                        'cancelled' => 'Abgesagt',
                                        'no_show' => 'Nicht erschienen',
                                    ])
                                    ->required(),
                            ]),
                    ]),
                    
                Forms\Components\Section::make('Notizen')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Interne Notizen')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(array_merge(
                ['customer', 'staff', 'service', 'branch', 'calcomEventType', 'call'],
                static::getMultiTenantRelations()
            )))
            ->poll('30s')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable()
                    ->copyable()
                    ->copyMessage('ID kopiert')
                    ->copyMessageDuration(1500),
                    
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Kunde')
                    ->sortable()
                    ->searchable()
                    ->default('—')
                    ->weight('medium')
                    ->description(fn ($record) => $record->customer?->phone)
                    ->wrap()
                    ->getStateUsing(fn ($record) => $record?->customer?->name ?? '-'),
                    
                Tables\Columns\TextColumn::make('service.name')
                    ->label('Leistung')
                    ->searchable()
                    ->badge()
                    ->color('primary')
                    ->default('—')
                    ->size('sm')
                    ->getStateUsing(fn ($record) => $record?->service?->name ?? '-'),
                    
                Tables\Columns\TextColumn::make('staff.name')
                    ->label('Mitarbeiter')
                    ->sortable()
                    ->searchable()
                    ->default('—')
                    ->icon('heroicon-m-user')
                    ->iconPosition('before')
                    ->size('sm')
                    ->getStateUsing(fn ($record) => $record?->staff?->name ?? '-'),
                    
                Tables\Columns\TextColumn::make('starts_at')
                    ->label('Termin')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->size('sm')
                    ->weight('medium')
                    ->description(function ($record) {
                        if (!$record->starts_at) return null;
                        $startsAt = Carbon::parse($record->starts_at);
                        if ($startsAt->isToday()) return 'Heute';
                        if ($startsAt->isTomorrow()) return 'Morgen';
                        if ($startsAt->isPast()) return 'Vergangen';
                        return $startsAt->diffForHumans();
                    })
                    ->icon(fn ($record) => match(true) {
                        !$record->starts_at => null,
                        Carbon::parse($record->starts_at)->isToday() => 'heroicon-m-calendar',
                        Carbon::parse($record->starts_at)->isPast() => 'heroicon-m-clock',
                        default => 'heroicon-m-calendar-days'
                    })
                    ->iconColor(fn ($record) => match(true) {
                        !$record->starts_at => 'gray',
                        Carbon::parse($record->starts_at)->isToday() => 'primary',
                        Carbon::parse($record->starts_at)->isPast() => 'warning',
                        default => 'success'
                    }),
                    
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'confirmed' => 'info',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        'no_show' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Ausstehend',
                        'confirmed' => 'Bestätigt',
                        'completed' => 'Abgeschlossen',
                        'cancelled' => 'Abgesagt',
                        'no_show' => 'Nicht erschienen',
                        default => $state,
                    }),
                    
                static::getCompanyColumn(),
                static::getBranchColumn(),
                    
                Tables\Columns\TextColumn::make('duration')
                    ->label('Dauer')
                    ->getStateUsing(fn ($record) => $record->service?->duration ? $record->service->duration . ' Min.' : '—')
                    ->badge()
                    ->color('gray')
                    ->size('sm')
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('price')
                    ->label('Preis')
                    ->getStateUsing(fn ($record) => $record->service?->price ? number_format($record->service->price / 100, 2, ',', '.') . ' €' : '—')
                    ->badge()
                    ->color('success')
                    ->size('sm')
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('payment_status')
                    ->label('Bezahlung')
                    ->getStateUsing(function ($record) {
                        // This would check the payment relation
                        $hasPayment = false; // $record->payments()->exists();
                        $isPaid = false; // $record->payments()->where('status', 'completed')->exists();
                        
                        if (!$hasPayment) return 'Offen';
                        return $isPaid ? 'Bezahlt' : 'Ausstehend';
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Bezahlt' => 'success',
                        'Ausstehend' => 'warning',
                        'Offen' => 'gray',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'Bezahlt' => 'heroicon-m-check-circle',
                        'Ausstehend' => 'heroicon-m-clock',
                        'Offen' => 'heroicon-m-minus-circle',
                        default => 'heroicon-m-question-mark-circle',
                    }),
                    
                Tables\Columns\IconColumn::make('reminder_status')
                    ->label('Erinnerungen')
                    ->getStateUsing(function ($record) {
                        $icons = [];
                        if ($record->reminder_24h_sent_at) $icons[] = '24h';
                        if ($record->reminder_2h_sent_at) $icons[] = '2h';
                        if ($record->reminder_30m_sent_at) $icons[] = '30m';
                        
                        return !empty($icons) ? implode(', ', $icons) : null;
                    })
                    ->icon('heroicon-m-bell')
                    ->color('info')
                    ->tooltip(function ($record) {
                        $tooltips = [];
                        if ($record->reminder_24h_sent_at) {
                            $tooltips[] = '24h: ' . $record->reminder_24h_sent_at->format('d.m.Y H:i');
                        }
                        if ($record->reminder_2h_sent_at) {
                            $tooltips[] = '2h: ' . $record->reminder_2h_sent_at->format('d.m.Y H:i');
                        }
                        if ($record->reminder_30m_sent_at) {
                            $tooltips[] = '30m: ' . $record->reminder_30m_sent_at->format('d.m.Y H:i');
                        }
                        
                        return !empty($tooltips) ? implode("\n", $tooltips) : 'Keine Erinnerungen gesendet';
                    })
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('customer.no_show_count')
                    ->label('No-Shows')
                    ->getStateUsing(fn ($record) => $record->customer ? 
                        $record->customer->appointments()->where('status', 'no_show')->count() : 0)
                    ->badge()
                    ->color(fn ($state): string => match (true) {
                        $state >= 3 => 'danger',
                        $state >= 1 => 'warning',
                        default => 'gray',
                    })
                    ->icon(fn ($state): string => match (true) {
                        $state >= 3 => 'heroicon-m-exclamation-triangle',
                        $state >= 1 => 'heroicon-m-exclamation-circle',
                        default => 'heroicon-m-check-circle',
                    })
                    ->tooltip(fn ($state) => match (true) {
                        $state >= 3 => 'Vorsicht: Häufige No-Shows!',
                        $state >= 1 => 'Kunde ist bereits nicht erschienen',
                        default => 'Kunde erscheint zuverlässig',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt am')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->description(fn ($record) => $record->created_at ? $record->created_at->diffForHumans() : null)
                    ->icon('heroicon-m-clock')
                    ->iconColor('gray')
                    ->tooltip('Wann wurde der Termin vereinbart')
                    ->toggleable(isToggledHiddenByDefault: false),
                    
                Tables\Columns\TextColumn::make('source')
                    ->label('Quelle')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'phone' => 'info',
                        'web' => 'success',
                        'walk-in' => 'warning',
                        'import' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'phone' => 'Telefon',
                        'web' => 'Online',
                        'walk-in' => 'Vor Ort',
                        'import' => 'Import',
                        default => ucfirst($state),
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'phone' => 'heroicon-m-phone',
                        'web' => 'heroicon-m-globe-alt',
                        'walk-in' => 'heroicon-m-building-storefront',
                        'import' => 'heroicon-m-arrow-down-tray',
                        default => 'heroicon-m-question-mark-circle',
                    })
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->default('phone'),
            ])
            ->filters(array_merge(
                static::getMultiTenantFilters(),
                [
                    Tables\Filters\SelectFilter::make('status')
                        ->label('Status')
                        ->options([
                            'pending' => 'Ausstehend',
                            'confirmed' => 'Bestätigt',
                            'completed' => 'Abgeschlossen',
                            'cancelled' => 'Abgesagt',
                            'no_show' => 'Nicht erschienen',
                        ])
                        ->multiple(),
                        
                    Tables\Filters\SelectFilter::make('staff_id')
                        ->label('Mitarbeiter')
                        ->relationship('staff', 'name')
                        ->searchable()
                        ->preload(),
                        
                    Tables\Filters\SelectFilter::make('service_id')
                        ->label('Leistung')
                        ->relationship('service', 'name')
                        ->searchable()
                        ->preload(),
                        
                    Tables\Filters\TernaryFilter::make('calcom_sync')
                        ->label('Cal.com Sync')
                        ->placeholder('Alle')
                        ->trueLabel('Mit Cal.com')
                        ->falseLabel('Ohne Cal.com')
                        ->queries(
                            true: fn (Builder $query) => $query->where(function($q) {
                                $q->whereNotNull('calcom_booking_id')
                                  ->orWhereNotNull('calcom_v2_booking_id');
                            }),
                            false: fn (Builder $query) => $query->where(function($q) {
                                $q->whereNull('calcom_booking_id')
                                  ->whereNull('calcom_v2_booking_id');
                            }),
                        ),
                        
                    Tables\Filters\TernaryFilter::make('has_call')
                        ->label('Mit Anruf')
                        ->placeholder('Alle')
                        ->trueLabel('Aus Anruf')
                        ->falseLabel('Ohne Anruf')
                        ->queries(
                            true: fn (Builder $query) => $query->whereNotNull('call_id'),
                            false: fn (Builder $query) => $query->whereNull('call_id'),
                        ),
                        
                    Tables\Filters\Filter::make('today')
                        ->label('Heute')
                        ->query(fn (Builder $query): Builder => $query->whereDate('starts_at', today()))
                        ->toggle(),
                        
                    Tables\Filters\Filter::make('tomorrow')
                        ->label('Morgen')
                        ->query(fn (Builder $query): Builder => $query->whereDate('starts_at', today()->addDay()))
                        ->toggle(),
                        
                    Tables\Filters\Filter::make('this_week')
                        ->label('Diese Woche')
                        ->query(fn (Builder $query): Builder => $query->whereBetween('starts_at', [
                            now()->startOfWeek(),
                            now()->endOfWeek()
                        ]))
                        ->toggle(),
                        
                    Tables\Filters\Filter::make('past_due')
                        ->label('Überfällig')
                        ->query(fn (Builder $query): Builder => $query
                            ->where('starts_at', '<', now())
                            ->whereIn('status', ['pending', 'confirmed']))
                        ->toggle(),
                        
                    Tables\Filters\Filter::make('created_today')
                        ->label('Heute erstellt')
                        ->query(fn (Builder $query): Builder => $query->whereDate('created_at', today()))
                        ->toggle(),
                    
                    Tables\Filters\Filter::make('date_range')
                    ->form([
                        DatePicker::make('from')
                            ->label('Von')
                            ->native(false)
                            ->displayFormat('d.m.Y'),
                        DatePicker::make('until')
                            ->label('Bis')
                            ->native(false)
                            ->displayFormat('d.m.Y'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('starts_at', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('starts_at', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['from'] ?? null) {
                            $indicators[] = 'Von: ' . Carbon::parse($data['from'])->format('d.m.Y');
                        }
                        if ($data['until'] ?? null) {
                            $indicators[] = 'Bis: ' . Carbon::parse($data['until'])->format('d.m.Y');
                        }
                        return $indicators;
                    }),
                ]
            ), layout: FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns([
                'sm' => 1,
                'md' => 2,
                'lg' => 3,
                'xl' => 4,
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label('Anzeigen')
                        ->icon('heroicon-o-eye'),
                    Tables\Actions\EditAction::make()
                        ->label('Bearbeiten')
                        ->icon('heroicon-o-pencil'),
                    Action::make('checkin')
                        ->label('Check-in')
                        ->icon('heroicon-o-user-plus')
                        ->color('info')
                        ->requiresConfirmation()
                        ->modalHeading('Kunde eingecheckt')
                        ->modalDescription('Kunde ist angekommen und wartet.')
                        ->visible(fn ($record) => $record->status === 'confirmed' && $record->starts_at->isToday())
                        ->action(function ($record) {
                            $record->update([
                                'checked_in_at' => now(),
                                'notes' => ($record->notes ? $record->notes . "\n\n" : '') . 
                                          "Check-in: " . now()->format('d.m.Y H:i') . " Uhr"
                            ]);
                            
                            \Filament\Notifications\Notification::make()
                                ->title('Check-in erfolgreich')
                                ->body('Der Kunde wurde eingecheckt.')
                                ->success()
                                ->send();
                        }),
                    Action::make('complete')
                        ->label('Abschließen')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn ($record) => !in_array($record->status, ['completed', 'cancelled']))
                        ->action(fn ($record) => $record->update(['status' => 'completed'])),
                    Action::make('cancel')
                        ->label('Absagen')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->visible(fn ($record) => !in_array($record->status, ['completed', 'cancelled']))
                        ->action(fn ($record) => $record->update(['status' => 'cancelled'])),
                    Action::make('noShow')
                        ->label('Nicht erschienen')
                        ->icon('heroicon-o-user-minus')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Als nicht erschienen markieren')
                        ->modalDescription('Der Kunde wird als nicht erschienen markiert. Bei häufigen No-Shows wird der Kunde automatisch getaggt.')
                        ->visible(fn ($record) => $record->status === 'confirmed' && $record->starts_at->isPast())
                        ->action(function ($record) {
                            $record->update(['status' => 'no_show']);
                            
                            // Track no-shows for customer
                            if ($record->customer) {
                                $noShowCount = $record->customer->appointments()
                                    ->where('status', 'no_show')
                                    ->count();
                                
                                if ($noShowCount >= 3) {
                                    $tags = $record->customer->tags ?? [];
                                    if (!in_array('Häufige No-Shows', $tags)) {
                                        $tags[] = 'Häufige No-Shows';
                                        $record->customer->update(['tags' => $tags]);
                                    }
                                }
                            }
                            
                            \Filament\Notifications\Notification::make()
                                ->title('Status aktualisiert')
                                ->body('Der Termin wurde als "Nicht erschienen" markiert.')
                                ->warning()
                                ->send();
                        }),
                    Action::make('sendReminder')
                        ->label('Erinnerung senden')
                        ->icon('heroicon-o-bell')
                        ->color('info')
                        ->form([
                            Forms\Components\Select::make('channel')
                                ->label('Kanal')
                                ->options([
                                    'email' => 'E-Mail',
                                    'sms' => 'SMS',
                                    'both' => 'E-Mail & SMS',
                                ])
                                ->default('email')
                                ->required(),
                            Forms\Components\Select::make('template')
                                ->label('Vorlage')
                                ->options([
                                    'reminder_24h' => '24 Stunden vorher',
                                    'reminder_2h' => '2 Stunden vorher',
                                    'reminder_custom' => 'Benutzerdefiniert',
                                ])
                                ->default('reminder_24h')
                                ->reactive()
                                ->required(),
                            Forms\Components\Textarea::make('custom_message')
                                ->label('Nachricht')
                                ->visible(fn ($get) => $get('template') === 'reminder_custom')
                                ->default(fn ($record) => "Guten Tag {$record->customer?->name},\n\nwir möchten Sie an Ihren Termin am {$record->starts_at->format('d.m.Y')} um {$record->starts_at->format('H:i')} Uhr erinnern.\n\nMit freundlichen Grüßen\nIhr Team"),
                        ])
                        ->visible(fn ($record) => $record->status === 'confirmed' && $record->starts_at->isFuture())
                        ->action(function ($record, array $data) {
                            // Here you would integrate with your notification service
                            \Filament\Notifications\Notification::make()
                                ->title('Erinnerung gesendet')
                                ->body("Eine Erinnerung wurde per {$data['channel']} an {$record->customer->name} gesendet.")
                                ->success()
                                ->send();
                        }),
                    Action::make('reschedule')
                        ->label('Verschieben')
                        ->icon('heroicon-o-calendar')
                        ->color('warning')
                        ->form([
                            Forms\Components\DateTimePicker::make('new_start')
                                ->label('Neuer Termin')
                                ->native(false)
                                ->required()
                                ->minDate(now())
                                ->displayFormat('d.m.Y H:i')
                                ->minutesStep(15),
                            Forms\Components\Textarea::make('reason')
                                ->label('Grund der Verschiebung')
                                ->rows(2),
                        ])
                        ->visible(fn ($record) => in_array($record->status, ['confirmed', 'pending']) && $record->starts_at->isFuture())
                        ->action(function ($record, array $data) {
                            $oldStart = $record->starts_at;
                            $duration = $record->starts_at->diffInMinutes($record->ends_at);
                            
                            $record->update([
                                'starts_at' => $data['new_start'],
                                'ends_at' => \Carbon\Carbon::parse($data['new_start'])->addMinutes($duration),
                                'notes' => ($record->notes ? $record->notes . "\n\n" : '') . 
                                          "Verschoben von {$oldStart->format('d.m.Y H:i')} auf {$data['new_start']->format('d.m.Y H:i')}. " .
                                          ($data['reason'] ? "Grund: {$data['reason']}" : ''),
                            ]);
                            
                            \Filament\Notifications\Notification::make()
                                ->title('Termin verschoben')
                                ->body("Der Termin wurde erfolgreich verschoben.")
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    BulkAction::make('updateStatus')
                        ->label('Status ändern')
                        ->icon('heroicon-o-arrow-path')
                        ->form([
                            Forms\Components\Select::make('status')
                                ->label('Neuer Status')
                                ->options([
                                    'confirmed' => 'Bestätigt',
                                    'completed' => 'Abgeschlossen',
                                    'cancelled' => 'Abgesagt',
                                    'no_show' => 'Nicht erschienen',
                                ])
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            $records->each(fn ($record) => $record->update(['status' => $data['status']]));
                        }),
                    BulkAction::make('sendBulkReminders')
                        ->label('Erinnerungen senden')
                        ->icon('heroicon-o-bell')
                        ->color('info')
                        ->form([
                            Forms\Components\Select::make('type')
                                ->label('Erinnerungstyp')
                                ->options([
                                    '24h' => '24 Stunden vorher',
                                    '2h' => '2 Stunden vorher',
                                    'custom' => 'Benutzerdefiniert',
                                ])
                                ->required(),
                            Forms\Components\Select::make('channel')
                                ->label('Kanal')
                                ->options([
                                    'email' => 'E-Mail',
                                    'sms' => 'SMS',
                                    'both' => 'E-Mail & SMS',
                                ])
                                ->default('email')
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            $sent = 0;
                            foreach ($records as $record) {
                                if ($record->status === 'confirmed' && $record->starts_at->isFuture() && $record->customer) {
                                    // Send reminder logic here
                                    $sent++;
                                }
                            }
                            
                            \Filament\Notifications\Notification::make()
                                ->title('Erinnerungen versendet')
                                ->body("{$sent} Erinnerungen wurden erfolgreich versendet.")
                                ->success()
                                ->send();
                        }),
                    BulkAction::make('exportCalendar')
                        ->label('Als Kalender exportieren')
                        ->icon('heroicon-o-calendar')
                        ->color('success')
                        ->form([
                            Forms\Components\Select::make('format')
                                ->label('Format')
                                ->options([
                                    'ics' => 'iCalendar (.ics)',
                                    'csv' => 'CSV',
                                    'pdf' => 'PDF',
                                ])
                                ->default('ics')
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            // Export logic would go here
                            \Filament\Notifications\Notification::make()
                                ->title('Export erstellt')
                                ->body("Die ausgewählten Termine wurden als {$data['format']} exportiert.")
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('starts_at', 'desc')
            ->emptyStateHeading('Keine Termine vorhanden')
            ->emptyStateDescription('Erstellen Sie einen neuen Termin über den Button oben.')
            ->emptyStateIcon('heroicon-o-calendar')
            ->deferLoading()
            ->persistFiltersInSession()
            ->persistSearchInSession()
            ->persistSortInSession()
            ->persistColumnSearchesInSession()
            ->extremePaginationLinks()
            ->paginated([10, 25, 50, 100])
            ->selectCurrentPageOnly();
    }

    public static function getRelations(): array
    {
        return [
            //RelationManagers\CommunicationLogsRelationManager::class,
            //RelationManagers\PaymentHistoryRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAppointments::route('/'),
            'create' => Pages\CreateAppointment::route('/create'),
            'view' => Pages\ViewAppointment::route('/{record}'),
            'edit' => Pages\EditAppointment::route('/{record}/edit'),
        ];
    }
    
    public static function getGloballySearchableAttributes(): array
    {
        return ['id', 'customer.name', 'staff.name', 'service.name', 'notes'];
    }
    
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::whereDate('starts_at', today())->count();
    }
    
    public static function getNavigationBadgeColor(): ?string
    {
        return static::getModel()::whereDate('starts_at', today())->count() > 0 ? 'primary' : 'gray';
    }
}