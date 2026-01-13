<?php

namespace App\Filament\Customer\Resources;

use App\Filament\Customer\Resources\PartnerInvoiceResource\Pages;
use App\Models\AggregateInvoice;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Grid as InfoGrid;
use Filament\Infolists\Components\RepeatableEntry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\HtmlString;

/**
 * Partner Invoice Resource for Customer Portal.
 *
 * Allows partners to view their monthly aggregate invoices.
 * Read-only with PDF download and Stripe link access.
 */
class PartnerInvoiceResource extends Resource
{
    protected static ?string $model = AggregateInvoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Abrechnung';

    protected static ?string $navigationLabel = 'Meine Rechnungen';

    protected static ?string $modelLabel = 'Rechnung';

    protected static ?string $pluralModelLabel = 'Rechnungen';

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'invoice_number';

    /**
     * Scope to only show invoices for the current user's company.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('partner_company_id', auth()->user()->company_id)
            ->with(['items.company']);
    }

    /**
     * Only show this resource if the user's company is a partner.
     */
    public static function canAccess(): bool
    {
        $user = auth()->user();

        if (!$user || !$user->company) {
            return false;
        }

        // Check if company is a partner (receives aggregate invoices)
        return $user->company->is_partner ?? false;
    }

    /**
     * Bypass AggregateInvoicePolicy for customer portal.
     * Partners can view their own invoices.
     */
    public static function canViewAny(): bool
    {
        return static::canAccess();
    }

    /**
     * Check if user can view a specific invoice.
     * Only allow viewing invoices belonging to the user's company.
     */
    public static function canView(Model $record): bool
    {
        $user = auth()->user();

        if (!$user || !$user->company_id) {
            return false;
        }

        // Security: Only allow viewing own company's invoices
        return $record->partner_company_id === $user->company_id;
    }

    /**
     * Disable create - invoices are system-generated.
     */
    public static function canCreate(): bool
    {
        return false;
    }

    /**
     * Disable edit - invoices are read-only for partners.
     */
    public static function canEdit(Model $record): bool
    {
        return false;
    }

    /**
     * Disable delete - partners cannot delete invoices.
     */
    public static function canDelete(Model $record): bool
    {
        return false;
    }

    /**
     * Disable bulk delete.
     */
    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $userId = auth()->id();

        return Cache::remember(
            "partner_invoices_open_{$userId}",
            now()->addMinutes(5),
            function () {
                $count = static::getEloquentQuery()
                    ->where('status', AggregateInvoice::STATUS_OPEN)
                    ->count();

                return $count > 0 ? (string) $count : null;
            }
        );
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $userId = auth()->id();

