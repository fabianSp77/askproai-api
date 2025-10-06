<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasCachedNavigationBadge;
use App\Filament\Resources\AppointmentModificationResource\Pages;
use App\Filament\Resources\AppointmentModificationResource\Widgets\ModificationStatsWidget;
use App\Models\AppointmentModification;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Database\Eloquent\Builder;

class AppointmentModificationResource extends Resource
{
    use HasCachedNavigationBadge;

    protected static ?string $model = AppointmentModification::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationGroup = 'Termine';

    protected static ?string $navigationLabel = 'Änderungsprotokoll';

    protected static ?string $modelLabel = 'Terminänderung';

    protected static ?string $pluralModelLabel = 'Terminänderungen';

    protected static ?int $navigationSort = 40;

    public static function getNavigationBadge(): ?string
    {
        return static::getCachedBadge(function() {
            return static::getModel()::where('created_at', '>=', now()->subDay())->count();
        });
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getCachedBadgeColor(function() {
            $count = static::getModel()::where('created_at', '>=', now()->subDay())->count();
            return $count > 10 ? 'danger' : ($count > 5 ? 'warning' : 'info');
        });
    }

    public static function form(Form $form): Form
    {
        // Read-only form for audit trail viewing
        return $form
            ->schema([
                Forms\Components\Section::make('Änderungsdetails')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('appointment_id')
                                    ->label('Termin ID')
                                    ->relationship('appointment', 'id')
                                    ->disabled()
                                    ->dehydrated(false),

                                Forms\Components\Select::make('customer_id')
                                    ->label('Kunde')
                                    ->relationship('customer', 'name')
                                    ->disabled()
                                    ->dehydrated(false),

                                Forms\Components\Select::make('modification_type')
                                    ->label('Änderungstyp')
                                    ->options([
                                        AppointmentModification::TYPE_CANCEL => 'Stornierung',
                                        AppointmentModification::TYPE_RESCHEDULE => 'Umplanung',
                                    ])
                                    ->disabled()
                                    ->dehydrated(false),

                                Forms\Components\TextInput::make('modified_by_type')
                                    ->label('Geändert von (Typ)')
                                    ->disabled()
                                    ->dehydrated(false),

                                Forms\Components\Toggle::make('within_policy')
                                    ->label('Innerhalb der Richtlinien')
                                    ->disabled()
                                    ->dehydrated(false),

                                Forms\Components\TextInput::make('fee_charged')
                                    ->label('Gebühr')
                                    ->prefix('€')
                                    ->disabled()
                                    ->dehydrated(false),
                            ]),

                        Forms\Components\Textarea::make('reason')
                            ->label('Grund')
                            ->rows(3)
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Zeitstempel')
                    ->icon('heroicon-o-clock')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DateTimePicker::make('created_at')
                                    ->label('Erstellt am')
                                    ->disabled()
                                    ->dehydrated(false),

                                Forms\Components\DateTimePicker::make('updated_at')
                                    ->label('Aktualisiert am')
                                    ->disabled()
                                    ->dehydrated(false),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->badge()
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('appointment_id')
                    ->label('Termin')
                    ->badge()
                    ->searchable()
                    ->sortable()
                    ->url(fn (AppointmentModification $record): ?string =>
                        $record->appointment_id
                            ? route('filament.admin.resources.appointments.view', ['record' => $record->appointment_id])
                            : null
                    )
                    ->color('info'),

                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Kunde')
                    ->searchable()
                    ->sortable()
                    ->url(fn (AppointmentModification $record): ?string =>
                        $record->customer_id
                            ? route('filament.admin.resources.customers.view', ['record' => $record->customer_id])
                            : null
                    )
                    ->weight('bold')
                    ->icon('heroicon-o-user'),

                Tables\Columns\TextColumn::make('modification_type')
                    ->label('Typ')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        AppointmentModification::TYPE_CANCEL => 'Stornierung',
                        AppointmentModification::TYPE_RESCHEDULE => 'Umplanung',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        AppointmentModification::TYPE_CANCEL => 'danger',
                        AppointmentModification::TYPE_RESCHEDULE => 'info',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        AppointmentModification::TYPE_CANCEL => 'heroicon-o-x-circle',
                        AppointmentModification::TYPE_RESCHEDULE => 'heroicon-o-arrow-path',
                        default => 'heroicon-o-question-mark-circle',
                    })
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('reason')
                    ->label('Grund')
                    ->limit(50)
                    ->searchable()
                    ->wrap()
                    ->tooltip(fn (AppointmentModification $record): ?string => $record->reason)
                    ->placeholder('—'),

                Tables\Columns\IconColumn::make('within_policy')
                    ->label('Richtlinien')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('fee_charged')
                    ->label('Gebühr')
                    ->money('EUR')
                    ->sortable()
                    ->toggleable()
                    ->color(fn ($state): string => $state > 0 ? 'warning' : 'gray'),

                Tables\Columns\TextColumn::make('modified_by_type')
                    ->label('Geändert von')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'User' => 'Benutzer',
                        'Staff' => 'Mitarbeiter',
                        'Customer' => 'Kunde',
                        'System' => 'System',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'User' => 'info',
                        'Staff' => 'primary',
                        'Customer' => 'warning',
                        'System' => 'gray',
                        default => 'gray',
                    })
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt')
                    ->dateTime('d.m.Y H:i')
                    ->description(fn (AppointmentModification $record): string =>
                        $record->created_at->diffForHumans()
                    )
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('modification_type')
                    ->label('Änderungstyp')
                    ->multiple()
                    ->options([
                        AppointmentModification::TYPE_CANCEL => 'Stornierung',
                        AppointmentModification::TYPE_RESCHEDULE => 'Umplanung',
                    ]),

                Tables\Filters\SelectFilter::make('modified_by_type')
                    ->label('Geändert von')
                    ->multiple()
                    ->options([
                        'User' => 'Benutzer',
                        'Staff' => 'Mitarbeiter',
                        'Customer' => 'Kunde',
                        'System' => 'System',
                    ]),

                Tables\Filters\TernaryFilter::make('within_policy')
                    ->label('Richtlinienkonform')
                    ->placeholder('Alle anzeigen')
                    ->trueLabel('Ja')
                    ->falseLabel('Nein'),

                Tables\Filters\TernaryFilter::make('fee_charged')
                    ->label('Gebühr berechnet')
                    ->placeholder('Alle anzeigen')
                    ->trueLabel('Ja')
                    ->falseLabel('Nein')
                    ->queries(
                        true: fn (Builder $query) => $query->where('fee_charged', '>', 0),
                        false: fn (Builder $query) => $query->where('fee_charged', '=', 0),
                    ),

                Tables\Filters\SelectFilter::make('customer_id')
                    ->label('Kunde')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Erstellt von')
                            ->native(false),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Erstellt bis')
                            ->native(false),
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
                            $indicators[] = Tables\Filters\Indicator::make('Erstellt ab ' . \Carbon\Carbon::parse($data['created_from'])->format('d.m.Y'))
                                ->removeField('created_from');
                        }

                        if ($data['created_until'] ?? null) {
                            $indicators[] = Tables\Filters\Indicator::make('Erstellt bis ' . \Carbon\Carbon::parse($data['created_until'])->format('d.m.Y'))
                                ->removeField('created_until');
                        }

                        return $indicators;
                    }),
            ], layout: Tables\Enums\FiltersLayout::AboveContent)
            ->filtersFormColumns(3)
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                // No bulk actions - audit trail is immutable
            ])
            ->modifyQueryUsing(fn (Builder $query) =>
                $query->with(['appointment', 'customer', 'modifiedBy'])
            )
            ->recordUrl(fn (AppointmentModification $record): string =>
                AppointmentModificationResource::getUrl('view', ['record' => $record])
            );
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Änderungsdetails')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('id')
                                    ->label('ID')
                                    ->badge(),

                                Infolists\Components\TextEntry::make('modification_type')
                                    ->label('Änderungstyp')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        AppointmentModification::TYPE_CANCEL => 'Stornierung',
                                        AppointmentModification::TYPE_RESCHEDULE => 'Umplanung',
                                        default => $state,
                                    })
                                    ->color(fn (string $state): string => match ($state) {
                                        AppointmentModification::TYPE_CANCEL => 'danger',
                                        AppointmentModification::TYPE_RESCHEDULE => 'info',
                                        default => 'gray',
                                    })
                                    ->icon(fn (string $state): string => match ($state) {
                                        AppointmentModification::TYPE_CANCEL => 'heroicon-o-x-circle',
                                        AppointmentModification::TYPE_RESCHEDULE => 'heroicon-o-arrow-path',
                                        default => 'heroicon-o-question-mark-circle',
                                    }),

                                Infolists\Components\TextEntry::make('modified_by_type')
                                    ->label('Geändert von')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        'User' => 'Benutzer',
                                        'Staff' => 'Mitarbeiter',
                                        'Customer' => 'Kunde',
                                        'System' => 'System',
                                        default => $state,
                                    })
                                    ->color(fn (string $state): string => match ($state) {
                                        'User' => 'info',
                                        'Staff' => 'primary',
                                        'Customer' => 'warning',
                                        'System' => 'gray',
                                        default => 'gray',
                                    }),
                            ]),

                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\IconEntry::make('within_policy')
                                    ->label('Innerhalb der Richtlinien')
                                    ->boolean()
                                    ->trueIcon('heroicon-o-check-circle')
                                    ->falseIcon('heroicon-o-x-circle')
                                    ->trueColor('success')
                                    ->falseColor('danger'),

                                Infolists\Components\TextEntry::make('fee_charged')
                                    ->label('Berechnete Gebühr')
                                    ->money('EUR')
                                    ->color(fn ($state): string => $state > 0 ? 'warning' : 'gray'),
                            ]),

                        Infolists\Components\TextEntry::make('reason')
                            ->label('Grund')
                            ->placeholder('Kein Grund angegeben')
                            ->columnSpanFull(),

                        Infolists\Components\TextEntry::make('metadata')
                            ->label('Zusätzliche Daten')
                            ->formatStateUsing(function ($state): string {
                                if (!$state || !is_array($state)) {
                                    return '—';
                                }
                                return collect($state)
                                    ->map(fn ($value, $key) => "**$key:** $value")
                                    ->join("\n");
                            })
                            ->markdown()
                            ->placeholder('—')
                            ->columnSpanFull()
                            ->visible(fn (AppointmentModification $record): bool =>
                                !empty($record->metadata)
                            ),
                    ]),

                Infolists\Components\Section::make('Termindetails')
                    ->icon('heroicon-o-calendar')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('appointment_id')
                                    ->label('Termin ID')
                                    ->badge()
                                    ->url(fn (AppointmentModification $record): ?string =>
                                        $record->appointment_id
                                            ? route('filament.admin.resources.appointments.view', ['record' => $record->appointment_id])
                                            : null
                                    )
                                    ->color('info'),

                                Infolists\Components\TextEntry::make('appointment.status')
                                    ->label('Aktueller Terminstatus')
                                    ->badge()
                                    ->placeholder('Termin gelöscht'),
                            ]),

                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('appointment.start_time')
                                    ->label('Terminzeit')
                                    ->dateTime('d.m.Y H:i')
                                    ->placeholder('—'),

                                Infolists\Components\TextEntry::make('appointment.service.name')
                                    ->label('Service')
                                    ->placeholder('—'),
                            ]),
                    ]),

                Infolists\Components\Section::make('Kundendetails')
                    ->icon('heroicon-o-user')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('customer.name')
                                    ->label('Name')
                                    ->weight('bold')
                                    ->url(fn (AppointmentModification $record): ?string =>
                                        $record->customer_id
                                            ? route('filament.admin.resources.customers.view', ['record' => $record->customer_id])
                                            : null
                                    )
                                    ->icon('heroicon-o-user'),

                                Infolists\Components\TextEntry::make('customer.email')
                                    ->label('E-Mail')
                                    ->icon('heroicon-o-envelope')
                                    ->copyable()
                                    ->placeholder('—'),

                                Infolists\Components\TextEntry::make('customer.phone_number')
                                    ->label('Telefon')
                                    ->icon('heroicon-o-phone')
                                    ->copyable()
                                    ->placeholder('—'),
                            ]),
                    ]),

                Infolists\Components\Section::make('Zeitstempel')
                    ->icon('heroicon-o-clock')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('created_at')
                                    ->label('Erstellt am')
                                    ->dateTime('d.m.Y H:i')
                                    ->description(fn (AppointmentModification $record): string =>
                                        $record->created_at->diffForHumans()
                                    )
                                    ->icon('heroicon-o-calendar'),

                                Infolists\Components\TextEntry::make('updated_at')
                                    ->label('Aktualisiert am')
                                    ->dateTime('d.m.Y H:i')
                                    ->description(fn (AppointmentModification $record): string =>
                                        $record->updated_at->diffForHumans()
                                    )
                                    ->icon('heroicon-o-calendar'),
                            ]),
                    ]),
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
            'index' => Pages\ListAppointmentModifications::route('/'),
            'view' => Pages\ViewAppointmentModification::route('/{record}'),
        ];
    }

    public static function getWidgets(): array
    {
        return [
            ModificationStatsWidget::class,
        ];
    }

    public static function canCreate(): bool
    {
        // Audit trail records are created automatically by the system
        return false;
    }

    public static function canEdit($record): bool
    {
        // Audit trail is immutable
        return false;
    }

    public static function canDelete($record): bool
    {
        // Audit trail should not be deleted
        return false;
    }

    public static function canDeleteAny(): bool
    {
        // Audit trail should not be deleted
        return false;
    }

    public static function getRecordTitle($record): ?string
    {
        return "Änderung #{$record->id}";
    }
}
