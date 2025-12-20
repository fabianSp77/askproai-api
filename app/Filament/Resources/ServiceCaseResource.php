<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServiceCaseResource\Pages;
use App\Models\ServiceCase;
use App\Models\ServiceCaseCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Database\Eloquent\Builder;

class ServiceCaseResource extends Resource
{
    protected static ?string $model = ServiceCase::class;
    protected static ?string $navigationIcon = 'heroicon-o-ticket';
    protected static ?string $navigationGroup = 'Service Gateway';
    protected static ?string $navigationLabel = 'Service Cases';
    protected static ?string $modelLabel = 'Service Case';
    protected static ?string $pluralModelLabel = 'Service Cases';
    protected static ?int $navigationSort = 10;

    /**
     * Only show in navigation when Service Gateway is enabled.
     * @see config/gateway.php 'mode_enabled'
     */
    public static function shouldRegisterNavigation(): bool
    {
        return config('gateway.mode_enabled', false);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Case Details')
                    ->schema([
                        Forms\Components\TextInput::make('subject')
                            ->label('Betreff')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('description')
                            ->label('Beschreibung')
                            ->required()
                            ->rows(4)
                            ->columnSpanFull(),
                        Forms\Components\Select::make('category_id')
                            ->label('Kategorie')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\Textarea::make('description')
                                    ->rows(3),
                                Forms\Components\TextInput::make('sla_response_hours')
                                    ->label('SLA Response (Stunden)')
                                    ->numeric()
                                    ->default(4),
                                Forms\Components\TextInput::make('sla_resolution_hours')
                                    ->label('SLA Resolution (Stunden)')
                                    ->numeric()
                                    ->default(24),
                            ]),
                        Forms\Components\Select::make('case_type')
                            ->label('Typ')
                            ->options([
                                ServiceCase::TYPE_INCIDENT => 'Störung',
                                ServiceCase::TYPE_REQUEST => 'Anfrage',
                                ServiceCase::TYPE_INQUIRY => 'Anliegen',
                            ])
                            ->required()
                            ->default(ServiceCase::TYPE_INQUIRY),
                        Forms\Components\Select::make('call_id')
                            ->label('Zugehöriger Anruf')
                            ->relationship('call', 'id')
                            ->searchable()
                            ->preload()
                            ->disabled(fn ($record) => $record && $record->call_id),
                        Forms\Components\Select::make('customer_id')
                            ->label('Kunde')
                            ->relationship('customer', 'name')
                            ->searchable()
                            ->preload(),
                    ])->columns(2),

                Forms\Components\Section::make('Priority & Status')
                    ->schema([
                        Forms\Components\Select::make('priority')
                            ->label('Priorität')
                            ->options([
                                ServiceCase::PRIORITY_LOW => 'Niedrig',
                                ServiceCase::PRIORITY_NORMAL => 'Normal',
                                ServiceCase::PRIORITY_HIGH => 'Hoch',
                                ServiceCase::PRIORITY_CRITICAL => 'Kritisch',
                            ])
                            ->required()
                            ->default(ServiceCase::PRIORITY_NORMAL),
                        Forms\Components\Select::make('urgency')
                            ->label('Dringlichkeit')
                            ->options([
                                ServiceCase::PRIORITY_LOW => 'Niedrig',
                                ServiceCase::PRIORITY_NORMAL => 'Normal',
                                ServiceCase::PRIORITY_HIGH => 'Hoch',
                                ServiceCase::PRIORITY_CRITICAL => 'Kritisch',
                            ])
                            ->required()
                            ->default(ServiceCase::PRIORITY_NORMAL),
                        Forms\Components\Select::make('impact')
                            ->label('Auswirkung')
                            ->options([
                                ServiceCase::PRIORITY_LOW => 'Niedrig',
                                ServiceCase::PRIORITY_NORMAL => 'Normal',
                                ServiceCase::PRIORITY_HIGH => 'Hoch',
                                ServiceCase::PRIORITY_CRITICAL => 'Kritisch',
                            ])
                            ->required()
                            ->default(ServiceCase::PRIORITY_NORMAL),
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                ServiceCase::STATUS_NEW => 'Neu',
                                ServiceCase::STATUS_OPEN => 'Offen',
                                ServiceCase::STATUS_PENDING => 'Wartend',
                                ServiceCase::STATUS_RESOLVED => 'Gelöst',
                                ServiceCase::STATUS_CLOSED => 'Geschlossen',
                            ])
                            ->required()
                            ->default(ServiceCase::STATUS_NEW),
                        Forms\Components\Select::make('assigned_to')
                            ->label('Zugewiesen an')
                            ->relationship('assignedTo', 'name')
                            ->searchable()
                            ->preload(),
                        Forms\Components\TextInput::make('external_reference')
                            ->label('Externe Referenz')
                            ->maxLength(255),
                    ])->columns(3),

                Forms\Components\Section::make('SLA & Output')
                    ->schema([
                        Forms\Components\DateTimePicker::make('sla_response_due_at')
                            ->label('SLA Response Deadline')
                            ->disabled(),
                        Forms\Components\DateTimePicker::make('sla_resolution_due_at')
                            ->label('SLA Resolution Deadline')
                            ->disabled(),
                        Forms\Components\Select::make('output_status')
                            ->label('Output Status')
                            ->options([
                                ServiceCase::OUTPUT_PENDING => 'Ausstehend',
                                ServiceCase::OUTPUT_SENT => 'Gesendet',
                                ServiceCase::OUTPUT_FAILED => 'Fehlgeschlagen',
                            ])
                            ->disabled(),
                        Forms\Components\DateTimePicker::make('output_sent_at')
                            ->label('Output gesendet am')
                            ->disabled(),
                        Forms\Components\Textarea::make('output_error')
                            ->label('Output Fehler')
                            ->rows(2)
                            ->disabled()
                            ->columnSpanFull(),
                    ])->columns(2)
                    ->collapsible()
                    ->collapsed(),

                Forms\Components\Section::make('AI Metadata')
                    ->schema([
                        Forms\Components\KeyValue::make('structured_data')
                            ->label('Strukturierte Daten')
                            ->columnSpanFull(),
                        Forms\Components\KeyValue::make('ai_metadata')
                            ->label('KI Metadaten')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->visibleOn('view'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('formatted_id')
                    ->label('Ticket-ID')
                    ->sortable(query: fn ($query, $direction) => $query->orderBy('id', $direction))
                    ->searchable(query: fn ($query, $search) => $query->where('id', 'like', "%{$search}%"))
                    ->copyable()
                    ->copyMessage('Ticket-ID kopiert')
                    ->weight('bold')
                    ->color('primary'),
                Tables\Columns\TextColumn::make('subject')
                    ->label('Betreff')
                    ->searchable()
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->subject),
                Tables\Columns\BadgeColumn::make('case_type')
                    ->label('Typ')
                    ->colors([
                        'danger' => ServiceCase::TYPE_INCIDENT,
                        'warning' => ServiceCase::TYPE_REQUEST,
                        'info' => ServiceCase::TYPE_INQUIRY,
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        ServiceCase::TYPE_INCIDENT => 'Störung',
                        ServiceCase::TYPE_REQUEST => 'Anfrage',
                        ServiceCase::TYPE_INQUIRY => 'Anliegen',
                        default => $state,
                    }),
                Tables\Columns\BadgeColumn::make('priority')
                    ->label('Priorität')
                    ->colors([
                        'gray' => ServiceCase::PRIORITY_LOW,
                        'primary' => ServiceCase::PRIORITY_NORMAL,
                        'warning' => ServiceCase::PRIORITY_HIGH,
                        'danger' => ServiceCase::PRIORITY_CRITICAL,
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        ServiceCase::PRIORITY_LOW => 'Niedrig',
                        ServiceCase::PRIORITY_NORMAL => 'Normal',
                        ServiceCase::PRIORITY_HIGH => 'Hoch',
                        ServiceCase::PRIORITY_CRITICAL => 'Kritisch',
                        default => $state,
                    }),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'gray' => ServiceCase::STATUS_NEW,
                        'info' => ServiceCase::STATUS_OPEN,
                        'warning' => ServiceCase::STATUS_PENDING,
                        'success' => ServiceCase::STATUS_RESOLVED,
                        'primary' => ServiceCase::STATUS_CLOSED,
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        ServiceCase::STATUS_NEW => 'Neu',
                        ServiceCase::STATUS_OPEN => 'Offen',
                        ServiceCase::STATUS_PENDING => 'Wartend',
                        ServiceCase::STATUS_RESOLVED => 'Gelöst',
                        ServiceCase::STATUS_CLOSED => 'Geschlossen',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Kategorie')
                    ->sortable()
                    ->searchable(),
                // Caller from ai_metadata (Voice AI captured data)
                Tables\Columns\TextColumn::make('ai_metadata.customer_name')
                    ->label('Anrufer')
                    ->placeholder('—')
                    ->searchable(query: function ($query, $search) {
                        return $query->where('ai_metadata->customer_name', 'like', "%{$search}%");
                    })
                    ->toggleable(),
                Tables\Columns\TextColumn::make('ai_metadata.customer_phone')
                    ->label('Telefon')
                    ->placeholder('—')
                    ->copyable()
                    ->copyMessage('Telefonnummer kopiert')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('ai_metadata.others_affected')
                    ->label('Mehrere betroffen')
                    ->boolean()
                    ->trueIcon('heroicon-o-user-group')
                    ->falseIcon('heroicon-o-user')
                    ->trueColor('danger')
                    ->falseColor('gray')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('CRM Kunde')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('assignedTo.name')
                    ->label('Zugewiesen')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('sla_status')
                    ->label('SLA')
                    ->state(function (ServiceCase $record): ?string {
                        if ($record->isResolutionOverdue()) {
                            return 'overdue';
                        }
                        if ($record->isResponseOverdue()) {
                            return 'warning';
                        }
                        return 'ok';
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'overdue' => 'heroicon-o-exclamation-circle',
                        'warning' => 'heroicon-o-exclamation-triangle',
                        'ok' => 'heroicon-o-check-circle',
                        default => 'heroicon-o-clock',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'overdue' => 'danger',
                        'warning' => 'warning',
                        'ok' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\BadgeColumn::make('output_status')
                    ->label('Output')
                    ->colors([
                        'warning' => ServiceCase::OUTPUT_PENDING,
                        'success' => ServiceCase::OUTPUT_SENT,
                        'danger' => ServiceCase::OUTPUT_FAILED,
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        ServiceCase::OUTPUT_PENDING => 'Ausstehend',
                        ServiceCase::OUTPUT_SENT => 'Gesendet',
                        ServiceCase::OUTPUT_FAILED => 'Fehler',
                        default => $state,
                    })
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        ServiceCase::STATUS_NEW => 'Neu',
                        ServiceCase::STATUS_OPEN => 'Offen',
                        ServiceCase::STATUS_PENDING => 'Wartend',
                        ServiceCase::STATUS_RESOLVED => 'Gelöst',
                        ServiceCase::STATUS_CLOSED => 'Geschlossen',
                    ])
                    ->multiple(),
                Tables\Filters\SelectFilter::make('priority')
                    ->label('Priorität')
                    ->options([
                        ServiceCase::PRIORITY_LOW => 'Niedrig',
                        ServiceCase::PRIORITY_NORMAL => 'Normal',
                        ServiceCase::PRIORITY_HIGH => 'Hoch',
                        ServiceCase::PRIORITY_CRITICAL => 'Kritisch',
                    ])
                    ->multiple(),
                Tables\Filters\SelectFilter::make('case_type')
                    ->label('Typ')
                    ->options([
                        ServiceCase::TYPE_INCIDENT => 'Störung',
                        ServiceCase::TYPE_REQUEST => 'Anfrage',
                        ServiceCase::TYPE_INQUIRY => 'Anliegen',
                    ])
                    ->multiple(),
                Tables\Filters\SelectFilter::make('category_id')
                    ->label('Kategorie')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\Filter::make('open')
                    ->label('Nur offene Cases')
                    ->query(fn (Builder $query): Builder => $query->open())
                    ->toggle(),
                Tables\Filters\Filter::make('overdue')
                    ->label('SLA überschritten')
                    ->query(fn (Builder $query): Builder => $query->where(function ($q) {
                        $q->where('sla_resolution_due_at', '<', now())
                          ->orWhere('sla_response_due_at', '<', now());
                    }))
                    ->toggle(),
                Tables\Filters\Filter::make('output_failed')
                    ->label('Output fehlgeschlagen')
                    ->query(fn (Builder $query): Builder => $query->where('output_status', ServiceCase::OUTPUT_FAILED))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('resend_output')
                    ->label('Output erneut senden')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (ServiceCase $record) => $record->output_status === ServiceCase::OUTPUT_FAILED)
                    ->action(function (ServiceCase $record) {
                        $record->update(['output_status' => ServiceCase::OUTPUT_PENDING]);
                        // Trigger resend via event or job
                        \Filament\Notifications\Notification::make()
                            ->title('Output wird erneut gesendet')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('mark_resolved')
                        ->label('Als gelöst markieren')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each->update(['status' => ServiceCase::STATUS_RESOLVED])),
                    Tables\Actions\BulkAction::make('assign')
                        ->label('Zuweisen')
                        ->icon('heroicon-o-user-plus')
                        ->form([
                            Forms\Components\Select::make('assigned_to')
                                ->label('Zuweisen an')
                                ->relationship('assignedTo', 'name')
                                ->searchable()
                                ->preload()
                                ->required(),
                        ])
                        ->action(function ($records, array $data) {
                            $records->each->update(['assigned_to' => $data['assigned_to']]);
                        }),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Case Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('formatted_id')
                            ->label('Ticket-ID')
                            ->badge()
                            ->color('primary')
                            ->copyable()
                            ->copyMessage('Ticket-ID kopiert'),
                        Infolists\Components\TextEntry::make('subject')
                            ->label('Betreff')
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('description')
                            ->label('Beschreibung')
                            ->columnSpanFull()
                            ->markdown(),
                        Infolists\Components\TextEntry::make('case_type')
                            ->label('Typ')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                ServiceCase::TYPE_INCIDENT => 'danger',
                                ServiceCase::TYPE_REQUEST => 'warning',
                                ServiceCase::TYPE_INQUIRY => 'info',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                ServiceCase::TYPE_INCIDENT => 'Störung',
                                ServiceCase::TYPE_REQUEST => 'Anfrage',
                                ServiceCase::TYPE_INQUIRY => 'Anliegen',
                                default => $state,
                            }),
                        Infolists\Components\TextEntry::make('category.name')
                            ->label('Kategorie'),
                        Infolists\Components\TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                ServiceCase::STATUS_NEW => 'gray',
                                ServiceCase::STATUS_OPEN => 'info',
                                ServiceCase::STATUS_PENDING => 'warning',
                                ServiceCase::STATUS_RESOLVED => 'success',
                                ServiceCase::STATUS_CLOSED => 'primary',
                                default => 'gray',
                            }),
                        Infolists\Components\TextEntry::make('priority')
                            ->label('Priorität')
                            ->badge(),
                        Infolists\Components\TextEntry::make('urgency')
                            ->label('Dringlichkeit')
                            ->badge(),
                        Infolists\Components\TextEntry::make('impact')
                            ->label('Auswirkung')
                            ->badge(),
                    ])->columns(3),
                // ========================================
                // CALLER INFORMATION (from Voice AI)
                // ========================================
                Infolists\Components\Section::make('Anrufer-Informationen')
                    ->description('Vom Voice-AI erfasste Kundendaten')
                    ->icon('heroicon-o-phone')
                    ->schema([
                        Infolists\Components\TextEntry::make('ai_metadata.customer_name')
                            ->label('Name')
                            ->placeholder('Nicht angegeben')
                            ->icon('heroicon-o-user'),
                        Infolists\Components\TextEntry::make('ai_metadata.customer_phone')
                            ->label('Telefon')
                            ->placeholder('Nicht angegeben')
                            ->icon('heroicon-o-phone')
                            ->copyable()
                            ->copyMessage('Telefonnummer kopiert'),
                        Infolists\Components\TextEntry::make('ai_metadata.customer_location')
                            ->label('Standort/Büro')
                            ->placeholder('Nicht angegeben')
                            ->icon('heroicon-o-map-pin'),
                        Infolists\Components\IconEntry::make('ai_metadata.others_affected')
                            ->label('Mehrere Personen betroffen')
                            ->boolean()
                            ->trueIcon('heroicon-o-user-group')
                            ->falseIcon('heroicon-o-user')
                            ->trueColor('danger')
                            ->falseColor('success'),
                        Infolists\Components\TextEntry::make('ai_metadata.retell_call_id')
                            ->label('Retell Call ID')
                            ->placeholder('—')
                            ->badge()
                            ->color('gray')
                            ->copyable(),
                        Infolists\Components\TextEntry::make('ai_metadata.finalized_at')
                            ->label('Erfasst am')
                            ->placeholder('—')
                            ->dateTime('d.m.Y H:i:s'),
                    ])->columns(3)
                    ->collapsible(),

                Infolists\Components\Section::make('Assignment & References')
                    ->schema([
                        Infolists\Components\TextEntry::make('call.id')
                            ->label('Anruf ID')
                            ->url(fn ($record) => $record->call_id ? route('filament.admin.resources.calls.view', $record->call_id) : null),
                        Infolists\Components\TextEntry::make('customer.name')
                            ->label('CRM Kunde')
                            ->url(fn ($record) => $record->customer_id ? route('filament.admin.resources.customers.edit', $record->customer_id) : null),
                        Infolists\Components\TextEntry::make('assignedTo.name')
                            ->label('Zugewiesen an'),
                        Infolists\Components\TextEntry::make('external_reference')
                            ->label('Externe Referenz'),
                    ])->columns(2),
                Infolists\Components\Section::make('SLA')
                    ->schema([
                        Infolists\Components\TextEntry::make('sla_response_due_at')
                            ->label('Response Deadline')
                            ->dateTime('d.m.Y H:i')
                            ->color(fn ($record) => $record->isResponseOverdue() ? 'danger' : 'success'),
                        Infolists\Components\TextEntry::make('sla_resolution_due_at')
                            ->label('Resolution Deadline')
                            ->dateTime('d.m.Y H:i')
                            ->color(fn ($record) => $record->isResolutionOverdue() ? 'danger' : 'success'),
                    ])->columns(2),
                Infolists\Components\Section::make('Output Status')
                    ->schema([
                        Infolists\Components\TextEntry::make('output_status')
                            ->label('Status')
                            ->badge(),
                        Infolists\Components\TextEntry::make('output_sent_at')
                            ->label('Gesendet am')
                            ->dateTime('d.m.Y H:i'),
                        Infolists\Components\TextEntry::make('output_error')
                            ->label('Fehler')
                            ->color('danger')
                            ->columnSpanFull(),
                    ])->columns(2),
                Infolists\Components\Section::make('Timestamps')
                    ->schema([
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Erstellt')
                            ->dateTime('d.m.Y H:i'),
                        Infolists\Components\TextEntry::make('updated_at')
                            ->label('Aktualisiert')
                            ->dateTime('d.m.Y H:i'),
                    ])->columns(2)
                    ->collapsible(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServiceCases::route('/'),
            'create' => Pages\CreateServiceCase::route('/create'),
            'view' => Pages\ViewServiceCase::route('/{record}'),
            'edit' => Pages\EditServiceCase::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::open()->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $count = static::getModel()::open()->count();
        if ($count > 10) return 'danger';
        if ($count > 5) return 'warning';
        return 'success';
    }
}
