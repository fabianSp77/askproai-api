<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\InvoiceResource\Pages;
use App\Filament\Admin\Traits\HasConsistentNavigation;
use App\Filament\Admin\Traits\HasTooltips;
use App\Models\Invoice;
use App\Models\Company;
use App\Models\TaxRate;
use App\Services\Tax\TaxService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\ViewField;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Support\Enums\FontWeight;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class InvoiceResource extends Resource
{
    use HasConsistentNavigation, HasTooltips;
    
    protected static ?string $model = Invoice::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = null;
    protected static ?string $navigationGroup = null;
    
    public static function getNavigationLabel(): string
    {
        return __('admin.resources.invoices');
    }
    
    public static function getNavigationGroup(): ?string
    {
        return __('admin.navigation.financial');
    }
    protected static ?int $navigationSort = 200;
    
    protected static ?string $modelLabel = null;
    
    protected static ?string $pluralModelLabel = null;
    
    public static function getModelLabel(): string
    {
        return __('admin.resources.invoices');
    }
    
    public static function getPluralModelLabel(): string
    {
        return __('admin.resources.invoices');
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        
        // Super admin can view all
        if ($user->hasRole('super_admin')) {
            return true;
        }
        
        // Check specific permission
        if ($user->can('view_any_invoice')) {
            return true;
        }
        
        // Company admins and accountants can view invoices
        return $user->company_id !== null && 
               ($user->hasRole('company_admin') || $user->hasRole('accountant'));
    }
    
    public static function canView($record): bool
    {
        $user = auth()->user();
        
        // Super admin can view all
        if ($user->hasRole('super_admin')) {
            return true;
        }
        
        // Check specific permission
        if ($user->can('view_invoice')) {
            return true;
        }
        
        // Company admins and accountants can view invoices from their company
        return $user->company_id === $record->company_id && 
               ($user->hasRole('company_admin') || $user->hasRole('accountant'));
    }
    
    public static function canEdit($record): bool
    {
        $user = auth()->user();
        
        // Super admin can edit all
        if ($user->hasRole('super_admin')) {
            return true;
        }
        
        // Check specific permission
        if ($user->can('update_invoice')) {
            return true;
        }
        
        // Only allow editing draft invoices for company admins and accountants
        return $record->status === 'draft' && 
               $user->company_id === $record->company_id && 
               ($user->hasRole('company_admin') || $user->hasRole('accountant'));
    }
    
    public static function canCreate(): bool
    {
        $user = auth()->user();
        
        // Super admin can create
        if ($user->hasRole('super_admin')) {
            return true;
        }
        
        // Check specific permission
        if ($user->can('create_invoice')) {
            return true;
        }
        
        // Company admins and accountants can create invoices
        return $user->company_id !== null && 
               ($user->hasRole('company_admin') || $user->hasRole('accountant'));
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema(static::getFormSchema());
    }
    
    public static function getFormSchema(): array
    {
        return [
                // Show info banner for usage-based invoices
                Section::make()
                    ->visible(function (?Invoice $record) {
                        if (!$record) return false;
                        // Check creation_mode first to avoid query
                        if ($record->creation_mode === 'usage') return true;
                        // Only check flexible items if not already determined by creation_mode
                        return $record->relationLoaded('items') 
                            ? $record->items->where('type', 'usage')->isNotEmpty()
                            : $record->items()->where('type', 'usage')->exists();
                    })
                    ->schema([
                        \Filament\Forms\Components\Placeholder::make('usage_info')
                            ->label('')
                            ->content(fn (?Invoice $record) => new \Illuminate\Support\HtmlString(
                                '<div class="bg-blue-50 p-4 rounded">
                                    <p class="text-sm text-blue-800 font-medium mb-2">📊 Nutzungsbasierte Rechnung</p>
                                    <p class="text-sm text-blue-700">Diese Rechnung wurde automatisch aus den Nutzungsdaten generiert. 
                                    Die Positionen basieren auf dem aktiven Preismodell und den tatsächlichen Anrufdaten für den Zeitraum 
                                    ' . ($record->period_start ? $record->period_start->format('d.m.Y') : '') . ' - ' . 
                                    ($record->period_end ? $record->period_end->format('d.m.Y') : '') . '.</p>
                                    <p class="text-sm text-blue-600 mt-2">Die generierten Positionen können bei Bedarf angepasst werden.</p>
                                </div>'
                            )),
                    ]),
                    
                Section::make('Rechnungsdetails')
                    ->description('Grundlegende Rechnungsinformationen')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Select::make('company_id')
                                    ->label('Unternehmen')
                                    ->relationship('company', 'name')
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->reactive()
                                    ->afterStateUpdated(function (Set $set, ?string $state) {
                                        if ($state) {
                                            $company = Company::find($state);
                                            if ($company?->is_small_business) {
                                                $set('tax_note', 'Gemäß § 19 UStG wird keine Umsatzsteuer berechnet.');
                                            }
                                        }
                                    }),
                                    
                                TextInput::make('invoice_number')
                                    ->label('Rechnungsnummer')
                                    ->disabled()
                                    ->dehydrated()
                                    ->placeholder('Wird automatisch generiert'),
                                    
                                Select::make('status')
                                    ->label('Status')
                                    ->options([
                                        'draft' => 'Entwurf',
                                        'open' => 'Offen',
                                        'paid' => 'Bezahlt',
                                        'overdue' => 'Überfällig',
                                        'cancelled' => 'Storniert',
                                    ])
                                    ->default('draft')
                                    ->required(),
                            ]),
                            
                        Grid::make(3)
                            ->schema([
                                DatePicker::make('invoice_date')
                                    ->label('Rechnungsdatum')
                                    ->default(now())
                                    ->required(),
                                    
                                DatePicker::make('due_date')
                                    ->label('Fälligkeitsdatum')
                                    ->default(now()->addDays(14))
                                    ->required(),
                                    
                                Select::make('payment_terms')
                                    ->label('Zahlungsbedingungen')
                                    ->options([
                                        'due_on_receipt' => 'Sofort fällig',
                                        'net15' => '15 Tage netto',
                                        'net30' => '30 Tage netto',
                                        'net60' => '60 Tage netto',
                                    ])
                                    ->default('net30'),
                            ]),
                            
                        Grid::make(2)
                            ->schema([
                                DatePicker::make('period_start')
                                    ->label('Leistungszeitraum von'),
                                    
                                DatePicker::make('period_end')
                                    ->label('Leistungszeitraum bis'),
                            ]),
                            
                        Textarea::make('tax_note')
                            ->label('Steuerhinweis')
                            ->rows(2)
                            ->placeholder('z.B. Kleinunternehmer-Hinweis')
                            ->visible(fn (Get $get) => $get('company_id') && Company::find($get('company_id'))?->is_small_business),
                    ]),
                    
                Section::make('Rechnungspositionen')
                    ->description('Fügen Sie die einzelnen Positionen hinzu')
                    ->schema([
                        Repeater::make('items')
                            ->relationship('items')
                            ->label('Positionen')
                            ->schema([
                                Grid::make(12)
                                    ->schema([
                                        Textarea::make('description')
                                            ->label('Beschreibung')
                                            ->required()
                                            ->rows(2)
                                            ->columnSpan(6),
                                            
                                        TextInput::make('quantity')
                                            ->label('Menge')
                                            ->numeric()
                                            ->default(1)
                                            ->required()
                                            ->reactive()
                                            ->columnSpan(2),
                                            
                                        TextInput::make('unit')
                                            ->label('Einheit')
                                            ->default('Stück')
                                            ->required()
                                            ->columnSpan(2),
                                            
                                        TextInput::make('unit_price')
                                            ->label('Einzelpreis')
                                            ->numeric()
                                            ->prefix('€')
                                            ->required()
                                            ->reactive()
                                            ->columnSpan(2),
                                    ]),
                                    
                                Grid::make(12)
                                    ->schema([
                                        // Tax rate temporarily disabled - no tax_rate_id column in invoice_items
                                        // Select::make('tax_rate_id')
                                        //     ->label('Steuersatz')
                                        //     ->options(function (Get $get) {
                                        //         $companyId = $get('../../company_id');
                                        //         if (!$companyId) {
                                        //             return TaxRate::system()->pluck('name', 'id');
                                        //         }
                                        //         
                                        //         $company = Company::find($companyId);
                                        //         if ($company?->is_small_business) {
                                        //             return TaxRate::where('rate', 0)->pluck('name', 'id');
                                        //         }
                                        //         
                                        //         return TaxRate::forCompany($companyId)->pluck('name', 'id');
                                        //     })
                                        //     ->required()
                                        //     ->reactive()
                                        //     ->columnSpan(3),
                                        
                                        Placeholder::make('tax_info')
                                            ->label('Steuer')
                                            ->content('Wird aus Firmenstatus berechnet')
                                            ->columnSpan(3),
                                            
                                        DatePicker::make('period_start')
                                            ->label('Leistung von')
                                            ->columnSpan(3),
                                            
                                        DatePicker::make('period_end')
                                            ->label('Leistung bis')
                                            ->columnSpan(3),
                                            
                                        Placeholder::make('amount')
                                            ->label('Gesamt')
                                            ->content(function (Get $get) {
                                                $quantity = $get('quantity') ?? 0;
                                                $unitPrice = $get('unit_price') ?? 0;
                                                $amount = $quantity * $unitPrice;
                                                return '€ ' . number_format($amount, 2, ',', '.');
                                            })
                                            ->columnSpan(3),
                                    ]),
                            ])
                            ->defaultItems(0)
                            ->addActionLabel('Position hinzufügen')
                            ->reorderable()
                            ->collapsible()
                            ->cloneable()
                            ->reactive()
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                // Recalculate totals
                                $items = $get('items') ?? [];
                                $companyId = $get('company_id');
                                
                                if (!$companyId) return;
                                
                                $company = Company::find($companyId);
                                $taxService = new TaxService();
                                
                                $subtotal = 0;
                                $totalTax = 0;
                                
                                foreach ($items as $item) {
                                    $amount = ($item['quantity'] ?? 0) * ($item['unit_price'] ?? 0);
                                    // Tax calculation simplified - no tax_rate_id in items
                                    $taxRate = $company->is_small_business ? 0 : 19;
                                    $taxAmount = $amount * ($taxRate / 100);
                                    
                                    $subtotal += $amount;
                                    $totalTax += $taxAmount;
                                }
                                
                                $set('subtotal', $subtotal);
                                $set('tax_amount', $totalTax);
                                $set('total', $subtotal + $totalTax);
                            }),
                    ]),
                    
                Section::make('Zusammenfassung')
                    ->description('Automatisch berechnete Summen')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('subtotal')
                                    ->label('Zwischensumme')
                                    ->prefix('€')
                                    ->disabled()
                                    ->dehydrated()
                                    ->numeric()
                                    ->default(0),
                                    
                                TextInput::make('tax_amount')
                                    ->label('Mehrwertsteuer')
                                    ->prefix('€')
                                    ->disabled()
                                    ->dehydrated()
                                    ->numeric()
                                    ->default(0),
                                    
                                TextInput::make('total')
                                    ->label('Gesamtbetrag')
                                    ->prefix('€')
                                    ->disabled()
                                    ->dehydrated()
                                    ->numeric()
                                    ->default(0)
                                    ->extraAttributes(['class' => 'font-bold text-lg']),
                            ]),
                    ]),
                    
                Section::make('Weitere Optionen')
                    ->collapsed()
                    ->schema([
                        Toggle::make('manual_editable')
                            ->label('Manuell bearbeitbar')
                            ->helperText('Nach Finalisierung kann die Rechnung nicht mehr bearbeitet werden')
                            ->default(true),
                            
                        Toggle::make('auto_advance')
                            ->label('Automatisch finalisieren')
                            ->helperText('Rechnung wird automatisch finalisiert und versendet')
                            ->default(false),
                            
                        Textarea::make('notes')
                            ->label('Interne Notizen')
                            ->rows(3)
                            ->placeholder('Notizen sind nur intern sichtbar'),
                    ]),
            ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('Rechnungsnr.')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold),
                    
                Tables\Columns\BadgeColumn::make('creation_mode')
                    ->label('Typ')
                    ->colors([
                        'primary' => 'usage',
                        'secondary' => 'manual',
                    ])
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'usage' => '📊 Nutzungsbasiert',
                        'manual' => '✏️ Manuell',
                        default => $state,
                    }),
                    
                Tables\Columns\TextColumn::make('company.name')
                    ->label('Unternehmen')
                    ->searchable()
                    ->sortable()
                    ->getStateUsing(fn ($record) => $record?->company?->name ?? '-'),
                    
                Tables\Columns\TextColumn::make('invoice_date')
                    ->label('Datum')
                    ->date('d.m.Y')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('due_date')
                    ->label('Fällig')
                    ->date('d.m.Y')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('total')
                    ->label('Betrag')
                    ->money('EUR')
                    ->sortable(),
                    
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'gray' => 'draft',
                        'warning' => 'open',
                        'success' => 'paid',
                        'danger' => fn ($state): bool => in_array($state, ['overdue', 'cancelled']),
                    ])
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'draft' => 'Entwurf',
                        'open' => 'Offen',
                        'paid' => 'Bezahlt',
                        'overdue' => 'Überfällig',
                        'cancelled' => 'Storniert',
                        default => $state,
                    }),
                    
                Tables\Columns\IconColumn::make('company.is_small_business')
                    ->label('Kleinunt.')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->tooltip(fn (Model $record): string => 
                        $record->company && $record->company->is_small_business 
                            ? 'Kleinunternehmer (0% MwSt)' 
                            : 'Regulär besteuert'
                    ),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'draft' => 'Entwurf',
                        'open' => 'Offen',
                        'paid' => 'Bezahlt',
                        'overdue' => 'Überfällig',
                        'cancelled' => 'Storniert',
                    ]),
                    
                Tables\Filters\SelectFilter::make('company_id')
                    ->label('Unternehmen')
                    ->relationship('company', 'name')
                    ->searchable()
                    ->preload(),
                    
                Tables\Filters\Filter::make('is_small_business')
                    ->label('Nur Kleinunternehmer')
                    ->query(fn (Builder $query): Builder => 
                        $query->whereHas('company', fn ($q) => $q->where('is_small_business', true))
                    ),
                    
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Von'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Bis'),
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
                    }),
            ])
            ->actions(static::applyTableActionTooltips([
                Tables\Actions\Action::make('preview')
                    ->label('Vorschau')
                    ->tooltip(static::tooltip('preview_invoice'))
                    ->icon('heroicon-o-eye')
                    ->modalContent(fn (Invoice $record): \Illuminate\Contracts\View\View => 
                        view('filament.admin.resources.invoice-preview', ['invoice' => $record])
                    )
                    ->modalHeading('Rechnungsvorschau')
                    ->modalWidth('5xl'),
                    
                Tables\Actions\Action::make('finalize')
                    ->label('Finalisieren')
                    ->tooltip(static::tooltip('finalize_invoice'))
                    ->icon('heroicon-o-check')
                    ->action(function (Invoice $record) {
                        $service = new \App\Services\Stripe\EnhancedStripeInvoiceService();
                        if ($service->finalizeInvoice($record)) {
                            \Filament\Notifications\Notification::make()
                                ->title('Rechnung finalisiert')
                                ->success()
                                ->send();
                        } else {
                            \Filament\Notifications\Notification::make()
                                ->title('Fehler beim Finalisieren')
                                ->danger()
                                ->send();
                        }
                    })
                    ->requiresConfirmation()
                    ->visible(fn (Invoice $record) => $record->status === 'draft' && !$record->finalized_at),
                    
                Tables\Actions\EditAction::make()
                    ->visible(fn (Invoice $record) => $record->manual_editable && !$record->finalized_at),
                    
                Tables\Actions\Action::make('download')
                    ->label('PDF')
                    ->tooltip(static::tooltip('download_pdf'))
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn (Invoice $record) => $record->pdf_url)
                    ->openUrlInNewTab()
                    ->visible(fn (Invoice $record) => $record->pdf_url),
            ]))
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn ($records) => $records !== null && $records->every(fn ($record) => 
                            $record->status === 'draft' && !$record->finalized_at
                        )),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'edit' => Pages\EditInvoice::route('/{record}/edit'),
        ];
    }
    
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['company', 'items']);
    }
}