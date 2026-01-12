<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AggregateInvoiceResource\Pages;
use App\Models\AggregateInvoice;
use App\Services\Billing\StripeInvoicingService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class AggregateInvoiceResource extends Resource
{
    protected static ?string $model = AggregateInvoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Partner-Rechnungen';

    protected static ?string $modelLabel = 'Partner-Rechnung';

    protected static ?string $pluralModelLabel = 'Partner-Rechnungen';

    protected static ?string $navigationGroup = 'Abrechnung';

    protected static ?int $navigationSort = 25;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Rechnungsdetails')
                    ->schema([
                        Forms\Components\Select::make('partner_company_id')
                            ->label('Partner')
                            ->relationship('partnerCompany', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->disabled(fn ($record) => $record && $record->status !== AggregateInvoice::STATUS_DRAFT),

                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\DatePicker::make('billing_period_start')
                                ->label('Abrechnungszeitraum von')
                                ->required()
                                ->disabled(fn ($record) => $record && $record->status !== AggregateInvoice::STATUS_DRAFT),

                            Forms\Components\DatePicker::make('billing_period_end')
                                ->label('Abrechnungszeitraum bis')
                                ->required()
                                ->disabled(fn ($record) => $record && $record->status !== AggregateInvoice::STATUS_DRAFT),
                        ]),

                        Forms\Components\TextInput::make('invoice_number')
                            ->label('Rechnungsnummer')
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'draft' => 'Entwurf',
                                'open' => 'Offen',
                                'paid' => 'Bezahlt',
                                'void' => 'Storniert',
                                'uncollectible' => 'Uneinbringlich',
                            ])
                            ->disabled(),
                    ]),

                Forms\Components\Section::make('Beträge')
                    ->schema([
                        Forms\Components\Grid::make(4)->schema([
                            Forms\Components\Placeholder::make('subtotal_display')
                                ->label('Netto')
                                ->content(fn ($record) => $record ? number_format($record->subtotal, 2, ',', '.') . ' €' : '—'),

                            Forms\Components\Placeholder::make('tax_display')
                                ->label('MwSt.')
                                ->content(fn ($record) => $record ? number_format($record->tax, 2, ',', '.') . ' € (' . $record->tax_rate . '%)' : '—'),

                            Forms\Components\Placeholder::make('total_display')
                                ->label('Brutto')
                                ->content(fn ($record) => $record ? new HtmlString('<span class="text-lg font-bold">' . number_format($record->total, 2, ',', '.') . ' €</span>') : '—'),

                            Forms\Components\Placeholder::make('due_at_display')
                                ->label('Fällig am')
                                ->content(fn ($record) => $record?->due_at?->format('d.m.Y') ?? '—'),
                        ]),
                    ])
                    ->visible(fn ($record) => $record !== null),

                Forms\Components\Section::make('Notizen')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Interne Notizen')
                            ->rows(3),
                    ])
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('Rechnungs-Nr.')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('partnerCompany.name')
                    ->label('Partner')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('billing_period_start')
                    ->label('Zeitraum')
                    ->formatStateUsing(function ($state, $record) {
                        return $state->format('m/Y');
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('total')
                    ->label('Betrag')
                    ->formatStateUsing(fn ($state) => number_format($state, 2, ',', '.') . ' €')
                    ->sortable()
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($record) => $record->getStatusLabel())
                    ->color(fn ($record) => $record->getStatusColor()),

                Tables\Columns\TextColumn::make('sent_at')
                    ->label('Versendet')
                    ->date('d.m.Y')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('paid_at')
                    ->label('Bezahlt')
                    ->date('d.m.Y')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('due_at')
                    ->label('Fällig')
                    ->date('d.m.Y')
                    ->placeholder('—'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('partner_company_id')
                    ->label('Partner')
                    ->relationship('partnerCompany', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'draft' => 'Entwurf',
                        'open' => 'Offen',
                        'paid' => 'Bezahlt',
                        'void' => 'Storniert',
                        'uncollectible' => 'Uneinbringlich',
                    ]),

                Tables\Filters\Filter::make('overdue')
                    ->label('Überfällig')
                    ->query(fn (Builder $query) => $query->overdue()),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make()
                        ->visible(fn ($record) => $record->status === AggregateInvoice::STATUS_DRAFT),

                    Tables\Actions\Action::make('send')
                        ->label('Versenden')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('success')
                        ->visible(fn ($record) => $record->status === AggregateInvoice::STATUS_DRAFT)
                        ->requiresConfirmation()
                        ->modalHeading('Rechnung versenden?')
                        ->modalDescription('Die Rechnung wird finalisiert und per E-Mail an den Partner versendet.')
                        ->action(function ($record) {
                            $service = app(StripeInvoicingService::class);
                            $service->finalizeAndSend($record);

                            \Filament\Notifications\Notification::make()
                                ->title('Rechnung versendet')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\Action::make('preview')
                        ->label('Vorschau')
                        ->icon('heroicon-o-eye')
                        ->url(fn ($record) => $record->stripe_hosted_invoice_url)
                        ->openUrlInNewTab()
                        ->visible(fn ($record) => $record->stripe_hosted_invoice_url),

                    Tables\Actions\Action::make('pdf')
                        ->label('PDF')
                        ->icon('heroicon-o-document-arrow-down')
                        ->url(fn ($record) => $record->stripe_pdf_url)
                        ->openUrlInNewTab()
                        ->visible(fn ($record) => $record->stripe_pdf_url),

                    Tables\Actions\Action::make('mark_paid')
                        ->label('Als bezahlt markieren')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn ($record) => $record->status === AggregateInvoice::STATUS_OPEN)
                        ->requiresConfirmation()
                        ->action(function ($record) {
                            $record->markAsPaid();

                            \Filament\Notifications\Notification::make()
                                ->title('Status aktualisiert')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\Action::make('void')
                        ->label('Stornieren')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->visible(fn ($record) => in_array($record->status, [AggregateInvoice::STATUS_DRAFT, AggregateInvoice::STATUS_OPEN]))
                        ->requiresConfirmation()
                        ->modalHeading('Rechnung stornieren?')
                        ->action(function ($record) {
                            $service = app(StripeInvoicingService::class);
                            $service->voidStripeInvoice($record);

                            \Filament\Notifications\Notification::make()
                                ->title('Rechnung storniert')
                                ->success()
                                ->send();
                        }),
                ])->iconButton(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => false), // Disable bulk delete for invoices
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped();
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Übersicht')
                    ->schema([
                        Infolists\Components\Grid::make(4)->schema([
                            Infolists\Components\TextEntry::make('invoice_number')
                                ->label('Rechnungsnummer'),

                            Infolists\Components\TextEntry::make('partnerCompany.name')
                                ->label('Partner'),

                            Infolists\Components\TextEntry::make('billing_period_display')
                                ->label('Abrechnungszeitraum'),

                            Infolists\Components\TextEntry::make('status')
                                ->label('Status')
                                ->badge()
                                ->formatStateUsing(fn ($record) => $record->getStatusLabel())
                                ->color(fn ($record) => $record->getStatusColor()),
                        ]),
                    ]),

                Infolists\Components\Section::make('Beträge')
                    ->schema([
                        Infolists\Components\Grid::make(4)->schema([
                            Infolists\Components\TextEntry::make('subtotal')
                                ->label('Netto')
                                ->formatStateUsing(fn ($state) => number_format($state, 2, ',', '.') . ' €'),

                            Infolists\Components\TextEntry::make('tax')
                                ->label('MwSt.')
                                ->formatStateUsing(fn ($state, $record) => number_format($state, 2, ',', '.') . ' € (' . $record->tax_rate . '%)'),

                            Infolists\Components\TextEntry::make('total')
                                ->label('Brutto')
                                ->formatStateUsing(fn ($state) => number_format($state, 2, ',', '.') . ' €')
                                ->size('lg')
                                ->weight('bold'),

                            Infolists\Components\TextEntry::make('due_at')
                                ->label('Fällig am')
                                ->date('d.m.Y'),
                        ]),
                    ]),

                Infolists\Components\Section::make('Positionen')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('items')
                            ->schema([
                                Infolists\Components\Grid::make(4)->schema([
                                    Infolists\Components\TextEntry::make('company.name')
                                        ->label('Unternehmen'),

                                    Infolists\Components\TextEntry::make('description')
                                        ->label('Beschreibung')
                                        ->formatStateUsing(function ($state, $record) {
                                            if ($record->description_detail) {
                                                return "{$state} ({$record->description_detail})";
                                            }
                                            return $state;
                                        }),

                                    Infolists\Components\TextEntry::make('item_type')
                                        ->label('Typ')
                                        ->formatStateUsing(fn ($record) => $record->getTypeLabel()),

                                    Infolists\Components\TextEntry::make('amount')
                                        ->label('Betrag')
                                        ->formatStateUsing(fn ($state) => number_format($state, 2, ',', '.') . ' €')
                                        ->alignEnd(),
                                ]),
                            ])
                            ->columns(1),
                    ]),

                Infolists\Components\Section::make('Zeitstempel')
                    ->schema([
                        Infolists\Components\Grid::make(4)->schema([
                            Infolists\Components\TextEntry::make('created_at')
                                ->label('Erstellt')
                                ->dateTime('d.m.Y H:i'),

                            Infolists\Components\TextEntry::make('finalized_at')
                                ->label('Finalisiert')
                                ->dateTime('d.m.Y H:i')
                                ->placeholder('—'),

                            Infolists\Components\TextEntry::make('sent_at')
                                ->label('Versendet')
                                ->dateTime('d.m.Y H:i')
                                ->placeholder('—'),

                            Infolists\Components\TextEntry::make('paid_at')
                                ->label('Bezahlt')
                                ->dateTime('d.m.Y H:i')
                                ->placeholder('—'),
                        ]),
                    ])
                    ->collapsed(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAggregateInvoices::route('/'),
            'create' => Pages\CreateAggregateInvoice::route('/create'),
            'view' => Pages\ViewAggregateInvoice::route('/{record}'),
            'edit' => Pages\EditAggregateInvoice::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $open = static::getModel()::where('status', 'open')->count();
        return $open > 0 ? (string) $open : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $overdue = static::getModel()::overdue()->count();
        return $overdue > 0 ? 'danger' : 'warning';
    }
}
