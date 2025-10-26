<?php

namespace App\Filament\Customer\Resources;

use App\Filament\Concerns\HasCachedNavigationBadge;
use App\Filament\Customer\Resources\InvoiceResource\Pages;
use App\Models\Invoice;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section as InfoSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\Grid as InfoGrid;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Fieldset;
use Illuminate\Database\Eloquent\Builder;
use Filament\Support\Enums\FontWeight;
use Carbon\Carbon;

class InvoiceResource extends Resource
{
    use HasCachedNavigationBadge;

    protected static ?string $model = Invoice::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Abrechnung';
    protected static ?string $navigationLabel = 'Rechnungen';
    protected static ?string $label = 'Rechnung';
    protected static ?string $pluralLabel = 'Rechnungen';
    protected static ?int $navigationSort = 2;
    protected static ?string $recordTitleAttribute = 'invoice_number';

    public static function getNavigationBadge(): ?string
    {
        return static::getCachedBadge(function() {
            return static::getModel()::where('company_id', auth()->user()->company_id)
                ->whereIn('status', [Invoice::STATUS_PENDING, Invoice::STATUS_OVERDUE])
                ->count();
        });
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getCachedBadgeColor(function() {
            $overdue = static::getModel()::where('company_id', auth()->user()->company_id)
                ->where('status', Invoice::STATUS_OVERDUE)
                ->count();
            return $overdue > 0 ? 'danger' : 'warning';
        });
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('Rechnungsnr.')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight(FontWeight::Bold),

                Tables\Columns\TextColumn::make('billing_name')
                    ->label('Kunde')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->billing_email)
                    ->wrap(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->colors([
                        'gray' => Invoice::STATUS_DRAFT,
                        'warning' => Invoice::STATUS_PENDING,
                        'info' => Invoice::STATUS_SENT,
                        'success' => Invoice::STATUS_PAID,
                        'primary' => Invoice::STATUS_PARTIAL,
                        'danger' => Invoice::STATUS_OVERDUE,
                    ])
                    ->formatStateUsing(fn (string $state): string => Invoice::getStatusOptions()[$state] ?? $state),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Gesamtbetrag')
                    ->money('EUR', locale: 'de_DE')
                    ->sortable(),

                Tables\Columns\TextColumn::make('balance_due')
                    ->label('Offener Betrag')
                    ->money('EUR', locale: 'de_DE')
                    ->sortable()
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'success'),

