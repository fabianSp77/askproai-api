<?php

namespace App\Filament\Customer\Resources;

use App\Filament\Concerns\HasCachedNavigationBadge;
use App\Filament\Customer\Resources\CallbackRequestResource\Pages;
use App\Models\CallbackRequest;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section as InfoSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Grid as InfoGrid;
use Filament\Infolists\Components\RepeatableEntry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class CallbackRequestResource extends Resource
{
    use HasCachedNavigationBadge;

    protected static ?string $model = CallbackRequest::class;
    protected static ?string $navigationIcon = 'heroicon-o-phone-arrow-down-left';
    protected static ?string $navigationGroup = 'CRM';
    protected static ?string $navigationLabel = 'Rückrufe';
    protected static ?string $modelLabel = 'Rückruf';
    protected static ?string $pluralModelLabel = 'Rückrufe';
    protected static ?int $navigationSort = 5;
    protected static ?string $tenantOwnershipRelationshipName = 'customer';

    public static function getNavigationBadge(): ?string
    {
        return static::getCachedBadge(function() {
            return static::getModel()::whereHas('customer', fn ($query) =>
                $query->where('company_id', auth()->user()->company_id)
            )
            ->where('status', CallbackRequest::STATUS_PENDING)
            ->count();
        });
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getCachedBadgeColor(function() {
            $count = static::getModel()::whereHas('customer', fn ($query) =>
                $query->where('company_id', auth()->user()->company_id)
            )
            ->where('status', CallbackRequest::STATUS_PENDING)
            ->count();
            return $count > 10 ? 'danger' : ($count > 5 ? 'warning' : ($count > 0 ? 'info' : null));
        });
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

                Tables\Columns\TextColumn::make('customer_name')
                    ->label('👤 Kunde')
                    ->weight('bold')
                    ->description(fn (CallbackRequest $record): string => $record->phone_number)
                    ->icon('heroicon-o-user')
                    ->searchable(['customer_name', 'phone_number'])
                    ->sortable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('status')
                    ->label('📊 Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        CallbackRequest::STATUS_PENDING => 'Ausstehend',
                        CallbackRequest::STATUS_ASSIGNED => 'Zugewiesen',
                        CallbackRequest::STATUS_CONTACTED => 'Kontaktiert',
                        CallbackRequest::STATUS_COMPLETED => 'Abgeschlossen',
                        CallbackRequest::STATUS_EXPIRED => 'Abgelaufen',
                        CallbackRequest::STATUS_CANCELLED => 'Abgebrochen',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        CallbackRequest::STATUS_PENDING => 'warning',
                        CallbackRequest::STATUS_ASSIGNED => 'info',
                        CallbackRequest::STATUS_CONTACTED => 'primary',
                        CallbackRequest::STATUS_COMPLETED => 'success',
                        CallbackRequest::STATUS_EXPIRED => 'danger',
                        CallbackRequest::STATUS_CANCELLED => 'gray',
                        default => 'gray',
                    })
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('priority')
                    ->label('🚨 Priorität')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        CallbackRequest::PRIORITY_NORMAL => 'Normal',
                        CallbackRequest::PRIORITY_HIGH => 'Hoch',
                        CallbackRequest::PRIORITY_URGENT => 'Dringend',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        CallbackRequest::PRIORITY_NORMAL => 'gray',
                        CallbackRequest::PRIORITY_HIGH => 'warning',
                        CallbackRequest::PRIORITY_URGENT => 'danger',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        CallbackRequest::PRIORITY_URGENT => 'heroicon-o-exclamation-triangle',
                        CallbackRequest::PRIORITY_HIGH => 'heroicon-o-arrow-up',
                        default => 'heroicon-o-minus',
                    })
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('branch.name')
                    ->label('🏢 Filiale')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('service.name')
                    ->label('🔧 Service')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->default('—'),

                Tables\Columns\TextColumn::make('assignedTo.name')
                    ->label('👨‍💼 Zugewiesen an')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->default('—')
                    ->icon('heroicon-o-user'),

                Tables\Columns\TextColumn::make('expires_at')
                    ->label('⏰ Läuft ab')
                    ->dateTime('d.m.Y H:i')
                    ->description(fn (CallbackRequest $record): ?string =>
                        $record->expires_at ? $record->expires_at->diffForHumans() : null
                    )
                    ->sortable()
                    ->toggleable()
                    ->color(fn (CallbackRequest $record): string =>
                        $record->is_overdue ? 'danger' : 'gray'
                    ),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt am')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('escalations_count')
                    ->label('⚠️ Eskalationen')
                    ->counts('escalations')
                    ->badge()
                    ->color('danger')
                    ->toggleable()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->multiple()
                    ->options([
                        CallbackRequest::STATUS_PENDING => 'Ausstehend',
                        CallbackRequest::STATUS_ASSIGNED => 'Zugewiesen',
                        CallbackRequest::STATUS_CONTACTED => 'Kontaktiert',
                        CallbackRequest::STATUS_COMPLETED => 'Abgeschlossen',
                        CallbackRequest::STATUS_EXPIRED => 'Abgelaufen',
                        CallbackRequest::STATUS_CANCELLED => 'Abgebrochen',
                    ]),

                SelectFilter::make('priority')
                    ->label('Priorität')
                    ->multiple()
                    ->options([
                        CallbackRequest::PRIORITY_NORMAL => 'Normal',
                        CallbackRequest::PRIORITY_HIGH => 'Hoch',
                        CallbackRequest::PRIORITY_URGENT => 'Dringend',
                    ]),

                TernaryFilter::make('overdue')
                    ->label('Überfällig')
                    ->queries(
                        true: fn (Builder $query) => $query->where('expires_at', '<', now())
                            ->whereNotIn('status', [
                                CallbackRequest::STATUS_COMPLETED,
                                CallbackRequest::STATUS_EXPIRED,
                                CallbackRequest::STATUS_CANCELLED
                            ]),
                        false: fn (Builder $query) => $query->where(function (Builder $query) {
                            $query->where('expires_at', '>=', now())
                                ->orWhereNull('expires_at')
                                ->orWhereIn('status', [
                                    CallbackRequest::STATUS_COMPLETED,
                                    CallbackRequest::STATUS_EXPIRED,
                                    CallbackRequest::STATUS_CANCELLED
                                ]);
                        }),
                    )
                    ->placeholder('Alle anzeigen'),

                Tables\Filters\Filter::make('recent')
                    ->label('Letzte 7 Tage')
                    ->query(fn (Builder $query): Builder => $query->where('created_at', '>=', now()->subDays(7)))
                    ->toggle(),
            ], layout: Tables\Enums\FiltersLayout::AboveContent)
            ->filtersFormColumns(3)
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->defaultPaginationPageOption(25)
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->striped();
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                InfoSection::make('Hauptinformationen')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        InfoGrid::make(3)
                            ->schema([
                                TextEntry::make('id')
                                    ->label('ID')
                                    ->badge(),

                                TextEntry::make('status')
                                    ->label('Status')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        CallbackRequest::STATUS_PENDING => 'Ausstehend',
                                        CallbackRequest::STATUS_ASSIGNED => 'Zugewiesen',
                                        CallbackRequest::STATUS_CONTACTED => 'Kontaktiert',
                                        CallbackRequest::STATUS_COMPLETED => 'Abgeschlossen',
                                        CallbackRequest::STATUS_EXPIRED => 'Abgelaufen',
                                        CallbackRequest::STATUS_CANCELLED => 'Abgebrochen',
                                        default => $state,
                                    })
                                    ->color(fn (string $state): string => match ($state) {
                                        CallbackRequest::STATUS_PENDING => 'warning',
                                        CallbackRequest::STATUS_ASSIGNED => 'info',
                                        CallbackRequest::STATUS_CONTACTED => 'primary',
                                        CallbackRequest::STATUS_COMPLETED => 'success',
                                        CallbackRequest::STATUS_EXPIRED => 'danger',
                                        CallbackRequest::STATUS_CANCELLED => 'gray',
                                        default => 'gray',
                                    }),

                                TextEntry::make('priority')
                                    ->label('Priorität')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        CallbackRequest::PRIORITY_NORMAL => 'Normal',
                                        CallbackRequest::PRIORITY_HIGH => 'Hoch',
                                        CallbackRequest::PRIORITY_URGENT => 'Dringend',
                                        default => $state,
                                    })
                                    ->color(fn (string $state): string => match ($state) {
                                        CallbackRequest::PRIORITY_NORMAL => 'gray',
                                        CallbackRequest::PRIORITY_HIGH => 'warning',
                                        CallbackRequest::PRIORITY_URGENT => 'danger',
                                        default => 'gray',
                                    }),
                            ]),

                        InfoGrid::make(2)
                            ->schema([
                                TextEntry::make('customer_name')
                                    ->label('Kundenname')
                                    ->icon('heroicon-o-user')
                                    ->weight('bold'),

                                TextEntry::make('phone_number')
                                    ->label('Telefonnummer')
                                    ->icon('heroicon-o-phone')
                                    ->copyable(),

                                TextEntry::make('customer.email')
                                    ->label('E-Mail')
                                    ->icon('heroicon-o-envelope')
                                    ->copyable()
                                    ->placeholder('—'),

                                TextEntry::make('branch.name')
                                    ->label('Filiale')
                                    ->icon('heroicon-o-building-office'),

                                TextEntry::make('service.name')
                                    ->label('Service')
                                    ->icon('heroicon-o-wrench-screwdriver')
                                    ->placeholder('—'),

                                TextEntry::make('staff.name')
                                    ->label('Bevorzugter Mitarbeiter')
                                    ->icon('heroicon-o-user')
                                    ->placeholder('—'),
                            ]),

                        TextEntry::make('preferred_time_window')
                            ->label('Bevorzugtes Zeitfenster')
                            ->formatStateUsing(function ($state): string {
                                if (!$state || !is_array($state)) {
                                    return '—';
                                }
                                return collect($state)
                                    ->map(fn ($time, $day) => "$day: $time")
                                    ->join(', ');
                            })
                            ->columnSpanFull(),

                        TextEntry::make('notes')
                            ->label('Notizen')
                            ->markdown()
                            ->placeholder('Keine Notizen')
                            ->columnSpanFull(),
                    ]),

                InfoSection::make('Bearbeitung')
                    ->icon('heroicon-o-cog')
                    ->schema([
                        InfoGrid::make(3)
                            ->schema([
                                TextEntry::make('assignedTo.name')
                                    ->label('Zugewiesen an')
                                    ->icon('heroicon-o-user')
                                    ->placeholder('Nicht zugewiesen'),

                                TextEntry::make('contacted_at')
                                    ->label('Kontaktiert am')
                                    ->dateTime('d.m.Y H:i')
                                    ->placeholder('Noch nicht kontaktiert'),

                                TextEntry::make('completed_at')
                                    ->label('Abgeschlossen am')
                                    ->dateTime('d.m.Y H:i')
                                    ->placeholder('Noch nicht abgeschlossen'),
                            ]),
                    ]),

                InfoSection::make('Zeitplanung')
                    ->icon('heroicon-o-clock')
                    ->schema([
                        InfoGrid::make(3)
                            ->schema([
                                TextEntry::make('created_at')
                                    ->label('Erstellt am')
                                    ->dateTime('d.m.Y H:i')
                                    ->helperText(fn (CallbackRequest $record): string =>
                                        $record->created_at->diffForHumans()
                                    ),

                                TextEntry::make('expires_at')
                                    ->label('Läuft ab am')
                                    ->dateTime('d.m.Y H:i')
                                    ->helperText(fn (CallbackRequest $record): ?string =>
                                        $record->expires_at ? $record->expires_at->diffForHumans() : null
                                    )
                                    ->placeholder('Kein Ablaufdatum')
                                    ->color(fn (CallbackRequest $record): string =>
                                        $record->is_overdue ? 'danger' : 'gray'
                                    ),

                                TextEntry::make('is_overdue')
                                    ->label('Überfällig')
                                    ->formatStateUsing(fn (bool $state): string => $state ? 'Ja' : 'Nein')
                                    ->badge()
                                    ->color(fn (bool $state): string => $state ? 'danger' : 'success')
                                    ->icon(fn (bool $state): string => $state ? 'heroicon-o-exclamation-triangle' : 'heroicon-o-check-circle'),
                            ]),
                    ]),

                InfoSection::make('Eskalationen')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->schema([
                        RepeatableEntry::make('escalations')
                            ->label('')
                            ->schema([
                                InfoGrid::make(3)
                                    ->schema([
                                        TextEntry::make('escalation_reason')
                                            ->label('Grund'),

                                        TextEntry::make('escalatedFrom.name')
                                            ->label('Von')
                                            ->placeholder('—'),

                                        TextEntry::make('escalatedTo.name')
                                            ->label('An')
                                            ->placeholder('—'),

                                        TextEntry::make('escalated_at')
                                            ->label('Eskaliert am')
                                            ->dateTime('d.m.Y H:i')
                                            ->helperText(fn ($record): string =>
                                                $record->escalated_at?->diffForHumans() ?? ''
                                            ),
                                    ]),
                            ])
                            ->placeholder('Keine Eskalationen')
                            ->columnSpanFull(),
                    ])
                    ->visible(fn (CallbackRequest $record): bool => $record->escalations()->exists()),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCallbackRequests::route('/'),
            'view' => Pages\ViewCallbackRequest::route('/{record}'),
        ];
    }

    /**
     * SECURITY: Safe scope bypass - CallbackRequest has no direct company_id FK
     * Pattern: withoutGlobalScopes() + whereHas on tenant-scoped relation
     * @see HasSecureScopeBypass::relatedTenantQuery()
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes()
            ->whereHas('customer', fn ($query) =>
                $query->where('company_id', auth()->user()->company_id)
            )
            ->with(['customer', 'branch', 'service', 'assignedTo'])
            ->withCount('escalations');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['customer.name', 'phone', 'reason'];
    }
}
