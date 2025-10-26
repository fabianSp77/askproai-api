<?php

namespace App\Filament\Customer\Resources;

use App\Filament\Concerns\HasCachedNavigationBadge;
use App\Filament\Customer\Resources\BalanceTopupResource\Pages;
use App\Models\BalanceTopup;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Grid as InfoGrid;
use Filament\Infolists\Components\Split;
use Filament\Infolists\Components\KeyValueEntry;
use Illuminate\Database\Eloquent\Builder;

class BalanceTopupResource extends Resource
{
    use HasCachedNavigationBadge;

    protected static ?string $model = BalanceTopup::class;
    protected static ?string $navigationIcon = 'heroicon-o-currency-euro';
    protected static ?string $navigationGroup = 'Abrechnung';
    protected static ?string $navigationLabel = 'Guthaben-Aufladungen';
    protected static ?string $modelLabel = 'Guthaben-Aufladung';
    protected static ?string $pluralModelLabel = 'Guthaben-Aufladungen';
    protected static ?int $navigationSort = 1;
    protected static ?string $recordTitleAttribute = 'reference_number';

    public static function getNavigationBadge(): ?string
    {
        return static::getCachedBadge(function() {
            return static::getModel()::where('company_id', auth()->user()->company_id)
                ->where('status', 'pending')
                ->count();
        });
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getCachedBadgeColor(function() {
            $count = static::getModel()::where('company_id', auth()->user()->company_id)
                ->where('status', 'pending')
                ->count();
            return $count > 5 ? 'warning' : 'primary';
        });
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference_number')
                    ->label('Referenz')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('bold')
                    ->icon('heroicon-m-hashtag'),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Betrag')
                    ->money('EUR')
                    ->sortable()
                    ->icon('heroicon-m-currency-euro')
                    ->color(fn (BalanceTopup $record) => match(true) {
                        $record->amount >= 1000 => 'success',
                        $record->amount >= 100 => 'primary',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Zahlungsmethode')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match($state) {
                        'credit_card' => '💳 Kreditkarte',
                        'debit_card' => '💳 Debitkarte',
                        'bank_transfer' => '🏦 Banküberweisung',
                        'sepa_direct_debit' => '🏦 SEPA-Lastschrift',
                        'paypal' => '🅿️ PayPal',
                        'stripe' => '💳 Stripe',
                        'klarna' => '🛍️ Klarna',
                        'apple_pay' => '🍎 Apple Pay',
                        'google_pay' => '🔍 Google Pay',
                        'crypto' => '₿ Krypto',
                        default => $state,
                    })
                    ->searchable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match($state) {
                        'pending' => '⏳ Ausstehend',
                        'processing' => '🔄 In Bearbeitung',
                        'completed' => '✅ Abgeschlossen',
                        'failed' => '❌ Fehlgeschlagen',
                        'cancelled' => '🚫 Storniert',
                        'refunded' => '↩️ Erstattet',
                        'partial_refund' => '↩️ Teilweise erstattet',
                        default => $state,
                    })
                    ->colors([
                        'success' => 'completed',
                        'warning' => ['pending', 'processing'],
                        'danger' => ['failed', 'cancelled'],
                        'info' => ['refunded', 'partial_refund'],
                    ])
                    ->icon(fn (string $state) => match($state) {
                        'completed' => 'heroicon-m-check-circle',
                        'pending' => 'heroicon-m-clock',
                        'processing' => 'heroicon-m-arrow-path',
                        'failed' => 'heroicon-m-x-circle',
                        'cancelled' => 'heroicon-m-no-symbol',
                        default => 'heroicon-m-question-mark-circle',
                    }),

                Tables\Columns\TextColumn::make('transaction_date')
                    ->label('Transaktionsdatum')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->icon('heroicon-m-calendar')
                    ->description(fn (BalanceTopup $record) =>
                        $record->transaction_date?->diffForHumans() ?? '-'),

                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('Rechnungsnr.')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->icon('heroicon-m-document-text'),