                Tables\Columns\TextColumn::make('issue_date')
                    ->label('Datum')
                    ->date('d.m.Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('due_date')
                    ->label('Fälligkeit')
                    ->date('d.m.Y')
                    ->sortable()
                    ->color(fn ($record) => $record->isOverdue() ? 'danger' : null)
                    ->description(fn ($record) =>
                        $record->days_until_due !== null
                            ? ($record->days_until_due < 0
                                ? abs($record->days_until_due) . ' Tage überfällig'
                                : $record->days_until_due . ' Tage')
                            : null
                    ),

                Tables\Columns\IconColumn::make('pdf_path')
                    ->label('PDF')
                    ->icon(fn ($state) => $state ? 'heroicon-o-document-check' : 'heroicon-o-document-minus')
                    ->color(fn ($state) => $state ? 'success' : 'gray')
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->multiple()
                    ->options(Invoice::getStatusOptions()),

                Tables\Filters\Filter::make('overdue')
                    ->label('Überfällig')
                    ->query(fn (Builder $query): Builder => $query->overdue()),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->defaultSort('issue_date', 'desc')
            ->poll('60s');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            InfoSection::make('Rechnungsübersicht')
                ->description('Hauptinformationen zur Rechnung')
                ->icon('heroicon-o-document-text')
                ->schema([
                    InfoGrid::make(4)->schema([
                        TextEntry::make('invoice_number')
                            ->label('Rechnungsnummer')
                            ->icon('heroicon-m-hashtag')
                            ->weight('bold')
                            ->size('lg')
                            ->copyable(),

                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->formatStateUsing(fn ($state) => Invoice::getStatusOptions()[$state] ?? $state)
                            ->color(fn ($state) => match($state) {
                                Invoice::STATUS_PAID => 'success',
                                Invoice::STATUS_OVERDUE => 'danger',
                                default => 'warning'
                            }),

                        TextEntry::make('total_amount')
                            ->label('Gesamtbetrag')
                            ->money('EUR')
                            ->weight('bold')
                            ->size('lg')
                            ->color('success'),

                        TextEntry::make('balance_due')
                            ->label('Offener Betrag')
                            ->money('EUR')
                            ->weight('bold')
                            ->color(fn ($state) => $state > 0 ? 'danger' : 'success')
                            ->icon(fn ($state) => $state > 0 ? 'heroicon-m-exclamation-circle' : 'heroicon-m-check-circle'),
                    ]),
                ]),

            InfoSection::make('Rechnungsempfänger')
                ->description('Kontakt- und Rechnungsdaten')
                ->icon('heroicon-o-user')
                ->collapsible()
                ->schema([
                    InfoGrid::make(3)->schema([
                        TextEntry::make('billing_name')
                            ->label('Rechnungsname')
                            ->icon('heroicon-m-building-office-2')
                            ->copyable(),

                        TextEntry::make('billing_email')
                            ->label('Rechnungs-Email')
                            ->icon('heroicon-m-envelope')
                            ->copyable(),

                        TextEntry::make('billing_phone')
                            ->label('Telefonnummer')
                            ->icon('heroicon-m-phone')
                            ->placeholder('Keine Telefonnummer')
                            ->copyable(),
                    ]),

                    TextEntry::make('billing_address')
                        ->label('Rechnungsadresse')
                        ->icon('heroicon-m-map-pin')
                        ->placeholder('Keine Adresse angegeben')
                        ->columnSpanFull(),
                ]),

            InfoSection::make('Rechnungspositionen')
                ->description('Aufstellung der einzelnen Leistungen')
                ->icon('heroicon-o-shopping-cart')
                ->collapsible()
                ->schema([
                    RepeatableEntry::make('line_items')
                        ->label(false)
                        ->schema([
                            InfoGrid::make(6)->schema([
                                TextEntry::make('description')
                                    ->label('Beschreibung')
                                    ->columnSpan(2)
                                    ->weight('semibold'),

                                TextEntry::make('quantity')
                                    ->label('Menge')
                                    ->numeric(2)
                                    ->suffix(' Stk'),

                                TextEntry::make('price')
                                    ->label('Einzelpreis')
                                    ->money('EUR'),

                                TextEntry::make('total')
                                    ->label('Gesamt')
                                    ->getStateUsing(fn ($record) =>
                                        ($record['quantity'] ?? 1) * ($record['price'] ?? 0)
                                    )
                                    ->money('EUR')
                                    ->weight('bold')
                                    ->color('success'),
                            ]),
                        ])
                        ->contained(false),
                ]),

            InfoSection::make('Beträge & Steuern')
                ->description('Finanzielle Aufschlüsselung')
                ->icon('heroicon-o-calculator')
                ->collapsible()
                ->collapsed()
                ->schema([
                    InfoGrid::make(2)->schema([
                        Fieldset::make('Berechnung')
                            ->schema([
                                TextEntry::make('subtotal')
                                    ->label('Zwischensumme')
                                    ->money('EUR'),
                                TextEntry::make('tax_amount')
                                    ->label('MwSt-Betrag')
                                    ->money('EUR'),
                                TextEntry::make('total_amount')
                                    ->label('Gesamtbetrag')
                                    ->money('EUR')
                                    ->weight('bold')
                                    ->color('success'),
                            ]),

                        Fieldset::make('Zahlungsstatus')
                            ->schema([
                                TextEntry::make('paid_amount')
                                    ->label('Bezahlt')
                                    ->money('EUR')
                                    ->color('success'),
                                TextEntry::make('balance_due')
                                    ->label('Offener Betrag')
                                    ->money('EUR')
                                    ->color(fn ($state) => $state > 0 ? 'danger' : 'success'),
                            ]),
                    ]),
                ]),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvoices::route('/'),
            'view' => Pages\ViewInvoice::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('company_id', auth()->user()->company_id)
            ->with(['customer']);
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
        return ['invoice_number', 'billing_name', 'billing_email', 'payment_reference'];
    }
}
