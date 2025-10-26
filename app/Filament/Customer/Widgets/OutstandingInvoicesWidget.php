<?php

namespace App\Filament\Customer\Widgets;

use App\Models\Invoice;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Number;
use Carbon\Carbon;

class OutstandingInvoicesWidget extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Offene Rechnungen';

    protected static ?string $pollingInterval = '300s';

    protected static ?int $sort = 4;

    public function table(Table $table): Table
    {
        $companyId = auth()->user()->company_id;

        try {
            return $table
                ->query(
                    Invoice::query()
                        ->where('company_id', $companyId)
                        ->unpaid()
                        ->with(['customer'])
                        ->orderByRaw("
                            CASE
                                WHEN status = 'overdue' THEN 1
                                WHEN due_date < NOW() THEN 2
                                WHEN due_date < DATE_ADD(NOW(), INTERVAL 7 DAY) THEN 3
                                ELSE 4
                            END,
                            due_date ASC
                        ")
                        ->limit(10)
                )
                ->columns([
                    Tables\Columns\TextColumn::make('invoice_number')
                        ->label('Rechnungs-Nr.')
                        ->icon('heroicon-m-hashtag')
                        ->weight('medium')
                        ->searchable()
                        ->sortable(),

                    Tables\Columns\TextColumn::make('total_amount')
                        ->label('Betrag')
                        ->formatStateUsing(fn ($state) => Number::currency($state, 'EUR', 'de'))
                        ->icon('heroicon-m-banknotes')
                        ->color('primary')
                        ->weight('bold')
                        ->sortable(),

                    Tables\Columns\TextColumn::make('due_date')
                        ->label('Fälligkeitsdatum')
                        ->formatStateUsing(function ($state, Invoice $record) {
                            $date = Carbon::parse($state);
                            $diff = now()->diffInDays($date, false);

                            if ($diff < 0) {
                                return $date->format('d.m.Y') . ' (Überfällig)';
                            } elseif ($diff == 0) {
                                return 'Heute fällig';
                            } elseif ($diff == 1) {
                                return 'Morgen fällig';
                            } elseif ($diff <= 7) {
                                return $date->format('d.m.Y') . " (in {$diff} Tagen)";
                            } else {
                                return $date->format('d.m.Y');
                            }
                        })
                        ->icon('heroicon-m-calendar')
                        ->color(function ($state) {
                            $date = Carbon::parse($state);
                            $diff = now()->diffInDays($date, false);

                            if ($diff < 0) return 'danger';
                            if ($diff <= 3) return 'warning';
                            if ($diff <= 7) return 'info';
                            return 'success';
                        })
                        ->sortable(),

                    Tables\Columns\TextColumn::make('status')
                        ->label('Status')
                        ->badge()
                        ->formatStateUsing(fn ($state) => match($state) {
                            'draft' => 'Entwurf',
                            'pending' => 'Ausstehend',
                            'sent' => 'Versendet',
                            'paid' => 'Bezahlt',
                            'partial' => 'Teilweise bezahlt',
                            'overdue' => 'Überfällig',
                            'cancelled' => 'Storniert',
                            'refunded' => 'Erstattet',
                            default => ucfirst($state ?? 'Unbekannt')
                        })
                        ->color(fn ($state, Invoice $record) => match($state) {
                            'draft' => 'gray',
                            'pending' => 'warning',
                            'sent' => 'info',
                            'paid' => 'success',
                            'partial' => 'primary',
                            'overdue' => 'danger',
                            'cancelled' => 'gray',
                            'refunded' => 'warning',
                            default => 'gray'
                        })
                        ->icon(fn ($state) => match($state) {
                            'overdue' => 'heroicon-o-exclamation-triangle',
                            'paid' => 'heroicon-o-check-circle',
                            'partial' => 'heroicon-o-clock',
                            default => 'heroicon-o-document-text'
                        })
                        ->sortable(),

                    Tables\Columns\TextColumn::make('balance_due')
                        ->label('Offener Betrag')
                        ->formatStateUsing(fn ($state) => Number::currency($state, 'EUR', 'de'))
                        ->icon('heroicon-m-exclamation-circle')
                        ->color(fn (Invoice $record) => $record->isOverdue() ? 'danger' : 'warning')
                        ->weight('medium')
                        ->sortable(),
                ])
                ->actions([
                    Tables\Actions\Action::make('view')
                        ->label('Details')
                        ->icon('heroicon-m-eye')
                        ->color('gray')
                        ->url(fn (Invoice $record): string =>
                            route('filament.customer.resources.invoices.view', ['record' => $record])
                        ),

                    Tables\Actions\Action::make('pay')
                        ->label('Bezahlen')
                        ->icon('heroicon-m-credit-card')
                        ->color('success')
                        ->visible(fn (Invoice $record) => $record->isPayable())
                        ->url(fn (Invoice $record): string =>
                            route('filament.customer.resources.invoices.pay', ['record' => $record])
                        ),
                ])
                ->emptyState(
                    view('filament.widgets.empty-state', [
                        'icon' => 'heroicon-o-check-circle',
                        'heading' => 'Keine offenen Rechnungen',
                        'description' => 'Alle Rechnungen sind beglichen. Hervorragend!',
                    ])
                )
                ->bulkActions([])
                ->paginated(false)
                ->striped()
                ->poll('300s')
                ->defaultSort('due_date', 'asc');
        } catch (\Exception $e) {
            \Log::error('OutstandingInvoicesWidget Error: ' . $e->getMessage());
            return $table
                ->query(Invoice::query()->whereRaw('0=1')) // Empty query on error
                ->columns([]);
        }
    }

    protected function getTableHeading(): string|HtmlString|null
    {
        $companyId = auth()->user()->company_id;

        $overdueCount = Invoice::where('company_id', $companyId)
            ->overdue()
            ->count();

        $overdueAmount = Invoice::where('company_id', $companyId)
            ->overdue()
            ->sum('balance_due') ?? 0;

        $totalOutstanding = Invoice::where('company_id', $companyId)
            ->unpaid()
            ->sum('balance_due') ?? 0;

        $overdueAmountFormatted = Number::currency($overdueAmount, 'EUR', 'de');
        $totalOutstandingFormatted = Number::currency($totalOutstanding, 'EUR', 'de');

        $overdueWarning = $overdueCount > 0
            ? "<span class='text-red-600 font-semibold'>⚠️ {$overdueCount} überfällig ({$overdueAmountFormatted})</span>"
            : "<span class='text-green-600'>✓ Keine überfälligen Rechnungen</span>";

        return new HtmlString("
            <div class='flex items-center justify-between'>
                <span class='text-lg font-semibold'>Offene Rechnungen</span>
                <div class='flex gap-4 text-sm'>
                    <span class='text-gray-500'>Gesamt: {$totalOutstandingFormatted}</span>
                    {$overdueWarning}
                </div>
            </div>
        ");
    }
}