                Tables\Columns\IconColumn::make('requires_approval')
                    ->label('Genehmigung')
                    ->boolean()
                    ->trueIcon('heroicon-o-shield-check')
                    ->falseIcon('heroicon-o-check')
                    ->trueColor('warning')
                    ->falseColor('success')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->multiple()
                    ->options([
                        'pending' => '⏳ Ausstehend',
                        'processing' => '🔄 In Bearbeitung',
                        'completed' => '✅ Abgeschlossen',
                        'failed' => '❌ Fehlgeschlagen',
                        'cancelled' => '🚫 Storniert',
                        'refunded' => '↩️ Erstattet',
                    ])
                    ->default(['pending', 'processing', 'completed']),

                Tables\Filters\SelectFilter::make('payment_method')
                    ->label('Zahlungsmethode')
                    ->multiple()
                    ->options([
                        'credit_card' => '💳 Kreditkarte',
                        'bank_transfer' => '🏦 Banküberweisung',
                        'paypal' => '🅿️ PayPal',
                        'stripe' => '💳 Stripe',
                    ]),
            ])
            ->filtersLayout(Tables\Enums\FiltersLayout::AboveContentCollapsible)
            ->persistFiltersInSession()
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('60s')
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->emptyStateHeading('Keine Aufladungen vorhanden')
            ->emptyStateDescription('Es sind noch keine Guthaben-Aufladungen vorhanden')
            ->emptyStateIcon('heroicon-o-currency-euro');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('💰 Aufladungs-Übersicht')
                    ->description('Detaillierte Informationen zur Guthaben-Aufladung')
                    ->icon('heroicon-o-banknotes')
                    ->schema([
                        Split::make([
                            Section::make([
                                TextEntry::make('reference_number')
                                    ->label('Referenznummer')
                                    ->copyable()
                                    ->weight('bold')
                                    ->icon('heroicon-m-hashtag'),

                                TextEntry::make('amount')
                                    ->label('Betrag')
                                    ->money('EUR')
                                    ->size('lg')
                                    ->weight('bold')
                                    ->icon('heroicon-m-currency-euro')
                                    ->color(fn (BalanceTopup $record) => match(true) {
                                        $record->amount >= 1000 => 'success',
                                        $record->amount >= 100 => 'primary',
                                        default => 'gray',
                                    }),

                                TextEntry::make('vat_amount')
                                    ->label('MwSt. (19%)')
                                    ->money('EUR')
                                    ->icon('heroicon-m-receipt-percent'),

                                TextEntry::make('total_amount')
                                    ->label('Gesamtbetrag')
                                    ->money('EUR')
                                    ->weight('bold')
                                    ->icon('heroicon-m-calculator'),
                            ])->grow(false),

                            Section::make([
                                TextEntry::make('status')
                                    ->label('Status')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state) => match($state) {
                                        'pending' => '⏳ Ausstehend',
                                        'processing' => '🔄 In Bearbeitung',
                                        'completed' => '✅ Abgeschlossen',
                                        'failed' => '❌ Fehlgeschlagen',
                                        'cancelled' => '🚫 Storniert',
                                        'refunded' => '↩️ Erstattet',
                                        default => $state,
                                    })
                                    ->color(fn (string $state) => match($state) {
                                        'completed' => 'success',
                                        'pending', 'processing' => 'warning',
                                        'failed', 'cancelled' => 'danger',
                                        'refunded' => 'info',
                                        default => 'gray',
                                    }),

                                TextEntry::make('payment_method')
                                    ->label('Zahlungsmethode')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state) => match($state) {
                                        'credit_card' => '💳 Kreditkarte',
                                        'bank_transfer' => '🏦 Banküberweisung',
                                        'paypal' => '🅿️ PayPal',
                                        'stripe' => '💳 Stripe',
                                        default => $state,
                                    }),

                                TextEntry::make('transaction_date')
                                    ->label('Transaktionsdatum')
                                    ->dateTime('d.m.Y H:i:s')
                                    ->icon('heroicon-m-calendar'),

                                TextEntry::make('processed_at')
                                    ->label('Bearbeitet am')
                                    ->dateTime('d.m.Y H:i:s')
                                    ->placeholder('Noch nicht bearbeitet')
                                    ->icon('heroicon-m-clock'),
                            ]),
                        ])->from('md'),
                    ]),

                Section::make('💳 Zahlungsdetails')
                    ->description('Informationen zur Zahlungsmethode und Gateway')
                    ->icon('heroicon-o-credit-card')
                    ->schema([
                        InfoGrid::make(3)
                            ->schema([
                                TextEntry::make('payment_gateway')
                                    ->label('Zahlungs-Gateway')
                                    ->placeholder('Nicht angegeben'),

                                TextEntry::make('payment_gateway_id')
                                    ->label('Gateway Transaktions-ID')
                                    ->copyable()
                                    ->placeholder('Keine ID'),

                                TextEntry::make('failure_reason')
                                    ->label('Fehlergrund')
                                    ->placeholder('Kein Fehler')
                                    ->visible(fn (BalanceTopup $record) =>
                                        $record->status === 'failed'),
                            ]),

                        KeyValueEntry::make('payment_metadata')
                            ->label('Zahlungs-Metadaten')
                            ->columnSpanFull(),
                    ])
                    ->collapsed(),

                Section::make('📊 Buchhaltung')
                    ->description('Rechnungs- und Buchhaltungsinformationen')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        InfoGrid::make(3)
                            ->schema([
                                TextEntry::make('invoice_number')
                                    ->label('Rechnungsnummer')
                                    ->copyable()
                                    ->placeholder('Noch nicht erstellt'),

                                TextEntry::make('invoice_date')
                                    ->label('Rechnungsdatum')
                                    ->date('d.m.Y')
                                    ->placeholder('Noch nicht erstellt'),

                                TextEntry::make('accounting_period')
                                    ->label('Abrechnungsperiode')
                                    ->placeholder('Nicht zugeordnet'),
                            ]),
                    ])
                    ->collapsed(),

                Section::make('📝 Notizen')
                    ->description('Interne und Kunden-Notizen')
                    ->schema([
                        TextEntry::make('notes')
                            ->label('Interne Notizen')
                            ->placeholder('Keine internen Notizen')
                            ->columnSpanFull()
                            ->prose(),

                        TextEntry::make('customer_notes')
                            ->label('Kundennotizen')
                            ->placeholder('Keine Kundennotizen')
                            ->columnSpanFull()
                            ->prose(),
                    ])
                    ->collapsed(),

                Section::make('⚙️ System-Informationen')
                    ->description('Technische Details und Metadaten')
                    ->schema([
                        InfoGrid::make(3)
                            ->schema([
                                TextEntry::make('id')
                                    ->label('System-ID')
                                    ->copyable(),

                                TextEntry::make('source')
                                    ->label('Quelle')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state) => match($state) {
                                        'manual' => '👤 Manuell',
                                        'api' => '🔌 API',
                                        'webhook' => '🔗 Webhook',
                                        'import' => '📥 Import',
                                        'automatic' => '🤖 Automatisch',
                                        default => $state,
                                    }),

                                TextEntry::make('created_at')
                                    ->label('Erstellt am')
                                    ->dateTime('d.m.Y H:i:s'),
                            ]),
                    ])
                    ->collapsed(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBalanceTopups::route('/'),
            'view' => Pages\ViewBalanceTopup::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('company_id', auth()->user()->company_id)
            ->with(['processor', 'approver']);
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
        return ['reference_number', 'invoice_number', 'payment_gateway_id'];
    }
}