        return Cache::remember(
            "partner_invoices_overdue_{$userId}",
            now()->addMinutes(5),
            function () {
                $overdue = static::getEloquentQuery()->overdue()->count();
                return $overdue > 0 ? 'danger' : 'warning';
            }
        );
    }

    public static function table(Table $table): Table
    {
        return $table
            ->deferLoading()
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('Rechnungsnummer')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('bold')
                    ->icon('heroicon-m-document-text'),

                Tables\Columns\TextColumn::make('billing_period_start')
                    ->label('Zeitraum')
                    ->formatStateUsing(fn ($state, $record) => $record->billing_period_display)
                    ->sortable(),

                Tables\Columns\TextColumn::make('total')
                    ->label('Betrag')
                    ->formatStateUsing(fn ($state) => number_format($state, 2, ',', '.') . ' EUR')
                    ->sortable()
                    ->alignEnd()
                    ->weight('bold')
                    ->color(fn ($record) => match ($record->status) {
                        AggregateInvoice::STATUS_PAID => 'success',
                        AggregateInvoice::STATUS_OPEN => $record->is_overdue ? 'danger' : 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($record) => $record->getStatusLabel())
                    ->color(fn ($record) => $record->getStatusColor()),

                Tables\Columns\TextColumn::make('due_at')
                    ->label('Fällig')
                    ->date('d.m.Y')
                    ->placeholder('—')
                    ->color(fn ($record) => $record->is_overdue ? 'danger' : null),

                Tables\Columns\TextColumn::make('sent_at')
                    ->label('Erhalten')
                    ->date('d.m.Y')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('paid_at')
                    ->label('Bezahlt am')
                    ->date('d.m.Y')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        AggregateInvoice::STATUS_OPEN => 'Offen',
                        AggregateInvoice::STATUS_PAID => 'Bezahlt',
                        AggregateInvoice::STATUS_VOID => 'Storniert',
                    ]),

                Tables\Filters\Filter::make('overdue')
                    ->label('Nur überfällige')
                    ->query(fn (Builder $query) => $query->overdue()),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->icon('heroicon-o-eye'),

                Tables\Actions\Action::make('pdf')
                    ->label('PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->url(fn ($record) => $record->stripe_pdf_url)
                    ->openUrlInNewTab()
                    ->visible(fn ($record) => $record->stripe_pdf_url)
                    ->color('success'),

                Tables\Actions\Action::make('online')
                    ->label('Online ansehen')
                    ->icon('heroicon-o-globe-alt')
                    ->url(fn ($record) => $record->stripe_hosted_invoice_url)
                    ->openUrlInNewTab()
                    ->visible(fn ($record) => $record->stripe_hosted_invoice_url)
                    ->color('primary'),
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->emptyStateHeading('Keine Rechnungen vorhanden')
            ->emptyStateDescription('Sobald Rechnungen erstellt werden, erscheinen sie hier.')
            ->emptyStateIcon('heroicon-o-document-text');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Rechnungsdetails')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        InfoGrid::make(4)->schema([
                            TextEntry::make('invoice_number')
                                ->label('Rechnungsnummer')
                                ->copyable()
                                ->weight('bold'),

                            TextEntry::make('billing_period_display')
                                ->label('Abrechnungszeitraum'),

                            TextEntry::make('status')
                                ->label('Status')
                                ->badge()
                                ->formatStateUsing(fn ($record) => $record->getStatusLabel())
                                ->color(fn ($record) => $record->getStatusColor()),

                            TextEntry::make('due_at')
                                ->label('Fällig am')
                                ->date('d.m.Y')
                                ->placeholder('—')
                                ->color(fn ($record) => $record->is_overdue ? 'danger' : null),
                        ]),
                    ]),

                Section::make('Beträge')
                    ->icon('heroicon-o-calculator')
                    ->schema([
                        InfoGrid::make(5)->schema([
                            TextEntry::make('subtotal')
                                ->label('Zwischensumme')
                                ->formatStateUsing(fn ($state) => number_format($state, 2, ',', '.') . ' EUR'),

                            TextEntry::make('discount')
                                ->label('Rabatt')
                                ->formatStateUsing(fn ($state, $record) => $record->discount_cents
                                    ? '-' . number_format($state, 2, ',', '.') . ' EUR'
                                    : '—')
                                ->color('success'),

                            TextEntry::make('tax')
                                ->label('MwSt.')
                                ->formatStateUsing(fn ($state, $record) =>
                                    number_format($state, 2, ',', '.') . ' EUR (' . $record->tax_rate . '%)'),

                            TextEntry::make('total')
                                ->label('Gesamtbetrag')
                                ->formatStateUsing(fn ($state) => number_format($state, 2, ',', '.') . ' EUR')
                                ->size('lg')
                                ->weight('bold')
                                ->color('primary'),

                            TextEntry::make('paid_at')
                                ->label('Bezahlt am')
                                ->date('d.m.Y H:i')
                                ->placeholder('Noch nicht bezahlt')
                                ->color(fn ($state) => $state ? 'success' : 'warning'),
                        ]),
                    ]),

                Section::make('Rechnungspositionen')
                    ->icon('heroicon-o-list-bullet')
                    ->schema([
                        RepeatableEntry::make('items')
                            ->label('')
                            ->schema([
                                InfoGrid::make(4)->schema([
                                    TextEntry::make('company.name')
                                        ->label('Unternehmen')
                                        ->placeholder('—'),

                                    TextEntry::make('description')
                                        ->label('Beschreibung')
                                        ->formatStateUsing(function ($state, $record) {
                                            if ($record->description_detail) {
                                                return "{$state} ({$record->description_detail})";
                                            }
                                            return $state;
                                        }),

                                    TextEntry::make('item_type')
                                        ->label('Typ')
                                        ->formatStateUsing(fn ($record) => $record->getTypeLabel())
                                        ->badge()
                                        ->color(fn ($record) => match ($record->item_type) {
                                            'call_minutes' => 'info',
                                            'monthly_service' => 'success',
                                            'setup_fee' => 'warning',
                                            'service_change' => 'primary',
                                            default => 'gray',
                                        }),

                                    TextEntry::make('amount')
                                        ->label('Betrag')
                                        ->formatStateUsing(fn ($state) => number_format($state, 2, ',', '.') . ' EUR')
                                        ->alignEnd()
                                        ->weight('bold'),
                                ]),
                            ])
                            ->columns(1),
                    ]),

                Section::make('Downloads')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->schema([
                        InfoGrid::make(2)->schema([
                            TextEntry::make('stripe_pdf_url')
                                ->label('PDF Rechnung')
                                ->formatStateUsing(fn ($state) => $state
                                    ? new HtmlString('<a href="' . $state . '" target="_blank" class="text-primary-600 hover:underline">PDF herunterladen</a>')
                                    : 'Nicht verfügbar')
                                ->html(),

                            TextEntry::make('stripe_hosted_invoice_url')
                                ->label('Online Ansicht')
                                ->formatStateUsing(fn ($state) => $state
                                    ? new HtmlString('<a href="' . $state . '" target="_blank" class="text-primary-600 hover:underline">Im Browser öffnen</a>')
                                    : 'Nicht verfügbar')
                                ->html(),
                        ]),
                    ])
                    ->visible(fn ($record) => $record->stripe_pdf_url || $record->stripe_hosted_invoice_url),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPartnerInvoices::route('/'),
            'view' => Pages\ViewPartnerInvoice::route('/{record}'),
        ];
    }
}
