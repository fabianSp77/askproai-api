<?php

namespace App\Filament\Customer\Resources;

use App\Filament\Concerns\HasCachedNavigationBadge;
use App\Filament\Customer\Resources\CustomerNoteResource\Pages;
use App\Models\CustomerNote;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section as InfoSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Grid as InfoGrid;
use Illuminate\Database\Eloquent\Builder;

class CustomerNoteResource extends Resource
{
    use HasCachedNavigationBadge;

    protected static ?string $model = CustomerNote::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'CRM';
    protected static ?string $navigationLabel = 'Notizen';
    protected static ?string $modelLabel = 'Notiz';
    protected static ?string $pluralModelLabel = 'Notizen';
    protected static ?int $navigationSort = 3;
    protected static ?string $tenantOwnershipRelationshipName = 'customer';

    public static function getNavigationBadge(): ?string
    {
        return static::getCachedBadge(function() {
            return static::getModel()::whereHas('customer', fn ($query) =>
                $query->where('company_id', auth()->user()->company_id)
            )
            ->where('is_important', true)
            ->count();
        });
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getCachedBadgeColor(function() {
            $count = static::getModel()::whereHas('customer', fn ($query) =>
                $query->where('company_id', auth()->user()->company_id)
            )
            ->where('is_important', true)
            ->count();
            return $count > 0 ? 'danger' : null;
        });
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('👤 Kunde')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->icon('heroicon-m-user'),

                Tables\Columns\TextColumn::make('subject')
                    ->label('📝 Betreff')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->limit(30)
                    ->tooltip(function ($record) {
                        return $record->subject;
                    }),

                Tables\Columns\BadgeColumn::make('type')
                    ->label('🏷️ Typ')
                    ->colors([
                        'primary' => 'general',
                        'success' => 'call',
                        'warning' => 'email',
                        'danger' => 'complaint',
                        'secondary' => 'meeting',
                        'info' => ['task', 'followup', 'feedback'],
                    ])
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'general' => 'Allgemein',
                        'call' => 'Anruf',
                        'email' => 'E-Mail',
                        'meeting' => 'Meeting',
                        'task' => 'Aufgabe',
                        'followup' => 'Follow-up',
                        'complaint' => 'Beschwerde',
                        'feedback' => 'Feedback',
                        default => $state,
                    })
                    ->searchable(),

                Tables\Columns\BadgeColumn::make('category')
                    ->label('📂 Kategorie')
                    ->colors([
                        'primary' => 'general',
                        'success' => 'sales',
                        'warning' => 'support',
                        'danger' => 'billing',
                        'secondary' => 'technical',
                        'info' => 'important',
                    ])
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'sales' => 'Vertrieb',
                        'support' => 'Support',
                        'technical' => 'Technisch',
                        'billing' => 'Abrechnung',
                        'general' => 'Allgemein',
                        'important' => 'Wichtig',
                        default => $state,
                    })
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\BadgeColumn::make('visibility')
                    ->label('👁️ Sichtbarkeit')
                    ->colors([
                        'success' => 'public',
                        'warning' => 'internal',
                        'danger' => 'private',
                    ])
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'public' => 'Öffentlich',
                        'internal' => 'Intern',
                        'private' => 'Privat',
                        default => $state,
                    })
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_important')
                    ->label('⚠️ Wichtig')
                    ->boolean()
                    ->trueIcon('heroicon-o-exclamation-circle')
                    ->falseIcon('')
                    ->trueColor('danger')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_pinned')
                    ->label('📌 Angeheftet')
                    ->boolean()
                    ->trueIcon('heroicon-o-bookmark')
                    ->falseIcon('')
                    ->trueColor('warning')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Erstellt von')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-m-user-circle')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('⏰ Erstellt am')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->description(fn ($record) => $record->created_at->diffForHumans()),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Aktualisiert')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('customer')
                    ->label('Kunde')
                    ->relationship('customer', 'name', fn ($query) =>
                        $query->where('company_id', auth()->user()->company_id)
                    )
                    ->searchable()
                    ->preload(),

                SelectFilter::make('type')
                    ->label('Typ')
                    ->options([
                        'general' => 'Allgemein',
                        'call' => 'Anruf',
                        'email' => 'E-Mail',
                        'meeting' => 'Meeting',
                        'task' => 'Aufgabe',
                        'followup' => 'Follow-up',
                        'complaint' => 'Beschwerde',
                        'feedback' => 'Feedback',
                    ]),

                SelectFilter::make('category')
                    ->label('Kategorie')
                    ->options([
                        'sales' => 'Vertrieb',
                        'support' => 'Support',
                        'technical' => 'Technisch',
                        'billing' => 'Abrechnung',
                        'general' => 'Allgemein',
                        'important' => 'Wichtig',
                    ]),

                TernaryFilter::make('is_important')
                    ->label('Nur wichtige')
                    ->placeholder('Alle Notizen')
                    ->trueLabel('Wichtige Notizen')
                    ->falseLabel('Normale Notizen'),

                TernaryFilter::make('is_pinned')
                    ->label('Angeheftet')
                    ->placeholder('Alle Notizen')
                    ->trueLabel('Angeheftete Notizen')
                    ->falseLabel('Nicht angeheftete Notizen'),

                Tables\Filters\Filter::make('recent')
                    ->label('Letzte 7 Tage')
                    ->query(fn (Builder $query): Builder => $query->where('created_at', '>=', now()->subDays(7)))
                    ->toggle(),
            ])
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
                InfoSection::make('Notiz-Details')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        InfoGrid::make(3)
                            ->schema([
                                TextEntry::make('customer.name')
                                    ->label('Kunde')
                                    ->icon('heroicon-o-user')
                                    ->weight('bold'),

                                TextEntry::make('type')
                                    ->label('Typ')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state): string => match($state) {
                                        'general' => 'Allgemein',
                                        'call' => 'Anruf',
                                        'email' => 'E-Mail',
                                        'meeting' => 'Meeting',
                                        'task' => 'Aufgabe',
                                        'followup' => 'Follow-up',
                                        'complaint' => 'Beschwerde',
                                        'feedback' => 'Feedback',
                                        default => $state,
                                    })
                                    ->color(fn (string $state): string => match($state) {
                                        'general' => 'primary',
                                        'call' => 'success',
                                        'email' => 'warning',
                                        'complaint' => 'danger',
                                        default => 'secondary',
                                    }),

                                TextEntry::make('category')
                                    ->label('Kategorie')
                                    ->badge()
                                    ->formatStateUsing(fn (?string $state): string => match($state) {
                                        'sales' => 'Vertrieb',
                                        'support' => 'Support',
                                        'technical' => 'Technisch',
                                        'billing' => 'Abrechnung',
                                        'general' => 'Allgemein',
                                        'important' => 'Wichtig',
                                        default => $state ?? '—',
                                    })
                                    ->color(fn (?string $state): string => match($state) {
                                        'sales' => 'success',
                                        'support' => 'warning',
                                        'billing' => 'danger',
                                        'important' => 'info',
                                        default => 'primary',
                                    }),
                            ]),

                        InfoGrid::make(3)
                            ->schema([
                                TextEntry::make('visibility')
                                    ->label('Sichtbarkeit')
                                    ->badge()
                                    ->formatStateUsing(fn (?string $state): string => match($state) {
                                        'public' => 'Öffentlich',
                                        'internal' => 'Intern',
                                        'private' => 'Privat',
                                        default => $state ?? 'Öffentlich',
                                    })
                                    ->color(fn (?string $state): string => match($state) {
                                        'public' => 'success',
                                        'internal' => 'warning',
                                        'private' => 'danger',
                                        default => 'success',
                                    }),

                                TextEntry::make('is_important')
                                    ->label('Wichtig')
                                    ->badge()
                                    ->formatStateUsing(fn (bool $state): string => $state ? '⚠️ Ja' : 'Nein')
                                    ->color(fn (bool $state): string => $state ? 'danger' : 'gray'),

                                TextEntry::make('is_pinned')
                                    ->label('Angeheftet')
                                    ->badge()
                                    ->formatStateUsing(fn (bool $state): string => $state ? '📌 Ja' : 'Nein')
                                    ->color(fn (bool $state): string => $state ? 'warning' : 'gray'),
                            ]),

                        TextEntry::make('subject')
                            ->label('Betreff')
                            ->weight('bold')
                            ->size('lg')
                            ->icon('heroicon-o-document-text')
                            ->columnSpanFull(),

                        TextEntry::make('content')
                            ->label('Inhalt')
                            ->columnSpanFull()
                            ->markdown()
                            ->placeholder('Kein Inhalt'),

                        InfoGrid::make(2)
                            ->schema([
                                TextEntry::make('creator.name')
                                    ->label('Erstellt von')
                                    ->icon('heroicon-o-user-circle')
                                    ->placeholder('Unbekannt'),

                                TextEntry::make('created_at')
                                    ->label('Erstellt am')
                                    ->dateTime('d.m.Y H:i:s')
                                    ->icon('heroicon-o-calendar')
                                    ->helperText(fn ($record) => $record->created_at->diffForHumans()),

                                TextEntry::make('updated_at')
                                    ->label('Aktualisiert am')
                                    ->dateTime('d.m.Y H:i:s')
                                    ->icon('heroicon-o-calendar-days')
                                    ->helperText(fn ($record) => $record->updated_at->diffForHumans()),
                            ]),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomerNotes::route('/'),
            'view' => Pages\ViewCustomerNote::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes() // Remove CompanyScope as no direct FK exists
            ->whereHas('customer', fn ($query) =>
                $query->where('company_id', auth()->user()->company_id)
            )
            ->with(['customer:id,name', 'createdBy:id,name']);
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
        return ['subject', 'content', 'customer.name'];
    }
}
